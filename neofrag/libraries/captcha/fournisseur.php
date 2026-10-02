<?php
declare(strict_types=1);

namespace NF\NeoFrag\Libraries\Captcha;

/**
 * Un fournisseur de captcha (2026-10-02).
 *
 * Le captcha de NeoFrag ne connaissait que reCAPTCHA v2, la clé secrète partait dans l'adresse de
 * vérification, et la politique de sécurité ouvrait Google à tout site, qu'il ait un captcha ou non.
 * Chaque fournisseur est désormais une classe : il dit son champ, l'élément à afficher, les scripts à
 * charger, les origines à ouvrir, et vérifie une réponse — sur le modèle des fournisseurs du statut live
 * (widgets/twitch/lib/live_provider.php). La vérification reçoit son transport HTTP en argument : elle se
 * teste sans réseau. La façade est NF\NeoFrag\Libraries\Captcha.
 */
interface Fournisseur
{
	/** La valeur du réglage nf_captcha_provider. */
	public static function cle(): string;

	/** Le nom affiché dans les réglages. */
	public static function nom(): string;

	/** Le fournisseur exige-t-il une clé de site et une clé secrète ? */
	public static function cles_requises(): bool;

	/** L'adresse où l'administrateur crée ses clés (ou lit la documentation). */
	public static function console(): string;

	/**
	 * Les origines que la politique de sécurité doit ouvrir pour lui.
	 *
	 * @return array{script?: list<string>, frame?: list<string>, style?: list<string>}
	 */
	public static function csp(): array;

	/** Le champ POST qui porte la réponse du visiteur. */
	public function champ(): string;

	/**
	 * L'élément à afficher : sa balise et ses attributs, valeurs BRUTES (le rendu les échappe).
	 *
	 * @return array{0: string, 1: array<string, string>}
	 */
	public function element(string $langue): array;

	/**
	 * Les scripts à charger, dans l'ordre : une adresse absolue pour un tiers, un nom js() pour les nôtres.
	 *
	 * @return list<string>
	 */
	public function scripts(string $langue): array;

	/**
	 * La réponse du visiteur est-elle valide ?
	 *
	 * @param callable $http fn(string $url, array $champs): ?array — un POST, la réponse JSON décodée
	 */
	public function verifier(string $reponse, string $ip, callable $http): bool;
}
