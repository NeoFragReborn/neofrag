<?php
declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * nf_resolve_public_ip() (neofrag/helpers/remote.php) : ce que le serveur accepte d'aller chercher lui-même — relais
 * d'images, flux RSS, webhooks. Depuis l'audit du 2026-10-09, « public » au sens strict : l'espace partagé des
 * opérateurs, les plages de test et de mesure sont refusés comme les plages privées et réservées.
 */
final class AdressesPubliquesTest extends TestCase
{
	protected function setUp(): void
	{
		require_once __DIR__.'/../../neofrag/helpers/remote.php';
	}

	/** @return array<string, array{string}> */
	public static function refusees(): array
	{
		return array_combine($l = ['127.0.0.1', '10.0.0.1', '172.16.5.4', '192.168.1.1', '169.254.169.254', '0.0.0.0', '100.64.1.2',
			'192.0.2.5', '198.18.0.1', '198.51.100.7', '203.0.113.9', '::1', 'fd00::1', 'fe80::1', '2001:db8::1', '::ffff:10.0.0.1'],
			array_map(static fn (string $ip): array => [$ip], $l));
	}

	#[DataProvider('refusees')]
	public function test_une_adresse_interne_ou_de_test_est_refusee(string $ip): void
	{
		$this->assertNull(nf_resolve_public_ip($ip));
	}

	public function test_une_adresse_publique_passe(): void
	{
		$this->assertSame('8.8.8.8', nf_resolve_public_ip('8.8.8.8'));
		$this->assertSame('2606:4700::1111', nf_resolve_public_ip('2606:4700::1111'));
	}

	public function test_un_autre_port_que_ceux_du_web_est_refuse(): void
	{
		$this->assertNull(nf_fetch_public_url('http://8.8.8.8:25/'), 'pas de requête, refusée avant');
		$this->assertNull(nf_fetch_public_url('https://8.8.8.8:3306/'));
	}
}
