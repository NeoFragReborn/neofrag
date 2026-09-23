<?php
declare(strict_types=1);

namespace NF\Tests\Headless;

/**
 * Le repli de langue d'un contenu monolingue (temps 1) — côté base de données.
 *
 * LE DÉFAUT ÉPINGLÉ ICI. Une actualité rédigée en français seulement proposait quand même les cinq
 * autres langues dans son sélecteur, et les cinq rendaient 404 : `WHERE lang = 'en'` ne trouvait
 * rien. Mesuré sur la démonstration le 2026-09-21 : soixante adresses mortes sur soixante-douze.
 * `Model::langue_du_contenu()` sert désormais la version qui existe.
 *
 * LE PIÈGE QUI A COÛTÉ UNE ITÉRATION. Le constructeur de requêtes est partagé et accumule :
 * interroger la base au milieu d'une chaîne détruit la chaîne de l'appelant. Les pages d'équipe et
 * d'article ont ainsi rendu 404 dans leur PROPRE langue. `Db::standalone()` met le panier de côté ;
 * le dernier test échouerait si quelqu'un retirait cet isolement.
 *
 * CE QUE CE TEST NE COUVRE PAS, ET OÙ ÇA L'EST. Le bandeau, les `hreflang` et le `canonical`
 * demandent une page rendue, que l'amorçage headless ne fournit pas : c'est
 * `tools/check-langues-contenu.php` qui les mesure, contre un site servi.
 */
final class LangueDuContenuTest extends HeadlessTestCase
{
	/**
	 * La marque des contenus semés ici. Elle sert à les RETROUVER pour les retirer.
	 *
	 * Pourquoi ne pas s'en remettre à la transaction de `HeadlessTestCase`. Elle suffit tant que
	 * `tearDown()` s'exécute — et une version intermédiaire de ce fichier levait une erreur avant
	 * d'appeler celui du parent, si bien que l'annulation n'avait jamais lieu. Six actualités de
	 * test sont alors restées dans la base du site de l'atelier, et c'est `check-liens` qui les a
	 * signalées, en suivant leurs liens depuis l'administration. Un test qui écrit retire ce qu'il
	 * a déposé, de lui-même.
	 */
	private const MARQUE = 'nf_test_langue_';

	private array $semees = [];
	private ?int $categorie = NULL;
	private ?int $auteur    = NULL;

	protected function tearDown(): void
	{
		foreach ($this->semees as $news_id)
		{
			$this->db()->where('news_id', $news_id)->delete('nf_news_lang');
			$this->db()->where('news_id', $news_id)->delete('nf_news');
		}

		if ($this->categorie !== NULL)
		{
			$this->db()->where('category_id', $this->categorie)->delete('nf_news_categories_lang');
			$this->db()->where('category_id', $this->categorie)->delete('nf_news_categories');
		}

		if ($this->auteur !== NULL)
		{
			$this->db()->where('id', $this->auteur)->delete('nf_user');
		}

		$this->semees    = [];
		$this->categorie = NULL;
		$this->auteur    = NULL;

		parent::tearDown();
	}

	/** Le modèle d'un module, pour atteindre la méthode protégée du socle commun. */
	private function resoudre(string $table, string $colonne, int $id): string
	{
		$modele  = \NeoFrag()->module('news')->model();
		$methode = new \ReflectionMethod($modele, 'langue_du_contenu');
		$methode->setAccessible(TRUE);

		return (string) $methode->invoke($modele, $table, $colonne, $id);
	}

	/**
	 * Une actualité jetable, avec exactement les langues demandées.
	 *
	 * Elle fabrique sa propre catégorie et son propre auteur : `nf_news` porte une clé étrangère vers
	 * chacun, et une installation NEUVE — celle que monte la CI — n'a ni catégorie ni contenu. Un test
	 * qui s'appuie sur les données d'un atelier passe chez soi et échoue partout ailleurs.
	 */
	private function semer(array $langues): int
	{
		$db = $this->db();

		if ($this->categorie === NULL)
		{
			$this->categorie = (int) $db->insert('nf_news_categories', ['name' => 'nf-test-langue']);

			$db->insert('nf_news_categories_lang', [
				'category_id' => $this->categorie,
				'lang'        => 'fr',
				'title'       => self::MARQUE.'categorie',
			]);
		}

		if ($this->auteur === NULL)
		{
			$this->auteur = $this->createUser('nftestlangue');
		}

		$news_id = (int) $db->insert('nf_news', [
			'category_id' => $this->categorie,
			'user_id'     => $this->auteur,
			'published'   => 1,
			'date'        => date('Y-m-d H:i:s'),
		]);

		$this->semees[] = $news_id;

		foreach ($langues as $langue)
		{
			$db->insert('nf_news_lang', [
				'news_id'      => $news_id,
				'lang'         => $langue,
				'title'        => self::MARQUE.$langue,
				'introduction' => '',
				'content'      => '',
				'tags'         => '',
			]);
		}

		return $news_id;
	}

	public function test_une_version_disponible_est_servie_telle_quelle(): void
	{
		$id = $this->semer(['fr']);

		$this->assertSame('fr', $this->resoudre('nf_news_lang', 'news_id', $id),
			'Un contenu qui n\'existe qu\'en français doit être servi en français.');
	}

	public function test_un_contenu_monolingue_est_servi_dans_SA_langue(): void
	{
		$id = $this->semer(['pt']);

		$this->assertSame('pt', $this->resoudre('nf_news_lang', 'news_id', $id),
			'Un contenu qui n\'existe qu\'en portugais doit être servi en portugais, pas rendre 404.');
	}

	public function test_le_choix_du_repli_ne_depend_pas_de_l_ordre_d_insertion(): void
	{
		// Deux contenus, mêmes langues, saisies dans l'ordre inverse : le repli doit être le même.
		$premier = $this->resoudre('nf_news_lang', 'news_id', $this->semer(['de', 'es']));
		$second  = $this->resoudre('nf_news_lang', 'news_id', $this->semer(['es', 'de']));

		$this->assertSame($premier, $second,
			'Le repli doit être stable : deux visiteurs, et un moteur de recherche, doivent voir la même version.');
	}

	public function test_un_contenu_inexistant_laisse_le_404_faire_son_travail(): void
	{
		$langue = $this->resoudre('nf_news_lang', 'news_id', 999999);

		$this->assertNotSame('pt', $langue,
			'Sans aucune version, il n\'y a rien à servir : cette méthode ne doit rien inventer.');
	}

	public function test_standalone_preserve_la_requete_en_cours(): void
	{
		$db = \NeoFrag()->db;
		$id = $this->semer(['fr', 'es']);

		// Une chaîne à moitié bâtie, comme dans un `check_*` de module…
		$db->select('lang')->from('nf_news_lang')->where('news_id', $id);

		// …qu'une requête isolée ne doit pas emporter. Elle compte les lignes que CE test vient de
		// semer : deux, quoi que le site contienne par ailleurs.
		$compte = $db->standalone(static function($db) use ($id){
			return $db->select('COUNT(*) AS n')->from('nf_news_lang')->where('news_id', $id)->row();
		});

		$langues = (array) $db->group_by('lang')->get();
		sort($langues);

		$this->assertSame(2, (int) $compte, 'La requête isolée doit s\'exécuter pour son propre compte.');
		$this->assertSame(['es', 'fr'], $langues,
			'La chaîne en cours doit survivre à la requête isolée : sans cela, la page rend 404 dans sa propre langue.');
	}
}
