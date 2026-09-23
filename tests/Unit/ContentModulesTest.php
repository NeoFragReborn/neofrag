<?php
declare(strict_types=1);

namespace NF\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use NF\Modules\Glossary\Lib\Term;
use NF\Modules\Recipes\Lib\Recipe;

/**
 * Les deux modules de contenu bâtis sur le patron des citations : recettes, dictionnaire.
 *
 * Ce qui y mérite une épreuve n'est pas l'affichage — il est échappé de bout en bout et couvert par
 * l'épreuve sur les pages servies — mais les deux décisions qui, elles, peuvent être subtilement
 * fausses : le découpage des ingrédients et des étapes saisis une par ligne, et le rangement d'un
 * terme sous sa lettre, accents et ligatures compris.
 *
 * Tests PURS : `Recipe` et `Term` ne connaissent ni la base ni le service locator.
 */
final class ContentModulesTest extends TestCase
{
	// ── Recettes ──────────────────────────────────────────────────────────────

	public function test_les_lignes_d_ingredients(): void
	{
		$saisie = "200 g de farine\n  3 œufs  \n\n\n1 pincée de sel\n";

		self::assertSame(['200 g de farine', '3 œufs', '1 pincée de sel'], Recipe::lignes($saisie));
	}

	public function test_les_fins_de_ligne_de_windows_et_des_vieux_mac(): void
	{
		self::assertSame(['un', 'deux'], Recipe::lignes("un\r\ndeux"), 'fins de ligne Windows');
		self::assertSame(['un', 'deux'], Recipe::lignes("un\rdeux"), 'fins de ligne des vieux Mac');
		self::assertSame([], Recipe::lignes("   \n\n  "), 'que du blanc : aucune ligne');
		self::assertSame([], Recipe::lignes(NULL));
	}

	public function test_les_entiers_sont_bornes(): void
	{
		self::assertSame(4, Recipe::entier('4', Recipe::PARTS_MAX));
		self::assertSame(0, Recipe::entier('', Recipe::PARTS_MAX), 'vide = non renseigné');
		self::assertSame(0, Recipe::entier('quatre', Recipe::PARTS_MAX));
		self::assertSame(0, Recipe::entier('-3', Recipe::PARTS_MAX), 'jamais de durée négative');
		self::assertSame(Recipe::PARTS_MAX, Recipe::entier('999999', Recipe::PARTS_MAX), 'une faute de frappe est ramenée au maximum');
		self::assertSame(Recipe::MINUTES_MAX, Recipe::entier('999999', Recipe::MINUTES_MAX));
	}

	/** @return array<string, array{int, string, string}> */
	public static function durees(): array
	{
		return [
			'non renseignée' => [0, '', ''],
			'négative'       => [-5, '', ''],
			'moins d une heure' => [45, '45 min', 'PT45M'],
			'une heure pile' => [60, '1 h', 'PT1H'],
			'une heure et demie' => [90, '1 h 30', 'PT1H30M'],
			'heure et minutes courtes' => [65, '1 h 05', 'PT1H5M'],
			'deux heures pile' => [120, '2 h', 'PT2H'],
		];
	}

	#[DataProvider('durees')]
	public function test_les_durees(int $minutes, string $lisible, string $iso): void
	{
		self::assertSame($lisible, Recipe::duree($minutes));
		self::assertSame($iso, Recipe::duree_iso($minutes), 'ce que lisent les moteurs de recherche');
	}

	// ── Dictionnaire ──────────────────────────────────────────────────────────

	/** @return array<string, array{string, string}> */
	public static function initiales(): array
	{
		return [
			'mot ordinaire'        => ['Ping', 'P'],
			'minuscule'            => ['ping', 'P'],
			'accent aigu'          => ['Éclaireur', 'E'],
			'accent grave'         => ['Èlite', 'E'],
			'cédille'              => ['Ça', 'C'],
			'ligature AE'          => ['Æther', 'A'],
			'ligature OE'          => ['Œuvre', 'O'],
			'eszett allemand'      => ['ßeta', 'S'],
			'tréma'                => ['Über', 'U'],
			'o barré'              => ['Ørsted', 'O'],
			'chiffre'              => ['1v1', '#'],
			'signe'                => ['@home', '#'],
			'espace en tête'       => ['   Ping', 'P'],
			'vide'                 => ['', '#'],
			'que des espaces'      => ['   ', '#'],
			'emoji'                => ['🎮 manette', '#'],
		];
	}

	#[DataProvider('initiales')]
	public function test_sous_quelle_lettre_ranger_un_terme(string $terme, string $attendu): void
	{
		self::assertSame($attendu, Term::initiale($terme));
	}

	public function test_l_index_alphabetique(): void
	{
		$alphabet = Term::alphabet();

		self::assertCount(27, $alphabet, '26 lettres et le signe des autres');
		self::assertSame('A', $alphabet[0]);
		self::assertSame('Z', $alphabet[25]);
		self::assertSame(Term::AUTRES, $alphabet[26]);
	}

	public function test_les_ancres_de_l_index(): void
	{
		self::assertSame('lettre-a', Term::ancre('A'));
		self::assertSame('lettre-autres', Term::ancre(Term::AUTRES), "« # » n'est pas un identifiant valide dans une adresse");
	}

	public function test_les_synonymes(): void
	{
		self::assertSame(['ping', 'latence'], Term::synonymes(' ping , latence '));
		self::assertSame(['ping'], Term::synonymes('ping, ping ,  ping'), 'les doublons ne servent à rien');
		self::assertSame(['ping latence'], Term::synonymes("ping\n  latence"), 'seule la virgule sépare');
		self::assertSame([], Term::synonymes(' , , '));
		self::assertSame([], Term::synonymes(NULL));
	}
}
