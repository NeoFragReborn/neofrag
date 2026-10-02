<?php
declare(strict_types=1);

namespace NF\NeoFrag\Libraries\Captcha;

/**
 * Google reCAPTCHA v2 (la case « je ne suis pas un robot ») : celui de NeoFrag, remis d'aplomb.
 *
 * Depuis le début de 2026, les clés se créent dans un projet Google Cloud ; celles d'avant et l'adresse
 * de vérification continuent de fonctionner. La v3 — un score, et l'envoi des formulaires intercepté
 * pour obtenir un jeton — viendra sur demande.
 */
final class Recaptcha extends Distant
{
	public static function cle(): string
	{
		return 'recaptcha';
	}

	public static function nom(): string
	{
		return 'Google reCAPTCHA v2';
	}

	public static function console(): string
	{
		return 'https://www.google.com/recaptcha/admin';
	}

	public static function csp(): array
	{
		return ['script' => ['https://www.google.com', 'https://www.gstatic.com'], 'frame' => ['https://www.google.com']];
	}

	public function champ(): string
	{
		return 'g-recaptcha-response';
	}

	protected function classe(): string
	{
		return 'g-recaptcha';
	}

	protected function adresse_verification(): string
	{
		return 'https://www.google.com/recaptcha/api/siteverify';
	}

	public function scripts(string $langue): array
	{
		return ['captcha', 'https://www.google.com/recaptcha/api.js?render=explicit&onload=nfCaptchaPret&hl='.rawurlencode($langue)];
	}
}
