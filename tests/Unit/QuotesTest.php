<?php
declare(strict_types=1);

namespace NF\Tests\Unit;

use PHPUnit\Framework\TestCase;
use NF\Modules\Quotes\Lib\Quote;

/**
 * Le module Citations.
 *
 * Deux décisions y méritent une épreuve : l'adresse de la source, qui part dans un `href` de la page
 * publique, et l'aperçu affiché dans l'administration. Le reste du module est de l'affichage échappé,
 * couvert par l'épreuve sur les pages réellement servies.
 *
 * Test PUR : `Quote` ne connaît ni la base ni le service locator.
 */
final class QuotesTest extends TestCase
{
	public function test_le_lien_de_source_n_accepte_que_http(): void
	{
		self::assertSame('https://exemple.org/a', Quote::lien_sur('https://exemple.org/a'));
		self::assertSame('http://exemple.org/a', Quote::lien_sur('  http://exemple.org/a  '));

		// Ce qui finirait dans un `href` de la page publique si on ne filtrait pas.
		self::assertSame('', Quote::lien_sur('javascript:alert(1)'));
		self::assertSame('', Quote::lien_sur('data:text/html,<script>alert(1)</script>'));
		self::assertSame('', Quote::lien_sur('file:///etc/passwd'));
		self::assertSame('', Quote::lien_sur('exemple.org'), 'sans schéma, on ne devine pas');
		self::assertSame('', Quote::lien_sur(''));
		self::assertSame('', Quote::lien_sur(NULL));
		self::assertSame('', Quote::lien_sur(['https://exemple.org']));
	}

	public function test_l_apercu_coupe_a_la_fin_d_un_mot(): void
	{
		$court = 'Une citation courte.';
		self::assertSame($court, Quote::apercu($court));

		$long   = str_repeat('mot ', 200);
		$apercu = Quote::apercu($long);

		self::assertLessThanOrEqual(Quote::APERCU + 1, mb_strlen($apercu));
		self::assertStringEndsWith('…', $apercu);
		self::assertStringNotContainsString('mo…', $apercu, 'la coupe tombe entre deux mots');
	}

	public function test_l_apercu_ramene_les_blancs_a_une_espace(): void
	{
		self::assertSame('Une citation sur deux lignes.', Quote::apercu("Une citation\n\tsur   deux lignes."));
		self::assertSame('', Quote::apercu('   '));
		self::assertSame('', Quote::apercu(NULL));
	}
}
