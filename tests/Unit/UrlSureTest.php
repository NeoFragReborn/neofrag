<?php
declare(strict_types=1);

namespace NF\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * nf_url_sure() (neofrag/helpers/location.php) : une adresse saisie ne sort dans un `href` que si son
 * schéma est sans danger. Un « javascript: » dans un diaporama, l'annuaire de liens, un signalement ou
 * un flux RSS exécutait du code chez celui qui cliquait (audit de la démonstration, 2026-10-02).
 */
final class UrlSureTest extends TestCase
{
	public static function setUpBeforeClass(): void
	{
		require_once __DIR__.'/../../neofrag/helpers/location.php';
	}

	/** @return array<string, array{string, bool}> */
	public static function adresses(): array
	{
		return [
			'vide'                    => ['', TRUE],
			'page du site'            => ['news', TRUE],
			'chemin absolu'           => ['/fr/forum', TRUE],
			'ancre'                   => ['#commentaires', TRUE],
			'web'                     => ['https://example.org/a?b=c', TRUE],
			'web, majuscules'         => ['HTTP://EXAMPLE.ORG', TRUE],
			'sans schéma'             => ['//cdn.example.org/x.js', TRUE],
			'courriel'                => ['mailto:contact@example.org', TRUE],
			'téléphone'               => ['tel:+33100000000', TRUE],
			'javascript'              => ['javascript:alert(1)', FALSE],
			'javascript, casse'       => ['JaVaScRiPt:alert(1)', FALSE],
			'javascript, espace'      => [' javascript:alert(1)', FALSE],
			'javascript, tabulation'  => ["java\tscript:alert(1)", FALSE],
			'javascript, retour'      => ["java\nscript:alert(1)", FALSE],
			'javascript « valide »'   => ['javascript://%0Aalert(1)', FALSE],
			'data'                    => ['data:text/html;base64,PHNjcmlwdD4=', FALSE],
			'vbscript'                => ['vbscript:msgbox(1)', FALSE],
		];
	}

	#[DataProvider('adresses')]
	public function testLeSchemaDecide(string $adresse, bool $sure): void
	{
		self::assertSame($sure, nf_url_sure($adresse), json_encode($adresse));
	}
}
