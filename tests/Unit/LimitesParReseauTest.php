<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use NF\NeoFrag\Libraries\Rate_Limit;

/**
 * Les limites d'essais comptent une adresse IPv6 pour son réseau /64 (audit de sécurité du 2026-10-09) : un abonné IPv6
 * dispose de tout un /64, et chaque adresse tirée dans ce réseau lui rendait tous ses essais — connexion, mot de passe
 * oublié, inscription. Rate_Limit::bloc_ip() fait la clé.
 */
final class LimitesParReseauTest extends TestCase
{
	public function test_une_adresse_ipv4_compte_entiere(): void
	{
		$this->assertSame('203.0.113.9', Rate_Limit::bloc_ip('203.0.113.9'));
	}

	public function test_une_adresse_ipv6_compte_pour_son_reseau(): void
	{
		$this->assertSame('2a01:e0a:1f:2c40::/64', Rate_Limit::bloc_ip('2a01:e0a:1f:2c40:aaaa:bbbb:cccc:dddd'));
		$this->assertSame(Rate_Limit::bloc_ip('2a01:e0a:1f:2c40::1'), Rate_Limit::bloc_ip('2a01:0e0a:001f:2c40:ffff:ffff:ffff:ffff'), 'deux adresses du même réseau, écrites autrement');
		$this->assertNotSame(Rate_Limit::bloc_ip('2a01:e0a:1f:2c40::1'), Rate_Limit::bloc_ip('2a01:e0a:1f:2c41::1'), 'le réseau voisin compte à part');
	}

	public function test_une_adresse_ipv4_ecrite_en_ipv6_compte_comme_l_ipv4(): void
	{
		$this->assertSame('198.51.100.7', Rate_Limit::bloc_ip('::ffff:198.51.100.7'));
	}

	public function test_ce_qui_n_est_pas_une_adresse_reste_tel_quel(): void
	{
		$this->assertSame('0.0.0.0', Rate_Limit::bloc_ip('0.0.0.0'));
		$this->assertSame('inconnue', Rate_Limit::bloc_ip('inconnue'));
	}
}
