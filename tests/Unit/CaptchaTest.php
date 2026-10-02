<?php
declare(strict_types=1);

namespace NF\Tests\Unit;

use AltchaOrg\Altcha\Algorithm\Pbkdf2;
use AltchaOrg\Altcha\Altcha as Bibliotheque;
use AltchaOrg\Altcha\Challenge;
use AltchaOrg\Altcha\Payload;
use AltchaOrg\Altcha\SolveChallengeOptions;
use NF\NeoFrag\Libraries\Captcha;
use NF\NeoFrag\Libraries\Captcha\Altcha;
use NF\NeoFrag\Libraries\Captcha\Hcaptcha;
use NF\NeoFrag\Libraries\Captcha\Recaptcha;
use NF\NeoFrag\Libraries\Captcha\Turnstile;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Le captcha : le choix du fournisseur, ce que chacun ouvre dans la politique de sécurité,
 * leur vérification — par un faux transport pour les tiers, et de bout en bout pour ALTCHA, dont la
 * bibliothèque sait résoudre un défi côté serveur.
 */
final class CaptchaTest extends TestCase
{
	// ── Le fournisseur actif ──────────────────────────────────────────────────────────────────────

	public static function reglages(): array
	{
		return [
			'aucun réglage, aucune clé : ALTCHA'          => ['', '', '', 'altcha'],
			'aucun réglage, clés reCAPTCHA : reCAPTCHA'    => ['', 'site', 'secret', 'recaptcha'],
			'ALTCHA choisi'                               => ['altcha', '', '', 'altcha'],
			'Turnstile avec ses clés'                     => ['turnstile', 'site', 'secret', 'turnstile'],
			'Turnstile sans clé secrète : ALTCHA'         => ['turnstile', 'site', '', 'altcha'],
			'hCaptcha sans aucune clé : ALTCHA'           => ['hcaptcha', '', '', 'altcha'],
			'fournisseur inconnu : ALTCHA'                => ['inconnu', 'site', 'secret', 'altcha'],
			'éteint'                                      => ['aucun', 'site', 'secret', ''],
		];
	}

	#[DataProvider('reglages')]
	public function test_le_fournisseur_actif(string $reglage, string $public, string $prive, string $attendu): void
	{
		self::assertSame($attendu, Captcha::cle_active($reglage, $public, $prive));
	}

	public function test_la_politique_de_securite_n_ouvre_que_le_fournisseur_actif(): void
	{
		self::assertSame([], Captcha::csp('altcha'));
		self::assertSame([], Captcha::csp(''));
		self::assertContains('https://www.google.com', Captcha::csp('recaptcha')['script']);
		self::assertContains('https://challenges.cloudflare.com', Captcha::csp('turnstile')['frame']);
		self::assertArrayNotHasKey('script', Captcha::csp('altcha'));
	}

	// ── Les fournisseurs hébergés chez un tiers ───────────────────────────────────────────────────

	public static function tiers(): array
	{
		return [
			'Turnstile' => [Turnstile::class, 'https://challenges.cloudflare.com/turnstile/v0/siteverify', 'cf-turnstile-response'],
			'hCaptcha'  => [Hcaptcha::class, 'https://api.hcaptcha.com/siteverify', 'h-captcha-response'],
			'reCAPTCHA' => [Recaptcha::class, 'https://www.google.com/recaptcha/api/siteverify', 'g-recaptcha-response'],
		];
	}

	#[DataProvider('tiers')]
	public function test_la_cle_secrete_part_dans_le_corps_jamais_dans_l_adresse(string $classe, string $adresse, string $champ): void
	{
		$appels = [];
		$http   = function(string $url, array $champs) use (&$appels): ?array {
			$appels[] = [$url, $champs];
			return ['success' => TRUE];
		};

		$fournisseur = new $classe('cle-de-site', 'cle-secrete');

		self::assertSame($champ, $fournisseur->champ());
		self::assertTrue($fournisseur->verifier('reponse-du-visiteur', '203.0.113.7', $http));
		self::assertSame($adresse, $appels[0][0]);
		self::assertStringNotContainsString('cle-secrete', $appels[0][0]);
		self::assertSame('cle-secrete', $appels[0][1]['secret']);
		self::assertSame('reponse-du-visiteur', $appels[0][1]['response']);
		self::assertSame('203.0.113.7', $appels[0][1]['remoteip']);
	}

	#[DataProvider('tiers')]
	public function test_un_refus_ou_une_reponse_illisible_echoue(string $classe): void
	{
		$fournisseur = new $classe('cle-de-site', 'cle-secrete');

		self::assertFalse($fournisseur->verifier('r', '', fn() => ['success' => FALSE]));
		self::assertFalse($fournisseur->verifier('r', '', fn() => NULL));
		self::assertFalse($fournisseur->verifier('r', '', fn() => ['success' => 'true']), 'seul le booléen VRAI vaut un succès');
	}

	#[DataProvider('tiers')]
	public function test_sans_reponse_aucun_appel(string $classe): void
	{
		$appele = FALSE;

		self::assertFalse((new $classe('site', 'secret'))->verifier('', '', function() use (&$appele) { $appele = TRUE; return ['success' => TRUE]; }));
		self::assertFalse($appele);
	}

	public function test_hcaptcha_joint_la_cle_de_site(): void
	{
		$champs = [];

		(new Hcaptcha('cle-de-site', 'secret'))->verifier('r', '', function(string $url, array $c) use (&$champs) { $champs = $c; return ['success' => TRUE]; });

		self::assertSame('cle-de-site', $champs['sitekey']);
	}

	// ── ALTCHA, de bout en bout ───────────────────────────────────────────────────────────────────

	/** Résout le défi d'un élément comme le ferait le navigateur, et rend la charge que le widget poste. */
	private static function resoudre(array $attributs, string $secret): string
	{
		$defi     = Challenge::fromArray(json_decode($attributs['challenge'], TRUE));
		$solution = (new Bibliotheque(hmacSignatureSecret: $secret))->solveChallenge(new SolveChallengeOptions(algorithm: new Pbkdf2(), challenge: $defi));

		self::assertNotNull($solution, 'le défi doit être soluble');

		return (new Payload($defi, $solution))->toBase64();
	}

	public function test_altcha_accepte_une_solution_puis_refuse_son_rejeu(): void
	{
		$vues    = [];
		$deja_vu = function(string $empreinte, int $jusqua) use (&$vues): bool {
			if (isset($vues[$empreinte]))
			{
				return TRUE;
			}

			$vues[$empreinte] = $jusqua;

			return FALSE;
		};

		$altcha                = new Altcha('secret-du-site', '/js/altcha/pbkdf2.js?v=1', ['label' => 'Je ne suis pas un robot'], $deja_vu);
		[$balise, $attributs] = $altcha->element('fr');

		self::assertSame('altcha-widget', $balise);
		self::assertSame('altcha', $attributs['name']);
		self::assertSame('/js/altcha/pbkdf2.js?v=1', $attributs['data-worker']);
		self::assertSame('PBKDF2/SHA-256', json_decode($attributs['challenge'], TRUE)['parameters']['algorithm']);
		self::assertSame(['hideLogo' => TRUE, 'hideFooter' => TRUE, 'language' => 'fr'], json_decode($attributs['configuration'], TRUE));
		self::assertSame(['label' => 'Je ne suis pas un robot'], json_decode($attributs['data-textes'], TRUE));

		$charge = self::resoudre($attributs, 'secret-du-site');

		self::assertTrue($altcha->verifier($charge, '', fn() => NULL));
		self::assertFalse($altcha->verifier($charge, '', fn() => NULL), 'une solution ne sert qu\'une fois');
		self::assertCount(1, $vues);
	}

	public function test_altcha_refuse_un_defi_signe_par_un_autre_site(): void
	{
		$charge = self::resoudre((new Altcha('secret-d-un-autre-site'))->element('fr')[1], 'secret-d-un-autre-site');

		self::assertFalse((new Altcha('secret-du-site'))->verifier($charge, '', fn() => NULL));
	}

	public function test_altcha_refuse_un_defi_expire(): void
	{
		$altcha = new Altcha('secret-du-site');
		$defi   = $altcha->defi(time() - 10);
		$charge = self::resoudre(['challenge' => $defi->toJson()], 'secret-du-site');

		self::assertFalse($altcha->verifier($charge, '', fn() => NULL));
	}

	public function test_altcha_refuse_ce_qui_n_est_pas_une_solution(): void
	{
		$altcha = new Altcha('secret-du-site');

		self::assertFalse($altcha->verifier('', '', fn() => NULL));
		self::assertFalse($altcha->verifier('pas du base64 !', '', fn() => NULL));
		self::assertFalse($altcha->verifier(base64_encode('{"challenge":{}}'), '', fn() => NULL));
		self::assertFalse($altcha->verifier(str_repeat('A', 9000), '', fn() => NULL));
	}
}
