<?php
declare(strict_types=1);

namespace NF\Tests\Integration;

/**
 * Écrire une traduction qui n'existe pas encore (m30, 2026-10-14).
 *
 * Le produit enregistrait une traduction par `->where('lang', $langue)->update('nf_…_lang', …)`. La
 * PREMIÈRE fois qu'on traduisait un contenu dans une langue, sa ligne n'existait pas : l'`update`
 * touchait zéro ligne, et le texte saisi était perdu sans un mot — quatorze endroits du produit.
 * `Db::ecrire_lang()` crée la ligne si elle manque, et ne change que les colonnes nommées.
 *
 * Ces tests rejouent le SQL que le constructeur fabrique, sur de vraies tables : c'est le
 * comportement de MySQL qui est en cause, pas le PHP.
 */
final class TraductionsDbTest extends IntegrationTestCase
{
	/** Le SQL de `Db::ecrire_lang()`. */
	private function ecrire_lang(string $table, array $cle, array $colonnes): void
	{
		$valeurs = array_merge($cle, $colonnes);
		$noms    = implode(', ', array_map(static fn ($c) => "`$c`", array_keys($valeurs)));
		$trous   = implode(', ', array_fill(0, count($valeurs), '?'));
		$maj     = implode(', ', array_map(static fn ($c) => "`$c` = VALUES(`$c`)", array_keys($colonnes)));

		$this->exec("INSERT INTO `$table` ($noms) VALUES ($trous) ON DUPLICATE KEY UPDATE $maj", array_values($valeurs));
	}

	/** L'ancienne écriture, celle qui perdait le texte. */
	private function ancienne_ecriture(string $table, array $cle, array $colonnes): int
	{
		$sets  = implode(', ', array_map(static fn ($c) => "`$c` = ?", array_keys($colonnes)));
		$where = implode(' AND ', array_map(static fn ($c) => "`$c` = ?", array_keys($cle)));

		return $this->exec("UPDATE `$table` SET $sets WHERE $where", array_merge(array_values($colonnes), array_values($cle)))->affected_rows;
	}

	private function actualite(): int
	{
		$this->exec("INSERT INTO nf_news_categories (name) VALUES ('itest')");
		$categorie = (int) self::$pdo->insert_id;
		$auteur    = $this->createUser('itest_trad');

		$this->exec("INSERT INTO nf_news (category_id, user_id, date, published) VALUES (?, ?, NOW(), '1')", [$categorie, $auteur]);
		$id = (int) self::$pdo->insert_id;
		$this->exec("INSERT INTO nf_news_lang (news_id, lang, title, introduction, content, tags) VALUES (?, 'fr', 'Titre français', 'Chapeau', '<p>Corps</p>', 'un,deux')", [$id]);

		return $id;
	}

	private function lire(int $id, string $langue): ?array
	{
		$res = $this->exec('SELECT title, introduction, content, tags FROM nf_news_lang WHERE news_id = ? AND lang = ?', [$id, $langue])->get_result();

		return $res ? ($res->fetch_assoc() ?: NULL) : NULL;
	}

	public function test_l_ancienne_ecriture_perdait_la_premiere_traduction(): void
	{
		$id = $this->actualite();

		$touchees = $this->ancienne_ecriture('nf_news_lang', ['news_id' => $id, 'lang' => 'en'], ['title' => 'English title']);

		$this->assertSame(0, $touchees, 'Aucune ligne touchée : rien n\'était écrit');
		$this->assertNull($this->lire($id, 'en'), 'Et le texte saisi était perdu, sans erreur');
	}

	public function test_la_premiere_traduction_cree_sa_ligne(): void
	{
		$id = $this->actualite();

		$this->ecrire_lang('nf_news_lang', ['news_id' => $id, 'lang' => 'en'], [
			'title' => 'English title', 'introduction' => 'Lead', 'content' => '<p>Body</p>', 'tags' => 'one,two',
		]);

		$en = $this->lire($id, 'en');
		$this->assertNotNull($en, 'La ligne anglaise existe');
		$this->assertSame('English title', $en['title']);
		$this->assertSame('Titre français', $this->lire($id, 'fr')['title'], 'Le français n\'a pas bougé');
	}

	public function test_une_seconde_ecriture_met_a_jour_sans_doubler(): void
	{
		$id = $this->actualite();

		$this->ecrire_lang('nf_news_lang', ['news_id' => $id, 'lang' => 'en'], ['title' => 'Before', 'introduction' => '', 'content' => '', 'tags' => '']);
		$this->ecrire_lang('nf_news_lang', ['news_id' => $id, 'lang' => 'en'], ['title' => 'After', 'introduction' => '', 'content' => '', 'tags' => '']);

		$this->assertSame('After', $this->lire($id, 'en')['title']);
		$this->assertSame(2, (int) $this->scalar('SELECT COUNT(*) FROM nf_news_lang WHERE news_id = ?', [$id]), 'Deux lignes : une par langue');
	}

	public function test_ce_qu_on_ne_nomme_pas_ne_bouge_pas(): void
	{
		// Un billet du Blog : `excerpt` et `tags` ont un défaut, on peut donc ne pas les nommer.
		$this->exec("INSERT INTO nf_articles_categories (name) VALUES ('itest')");
		$categorie = (int) self::$pdo->insert_id;
		$this->exec("INSERT INTO nf_articles (category_id, user_id, date, published) VALUES (?, ?, NOW(), '1')", [$categorie, $this->createUser('itest_trad')]);
		$id = (int) self::$pdo->insert_id;
		$this->exec("INSERT INTO nf_articles_lang (article_id, lang, title, excerpt, content, tags) VALUES (?, 'fr', 'Titre', 'Chapeau', '<p>Corps</p>', 'un,deux')", [$id]);

		// Une édition qui ne touche que le titre et le corps laisse le chapeau et les étiquettes : c'est la
		// différence avec `replace()`, qui efface la ligne et remet les autres colonnes à leur défaut.
		$this->ecrire_lang('nf_articles_lang', ['article_id' => $id, 'lang' => 'fr'], ['title' => 'Titre corrigé', 'content' => '<p>Corps</p>']);

		$res = $this->exec("SELECT title, excerpt, tags FROM nf_articles_lang WHERE article_id = ? AND lang = 'fr'", [$id])->get_result()->fetch_assoc();
		$this->assertSame('Titre corrigé', $res['title']);
		$this->assertSame('Chapeau', $res['excerpt'], 'REPLACE aurait remis le chapeau à vide');
		$this->assertSame('un,deux', $res['tags']);
	}

	public function test_six_langues_cohabitent(): void
	{
		$id = $this->actualite();

		foreach (['en' => 'English', 'de' => 'Deutsch', 'es' => 'Español', 'it' => 'Italiano', 'pt' => 'Português'] as $langue => $titre)
		{
			$this->ecrire_lang('nf_news_lang', ['news_id' => $id, 'lang' => $langue], ['title' => $titre, 'introduction' => '', 'content' => '', 'tags' => '']);
		}

		$this->assertSame(6, (int) $this->scalar('SELECT COUNT(*) FROM nf_news_lang WHERE news_id = ?', [$id]));
		$this->assertSame('Deutsch', $this->lire($id, 'de')['title']);
		$this->assertSame('Titre français', $this->lire($id, 'fr')['title']);
	}

	public function test_une_colonne_obligatoire_doit_etre_donnee_a_la_creation(): void
	{
		$id = $this->actualite();

		// `introduction` est NOT NULL sans défaut : créer la ligne anglaise sans elle est REFUSÉ, bruyamment.
		// Les douze appels du produit donnent bien toutes les colonnes de texte de leur table.
		$this->expectException(\PHPUnit\Framework\AssertionFailedError::class);
		$this->ecrire_lang('nf_news_lang', ['news_id' => $id, 'lang' => 'en'], ['title' => 'English title']);
	}

	/**
	 * Les groupes écrivaient pire encore : sans la langue dans le WHERE, l'UPDATE visait TOUTES les
	 * lignes du groupe et leur posait la même langue — doublon de clé primaire à la deuxième ligne.
	 */
	public function test_les_groupes_ne_renomment_plus_toutes_leurs_langues(): void
	{
		$this->exec("INSERT INTO nf_groups (name, color, icon) VALUES ('itest', 'primary', 'fas fa-users')");
		$groupe = (int) self::$pdo->insert_id;
		$this->exec("INSERT INTO nf_groups_lang (group_id, lang, title) VALUES (?, 'fr', 'Groupe'), (?, 'en', 'Group')", [$groupe, $groupe]);

		$this->ecrire_lang('nf_groups_lang', ['group_id' => $groupe, 'lang' => 'de'], ['title' => 'Gruppe']);

		$this->assertSame(3, (int) $this->scalar('SELECT COUNT(*) FROM nf_groups_lang WHERE group_id = ?', [$groupe]));
		$this->assertSame('Groupe', $this->scalar("SELECT title FROM nf_groups_lang WHERE group_id = ? AND lang = 'fr'", [$groupe]));
		$this->assertSame('Group', $this->scalar("SELECT title FROM nf_groups_lang WHERE group_id = ? AND lang = 'en'", [$groupe]));
	}
}
