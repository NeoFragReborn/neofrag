<?php
declare(strict_types=1);

namespace NF\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use NF\Modules\Webradio\Lib\Schedule;

/**
 * La grille de la webradio.
 *
 * Deux choses y méritent une épreuve. D'abord le créneau qui **enjambe minuit** : « samedi 22:00 →
 * 02:00 » est une émission de nuit ordinaire, et la comparaison naïve la rend toujours absente — le
 * même motif que la saison qui enjambe le Nouvel An, rencontré sur l'effet saisonnier. Ensuite
 * l'**origine** du flux, qui part dans la politique de sécurité du site : mal extraite, soit le flux
 * est bloqué, soit on ouvre la politique plus large qu'il ne faut.
 *
 * Test PUR : `Schedule` ne connaît ni la base, ni le réseau, ni le service locator.
 */
final class WebradioTest extends TestCase
{
	// ── Les heures ────────────────────────────────────────────────────────────

	public function test_le_format_des_heures(): void
	{
		self::assertSame('09:05', Schedule::heure('09:05'));
		self::assertSame('09:05', Schedule::heure('9:05'), 'une heure à un chiffre est normalisée');
		self::assertSame('09:05', Schedule::heure('  09:05  '));
		self::assertSame('00:00', Schedule::heure('0:00'));
		self::assertSame('23:59', Schedule::heure('23:59'));
		self::assertSame('', Schedule::heure('24:00'), 'minuit s\'écrit 00:00');
		self::assertSame('', Schedule::heure('12:60'));
		self::assertSame('', Schedule::heure('midi'));
		self::assertSame('', Schedule::heure(''));
		self::assertSame('', Schedule::heure(NULL));
	}

	public function test_le_jour_est_borne(): void
	{
		self::assertSame(1, Schedule::jour('1'), 'lundi');
		self::assertSame(7, Schedule::jour('7'), 'dimanche');
		self::assertSame(1, Schedule::jour('0'), 'hors bornes : on retombe sur lundi');
		self::assertSame(1, Schedule::jour('8'));
		self::assertSame(1, Schedule::jour('mardi'));
	}

	// ── Ce qui passe à l'antenne ──────────────────────────────────────────────

	/** @return array<string, array{int, string, string, int, string, bool}> */
	public static function creneaux(): array
	{
		return [
			// Créneau ordinaire, du mardi 14h à 16h.
			'en plein milieu'           => [2, '14:00', '16:00', 2, '15:00', TRUE],
			'à la minute du début'      => [2, '14:00', '16:00', 2, '14:00', TRUE],
			'à la minute de la fin'     => [2, '14:00', '16:00', 2, '16:00', FALSE],
			'une minute avant'          => [2, '14:00', '16:00', 2, '13:59', FALSE],
			'le bon horaire, le mauvais jour' => [2, '14:00', '16:00', 3, '15:00', FALSE],

			// Créneau de nuit, samedi 22h → 02h : le cas qui casse la comparaison naïve.
			'samedi soir, avant minuit' => [6, '22:00', '02:00', 6, '23:30', TRUE],
			'dimanche, après minuit'    => [6, '22:00', '02:00', 7, '01:00', TRUE],
			'dimanche, après la fin'    => [6, '22:00', '02:00', 7, '02:30', FALSE],
			'samedi, avant le début'    => [6, '22:00', '02:00', 6, '21:00', FALSE],
			'lundi, sans rapport'       => [6, '22:00', '02:00', 1, '23:30', FALSE],

			// Le passage du dimanche au lundi, que le modulo 7 doit franchir.
			'dimanche soir'             => [7, '23:00', '01:00', 7, '23:30', TRUE],
			'lundi au petit matin'      => [7, '23:00', '01:00', 1, '00:30', TRUE],
			'lundi trop tard'           => [7, '23:00', '01:00', 1, '01:30', FALSE],

			// Vingt-quatre heures d'antenne.
			'toute la journée, le matin' => [3, '00:00', '00:00', 3, '08:00', TRUE],
			'toute la journée, la nuit'  => [3, '00:00', '00:00', 3, '23:59', TRUE],
			'toute la journée, un autre jour' => [3, '00:00', '00:00', 4, '08:00', FALSE],

			// Horaires illisibles : jamais à l'antenne, plutôt que toujours.
			'horaire de début illisible' => [2, 'midi', '16:00', 2, '15:00', FALSE],
			'horaire de fin illisible'   => [2, '14:00', '', 2, '15:00', FALSE],
		];
	}

	#[DataProvider('creneaux')]
	public function test_le_creneau_est_il_a_l_antenne(int $jour_creneau, string $debut, string $fin, int $jour, string $heure, bool $attendu): void
	{
		self::assertSame($attendu, Schedule::en_direct($jour_creneau, $debut, $fin, $jour, $heure));
	}

	public function test_le_lendemain_franchit_la_semaine(): void
	{
		self::assertSame(2, Schedule::lendemain(1));
		self::assertSame(1, Schedule::lendemain(7), 'après dimanche vient lundi');
	}

	public function test_l_emission_a_l_antenne_parmi_plusieurs(): void
	{
		$grille = [
			['title' => 'Matinale', 'day' => 2, 'start_time' => '07:00', 'end_time' => '09:00'],
			['title' => 'Midi',     'day' => 2, 'start_time' => '12:00', 'end_time' => '14:00'],
			['title' => 'Nuit',     'day' => 2, 'start_time' => '23:00', 'end_time' => '01:00'],
		];

		self::assertSame('Matinale', Schedule::a_l_antenne($grille, 2, '08:00')['title']);
		self::assertSame('Midi', Schedule::a_l_antenne($grille, 2, '13:59')['title']);
		self::assertSame('Nuit', Schedule::a_l_antenne($grille, 3, '00:30')['title'], 'la nuit du mardi déborde sur le mercredi');
		self::assertNull(Schedule::a_l_antenne($grille, 2, '10:00'), 'entre deux émissions, personne');
		self::assertNull(Schedule::a_l_antenne([], 2, '10:00'), 'une grille vide');
	}

	// ── Ce qui part dans la politique de sécurité ─────────────────────────────

	/** @return array<string, array{mixed, string}> */
	public static function origines(): array
	{
		return [
			'https simple'        => ['https://radio.example/stream', 'https://radio.example'],
			'avec un port'        => ['http://radio.example:8000/live.mp3', 'http://radio.example:8000'],
			'majuscules'          => ['HTTPS://Radio.Example/Stream', 'https://radio.example'],
			'avec des paramètres' => ['https://radio.example/s?x=1&y=2', 'https://radio.example'],
			'sous-domaine'        => ['https://a.b.radio.example/s', 'https://a.b.radio.example'],
			'pas du http'         => ['ftp://radio.example/s', ''],
			'du texte'            => ['radio.example', ''],
			'chaîne vide'         => ['', ''],
			'non renseigné'       => [NULL, ''],
			'un tableau'          => [['https://radio.example'], ''],
		];
	}

	#[DataProvider('origines')]
	public function test_l_origine_du_flux($url, string $attendu): void
	{
		self::assertSame($attendu, Schedule::origine($url));
	}

	/**
	 * L'origine part dans un en-tête où l'ESPACE sépare les sources : un hôte qui en contiendrait
	 * permettrait d'y glisser une seconde source, voire une autre directive.
	 */
	public function test_une_origine_ne_peut_pas_injecter_dans_l_en_tete(): void
	{
		self::assertSame('', Schedule::origine("https://radio.example 'unsafe-inline'"));
		self::assertSame('', Schedule::origine("https://radio.example;script-src *"));
		self::assertSame('', Schedule::origine("https://radio.example\nX-Autre: 1"));
		self::assertStringNotContainsString(' ', Schedule::origine('https://radio.example:8000/s'));
	}

	public function test_l_adresse_du_flux(): void
	{
		self::assertSame('https://radio.example/stream', Schedule::flux('https://radio.example/stream'));
		self::assertSame('http://radio.example:8000/live', Schedule::flux('http://radio.example:8000/live'));
		self::assertSame('', Schedule::flux('javascript:alert(1)'));
		self::assertSame('', Schedule::flux('file:///etc/passwd'));
		self::assertSame('', Schedule::flux(NULL));
	}

	/** Un flux en `http` sur un site en `https` est refusé par le navigateur : l'écran doit le dire. */
	public function test_le_contenu_mixte_est_reconnu(): void
	{
		self::assertTrue(Schedule::contenu_mixte('http://radio.example/live'));
		self::assertFalse(Schedule::contenu_mixte('https://radio.example/live'));
		self::assertFalse(Schedule::contenu_mixte(''));
	}
}
