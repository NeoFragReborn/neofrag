<?php
declare(strict_types=1);

namespace NF\Tests\Integration;

/**
 * Tests d'intégration de la recherche FULLTEXT du forum, contre la vraie base.
 *
 * Mime le SQL EXACT de `Forum_Fulltext::search_fulltext` : `MATCH … AGAINST (… IN BOOLEAN MODE)`
 * sur le message ET sur le titre du sujet, la jointure sur l'auteur non supprimé, et le filtrage
 * par forum.
 *
 * ── Pourquoi cette classe ne ressemble pas aux autres ────────────────────────────────────────
 *
 * Elle **n'hérite pas de la transaction annulée** du socle, et c'est la raison d'être de cette
 * note. Mesuré le 2026-09-17 : dans une transaction non validée, `LIKE` trouve une ligne qu'on
 * vient d'insérer, mais `MATCH … AGAINST` en trouve **zéro** — InnoDB ne met son index FULLTEXT à
 * jour qu'au commit.
 *
 * Un test miroir écrit sur le modèle habituel n'aurait donc rien mesuré : il aurait trouvé zéro
 * résultat, et un test qui affirme « zéro résultat pour un terme absent » serait passé au vert en
 * étant complètement aveugle. C'est exactement le genre de contrôle qui coûte plus cher qu'il ne
 * rapporte, parce qu'on cesse de s'en méfier.
 *
 * Les fixtures sont donc **validées**, et nettoyées en fin de test par leur marqueur — nettoyage
 * rejoué aussi en début de test, pour qu'une exécution interrompue ne laisse rien derrière elle.
 *
 * Contrainte du moteur : `innodb_ft_min_token_size` vaut 3, et le constructeur de requête booléenne
 * écarte de son côté les mots de moins de trois lettres. Les termes de ce test en font donc plus.
 */
final class ForumFulltextDbTest extends IntegrationTestCase
{
	private string $jeton = '';

	/** @var array<string,int> */
	private array $fixtures = [];

	/**
	 * Pas de transaction : cf. la note de tête. On nettoie plutôt qu'on n'annule.
	 */
	protected function setUp(): void
	{
		// Pas de parent::setUp() (le FULLTEXT ne voit pas une transaction non validée), mais le même saut.
		if (self::$indisponible !== null)
		{
			self::markTestSkipped(self::$indisponible);
		}

		$this->jeton = 'ftitest'.substr(md5(uniqid('', true)), 0, 8);
		$this->purger();
	}

	protected function tearDown(): void
	{
		$this->purger();
	}

	/** Efface tout ce que cette classe a pu semer, y compris lors d'une exécution interrompue. */
	private function purger(): void
	{
		if (!self::$pdo)
		{
			return;
		}

		self::$pdo->query("DELETE m FROM nf_forum_messages m JOIN nf_forum_topics t ON m.topic_id = t.topic_id JOIN nf_forum f ON t.forum_id = f.forum_id WHERE f.title LIKE 'ftitest%'");
		self::$pdo->query("DELETE t FROM nf_forum_topics t JOIN nf_forum f ON t.forum_id = f.forum_id WHERE f.title LIKE 'ftitest%'");
		self::$pdo->query("DELETE FROM nf_forum WHERE title LIKE 'ftitest%'");
		self::$pdo->query("DELETE FROM nf_forum_categories WHERE title LIKE 'ftitest%'");
		self::$pdo->query("DELETE FROM nf_user WHERE username LIKE 'ftitest%'");
	}

	/** Monte la chaîne catégorie → forum → sujet → message, et rend les identifiants. */
	private function fixtures(string $titre_sujet, string $message): array
	{
		if (!isset($this->fixtures['user']))
		{
			$this->exec("INSERT INTO nf_user (username, password, salt, data) VALUES (?, '', '', '')", [$this->jeton.'_auteur']);
			$this->fixtures['user'] = (int) self::$pdo->insert_id;

			$this->exec("INSERT INTO nf_forum_categories (title, `order`, vip_only) VALUES (?, 0, 0)", [$this->jeton.'_cat']);
			$this->fixtures['categorie'] = (int) self::$pdo->insert_id;

			$this->exec("INSERT INTO nf_forum (parent_id, is_subforum, title, description, `order`) VALUES (?, '0', ?, '', 0)",
				[$this->fixtures['categorie'], $this->jeton.'_forum']);
			$this->fixtures['forum'] = (int) self::$pdo->insert_id;
		}

		$this->exec("INSERT INTO nf_forum_topics (forum_id, title, status, views, count_messages) VALUES (?, ?, '0', 0, 1)",
			[$this->fixtures['forum'], $titre_sujet]);
		$topic = (int) self::$pdo->insert_id;

		$this->exec("INSERT INTO nf_forum_messages (topic_id, user_id, message) VALUES (?, ?, ?)",
			[$topic, $this->fixtures['user'], $message]);

		return ['topic' => $topic, 'message' => (int) self::$pdo->insert_id];
	}

	/**
	 * La clause de recherche, telle quelle : le message OU le titre du sujet, auteur non supprimé.
	 *
	 * @return list<int> identifiants des messages trouvés
	 */
	private function chercher(string $booleen, ?int $forum_id = NULL): array
	{
		$sql = "SELECT m.message_id
		        FROM nf_forum_messages m
		        JOIN nf_forum_topics t ON m.topic_id = t.topic_id
		        JOIN nf_forum        f ON t.forum_id = f.forum_id
		        JOIN nf_user         u ON m.user_id = u.id AND u.deleted = '0'
		        WHERE (MATCH(m.message) AGAINST (? IN BOOLEAN MODE) > 0
		            OR MATCH(t.title)   AGAINST (? IN BOOLEAN MODE) > 0)";

		$params = [$booleen, $booleen];

		if ($forum_id !== NULL)
		{
			$sql     .= ' AND t.forum_id = ?';
			$params[] = $forum_id;
		}

		$res = $this->exec($sql, $params)->get_result();
		$ids = [];

		while ($row = $res->fetch_row())
		{
			$ids[] = (int) $row[0];
		}

		sort($ids);

		return $ids;
	}

	// ------------------------------------------------------------------ le socle

	public function test_l_index_fulltext_existe_bien_sur_les_deux_colonnes(): void
	{
		// Sans ces index, MATCH lève une erreur SQL — et toute la recherche du forum est morte.
		// Le vérifier ici évite de diagnostiquer « zéro résultat » pendant une heure.
		foreach (['nf_forum_messages' => 'message', 'nf_forum_topics' => 'title'] as $table => $colonne)
		{
			$n = (int) $this->scalar(
				"SELECT COUNT(*) FROM information_schema.statistics
				 WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ? AND index_type = 'FULLTEXT'",
				[$table, $colonne]
			);

			$this->assertSame(1, $n, "index FULLTEXT attendu sur $table($colonne)");
		}
	}

	// ------------------------------------------------------------------ la recherche

	public function test_un_mot_du_message_remonte_le_message(): void
	{
		$a = $this->fixtures($this->jeton.' titre banal', 'contenu avec le mot zorglub dedans');
		$b = $this->fixtures($this->jeton.' autre titre', 'contenu sans rapport aucun');

		$trouves = $this->chercher('+zorglub*');

		$this->assertSame([$a['message']], $trouves);
		$this->assertNotContains($b['message'], $trouves);
	}

	public function test_un_mot_du_TITRE_remonte_les_messages_du_sujet(): void
	{
		// La moitié qu'on oublie : la recherche porte AUSSI sur le titre du sujet. C'est ce qui
		// fait qu'un message sans le mot cherché peut légitimement figurer dans les résultats.
		$a = $this->fixtures($this->jeton.' flibustier des mers', 'ce message ne contient pas le mot');

		$this->assertSame([$a['message']], $this->chercher('+flibustier*'));
	}

	public function test_un_terme_absent_ne_remonte_rien(): void
	{
		$this->fixtures($this->jeton.' titre banal', 'contenu avec le mot zorglub dedans');

		// Le témoin qui donne du sens aux autres : sans lui, un test qui trouve zéro partout
		// passerait tout aussi bien avec une recherche cassée.
		$this->assertSame([], $this->chercher('+introuvablissime*'));
	}

	public function test_le_filtre_par_forum_restreint_bien(): void
	{
		$a = $this->fixtures($this->jeton.' titre banal', 'contenu avec le mot zorglub dedans');

		$this->assertSame([$a['message']], $this->chercher('+zorglub*', $this->fixtures['forum']));
		$this->assertSame([], $this->chercher('+zorglub*', $this->fixtures['forum'] + 100000),
			'un autre forum ne doit rien rendre');
	}

	public function test_un_message_d_auteur_supprime_disparait(): void
	{
		$a = $this->fixtures($this->jeton.' titre banal', 'contenu avec le mot zorglub dedans');

		$this->assertSame([$a['message']], $this->chercher('+zorglub*'));

		$this->exec("UPDATE nf_user SET deleted = '1' WHERE id = ?", [$this->fixtures['user']]);

		$this->assertSame([], $this->chercher('+zorglub*'),
			'la jointure sur l\'auteur exige deleted = 0');
	}
}
