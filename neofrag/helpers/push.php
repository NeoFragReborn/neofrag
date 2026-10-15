<?php
declare(strict_types=1);
/**
 * Les notifications PUSH (d11, 2026-10-16) : un message qui arrive dans le navigateur du membre, même site fermé.
 *
 * Chargé à la demande : `require_once NEOFRAG_CMS.'/neofrag/helpers/push.php'`.
 *
 * Sans bibliothèque ajoutée : le protocole tient en trois normes, que PHP sait servir avec OpenSSL.
 *  - RFC 8292 (VAPID) : le site se présente au service de push du navigateur par un jeton signé (ES256) avec SA clé ;
 *    la clé publique est donnée au navigateur à l'abonnement, et seul ce site peut ensuite lui écrire.
 *  - RFC 8291 (chiffrement) : le message est chiffré pour CET abonnement (ECDH P-256 + HKDF + AES-128-GCM) ; le
 *    service de push (Google, Mozilla, Apple, Microsoft) le transporte sans pouvoir le lire.
 *  - RFC 8030 : l'envoi lui-même, un POST vers l'adresse que le navigateur a donnée.
 *
 * L'adresse d'un abonnement vient du navigateur d'un MEMBRE : le serveur y envoie ensuite une requête. Elle n'est
 * donc reçue que si elle désigne un service de push connu, en https (nf_push_adresse_recue()) — sans quoi un membre
 * ferait écrire le serveur vers son propre réseau interne.
 */

/** Les services de push des navigateurs : le seul endroit où le serveur accepte d'écrire. */
const NF_PUSH_SERVICES = [
	'fcm.googleapis.com', 'android.googleapis.com',          // Chrome, Edge, Opera, Brave, Samsung
	'updates.push.services.mozilla.com', 'push.services.mozilla.com', // Firefox
	'.notify.windows.com',                                     // Edge hérité (WNS)
	'web.push.apple.com', '.push.apple.com',                   // Safari
];

function nf_push_b64u_encoder(string $octets): string
{
	return rtrim(strtr(base64_encode($octets), '+/', '-_'), '=');
}

function nf_push_b64u_decoder(string $texte): string
{
	return (string) base64_decode(strtr($texte, '-_', '+/').str_repeat('=', (4 - strlen($texte) % 4) % 4), TRUE);
}

/** Le push est-il possible ici : OpenSSL avec la courbe P-256, et cURL pour l'envoi. */
function nf_push_possible(): bool
{
	static $possible = NULL;

	// Essayé pour de vrai, une fois : sous Windows, une extension OpenSSL sans son fichier de configuration
	// connaît la courbe mais ne sait pas créer de clé.
	return $possible ??= function_exists('openssl_pkey_new') && function_exists('openssl_pkey_derive') && function_exists('curl_init')
		&& in_array('prime256v1', (array) openssl_get_curve_names(), TRUE)
		&& @openssl_pkey_new(['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC]) !== FALSE;
}

/** Le point public (65 octets, non compressé) d'une clé EC P-256. */
function nf_push_point_public($cle): string
{
	$details = openssl_pkey_get_details($cle);

	if (!is_array($details) || !isset($details['ec']['x'], $details['ec']['y']))
	{
		throw new RuntimeException('push : clé EC illisible');
	}

	return "\x04".str_pad($details['ec']['x'], 32, "\0", STR_PAD_LEFT).str_pad($details['ec']['y'], 32, "\0", STR_PAD_LEFT);
}

/** Une clé PUBLIQUE OpenSSL à partir d'un point P-256 brut (le `p256dh` d'un abonnement). */
function nf_push_cle_publique(string $point)
{
	if (strlen($point) !== 65 || $point[0] !== "\x04")
	{
		throw new InvalidArgumentException('push : point public P-256 attendu (65 octets)');
	}

	// SubjectPublicKeyInfo d'une clé EC P-256 : l'en-tête est fixe, le point suit.
	$der = hex2bin('3059301306072a8648ce3d020106082a8648ce3d030107034200').$point;
	$cle = openssl_pkey_get_public("-----BEGIN PUBLIC KEY-----\n".chunk_split(base64_encode($der), 64, "\n")."-----END PUBLIC KEY-----\n");

	if ($cle === FALSE)
	{
		throw new InvalidArgumentException('push : point public refusé par OpenSSL');
	}

	return $cle;
}

/**
 * Une clé PRIVÉE OpenSSL à partir de son scalaire brut (32 octets) et de son point public : sert aux épreuves, qui
 * rejouent les exemples de la RFC 8291 avec ses clés.
 */
function nf_push_cle_privee_brute(string $scalaire, string $point)
{
	// ECPrivateKey (RFC 5915) : version 1, le scalaire, la courbe, le point.
	$der = hex2bin('30770201010420').$scalaire.hex2bin('a00a06082a8648ce3d030107a144034200').$point;
	$cle = openssl_pkey_get_private("-----BEGIN EC PRIVATE KEY-----\n".chunk_split(base64_encode($der), 64, "\n")."-----END EC PRIVATE KEY-----\n");

	if ($cle === FALSE)
	{
		throw new InvalidArgumentException('push : scalaire privé refusé par OpenSSL');
	}

	return $cle;
}

/** Une nouvelle paire P-256. */
function nf_push_paire()
{
	$cle = openssl_pkey_new(['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC]);

	if ($cle === FALSE)
	{
		throw new RuntimeException('push : OpenSSL ne sait pas créer de clé P-256');
	}

	return $cle;
}

/**
 * Les clés VAPID du site, créées au premier besoin : la publique (en base64url, donnée aux navigateurs) et la
 * privée (PEM, qui ne quitte jamais le serveur). NULL si le push n'est pas possible ici.
 *
 * @return array{publique: string, privee: string}|null
 */
function nf_push_cles(): ?array
{
	$config = NeoFrag()->config;

	if (!nf_push_possible())
	{
		return NULL;
	}

	if (!$config->nf_push_vapid_publique || !$config->nf_push_vapid_privee)
	{
		$cle = nf_push_paire();

		if (!openssl_pkey_export($cle, $pem))
		{
			return NULL;
		}

		NeoFrag()->config('nf_push_vapid_privee', $pem);
		NeoFrag()->config('nf_push_vapid_publique', nf_push_b64u_encoder(nf_push_point_public($cle)));
	}

	return ['publique' => (string) $config->nf_push_vapid_publique, 'privee' => (string) $config->nf_push_vapid_privee];
}

/** L'adresse d'un abonnement est-elle celle d'un service de push connu, en https ? */
function nf_push_adresse_recue(string $adresse): bool
{
	$parties = parse_url($adresse);

	if (!is_array($parties) || ($parties['scheme'] ?? '') !== 'https' || empty($parties['host']) || isset($parties['user']) || isset($parties['port']))
	{
		return FALSE;
	}

	$hote = strtolower($parties['host']);

	foreach (NF_PUSH_SERVICES as $service)
	{
		if ($service[0] === '.' ? str_ends_with($hote, $service) : $hote === $service)
		{
			return TRUE;
		}
	}

	return FALSE;
}

/**
 * Chiffre un message pour un abonnement (RFC 8291, `aes128gcm`). Rend le CORPS à envoyer : sel (16), taille des
 * enregistrements (4), longueur de la clé (1), clé publique éphémère (65), puis le texte chiffré et son étiquette.
 *
 * `$ephemere` et `$sel` ne servent qu'aux épreuves (les exemples de la RFC) : en vrai, ils sont tirés à chaque
 * message, et c'est ce qui rend deux messages identiques indiscernables.
 */
function nf_push_chiffrer(string $message, string $p256dh, string $auth, $ephemere = NULL, ?string $sel = NULL): string
{
	$ephemere ??= nf_push_paire();
	$sel      ??= random_bytes(16);

	$public_ua = $p256dh;
	$public_as = nf_push_point_public($ephemere);
	$secret    = openssl_pkey_derive(nf_push_cle_publique($public_ua), $ephemere);

	if ($secret === FALSE || strlen($auth) !== 16 || strlen($sel) !== 16)
	{
		throw new RuntimeException('push : échange de clés impossible');
	}

	// RFC 8291 §3.3 et §3.4 : le secret commun mêlé à celui de l'abonnement, puis la clé et le nonce du message.
	$ikm   = hash_hkdf('sha256', $secret, 32, "WebPush: info\0".$public_ua.$public_as, $auth);
	$cle   = hash_hkdf('sha256', $ikm, 16, "Content-Encoding: aes128gcm\0", $sel);
	$nonce = hash_hkdf('sha256', $ikm, 12, "Content-Encoding: nonce\0", $sel);

	// Un seul enregistrement : le message, puis le délimiteur du DERNIER enregistrement (RFC 8188 §2).
	$chiffre = openssl_encrypt($message."\x02", 'aes-128-gcm', $cle, OPENSSL_RAW_DATA, $nonce, $etiquette, '', 16);

	if ($chiffre === FALSE)
	{
		throw new RuntimeException('push : chiffrement impossible');
	}

	return $sel.pack('N', 4096).chr(65).$public_as.$chiffre.$etiquette;
}

/**
 * Déchiffre un corps `aes128gcm` côté NAVIGATEUR : ce que fait le navigateur à l'arrivée. Ne sert qu'aux épreuves,
 * pour vérifier qu'un message chiffré par nf_push_chiffrer() se relit.
 */
function nf_push_dechiffrer(string $corps, $privee_ua, string $auth): string
{
	$sel       = substr($corps, 0, 16);
	$longueur  = ord($corps[20]);
	$public_as = substr($corps, 21, $longueur);
	$chiffre   = substr($corps, 21 + $longueur, -16);
	$etiquette = substr($corps, -16);
	$public_ua = nf_push_point_public($privee_ua);
	$secret    = openssl_pkey_derive(nf_push_cle_publique($public_as), $privee_ua);

	$ikm   = hash_hkdf('sha256', (string) $secret, 32, "WebPush: info\0".$public_ua.$public_as, $auth);
	$cle   = hash_hkdf('sha256', $ikm, 16, "Content-Encoding: aes128gcm\0", $sel);
	$nonce = hash_hkdf('sha256', $ikm, 12, "Content-Encoding: nonce\0", $sel);
	$clair = openssl_decrypt($chiffre, 'aes-128-gcm', $cle, OPENSSL_RAW_DATA, $nonce, $etiquette);

	if ($clair === FALSE)
	{
		throw new RuntimeException('push : message indéchiffrable');
	}

	// Le message, son délimiteur (0x02), puis un éventuel remplissage de zéros (RFC 8188 §2).
	return substr(rtrim($clair, "\0"), 0, -1);
}

/**
 * Le jeton VAPID (RFC 8292) pour une adresse : signé ES256 par la clé du site, valable douze heures, pour l'ORIGINE
 * du service de push. `sub` dit au service qui joindre en cas d'abus : l'adresse du site.
 */
function nf_push_jeton(string $adresse, string $privee_pem, string $contact): string
{
	$parties  = parse_url($adresse);
	$audience = ($parties['scheme'] ?? 'https').'://'.($parties['host'] ?? '');
	$entete   = nf_push_b64u_encoder((string) json_encode(['typ' => 'JWT', 'alg' => 'ES256']));
	$charge   = nf_push_b64u_encoder((string) json_encode(['aud' => $audience, 'exp' => time() + 12 * 3600, 'sub' => $contact], JSON_UNESCAPED_SLASHES));
	$signe    = $entete.'.'.$charge;

	if (!openssl_sign($signe, $der, $privee_pem, OPENSSL_ALGO_SHA256))
	{
		throw new RuntimeException('push : signature VAPID impossible');
	}

	return $signe.'.'.nf_push_b64u_encoder(nf_push_der_vers_brut((string) $der));
}

/** Une signature ECDSA DER (SEQUENCE de deux INTEGER) en signature brute R‖S de 64 octets, comme JOSE l'attend. */
function nf_push_der_vers_brut(string $der): string
{
	$position = 2;

	if ((ord($der[1]) & 0x80) !== 0)
	{
		$position += ord($der[1]) & 0x7f;
	}

	$entiers = [];

	for ($i = 0; $i < 2; $i++)
	{
		$longueur   = ord($der[$position + 1]);
		$valeur     = substr($der, $position + 2, $longueur);
		$entiers[]  = str_pad(ltrim($valeur, "\0"), 32, "\0", STR_PAD_LEFT);
		$position  += 2 + $longueur;
	}

	return $entiers[0].$entiers[1];
}

/**
 * Envoie un message à un abonnement. Rend le code HTTP du service de push (201 : remis ; 404/410 : l'abonnement
 * n'existe plus, à oublier), 0 si rien n'est parti.
 *
 * @param array{endpoint: string, p256dh: string, auth: string} $abonnement les clés en base64url, comme le navigateur les donne
 */
function nf_push_envoyer(array $abonnement, array $message, int $ttl = 86400): int
{
	$cles = nf_push_cles();

	if ($cles === NULL || !nf_push_adresse_recue((string) $abonnement['endpoint']))
	{
		return 0;
	}

	$corps  = nf_push_chiffrer((string) json_encode($message, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
		nf_push_b64u_decoder((string) $abonnement['p256dh']), nf_push_b64u_decoder((string) $abonnement['auth']));
	$contact = site_origin() !== '' ? site_origin() : 'mailto:'.((string) NeoFrag()->config->nf_contact ?: 'webmaster@localhost');
	$jeton   = nf_push_jeton((string) $abonnement['endpoint'], $cles['privee'], $contact);
	$requete = curl_init((string) $abonnement['endpoint']);

	curl_setopt_array($requete, [
		CURLOPT_POST           => TRUE,
		CURLOPT_POSTFIELDS     => $corps,
		CURLOPT_RETURNTRANSFER => TRUE,
		CURLOPT_TIMEOUT        => 6,
		CURLOPT_CONNECTTIMEOUT => 4,
		CURLOPT_FOLLOWLOCATION => FALSE,
		CURLOPT_PROTOCOLS      => CURLPROTO_HTTPS,
		CURLOPT_HTTPHEADER     => [
			'Content-Type: application/octet-stream',
			'Content-Encoding: aes128gcm',
			'TTL: '.$ttl,
			'Urgency: normal',
			'Authorization: vapid t='.$jeton.', k='.$cles['publique'],
		],
	]);

	curl_exec($requete);
	$code = (int) curl_getinfo($requete, CURLINFO_RESPONSE_CODE);

	return $code;
}
