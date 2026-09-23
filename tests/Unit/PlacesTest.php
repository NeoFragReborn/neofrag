<?php
declare(strict_types=1);

namespace NF\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use NF\Modules\Places\Lib\Place;

/**
 * La carte des lieux.
 *
 * Ce qui mérite une épreuve, ce sont les coordonnées. Une latitude hors bornes ne lève aucune
 * erreur : elle déplace simplement la carte quelque part d'autre, et personne ne comprend pourquoi.
 * Une virgule décimale copiée d'un tableur envoie le lieu à l'équateur. Une valeur minuscule écrite
 * en notation exponentielle casse silencieusement l'URL et le JavaScript.
 *
 * Test PUR : `Place` ne connaît ni la base, ni le réseau, ni le service locator.
 */
final class PlacesTest extends TestCase
{
	/** @return array<string, array{mixed, float|null}> */
	public static function latitudes(): array
	{
		return [
			'Paris'                   => ['48.8566', 48.8566],
			'virgule décimale'        => ['48,8566', 48.8566],
			'espaces autour'          => ['  48.8566  ', 48.8566],
			'négative'                => ['-33.8688', -33.8688],
			'zéro est un vrai lieu'   => ['0', 0.0],
			'pôle Nord'               => ['90', 90.0],
			'pôle Sud'                => ['-90', -90.0],
			'au-delà du pôle'         => ['90.1', NULL],
			'bien au-delà'            => ['1000', NULL],
			'longitude prise pour une latitude' => ['-179', NULL],
			'chaîne vide'             => ['', NULL],
			'non renseigné'           => [NULL, NULL],
			'du texte'                => ['Paris', NULL],
			'un tableau'              => [['48.8566'], NULL],
			'trop de décimales'       => ['48.85661234567', 48.856612],
		];
	}

	#[DataProvider('latitudes')]
	public function test_la_latitude($valeur, ?float $attendu): void
	{
		self::assertSame($attendu, Place::latitude($valeur));
	}

	public function test_la_longitude(): void
	{
		self::assertSame(2.3522, Place::longitude('2.3522'));
		self::assertSame(180.0, Place::longitude('180'));
		self::assertSame(-180.0, Place::longitude('-180'));
		self::assertNull(Place::longitude('180.1'));
		self::assertSame(179.0, Place::longitude('179'), 'une longitude peut dépasser 90, contrairement à une latitude');
	}

	public function test_le_zoom_est_borne(): void
	{
		self::assertSame(13, Place::zoom('13'));
		self::assertSame(Place::ZOOM_MIN, Place::zoom('0'), 'zoom 0 n\'existe pas chez OpenStreetMap');
		self::assertSame(Place::ZOOM_MAX, Place::zoom('99'));
		self::assertSame(Place::ZOOM_DEFAUT, Place::zoom('pas un nombre'));
		self::assertSame(Place::ZOOM_DEFAUT, Place::zoom(NULL));
	}

	// ── Le cadrage ────────────────────────────────────────────────────────────

	public function test_sans_aucun_lieu_la_carte_montre_quand_meme_quelque_chose(): void
	{
		$cadrage = Place::cadrage([]);

		self::assertSame(5, $cadrage['zoom']);
		self::assertIsFloat($cadrage['lat']);
	}

	public function test_un_seul_lieu_est_centre_et_serre(): void
	{
		$cadrage = Place::cadrage([['lat' => 48.8566, 'lon' => 2.3522]]);

		self::assertSame(48.8566, $cadrage['lat']);
		self::assertSame(2.3522, $cadrage['lon']);
		self::assertSame(15, $cadrage['zoom'], 'rien à faire tenir : on peut serrer');
	}

	public function test_plusieurs_lieux_proches_sont_tous_visibles(): void
	{
		// Deux points de Paris, à quelques kilomètres.
		$cadrage = Place::cadrage([
			['lat' => 48.8566, 'lon' => 2.3522],
			['lat' => 48.8738, 'lon' => 2.2950],
		]);

		self::assertEqualsWithDelta(48.8652, $cadrage['lat'], 0.001);
		self::assertEqualsWithDelta(2.3236, $cadrage['lon'], 0.001);
		self::assertGreaterThanOrEqual(12, $cadrage['zoom']);
	}

	public function test_des_lieux_dispersés_font_prendre_du_recul(): void
	{
		// Paris et Sydney : 149 degrés d'étendue en longitude, soit le palier le plus large.
		$cadrage = Place::cadrage([
			['lat' => 48.8566, 'lon' => 2.3522],
			['lat' => -33.8688, 'lon' => 151.2093],
		]);

		self::assertLessThanOrEqual(3, $cadrage['zoom'], "la moitié du globe tient à peine à l'écran");
		self::assertGreaterThanOrEqual(Place::ZOOM_MIN, $cadrage['zoom']);
	}

	/**
	 * Le cadrage doit tenir compte de la DEUX dimensions : une file de lieux nord-sud est aussi
	 * étendue qu'une file est-ouest, et doit reculer autant.
	 */
	public function test_une_file_nord_sud_recule_autant_qu_une_file_est_ouest(): void
	{
		$nord_sud = Place::cadrage([['lat' => 40.0, 'lon' => 2.0], ['lat' => 50.0, 'lon' => 2.0]]);
		$est_ouest = Place::cadrage([['lat' => 45.0, 'lon' => -3.0], ['lat' => 45.0, 'lon' => 7.0]]);

		self::assertSame($nord_sud['zoom'], $est_ouest['zoom']);
	}

	public function test_les_paliers_de_zoom(): void
	{
		self::assertSame(15, Place::zoom_pour(0.0), 'un seul point');
		self::assertSame(14, Place::zoom_pour(0.01));
		self::assertSame(12, Place::zoom_pour(0.1));
		self::assertSame(9, Place::zoom_pour(1.0));
		self::assertSame(6, Place::zoom_pour(10.0));
		self::assertSame(Place::ZOOM_MIN, Place::zoom_pour(300.0), 'plus large que le monde');
	}

	// ── Ce qui part dans une URL ou dans du JavaScript ────────────────────────

	/**
	 * `(string) 0.0000001` rend « 1.0E-7 », que ni une URL ni JavaScript ne relisent comme on
	 * l'espère — et sous une locale francophone, `printf('%f')` mettrait une virgule.
	 */
	public function test_un_nombre_ne_part_jamais_en_notation_exponentielle(): void
	{
		// `(string) 0.000001` rend « 1.0E-6 » ; la sortie attendue est la forme décimale complète.
		self::assertSame('0.000001', Place::nombre(0.000001));
		self::assertStringNotContainsString('E', Place::nombre(0.000001));
		// En deçà de la précision retenue (six décimales, ~11 cm), il n'y a plus de coordonnée : zéro.
		self::assertSame('0', Place::nombre(0.0000001));
		self::assertSame('48.8566', Place::nombre(48.8566));
		self::assertSame('-33.8688', Place::nombre(-33.8688));
		self::assertSame('0', Place::nombre(0.0), 'ni « 0.000000 », ni la chaîne vide');
		self::assertSame('90', Place::nombre(90.0));
		self::assertStringNotContainsString(',', Place::nombre(48.8566), 'jamais de virgule décimale');
	}

	public function test_le_lien_vers_openstreetmap(): void
	{
		$lien = Place::lien_osm(48.8566, 2.3522, 14);

		self::assertSame('https://www.openstreetmap.org/?mlat=48.8566&mlon=2.3522#map=14/48.8566/2.3522', $lien);
		self::assertStringStartsWith('https://', $lien, 'jamais autre chose que https dans un href');
	}

	public function test_le_lien_borne_aussi_son_zoom(): void
	{
		self::assertStringContainsString('#map=19/', Place::lien_osm(0.0, 0.0, 99));
		self::assertStringContainsString('#map=1/', Place::lien_osm(0.0, 0.0, 0));
	}

	// ── Ce qui part dans un style ou dans un href ─────────────────────────────

	/**
	 * La couleur d'une catégorie part dans un attribut `data-`, puis le script la pose sur le
	 * marqueur. Une valeur libre y serait une injection CSS.
	 */
	public function test_la_couleur_n_accepte_que_l_hexadecimal(): void
	{
		self::assertSame('#c1440e', Place::couleur('#C1440E'));
		self::assertSame('#abc', Place::couleur(' #abc '));
		self::assertSame('', Place::couleur('red'), 'un nom de couleur ouvrirait la porte au reste');
		self::assertSame('', Place::couleur('red;background:url(//ailleurs)'));
		self::assertSame('', Place::couleur('#12345'), 'trois ou six chiffres, pas cinq');
		self::assertSame('', Place::couleur('#gggggg'));
		self::assertSame('', Place::couleur(''));
		self::assertSame('', Place::couleur(NULL));
		self::assertSame('', Place::couleur(['#abc']));
	}

	public function test_le_lien_du_lieu_n_accepte_que_http(): void
	{
		self::assertSame('https://exemple.org/', Place::lien_sur('https://exemple.org/'));
		self::assertSame('', Place::lien_sur('javascript:alert(1)'));
		self::assertSame('', Place::lien_sur('geo:48.85,2.35'), "même un schéma plausible reste refusé");
		self::assertSame('', Place::lien_sur(NULL));
	}
}
