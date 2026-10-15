<?php
declare(strict_types=1);

namespace NF\Tests\Integration;

/**
 * Le diaporama traduit (m22, 2026-10-13) : le titre et la légende d'une vue vivent dans
 * `nf_slider_slides_lang`, une ligne par langue, et le widget sert la MEILLEURE traduction — celle
 * de la langue demandée, sinon le français, sinon la première écrite (`Db::join_lang()`).
 *
 * Ce que ces tests protègent, et pourquoi :
 *   - une vue traduite dans une seule langue doit rester visible dans les cinq autres. L'écriture
 *     naïve — `join(...)->where('lang', $langue)` — rend la jointure interne et la fait DISPARAÎTRE
 *     (le défaut des équipes, journal du 2026-10-01) ;
 *   - une vue sans aucune traduction garde son image : la jointure est GAUCHE ;
 *   - traduire dans une langue ne touche pas les autres, et la première traduction dans une langue
 *     s'écrit même si sa ligne n'existe pas encore (`replace`, pas `update` — un `update` sans ligne
 *     n'écrit rien, en silence).
 *
 * Les requêtes sont celles que le constructeur fabrique, recopiées ici : les tests d'intégration
 * parlent à la base, pas au framework.
 */
final class SliderLangDbTest extends IntegrationTestCase
{
	/** La jointure exacte de `join_lang('nf_slider_slides_lang l', 'slide_id', 's.id', $langue, 'LEFT')`. */
	private function titres(string $langue): array
	{
		$sql = 'SELECT s.id, l.title, l.caption
				FROM nf_slider_slides s
				LEFT JOIN nf_slider_slides_lang l ON l.slide_id = s.id AND l.lang = (
					SELECT l_choix.lang FROM nf_slider_slides_lang l_choix
					WHERE l_choix.slide_id = s.id
					ORDER BY l_choix.lang = ? DESC, l_choix.lang = "fr" DESC, l_choix.lang
					LIMIT 1)
				WHERE s.active = 1
				ORDER BY s.sort_order, s.id';

		$stmt = self::$pdo->prepare($sql);
		$stmt->bind_param('s', $langue);
		$stmt->execute();

		$lignes = [];

		foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $ligne)
		{
			$lignes[(int) $ligne['id']] = $ligne;
		}

		return $lignes;
	}

	private function vue(int $ordre = 0, int $active = 1): int
	{
		$this->exec("INSERT INTO nf_slider_slides (image_url, link, sort_order, active) VALUES ('upload/itest.jpg', '', ?, ?)", [$ordre, $active]);

		return (int) self::$pdo->insert_id;
	}

	private function traduire(int $id, string $langue, string $titre, string $legende = ''): void
	{
		$this->exec('REPLACE INTO nf_slider_slides_lang (slide_id, lang, title, caption) VALUES (?, ?, ?, ?)', [$id, $langue, $titre, $legende]);
	}

	public function test_chaque_langue_recoit_sa_traduction(): void
	{
		$id = $this->vue();
		$this->traduire($id, 'fr', 'Bienvenue sur la démo', 'Navigue librement.');
		$this->traduire($id, 'en', 'Welcome to the demo', 'Have a look around.');

		$this->assertSame('Bienvenue sur la démo', $this->titres('fr')[$id]['title']);
		$this->assertSame('Welcome to the demo',   $this->titres('en')[$id]['title']);
		$this->assertSame('Have a look around.',   $this->titres('en')[$id]['caption'], 'La légende vient de la MÊME ligne que le titre');
	}

	public function test_une_vue_ecrite_dans_une_seule_langue_reste_visible_partout(): void
	{
		$id = $this->vue();
		$this->traduire($id, 'fr', 'Recrutement ouvert');

		foreach (['en', 'de', 'es', 'it', 'pt'] as $langue)
		{
			$vues = $this->titres($langue);
			$this->assertArrayHasKey($id, $vues, 'La vue existe en '.$langue);
			$this->assertSame('Recrutement ouvert', $vues[$id]['title'], 'Le repli français sert en '.$langue);
		}
	}

	public function test_le_repli_prend_la_premiere_langue_ecrite_a_defaut_du_francais(): void
	{
		$id = $this->vue();
		$this->traduire($id, 'it', 'Reclutamento aperto');

		$this->assertSame('Reclutamento aperto', $this->titres('de')[$id]['title'], 'Sans français, la première langue écrite');
		$this->assertSame('Reclutamento aperto', $this->titres('it')[$id]['title']);
	}

	public function test_une_vue_sans_aucune_traduction_garde_son_image(): void
	{
		$id = $this->vue();

		$vues = $this->titres('fr');
		$this->assertArrayHasKey($id, $vues, 'La jointure est GAUCHE : la vue ne disparaît pas');
		$this->assertNull($vues[$id]['title'], 'Sans traduction, pas de titre — le widget n\'affiche alors aucune légende');
	}

	public function test_traduire_une_langue_ne_touche_pas_les_autres(): void
	{
		$id = $this->vue();
		$this->traduire($id, 'fr', 'Avant');
		$this->traduire($id, 'en', 'Before');
		$this->traduire($id, 'en', 'After');

		$this->assertSame('Avant', $this->titres('fr')[$id]['title']);
		$this->assertSame('After', $this->titres('en')[$id]['title']);
		$this->assertSame(2, (int) $this->scalar('SELECT COUNT(*) FROM nf_slider_slides_lang WHERE slide_id = ?', [$id]), 'Deux lignes, pas trois');
	}

	public function test_la_premiere_traduction_dans_une_langue_s_ecrit_sans_ligne_prealable(): void
	{
		$id = $this->vue();
		$this->traduire($id, 'fr', 'Bienvenue');

		// Ce qu'un `update` aurait fait : zéro ligne touchée, et le texte perdu sans un mot.
		$this->exec("UPDATE nf_slider_slides_lang SET title = 'Willkommen' WHERE slide_id = ? AND lang = 'de'", [$id]);
		$this->assertSame(0, (int) $this->scalar("SELECT COUNT(*) FROM nf_slider_slides_lang WHERE slide_id = ? AND lang = 'de'", [$id]));

		// Ce que fait le modèle (`Db::replace`).
		$this->traduire($id, 'de', 'Willkommen');
		$this->assertSame('Willkommen', $this->titres('de')[$id]['title']);
	}

	public function test_supprimer_une_vue_emporte_ses_traductions(): void
	{
		$id = $this->vue();
		$this->traduire($id, 'fr', 'À supprimer');
		$this->traduire($id, 'en', 'To delete');

		$this->exec('DELETE FROM nf_slider_slides WHERE id = ?', [$id]);

		$this->assertSame(0, (int) $this->scalar('SELECT COUNT(*) FROM nf_slider_slides_lang WHERE slide_id = ?', [$id]), 'La clé étrangère efface les traductions');
	}
}
