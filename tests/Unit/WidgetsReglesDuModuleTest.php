<?php
declare(strict_types=1);

namespace NF\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use NF\Widgets\Recruits\Controllers\Index as WidgetRecrutement;

/**
 * Un widget montre le contenu d'un module : il doit en suivre les règles d'affichage. Plusieurs les
 * ignoraient (2026-10-04) :
 *
 *   - « Sondages » montrait toujours les pourcentages (cf. SondagesResultatsTest) ;
 *   - « Galeries » listait et tirait au hasard dans les albums brouillons, programmés, à la corbeille
 *     ou réservés à un groupe ;
 *   - « Forum » affichait l'extrait des messages des catégories réservées au VIP, et les messages
 *     supprimés par la modération en lignes vides ;
 *   - « Événements » et le calendrier montraient un événement avant son heure de parution ;
 *   - « Recrutement » listait les offres indisponibles quand le module les masquait.
 *
 * La règle des offres indisponibles est une fonction pure ; les autres vivent dans des requêtes,
 * que ces épreuves vérifient à la source.
 */
final class WidgetsReglesDuModuleTest extends TestCase
{
	private const RACINE = __DIR__.'/../..';

	private const MAINTENANT = 1_800_000_000;

	/** @return array<string, array{array<string, mixed>, bool}> */
	public static function offres(): array
	{
		$ouverte = ['closed' => '0', 'candidacies_accepted' => 1, 'size' => 3, 'date_end' => NULL];

		return [
			'ouverte'                    => [$ouverte, FALSE],
			'close'                      => [['closed' => '1'] + $ouverte, TRUE],
			'complète'                   => [['candidacies_accepted' => 3] + $ouverte, TRUE],
			'date limite passée'         => [['date_end' => date('Y-m-d', self::MAINTENANT - 86400)] + $ouverte, TRUE],
			'date limite à venir'        => [['date_end' => date('Y-m-d', self::MAINTENANT + 86400 * 2)] + $ouverte, FALSE],
		];
	}

	/** @param array<string, mixed> $offre */
	#[DataProvider('offres')]
	public function test_une_offre_indisponible(array $offre, bool $attendu): void
	{
		self::assertSame($attendu, WidgetRecrutement::indisponible($offre, self::MAINTENANT));
	}

	public function test_le_widget_recrutement_lit_le_reglage_du_module(): void
	{
		self::assertStringContainsString('recruits_hide_unavailable', (string) file_get_contents(self::RACINE.'/widgets/recruits/controllers/index.php'));
	}

	public function test_le_widget_galeries_ne_montre_que_les_albums_visibles(): void
	{
		$modele = (string) file_get_contents(self::RACINE.'/widgets/gallery/models/gallery.php');

		// La règle : corbeille, publication, parution, droit de voir l'album.
		foreach (["->where('deleted_at', NULL)", "->where('published', TRUE)", "->where('date <=',", "'gallery_see'"] as $condition)
		{
			self::assertStringContainsString($condition, $modele);
		}

		// Et chaque lecture du widget passe par elle.
		foreach (['get_gallery', 'get_random_image', 'get_images', 'get_categories'] as $methode)
		{
			self::assertSame(1, preg_match('/public function '.$methode.'\(.*?\n\t\}/s', $modele, $corps), $methode);
			self::assertStringContainsString('albums_visibles()', $corps[0], $methode);
		}
	}

	public function test_le_widget_forum_suit_le_vip_et_la_moderation(): void
	{
		$modele = (string) file_get_contents(self::RACINE.'/widgets/forum/models/forum.php');

		self::assertStringContainsString('->reservee_vip(', $modele);
		self::assertStringContainsString("->where('m.deleted_at', NULL)", $modele);
		self::assertStringContainsString('public function reservee_vip(int $category_id): bool', (string) file_get_contents(self::RACINE.'/modules/forum/models/forum.php'));
	}

	/** @return array<string, array{string, int}> fichier, nombre de requêtes publiques attendues */
	public static function lectures_d_evenements(): array
	{
		return [
			'widget Événements'           => ['widgets/events/models/events.php', 3],
			'calendrier (flux JSON)'      => ['modules/events/controllers/ajax.php', 1],
		];
	}

	#[DataProvider('lectures_d_evenements')]
	public function test_la_publication_programmee_des_evenements(string $fichier, int $requetes): void
	{
		$source = (string) file_get_contents(self::RACINE.'/'.$fichier);

		self::assertSame($requetes, substr_count($source, "e.publish_date IS NULL OR e.publish_date <= NOW()"));
	}
}
