<?php
declare(strict_types=1);

namespace NF\Tests\Headless;

use NF\NeoFrag\Libraries\Totp_Service;
use PragmaRX\Google2FA\Google2FA;

/**
 * La double authentification (neofrag/libraries/totp_service.php) : secret, code à six chiffres
 * vérifié dans une fenêtre de tolérance, codes de récupération hachés et consommés une seule fois.
 * Elle n'avait aucun test direct au moment de passer en `strict_types` ; la vérification d'un code
 * réel est calculée avec la même bibliothèque que le service, jamais recopiée.
 */
final class TotpServiceTest extends HeadlessTestCase
{
	private function totp(): Totp_Service
	{
		return new Totp_Service(\NeoFrag());
	}

	public function test_le_secret_est_du_base32_de_trente_deux_caracteres(): void
	{
		$secret = $this->totp()->generate_secret();

		$this->assertSame(32, strlen($secret));
		$this->assertMatchesRegularExpression('/^[A-Z2-7]+$/', $secret);
	}

	public function test_un_code_courant_est_accepte_et_un_code_faux_refuse(): void
	{
		$totp   = $this->totp();
		$secret = $totp->generate_secret();
		$code   = (new Google2FA())->getCurrentOtp($secret);

		$this->assertTrue($totp->verify($secret, $code));

		if ($code[0] !== '0')
		{
			// Un entier perd ses zéros de tête : on ne joue ce cas que quand la conversion est sans perte.
			$this->assertTrue($totp->verify($secret, (int) $code), 'Un code passé en entier (formulaire mal typé) est accepté sous strict_types.');
		}

		if ($code !== '000000')
		{
			$this->assertFalse($totp->verify($secret, '000000'), 'Un code faux est refusé.');
		}
		$this->assertFalse($totp->verify($secret, 'abcdef'), 'Six caractères non numériques sont refusés avant tout calcul.');
		$this->assertFalse($totp->verify($secret, '12345'), 'Cinq chiffres sont refusés.');
		$this->assertFalse($totp->verify('', $code), 'Sans secret, rien ne passe.');
	}

	public function test_l_adresse_de_provisionnement_porte_le_compte_et_le_secret(): void
	{
		$uri = $this->totp()->provisioning_uri('NeoFrag', 'alex', 'JBSWY3DPEHPK3PXP');

		$this->assertStringStartsWith('otpauth://totp/', $uri);
		$this->assertStringContainsString('secret=JBSWY3DPEHPK3PXP', $uri);
		$this->assertStringContainsString('alex', $uri);
		$this->assertStringStartsWith('data:image/svg+xml;base64,', $this->totp()->qr_code_svg_data_uri('NeoFrag', 'alex', 'JBSWY3DPEHPK3PXP'));
	}

	public function test_les_codes_de_recuperation_sont_haches_et_consommes_une_seule_fois(): void
	{
		$totp = $this->totp();
		$user = $this->createUser('totp');

		$codes = $totp->generate_recovery_codes(4);
		$this->assertCount(4, $codes['plain']);
		$this->assertCount(4, $codes['hashed']);

		foreach ($codes['plain'] as $i => $clair)
		{
			$this->assertMatchesRegularExpression('/^[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{4}$/', $clair);
			$this->assertTrue(password_verify($clair, $codes['hashed'][$i]), 'Le haché correspond au code en clair.');
			$this->assertNotSame($clair, $codes['hashed'][$i], 'Le code stocké n\'est jamais le code en clair.');
		}

		$totp->store_recovery_codes($user, $codes['hashed']);
		$this->assertSame(4, $totp->count_unused_recovery_codes($user));

		$this->assertTrue($totp->verify_and_consume_recovery_code($user, $codes['plain'][1]), 'Un code valide est accepté…');
		$this->assertSame(3, $totp->count_unused_recovery_codes($user), '… et marqué consommé.');
		$this->assertFalse($totp->verify_and_consume_recovery_code($user, $codes['plain'][1]), 'Le même code ne sert pas deux fois.');
		$this->assertFalse($totp->verify_and_consume_recovery_code($user, 'AAAA-BBBB-CCCC-DDDD'), 'Un code inconnu est refusé.');

		// Remplacer les codes efface les anciens.
		$totp->store_recovery_codes($user, $totp->generate_recovery_codes(2)['hashed']);
		$this->assertSame(2, $totp->count_unused_recovery_codes($user));
	}
}
