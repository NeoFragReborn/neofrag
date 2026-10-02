<?php
declare(strict_types=1);

namespace NF\NeoFrag\Libraries\Captcha;

/** Cloudflare Turnstile : gratuit, sans casse-tête pour le visiteur ; clés sur dash.cloudflare.com. */
final class Turnstile extends Distant
{
	public static function cle(): string
	{
		return 'turnstile';
	}

	public static function nom(): string
	{
		return 'Cloudflare Turnstile';
	}

	public static function console(): string
	{
		return 'https://dash.cloudflare.com/?to=/:account/turnstile';
	}

	public static function csp(): array
	{
		return ['script' => ['https://challenges.cloudflare.com'], 'frame' => ['https://challenges.cloudflare.com']];
	}

	public function champ(): string
	{
		return 'cf-turnstile-response';
	}

	protected function classe(): string
	{
		return 'cf-turnstile';
	}

	protected function adresse_verification(): string
	{
		return 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
	}

	public function scripts(string $langue): array
	{
		return ['captcha', 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit&onload=nfCaptchaPret'];
	}
}
