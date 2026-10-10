<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * nf_envoi_d_un_autre_site() (neofrag/helpers/location.php) : un envoi parti d'un autre site est refusé avant toute route
 * (audit de sécurité du 2026-10-09). Le navigateur le dit par `Sec-Fetch-Site`, à défaut par `Origin` ; un client sans
 * navigateur (le bot, un service de paiement) n'envoie ni l'un ni l'autre et passe.
 */
final class EnvoisDUnAutreSiteTest extends TestCase
{
	protected function setUp(): void
	{
		require_once __DIR__.'/../../neofrag/helpers/location.php';
	}

	/** @param array<string, string> $entetes */
	private function envoi(array $entetes, string $methode = 'POST', string $hote = 'neofrag-reborn.xyz'): array
	{
		return ['REQUEST_METHOD' => $methode, 'HTTP_HOST' => $hote] + $entetes;
	}

	public function test_une_lecture_n_est_jamais_refusee(): void
	{
		$this->assertFalse(nf_envoi_d_un_autre_site($this->envoi(['HTTP_SEC_FETCH_SITE' => 'cross-site'], 'GET')));
		$this->assertFalse(nf_envoi_d_un_autre_site($this->envoi(['HTTP_SEC_FETCH_SITE' => 'cross-site'], 'HEAD')));
	}

	public function test_le_navigateur_dit_d_ou_vient_l_envoi(): void
	{
		$this->assertFalse(nf_envoi_d_un_autre_site($this->envoi(['HTTP_SEC_FETCH_SITE' => 'same-origin'])));
		$this->assertTrue(nf_envoi_d_un_autre_site($this->envoi(['HTTP_SEC_FETCH_SITE' => 'cross-site', 'HTTP_ORIGIN' => 'https://piege.example'])));
		$this->assertTrue(nf_envoi_d_un_autre_site($this->envoi(['HTTP_SEC_FETCH_SITE' => 'cross-site'], 'DELETE')));
	}

	public function test_un_sous_domaine_voisin_est_un_autre_site_sauf_www(): void
	{
		$this->assertTrue(nf_envoi_d_un_autre_site($this->envoi(['HTTP_SEC_FETCH_SITE' => 'same-site', 'HTTP_ORIGIN' => 'https://demo.neofrag-reborn.xyz'])));
		$this->assertFalse(nf_envoi_d_un_autre_site($this->envoi(['HTTP_SEC_FETCH_SITE' => 'same-site', 'HTTP_ORIGIN' => 'https://www.neofrag-reborn.xyz'])));
	}

	public function test_sans_sec_fetch_site_l_origine_decide(): void
	{
		$this->assertFalse(nf_envoi_d_un_autre_site($this->envoi(['HTTP_ORIGIN' => 'https://neofrag-reborn.xyz'])));
		$this->assertFalse(nf_envoi_d_un_autre_site($this->envoi(['HTTP_ORIGIN' => 'http://127.0.0.1:8250'], 'POST', '127.0.0.1:8250')), 'le port compte avec l’hôte');
		$this->assertTrue(nf_envoi_d_un_autre_site($this->envoi(['HTTP_ORIGIN' => 'http://127.0.0.1:9999'], 'POST', '127.0.0.1:8250')));
		$this->assertTrue(nf_envoi_d_un_autre_site($this->envoi(['HTTP_ORIGIN' => 'https://piege.example'])));
		$this->assertTrue(nf_envoi_d_un_autre_site($this->envoi(['HTTP_ORIGIN' => 'null'])), 'une page sans origine (cadre isolé, fichier local)');
	}

	public function test_l_origine_du_site_est_acceptee_telle_quelle(): void
	{
		$this->assertFalse(nf_envoi_d_un_autre_site($this->envoi(['HTTP_ORIGIN' => 'https://neofrag-reborn.xyz'], 'POST', 'interne:8080'), 'https://neofrag-reborn.xyz'));
		$this->assertTrue(nf_envoi_d_un_autre_site($this->envoi(['HTTP_ORIGIN' => 'https://piege.example'], 'POST', 'interne:8080'), 'https://neofrag-reborn.xyz'));
	}

	public function test_un_client_sans_navigateur_passe(): void
	{
		$this->assertFalse(nf_envoi_d_un_autre_site($this->envoi([])), 'le bot, un service de paiement, un outil');
	}
}
