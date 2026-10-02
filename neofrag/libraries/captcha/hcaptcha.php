<?php
declare(strict_types=1);

namespace NF\NeoFrag\Libraries\Captcha;

/** hCaptcha : gratuit pour un usage courant ; clés sur dashboard.hcaptcha.com. */
final class Hcaptcha extends Distant
{
	public static function cle(): string
	{
		return 'hcaptcha';
	}

	public static function nom(): string
	{
		return 'hCaptcha';
	}

	public static function console(): string
	{
		return 'https://dashboard.hcaptcha.com/';
	}

	public static function csp(): array
	{
		$origines = ['https://hcaptcha.com', 'https://*.hcaptcha.com'];

		return ['script' => $origines, 'frame' => $origines, 'style' => $origines];
	}

	public function champ(): string
	{
		return 'h-captcha-response';
	}

	protected function classe(): string
	{
		return 'h-captcha';
	}

	protected function adresse_verification(): string
	{
		return 'https://api.hcaptcha.com/siteverify';
	}

	protected function champs_verification(string $reponse, string $ip): array
	{
		// hCaptcha recommande de joindre la clé de site : il refuse une réponse obtenue pour une autre.
		return parent::champs_verification($reponse, $ip) + ['sitekey' => $this->site];
	}

	public function scripts(string $langue): array
	{
		return ['captcha', 'https://js.hcaptcha.com/1/api.js?render=explicit&onload=nfCaptchaPret&hl='.rawurlencode($langue)];
	}
}
