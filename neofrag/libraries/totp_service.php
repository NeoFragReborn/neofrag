<?php
/**
 * https://neofr.ag
 * Service TOTP (Time-based One-Time Password) pour le 2FA.
 * Wrapper autour de pragmarx/google2fa + bacon/bacon-qr-code.
 */

namespace NF\NeoFrag\Libraries;

use NF\NeoFrag\Library;
use PragmaRX\Google2FA\Google2FA;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class Totp_Service extends Library
{
	private function google2fa()
	{
		return new Google2FA();
	}

	/**
	 * Génère un secret TOTP base32.
	 */
	public function generate_secret()
	{
		return $this->google2fa()->generateSecretKey(32);
	}

	/**
	 * Génère le contenu otpauth:// pour un secret.
	 */
	public function provisioning_uri($company, $account, $secret)
	{
		return $this->google2fa()->getQRCodeUrl($company, $account, $secret);
	}

	/**
	 * Génère un QR code SVG (data URI inline pour <img src="...">) à scanner.
	 */
	public function qr_code_svg_data_uri($company, $account, $secret)
	{
		$uri = $this->provisioning_uri($company, $account, $secret);

		$renderer = new ImageRenderer(
			new RendererStyle(200),
			new SvgImageBackEnd()
		);

		$writer = new Writer($renderer);
		$svg = $writer->writeString($uri);

		return 'data:image/svg+xml;base64,'.base64_encode($svg);
	}

	/**
	 * Vérifie un code TOTP (6 chiffres).
	 *
	 * @param string $secret Le secret base32 stocké en DB
	 * @param string $code Le code saisi par l'user (string de 6 chiffres)
	 * @return bool
	 */
	public function verify($secret, $code)
	{
		if (!$secret || !preg_match('/^\d{6}$/', trim($code)))
		{
			return FALSE;
		}

		return (bool) $this->google2fa()->verifyKey($secret, trim($code), 2);
	}

	/**
	 * Génère N codes de récupération aléatoires (à stocker hashés).
	 * Format : 4 groupes de 4 chiffres alphanumériques.
	 *
	 * @return array{plain: string[], hashed: string[]}
	 */
	public function generate_recovery_codes($count = 10)
	{
		$plain = [];
		$hashed = [];

		for ($i = 0; $i < $count; $i++)
		{
			$code = strtoupper(bin2hex(random_bytes(8)));
			$code = substr($code, 0, 4).'-'.substr($code, 4, 4).'-'.substr($code, 8, 4).'-'.substr($code, 12, 4);
			$plain[] = $code;
			$hashed[] = password_hash($code, PASSWORD_ARGON2ID);
		}

		return ['plain' => $plain, 'hashed' => $hashed];
	}

	/**
	 * Vérifie un code de récupération et le marque utilisé.
	 *
	 * @param int $user_id
	 * @param string $code Le code saisi par l'user
	 * @return bool
	 */
	public function verify_and_consume_recovery_code($user_id, $code)
	{
		$rows = NeoFrag()->db	->select('id', 'code_hash')
								->from('nf_user_totp_recovery')
								->where('user_id', $user_id)
								->where('used_at', NULL)
								->get();

		foreach ($rows as $row)
		{
			if (password_verify($code, $row['code_hash']))
			{
				NeoFrag()->db->execute('UPDATE nf_user_totp_recovery SET used_at = NOW() WHERE id = '.(int)$row['id']);
				return TRUE;
			}
		}

		return FALSE;
	}

	/**
	 * Stocke des codes de récupération (hashés) pour un user, en remplaçant les anciens.
	 */
	public function store_recovery_codes($user_id, $hashed_codes)
	{
		NeoFrag()->db	->from('nf_user_totp_recovery')
						->where('user_id', $user_id)
						->delete();

		foreach ($hashed_codes as $hash)
		{
			NeoFrag()->db->insert('nf_user_totp_recovery', [
				'user_id'   => $user_id,
				'code_hash' => $hash
			]);
		}
	}

	/**
	 * Compte les codes de récupération non utilisés pour un user.
	 */
	public function count_unused_recovery_codes($user_id)
	{
		return (int) NeoFrag()->db	->select('COUNT(*)')
									->from('nf_user_totp_recovery')
									->where('user_id', $user_id)
									->where('used_at', NULL)
									->row();
	}
}
