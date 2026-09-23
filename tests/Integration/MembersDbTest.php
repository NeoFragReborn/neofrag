<?php
declare(strict_types=1);

namespace NF\Tests\Integration;

/**
 * Tests d'intégration de l'annuaire des membres et de leur recherche, contre la vraie base.
 *
 * Mime le SQL EXACT de deux endroits qui doivent rester d'accord :
 *
 *   - l'annuaire public — `Members\Controllers\Checker::index`, qui liste
 *     `nf_user WHERE deleted = '0' ORDER BY username` ;
 *   - la recherche des membres — `Members\Controllers\Search::search`, ajoutée le 2026-09-17,
 *     qui cherche sur le pseudo, le prénom et le nom.
 *
 * **L'invariant qui compte est l'accord entre les deux.** Une recherche qui montrerait quelqu'un
 * que l'annuaire ne liste pas serait une fuite ; une recherche qui en montrerait moins serait un
 * défaut. Le test l'exprime tel quel : à jeu de données identique, l'ensemble trouvé par la
 * recherche large doit être exactement l'ensemble listé par l'annuaire.
 *
 * NB : `nf_user_profile` n'est PAS garanti. Un compte créé sans passer par l'inscription — un
 * import, une fixture, une création en base — n'a pas de ligne de profil. D'où la jointure GAUCHE
 * dans la recherche, et le test qui la fige : en jointure stricte, ces membres disparaîtraient de
 * la recherche sans que rien ne le signale.
 */
final class MembersDbTest extends IntegrationTestCase
{
	/** La clause de l'annuaire public, telle quelle. */
	private const ANNUAIRE = "SELECT id FROM nf_user WHERE deleted = '0'";

	/** La clause de la recherche, telle quelle (cf. Members\Controllers\Search::search). */
	private const RECHERCHE = "SELECT u.id FROM nf_user u
	                           LEFT JOIN nf_user_profile p ON u.id = p.id
	                           WHERE u.deleted = '0'
	                             AND (u.username LIKE ? OR p.first_name LIKE ? OR p.last_name LIKE ?)";

	/** Crée un membre avec sa ligne de profil, et renvoie son id. */
	private function membreAvecProfil(string $pseudo, string $prenom = '', string $nom = ''): int
	{
		$this->exec("INSERT INTO nf_user (username, password, salt, data) VALUES (?, '', '', '')", [$pseudo]);
		$id = (int) self::$pdo->insert_id;

		$this->exec(
			"INSERT INTO nf_user_profile (id, first_name, last_name, signature, country, location, quote, website, linkedin, github, instagram, twitch)
			 VALUES (?, ?, ?, '', '', '', '', '', '', '', '', '')",
			[$id, $prenom, $nom]
		);

		return $id;
	}

	/** @return list<int> */
	private function chercher(string $terme): array
	{
		$like = '%'.$terme.'%';
		$res  = $this->exec(self::RECHERCHE, [$like, $like, $like])->get_result();
		$ids  = [];

		while ($row = $res->fetch_row())
		{
			$ids[] = (int) $row[0];
		}

		sort($ids);

		return $ids;
	}

	// ------------------------------------------------------------------ l'annuaire

	public function test_un_compte_supprime_ne_figure_pas_dans_l_annuaire(): void
	{
		$vivant = $this->membreAvecProfil('itest_vivant_'.substr(md5(uniqid('', true)), 0, 8));
		$efface = $this->membreAvecProfil('itest_efface_'.substr(md5(uniqid('', true)), 0, 8));

		$this->exec("UPDATE nf_user SET deleted = '1' WHERE id = ?", [$efface]);

		$ids = [];
		$res = $this->exec(self::ANNUAIRE)->get_result();

		while ($row = $res->fetch_row())
		{
			$ids[] = (int) $row[0];
		}

		$this->assertContains($vivant, $ids);
		$this->assertNotContains($efface, $ids, 'un compte supprimé ne doit pas être listé');
	}

	// ------------------------------------------------------------------ la recherche

	public function test_la_recherche_trouve_par_pseudo_prenom_et_nom(): void
	{
		$jeton = substr(md5(uniqid('', true)), 0, 8);
		$id    = $this->membreAvecProfil('itest_pseudo'.$jeton, 'Prenom'.$jeton, 'Nom'.$jeton);

		$this->assertSame([$id], $this->chercher('itest_pseudo'.$jeton), 'trouvé par pseudo');
		$this->assertSame([$id], $this->chercher('Prenom'.$jeton),       'trouvé par prénom');
		$this->assertSame([$id], $this->chercher('Nom'.$jeton),          'trouvé par nom');
	}

	public function test_la_recherche_trouve_un_membre_SANS_ligne_de_profil(): void
	{
		// `createUser()` du socle n'insère QUE dans nf_user — exactement le cas d'un compte importé
		// ou créé en base. En jointure stricte, il disparaîtrait de la recherche.
		$id  = $this->createUser('itest_sansprofil');
		$nom = (string) $this->scalar('SELECT username FROM nf_user WHERE id = ?', [$id]);

		$this->assertSame(
			0,
			(int) $this->scalar('SELECT COUNT(*) FROM nf_user_profile WHERE id = ?', [$id]),
			'le membre ne doit effectivement avoir aucune ligne de profil'
		);

		$this->assertSame([$id], $this->chercher($nom),
			'la jointure sur le profil doit être GAUCHE : sans elle, ce membre serait invisible');
	}

	public function test_un_compte_supprime_reste_invisible_dans_la_recherche(): void
	{
		$jeton = substr(md5(uniqid('', true)), 0, 8);
		$id    = $this->membreAvecProfil('itest_parti'.$jeton, 'Parti'.$jeton);

		$this->assertSame([$id], $this->chercher('itest_parti'.$jeton));

		$this->exec("UPDATE nf_user SET deleted = '1' WHERE id = ?", [$id]);

		$this->assertSame([], $this->chercher('itest_parti'.$jeton),
			'la recherche applique le même filtre que l\'annuaire');
		$this->assertSame([], $this->chercher('Parti'.$jeton),
			'y compris quand le terme correspond au prénom');
	}

	// ------------------------------------------------------------------ l'invariant

	public function test_la_recherche_ne_montre_ni_plus_ni_moins_que_l_annuaire(): void
	{
		$jeton = substr(md5(uniqid('', true)), 0, 8);

		$attendus = [
			$this->membreAvecProfil('itest_inv_a'.$jeton, 'Alpha'.$jeton, 'Un'.$jeton),
			$this->membreAvecProfil('itest_inv_b'.$jeton),                 // profil vide
			$this->createUser('itest_inv_c'.$jeton),                       // aucun profil du tout
		];

		$exclu = $this->membreAvecProfil('itest_inv_d'.$jeton, 'Delta'.$jeton);
		$this->exec("UPDATE nf_user SET deleted = '1' WHERE id = ?", [$exclu]);

		// Un terme que TOUS les comptes de ce test portent dans leur pseudo.
		$trouves = $this->chercher('itest_inv_');

		// Restreint au jeu de ce test : la base peut contenir d'autres `itest_inv_` d'un run passé.
		$trouves = array_values(array_intersect($trouves, array_merge($attendus, [$exclu])));
		sort($attendus);

		$this->assertSame($attendus, $trouves,
			'la recherche doit rendre exactement les membres que l\'annuaire liste — ni fuite, ni oubli');
	}
}
