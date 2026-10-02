<?php
declare(strict_types=1);

namespace NF\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Dans chaque langue, une date que le sélecteur affiche se relit : le format de `date()` (celui de
 * flatpickr et du pré-remplissage) et la lecture de `date2sql()` / `datetime2sql()` vont ensemble.
 *
 * L'allemand affichait « 02.10.2026 » et ne relisait que « 02/10/2026 » : chaque date saisie dans un
 * formulaire allemand partait sans conversion, et le graphique des statistiques ne se chargeait pas.
 * L'anglais affichait « m/d/Y g:i A » et relisait « d/m/Y H:i » : le 2 octobre s'enregistrait le
 * 10 février, et flatpickr écrivait « g:05 A » (2026-10-02, en parcourant l'administration).
 */
final class FormatsDateLanguesTest extends TestCase
{
	/** @return array<string, array{string}> */
	public static function langues(): array
	{
		return array_combine(['fr', 'en', 'de', 'es', 'it', 'pt'], array_map(static fn ($l) => [$l], ['fr', 'en', 'de', 'es', 'it', 'pt']));
	}

	/**
	 * L'addon de langue, sans le démarrer : date(), date2sql() et datetime2sql() ne lisent aucun état.
	 */
	private static function langue(string $code): object
	{
		require_once __DIR__.'/../../addons/language_'.$code.'/language_'.$code.'.php';

		$classe = 'Language_'.ucfirst($code);

		return (new \ReflectionClass('NF\\Addons\\'.$classe.'\\'.$classe))->newInstanceWithoutConstructor();
	}

	#[DataProvider('langues')]
	public function testUneDateAfficheeSeRelit(string $code): void
	{
		$langue  = self::langue($code);
		$formats = $langue->date();
		$moment  = new \DateTimeImmutable('2026-10-02 14:05:00');

		$date = $moment->format($formats['short_date']);
		$langue->date2sql($date);
		self::assertSame('2026-10-02', $date, "$code : « {$formats['short_date']} » ne se relit pas");

		$date_heure = $moment->format($formats['short_date_time']);
		$langue->datetime2sql($date_heure);
		self::assertSame('2026-10-02 14:05:00', $date_heure, "$code : « {$formats['short_date_time']} » ne se relit pas");
	}
}
