<?php
declare(strict_types=1);

namespace NF\NeoFrag\Libraries\Captcha;

/**
 * Ce que partagent les fournisseurs hébergés chez un tiers (Turnstile, hCaptcha, reCAPTCHA) : une clé de
 * site affichée, une clé secrète qui ne quitte pas le serveur, et une vérification par POST chez eux.
 *
 * La vérification passe la clé secrète dans le CORPS de la requête : l'ancien code de NeoFrag l'envoyait
 * dans l'adresse (GET), où elle finit dans les journaux des serveurs traversés.
 */
abstract class Distant implements Fournisseur
{
	public function __construct(
		protected readonly string $site,
		protected readonly string $secret,
	) {
	}

	public static function cles_requises(): bool
	{
		return TRUE;
	}

	/** L'adresse de vérification du fournisseur. */
	abstract protected function adresse_verification(): string;

	/** La classe CSS que son script reconnaît. */
	abstract protected function classe(): string;

	public function element(string $langue): array
	{
		return ['div', [
			'class'           => $this->classe().' nf-captcha',
			'data-nf-captcha' => static::cle(),
			'data-sitekey'    => $this->site,
			'data-language'   => $langue,
		]];
	}

	/** Les champs envoyés avec la vérification ; hCaptcha y ajoute la clé de site. */
	protected function champs_verification(string $reponse, string $ip): array
	{
		return ['secret' => $this->secret, 'response' => $reponse, 'remoteip' => $ip];
	}

	public function verifier(string $reponse, string $ip, callable $http): bool
	{
		if ($reponse === '' || $this->secret === '')
		{
			return FALSE;
		}

		$resultat = $http($this->adresse_verification(), $this->champs_verification($reponse, $ip));

		return is_array($resultat) && ($resultat['success'] ?? FALSE) === TRUE;
	}
}
