<?php
declare(strict_types=1);

namespace NF\Tests\Headless;

/**
 * Un widget qui montre ce que fait un module le déclare dans `requires`, et se tait sans lui.
 *
 * Jusqu'au 2026-10-09, une liste de quinze noms dans Addons\Widget devinait le module d'un widget à ses
 * tables : elle oubliait Recrutement, et ne voyait pas un module désactivé — son widget restait affiché,
 * avec des liens vers des pages fermées, et l'éditeur en direct le proposait encore. La déclaration du
 * widget fait foi : Widget::modules_manquants().
 */
final class WidgetModulesRequisTest extends HeadlessTestCase
{
	/** Les widgets qui portent le nom d'un module sans en dépendre : ils lisent les tables du cœur, ou rien du module. */
	private const INDEPENDANTS = [
		'discord' => 'le salon Discord public, sans rien du module de liaison',
		'members' => 'les comptes du cœur ; seul son lien « Liste des membres » suit le module',
	];

	public function test_un_widget_de_module_declare_son_module(): void
	{
		$verifies = 0;

		foreach (NeoFrag()->model2('addon')->get('widget') as $widget)
		{
			$nom = $widget->info()->name;

			if (!is_dir(NEOFRAG_CMS.'/modules/'.$nom) || isset(self::INDEPENDANTS[$nom]))
			{
				continue;
			}

			$this->assertContains($nom, (array) ($widget->info()->requires ?? []),
				"Le widget « $nom » montre ce que fait le module du même nom : il doit le déclarer dans requires.");
			$verifies++;
		}

		$this->assertGreaterThanOrEqual(15, $verifies, 'Trop peu de widgets de module trouvés : le banc est-il complet ?');
	}

	/**
	 * Ce que l'éditeur en direct propose se vérifie en vrai, connecté en administrateur : son tri des noms veut une
	 * langue chargée, que ce banc n'a pas.
	 */
	public function test_sans_son_module_le_widget_se_tait(): void
	{
		$forum  = NeoFrag()->module('forum');
		$widget = NeoFrag()->widget('forum');

		if (!$forum || !$widget)
		{
			$this->markTestSkipped('Le module ou le widget Forum n\'est pas installé ici.');
		}

		$this->assertSame([], $widget->modules_manquants(), 'Avec son module actif, le widget Forum ne doit rien réclamer.');

		// Le module désactivé, en mémoire seulement : la base n'est pas touchée.
		$reglages = $forum->settings();
		$avant    = $reglages->enabled ?? NULL;
		$reglages->enabled = FALSE;

		try
		{
			$this->assertFalse($forum->is_enabled());
			$this->assertSame(['forum'], $widget->modules_manquants());
			$this->assertNull($widget->output('index'), 'Module désactivé : le widget ne doit rien rendre.');
		}
		finally
		{
			$reglages->enabled = $avant;
		}

		$this->assertSame([], $widget->modules_manquants(), 'Le module réactivé, le widget revient.');
	}

	public function test_les_widgets_independants_vivent_sans_le_module(): void
	{
		foreach (self::INDEPENDANTS as $nom => $pourquoi)
		{
			if ($widget = NeoFrag()->widget($nom))
			{
				$this->assertSame([], (array) ($widget->info()->requires ?? []), "Le widget « $nom » vit sans le module ($pourquoi).");
			}
		}
	}

	/** La page des e-mails ne liste que les gabarits des modules installés : le seed porte ceux du forum, même sans lui. */
	public function test_les_gabarits_d_un_module_absent_ne_se_listent_pas(): void
	{
		$this->db()->insert('nf_email_templates', ['key' => 'essai.absent', 'title' => 'Essai absent', 'module' => 'module_absent_essai']);
		$this->db()->insert('nf_email_templates', ['key' => 'essai.coeur', 'title' => 'Essai cœur', 'module' => NULL]);

		$cles = array_column(NeoFrag()->module('emails')->model()->get_templates('fr'), 'key');

		$this->assertNotContains('essai.absent', $cles, 'Le gabarit d\'un module absent ne doit pas se lister.');
		$this->assertContains('essai.coeur', $cles, 'Un gabarit sans module (le cœur) se liste toujours.');
	}
}
