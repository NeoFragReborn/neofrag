<?php
declare(strict_types=1);

namespace NF\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use NF\Widgets\Seasonal\Lib\Season;

/**
 * La plage saisonnière de l'effet décoratif.
 *
 * Ce qui mérite une épreuve n'est pas le dessin — un flocon mal placé se voit — mais la **plage de
 * dates**, qui décide si quelque chose s'affiche, et la normalisation des réglages, qui partent
 * dans un attribut HTML puis dans du JavaScript.
 *
 * Le cas piégeux de la plage : une saison qui enjambe le Nouvel An. Du 15 décembre au 6 janvier, la
 * comparaison naïve `début <= aujourd'hui <= fin` est toujours fausse, et l'effet ne fonctionnerait
 * jamais précisément quand on le veut le plus.
 *
 * Test PUR : `Season` n'a aucune dépendance au service locator, à l'image des providers de
 * `widgets/twitch/lib`.
 */
final class SeasonalRangeTest extends TestCase
{
	/** @return array<string, array{string, string, string, bool}> */
	public static function saisons(): array
	{
		return [
			// Plage dans une même année civile.
			'été, en plein été'        => ['06-21', '09-21', '07-14', TRUE],
			'été, la veille'           => ['06-21', '09-21', '06-20', FALSE],
			'été, premier jour inclus' => ['06-21', '09-21', '06-21', TRUE],
			'été, dernier jour inclus' => ['06-21', '09-21', '09-21', TRUE],
			'été, le lendemain'        => ['06-21', '09-21', '09-22', FALSE],

			// Plage à cheval sur le Nouvel An : le cas qui casse la comparaison naïve.
			'hiver, en décembre'       => ['12-15', '01-06', '12-24', TRUE],
			'hiver, en janvier'        => ['12-15', '01-06', '01-02', TRUE],
			'hiver, premier jour'      => ['12-15', '01-06', '12-15', TRUE],
			'hiver, dernier jour'      => ['12-15', '01-06', '01-06', TRUE],
			'hiver, juste avant'       => ['12-15', '01-06', '12-14', FALSE],
			'hiver, juste après'       => ['12-15', '01-06', '01-07', FALSE],
			'hiver, en plein été'      => ['12-15', '01-06', '07-14', FALSE],

			// Un seul jour.
			'un seul jour, ce jour-là' => ['10-31', '10-31', '10-31', TRUE],
			'un seul jour, la veille'  => ['10-31', '10-31', '10-30', FALSE],
		];
	}

	#[DataProvider('saisons')]
	public function test_la_plage_saisonniere(string $debut, string $fin, string $jour, bool $attendu): void
	{
		self::assertSame($attendu, Season::en_cours($debut, $fin, $jour));
	}

	public function test_une_plage_vide_vaut_toute_l_annee(): void
	{
		self::assertTrue(Season::en_cours('', '', '03-15'));
		self::assertTrue(Season::en_cours('12-01', '', '03-15'), 'une seule borne ne définit pas une saison');
		self::assertTrue(Season::en_cours('', '01-06', '07-14'));
	}

	/**
	 * Une borne mal formée vaut « pas de borne », et non « jamais ».
	 *
	 * Le contraire ferait disparaître l'effet sans rien dire, sur un réglage écrit à la main ou
	 * importé d'un autre site — et personne ne saurait pourquoi.
	 */
	public function test_une_borne_invalide_est_ignoree(): void
	{
		self::assertTrue(Season::en_cours('pas une date', '01-06', '07-14'));
		self::assertTrue(Season::en_cours('13-45', '01-06', '07-14'), 'mois 13 et jour 45 : rejetés');
		self::assertTrue(Season::en_cours('2026-12-15', '01-06', '07-14'), "une année n'a pas sa place ici");
	}

	public function test_le_format_des_bornes(): void
	{
		self::assertSame('12-15', Season::jour_valide('12-15'));
		self::assertSame('12-15', Season::jour_valide('  12-15  '), "les espaces d'une saisie sont tolérés");
		self::assertSame('02-29', Season::jour_valide('02-29'), '29 février accepté : la comparaison est jour à jour');
		self::assertSame('', Season::jour_valide('1-5'), 'deux chiffres exigés de chaque côté');
		self::assertSame('', Season::jour_valide('00-10'));
		self::assertSame('', Season::jour_valide('12-32'));
		self::assertSame('', Season::jour_valide(''));
		self::assertSame('', Season::jour_valide(['12-15']), 'un réglage sérialisé peut rendre un tableau');
		self::assertSame('', Season::jour_valide(NULL));
	}

	/** Rien de ce qui vient de la base ne part tel quel dans le HTML. */
	public function test_les_reglages_inconnus_retombent_sur_le_defaut(): void
	{
		$reglages = Season::normaliser([
			'effect'  => '"><script>alert(1)</script>',
			'density' => 'gigantesque',
			'from'    => 'n\'importe quoi',
			'to'      => '01-06',
		]);

		self::assertSame(['effect' => 'snow', 'density' => 'normal', 'from' => '', 'to' => '01-06'], $reglages);
	}

	public function test_les_reglages_valides_sont_conserves(): void
	{
		self::assertSame(
			['effect' => 'confetti', 'density' => 'high', 'from' => '12-15', 'to' => '01-06'],
			Season::normaliser(['effect' => 'confetti', 'density' => 'high', 'from' => '12-15', 'to' => '01-06'])
		);
	}

	public function test_des_reglages_absents_donnent_les_defauts(): void
	{
		self::assertSame(['effect' => 'snow', 'density' => 'normal', 'from' => '', 'to' => ''], Season::normaliser([]));
	}

	/** Chaque densité proposée à l'administrateur doit avoir son nombre de particules. */
	public function test_chaque_densite_a_son_nombre_de_particules(): void
	{
		foreach (array_keys(Season::DENSITES) as $densite)
		{
			self::assertGreaterThan(0, Season::particules((string) $densite));
		}

		self::assertSame(Season::DENSITES['normal'], Season::particules('inconnue'));
	}
}
