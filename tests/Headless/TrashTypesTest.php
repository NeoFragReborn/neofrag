<?php
declare(strict_types=1);

namespace NF\Tests\Headless;

/**
 * Corbeille — la collecte des types restaurables auprès des modules (inversion du 2026-09-15).
 *
 * Avant, `Trash` tenait EN DUR la table, la clé primaire et les méthodes de restauration de
 * news/articles/gallery/comments/forum. Un module du cœur connaissait donc nommément des modules
 * optionnels, ce qui interdisait d'alléger le paquet : la corbeille les aurait réclamés.
 *
 * Ce test épingle le contrat de l'inversion : la corbeille ne connaît personne, ce sont les modules
 * qui se déclarent via `trash_types()`. Il échouerait si quelqu'un remettait une liste en dur, ou si
 * un module perdait sa déclaration.
 */
final class TrashTypesTest extends HeadlessTestCase
{
	private function types(): array
	{
		return NeoFrag()->module('trash')->types();
	}

	public function test_la_corbeille_ne_contient_aucune_liste_en_dur(): void
	{
		$source = file_get_contents(NEOFRAG_CMS.'/modules/trash/trash.php');

		$this->assertStringNotContainsString('const TYPES', $source,
			'La corbeille ne doit plus porter de registre en dur : les modules se déclarent eux-mêmes.');

		foreach (['nf_news', 'nf_articles', 'nf_gallery', 'nf_forum_messages'] as $table)
		{
			$this->assertStringNotContainsString($table, $source,
				"La corbeille ne doit plus nommer la table $table d'un autre module.");
		}
	}

	public function test_les_modules_installes_declarent_bien_leurs_types(): void
	{
		$types = $this->types();

		$this->assertNotEmpty($types, 'Sur une installation complète, au moins un module déclare un type.');

		// Les cinq types de l'ancien registre en dur, désormais déclarés par leurs modules.
		foreach (['news' => 'news', 'article' => 'articles', 'gallery' => 'gallery',
		          'comment' => 'comments', 'forum' => 'forum'] as $type => $module)
		{
			$this->assertArrayHasKey($type, $types, "Le type « $type » doit être déclaré par le module $module.");
			$this->assertSame($module, $types[$type]['module'],
				"Le propriétaire du type « $type » est déduit du module déclarant, pas recopié.");
		}
	}

	public function test_chaque_type_porte_le_contrat_attendu(): void
	{
		foreach ($this->types() as $type => $cfg)
		{
			foreach (['label', 'table', 'pk', 'restore', 'purge'] as $cle)
			{
				$this->assertArrayHasKey($cle, $cfg, "Le type « $type » doit déclarer « $cle ».");
				$this->assertNotSame('', (string) $cfg[$cle], "« $cle » du type « $type » ne doit pas être vide.");
			}

			$this->assertTrue(
				isset($cfg['lang']) || isset($cfg['content']),
				"Le type « $type » doit dire d'où vient son titre : « lang » (table de langue) ou « content » (colonne)."
			);
		}
	}
}
