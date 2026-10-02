<?php
declare(strict_types=1);

namespace NF\NeoFrag\Libraries\Captcha;

use AltchaOrg\Altcha\Algorithm\Pbkdf2;
use AltchaOrg\Altcha\Altcha as Bibliotheque;
use AltchaOrg\Altcha\Challenge;
use AltchaOrg\Altcha\CreateChallengeOptions;
use AltchaOrg\Altcha\Payload;
use AltchaOrg\Altcha\VerifySolutionOptions;

/**
 * ALTCHA : le fournisseur par défaut (2026-10-02). Libre (MIT), hébergé par le site lui-même, sans
 * compte, sans cookie ni pistage, sans service tiers : il protège un site dès son installation.
 *
 * Le navigateur prouve un petit calcul (PBKDF2/SHA-256, environ une seconde) que le serveur vérifie en une
 * à deux millisecondes. Un robot qui envoie en masse paie ce calcul à chaque envoi ; un visiteur ne voit
 * qu'une case cochée d'elle-même. Une preuve de calcul ne distingue pas un humain d'une machine : elle rend
 * l'abus coûteux. Contre un attaquant patient, Turnstile ou hCaptcha jugent davantage.
 *
 * Le widget est le build strict d'`altcha` 3.2.4 (js/altcha/altcha.min.js) et son calcul le worker
 * js/altcha/pbkdf2.js — le build par défaut crée ses workers en `blob:`, que notre CSP refuse. La
 * bibliothèque PHP est `altcha-org/altcha` 2.x, du même protocole. Le défi est écrit dans la page :
 * aucune adresse à servir.
 */
final class Altcha implements Fournisseur
{
	/** Le coût d'un essai (itérations PBKDF2) et la plage du nombre à trouver : environ une seconde. */
	public const COUT = 2000;
	public const COMPTEUR_MIN = 1000;
	public const COMPTEUR_MAX = 4000;

	/** La durée de vie d'un défi : le temps de remplir un formulaire long. */
	public const DUREE = 1200;

	/**
	 * @param string        $secret  la clé qui signe les défis (dérivée de la clé du site, Crypt::derive)
	 * @param string        $worker  l'adresse du fichier de calcul (js/altcha/pbkdf2.js)
	 * @param array         $textes  les textes du widget, traduits par le site
	 * @param \Closure|null $deja_vu fn(string $empreinte, int $jusqua): bool — VRAI si cette solution a déjà
	 *                               été présentée ; sinon la retient jusqu'à l'expiration de son défi. Sans
	 *                               lui, une solution resservie passerait jusqu'à expiration.
	 */
	public function __construct(
		private readonly string $secret,
		private readonly string $worker = '',
		private readonly array $textes = [],
		private readonly ?\Closure $deja_vu = NULL,
	) {
	}

	public static function cle(): string
	{
		return 'altcha';
	}

	public static function nom(): string
	{
		return 'ALTCHA';
	}

	public static function cles_requises(): bool
	{
		return FALSE;
	}

	public static function console(): string
	{
		return 'https://altcha.org/';
	}

	public static function csp(): array
	{
		// Tout vient du site : le widget, son calcul (un worker de même origine, que script-src 'self'
		// couvre) et ses styles.
		return [];
	}

	public function champ(): string
	{
		return 'altcha';
	}

	private function bibliotheque(): Bibliotheque
	{
		return new Bibliotheque(hmacSignatureSecret: $this->secret);
	}

	/** Un défi neuf, signé, qui expire à $expire (par défaut dans DUREE secondes). */
	public function defi(?int $expire = NULL): Challenge
	{
		return $this->bibliotheque()->createChallenge(new CreateChallengeOptions(
			algorithm: new Pbkdf2(),
			cost: self::COUT,
			counter: random_int(self::COMPTEUR_MIN, self::COMPTEUR_MAX),
			expiresAt: $expire ?? time() + self::DUREE,
		));
	}

	public function element(string $langue): array
	{
		return ['altcha-widget', [
			'name'            => $this->champ(),
			'challenge'       => $this->defi()->toJson(),
			'configuration'   => (string) json_encode([
				'hideLogo'   => TRUE,
				'hideFooter' => TRUE,
				'language'   => $langue,
			], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
			// Le calcul part dès que le visiteur entre dans le formulaire : fini quand il l'envoie.
			'auto'            => 'onfocus',
			'class'           => 'nf-captcha',
			'data-nf-captcha' => self::cle(),
			'data-worker'     => $this->worker,
			// Le widget 3.x ne prend pas ses textes dans sa configuration mais dans un registre par
			// langue ($altcha.i18n), où js/captcha.js les dépose sous le code de `language`.
			'data-textes'     => (string) json_encode($this->textes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
		]];
	}

	public function scripts(string $langue): array
	{
		return ['altcha/altcha.min', 'captcha'];
	}

	public function verifier(string $reponse, string $ip, callable $http): bool
	{
		// Une solution tient en quelques centaines d'octets : au-delà, ce n'est pas une solution.
		if ($reponse === '' || strlen($reponse) > 8192)
		{
			return FALSE;
		}

		try
		{
			$solution = Payload::fromBase64($reponse);
			$resultat = $this->bibliotheque()->verifySolution(new VerifySolutionOptions(algorithm: new Pbkdf2(), payload: $solution));
		}
		catch (\Throwable)
		{
			return FALSE;
		}

		if (!$resultat->verified || $resultat->expired)
		{
			return FALSE;
		}

		if ($this->deja_vu !== NULL)
		{
			$empreinte = hash('sha256', (string) $solution->challenge->signature);
			$jusqua    = $solution->challenge->parameters->expiresAt ?? time() + self::DUREE;

			if (($this->deja_vu)($empreinte, $jusqua))
			{
				return FALSE;
			}
		}

		return TRUE;
	}
}
