<?php
declare(strict_types=1);

namespace NF\Tests\Headless;

/**
 * Ce qu'un addon autorise : désactivation et désinstallation.
 *
 * Ces deux réponses provenaient de trois tableaux statiques (Module::$core, Widget::$core,
 * Theme::$core) et d'une surcharge (Widget::is_removable()) qui se contredisaient et avaient tous
 * dérivé : celle des widgets protégeait sept noms qui ne sont pas des widgets, celle des thèmes
 * protégeait un thème « default » inexistant — en laissant `nebula`, le seul thème public livré,
 * supprimable depuis l'administration. Ils sont remplacés par la déclaration de chaque addon.
 */
final class AddonCapabilitiesTest extends HeadlessTestCase
{
	private function addon(string $type, string $nom)
	{
		foreach (NeoFrag()->model2('addon')->get($type) as $a)
		{
			if ($a->info()->name === $nom)
			{
				return $a;
			}
		}

		$this->fail("Addon $type:$nom introuvable.");
	}

	/** Le défaut qui a motivé ce chantier : nebula était supprimable. */
	public function test_les_themes_non_choisis_ne_sont_pas_supprimables(): void
	{
		$themes = ['admin' => 'le back-office', 'nebula' => 'le seul thème public livré'];

		// La vitrine n'existe que dans le dépôt de développement : le produit publié ne la porte jamais,
		// et ce test y échouait (« Addon theme:vitrine introuvable », banc d'essai du 2026-10-04).
		if (is_dir(dirname(__DIR__, 2).'/themes/vitrine'))
		{
			$themes['vitrine'] = 'notre propre site, non distribué';
		}

		foreach ($themes as $nom => $pourquoi)
		{
			$this->assertFalse($this->addon('theme', $nom)->is_removable(),
				"Le thème « $nom » ($pourquoi) ne doit pas être supprimable.");
		}
	}

	public function test_les_themes_du_catalogue_restent_supprimables(): void
	{
		foreach (['blockcraft', 'extend', 'forge', 'granite'] as $nom)
		{
			$this->assertTrue($this->addon('theme', $nom)->is_removable(),
				"Le thème « $nom » vient du marketplace : il doit rester supprimable.");
		}
	}

	public function test_le_coeur_n_est_pas_desinstallable(): void
	{
		foreach (['access', 'admin', 'settings', 'user', 'pages', 'menu'] as $nom)
		{
			$this->assertFalse($this->addon('module', $nom)->is_removable(),
				"Le module « $nom » appartient au cœur : il ne doit pas être supprimable.");
		}
	}

	public function test_l_infrastructure_ne_peut_pas_etre_eteinte(): void
	{
		foreach (['access', 'addons', 'admin', 'live_editor', 'monitoring', 'settings', 'statistics', 'user'] as $nom)
		{
			$a = $this->addon('module', $nom);

			$this->assertFalse($a->is_deactivatable(), "Le module « $nom » ne doit pas pouvoir être désactivé.");
			$this->assertTrue($a->is_enabled(), "Un module qu'on ne peut pas éteindre est forcément actif.");
		}
	}

	public function test_un_module_optionnel_reste_desinstallable(): void
	{
		foreach (['forum', 'news', 'wiki', 'shop'] as $nom)
		{
			$this->assertTrue($this->addon('module', $nom)->is_removable(),
				"Le module « $nom » est optionnel : il doit rester désinstallable.");
		}
	}
}
