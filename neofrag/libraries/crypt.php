<?php
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\NeoFrag\Libraries;

use NF\NeoFrag\Library;

class Crypt extends Library
{
	protected $_key = '';

	public function __construct($caller, $config)
	{
		parent::__construct($caller);

		$this->_key = $config['key'];
	}

	// Chiffrement symétrique générique (sortie rawurlencode-safe pour cookies/URLs). AES-256-GCM
	// authentifié, IV CSPRNG aléatoire PAR message stocké avec le chiffré (iv 12 + tag 16) — plus de
	// rand() ni de réutilisation d'IV (en CTR, réutiliser l'IV cassait la confidentialité). NB : non
	// appelé par le cœur aujourd'hui (les secrets au repos passent par encrypt_secret/decrypt_secret) ;
	// gardé correct pour tout usage applicatif de `$this->crypt(...)` / `->decode(...)`.
	public function __invoke($data)
	{
		$iv  = random_bytes(12);
		$tag = '';
		$ct  = openssl_encrypt(json_encode($data), 'aes-256-gcm', hash('sha256', $this->_key, TRUE), OPENSSL_RAW_DATA, $iv, $tag, '', 16);

		return $ct === FALSE ? FALSE : rawurlencode(base64_encode($iv.$tag.$ct));
	}

	public function decode($data)
	{
		$raw = base64_decode(rawurldecode((string)$data), TRUE);

		if ($raw === FALSE || strlen($raw) < 29)
		{
			return NULL;
		}

		$pt = openssl_decrypt(substr($raw, 28), 'aes-256-gcm', hash('sha256', $this->_key, TRUE), OPENSSL_RAW_DATA, substr($raw, 0, 12), substr($raw, 12, 16));

		return $pt === FALSE ? NULL : json_decode($pt);
	}

	public function hash($data, $length = 32, $charset = [])
	{
		if (!is_string($data))
		{
			$data = json_encode($data);
		}

		$data = hash('sha512', $data, TRUE);

		if (!$charset)
		{
			$charset = array_merge(range('a', 'z'), range(0, 9));
		}

		$n = count($charset);
		$i = 0;

		return implode(array_map(function($a) use ($charset, $n, &$i){
			return $charset[(++$i * array_sum(array_map('ord', str_split(sha1($a))))) % $n];
		}, str_split($data, ceil(strlen($data) / $length))));
	}

	// Chiffrement AU REPOS (distinct de __invoke/decode qui sont scopés session) : pour
	// stocker un secret en base (mot de passe SMTP, secret TOTP) qu'on doit pouvoir déchiffrer
	// hors de la session qui l'a écrit. AES-256-GCM (authentifié), IV aléatoire stocké AVEC le
	// chiffré, clé dérivée de config/crypt.php. Préfixe versionné → déchiffrement transparent :
	// une valeur en clair héritée (sans préfixe) est renvoyée telle quelle (pas de migration dure).
	const AT_REST_PREFIX = 'nfenc1:';

	public function encrypt_secret($plaintext)
	{
		if ($plaintext === NULL || $plaintext === '')
		{
			return $plaintext;
		}

		$iv  = random_bytes(12);
		$tag = '';
		$ct  = openssl_encrypt((string)$plaintext, 'aes-256-gcm', hash('sha256', $this->_key, TRUE), OPENSSL_RAW_DATA, $iv, $tag, '', 16);

		// Fail-safe : en cas d'échec on renvoie le clair plutôt que d'écrire une valeur illisible.
		return $ct === FALSE ? $plaintext : self::AT_REST_PREFIX.base64_encode($iv.$tag.$ct);
	}

	public function decrypt_secret($stored)
	{
		$stored = (string)$stored;
		$prefix = self::AT_REST_PREFIX;

		// Valeur en clair héritée (jamais chiffrée) : passthrough transparent.
		if (strncmp($stored, $prefix, strlen($prefix)) !== 0)
		{
			return $stored;
		}

		$raw = base64_decode(substr($stored, strlen($prefix)), TRUE);

		if ($raw === FALSE || strlen($raw) < 29)
		{
			return '';
		}

		$pt = openssl_decrypt(substr($raw, 28), 'aes-256-gcm', hash('sha256', $this->_key, TRUE), OPENSSL_RAW_DATA, substr($raw, 0, 12), substr($raw, 12, 16));

		return $pt === FALSE ? '' : $pt;
	}
}
