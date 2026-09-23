<?php
declare(strict_types=1);

namespace NF\Tests\Headless;

use NF\NeoFrag\Libraries\Rate_Limit;

/**
 * La limitation de débit (neofrag/libraries/rate_limit.php) garde la connexion, le formulaire de
 * contact, les commentaires : trois échecs et la clé est bloquée. Elle vit dans la table
 * `nf_rate_limit` pour tenir sans Redis sur un hébergement mutualisé. Testée ici contre la vraie
 * base, dans la transaction annulée du socle — elle n'avait aucun test au moment de passer en
 * `strict_types`.
 */
final class RateLimitTest extends HeadlessTestCase
{
	private function limite(): Rate_Limit
	{
		return new Rate_Limit(\NeoFrag());
	}

	private function cle(): string
	{
		return 'test:'.bin2hex(random_bytes(6));
	}

	public function test_une_cle_inconnue_est_autorisee(): void
	{
		$this->assertSame(['allowed' => TRUE, 'retry_after' => 0], $this->limite()->check($this->cle()));
	}

	public function test_le_seuil_bloque_la_cle_et_annonce_le_delai(): void
	{
		$cle = $this->cle();
		$rl  = $this->limite();

		$this->assertSame(['attempts' => 1, 'locked' => FALSE, 'retry_after' => 0], $rl->hit($cle, 3, 60, 900));
		$this->assertSame(2, $rl->hit($cle, 3, 60, 900)['attempts']);

		$troisieme = $rl->hit($cle, 3, 60, 900);
		$this->assertTrue($troisieme['locked'], 'La troisième tentative sur un seuil de 3 bloque.');
		$this->assertSame(900, $troisieme['retry_after']);

		$verif = $rl->check($cle);
		$this->assertFalse($verif['allowed']);
		$this->assertGreaterThan(850, $verif['retry_after']);
		$this->assertLessThanOrEqual(900, $verif['retry_after']);

		// Une tentative de plus pendant le blocage ne recompte pas : elle rappelle le blocage.
		$encore = $rl->hit($cle, 3, 60, 900);
		$this->assertTrue($encore['locked']);
		$this->assertSame(3, $encore['attempts']);
	}

	public function test_reset_libere_la_cle(): void
	{
		$cle = $this->cle();
		$rl  = $this->limite();

		$rl->hit($cle, 1, 60, 900);
		$this->assertFalse($rl->check($cle)['allowed']);

		$rl->reset($cle);
		$this->assertTrue($rl->check($cle)['allowed']);
	}

	/** Les réglages arrivent parfois en chaîne (valeur de configuration) : strict_types ne doit rien casser. */
	public function test_des_seuils_en_chaine_sont_acceptes(): void
	{
		$cle = $this->cle();
		$rl  = $this->limite();

		$rl->hit($cle, '2', '60', '300');
		$second = $rl->hit($cle, '2', '60', '300');

		$this->assertTrue($second['locked']);
		$this->assertSame(300, $second['retry_after']);
	}

	public function test_l_ip_du_client_ignore_les_en_tetes_de_proxy_par_defaut(): void
	{
		$sauvegarde = $_SERVER;

		$_SERVER['REMOTE_ADDR']          = '203.0.113.9';
		$_SERVER['HTTP_X_FORWARDED_FOR'] = '198.51.100.1, 203.0.113.9';
		$_SERVER['HTTP_CF_CONNECTING_IP'] = '198.51.100.2';

		try
		{
			if (defined('NEOFRAG_TRUSTED_PROXY') && NEOFRAG_TRUSTED_PROXY)
			{
				$this->markTestSkipped('NEOFRAG_TRUSTED_PROXY est actif sur cette installation : le cas par défaut ne peut pas être joué.');
			}

			$this->assertSame('203.0.113.9', Rate_Limit::client_ip(),
				"Sans proxy de confiance déclaré, seul REMOTE_ADDR fait foi : les en-têtes sont forgeables par le client.");
		}
		finally
		{
			$_SERVER = $sauvegarde;
		}
	}
}
