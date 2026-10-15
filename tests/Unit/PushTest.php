<?php
declare(strict_types=1);

namespace NF\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Les notifications push (d11) : le chiffrement et la signature, éprouvés contre les NORMES et non contre eux-mêmes.
 *
 * Le chiffrement rejoue l'exemple de la RFC 8291 (annexe A) : mêmes clés, même sel, le corps doit sortir au bit près.
 * Un aller-retour avec ses propres fonctions ne prouverait rien — deux erreurs symétriques s'annulent ; un navigateur,
 * lui, ne les pardonnerait pas.
 */
final class PushTest extends TestCase
{
	// RFC 8291, annexe A.
	private const MESSAGE    = 'When I grow up, I want to be a watermelon';
	private const AS_PRIVEE  = 'yfWPiYE-n46HLnH0KqZOF1fJJU3MYrct3AELtAQ-oRw';
	private const AS_PUBLIQUE = 'BP4z9KsN6nGRTbVYI_c7VJSPQTBtkgcy27mlmlMoZIIgDll6e3vCYLocInmYWAmS6TlzAC8wEqKK6PBru3jl7A8';
	private const UA_PRIVEE  = 'q1dXpw3UpT5VOmu_cf_v6ih07Aems3njxI-JWgLcM94';
	private const UA_PUBLIQUE = 'BCVxsr7N_eNgVRqvHtD0zTZsEc6-VV-JvLexhqUzORcxaOzi6-AYWXvTBHm4bjyPjs7Vd8pZGH6SRpkNtoIAiw4';
	private const SEL        = 'DGv6ra1nlYgDCS1FRnbzlw';
	private const AUTH       = 'BTBZMqHH6r4Tts7J_aSIgg';
	private const CORPS      = 'DGv6ra1nlYgDCS1FRnbzlwAAEABBBP4z9KsN6nGRTbVYI_c7VJSPQTBtkgcy27mlmlMoZIIgDll6e3vCYLocInmYWAmS6TlzAC8wEqKK6PBru3jl7A_yl95bQpu6cVPTpK4Mqgkf1CXztLVBSt2Ks3oZwbuwXPXLWyouBWLVWGNWQexSgSxsj_Qulcy4a-fN';

	public static function setUpBeforeClass(): void
	{
		require_once dirname(__DIR__, 2).'/neofrag/helpers/push.php';
	}

	protected function setUp(): void
	{
		if (!function_exists('openssl_pkey_derive') || !in_array('prime256v1', (array) openssl_get_curve_names(), TRUE))
		{
			self::markTestSkipped('OpenSSL sans la courbe P-256');
		}
	}

	/** Ces épreuves CRÉENT des clés : là où OpenSSL ne le sait pas (Windows sans sa configuration), elles sautent. */
	private function creation_de_cles(): void
	{
		if (!nf_push_possible())
		{
			self::markTestSkipped('OpenSSL ne sait pas créer de clé P-256 ici');
		}
	}

	public function test_le_corps_de_la_rfc_se_dechiffre_avec_la_cle_du_navigateur(): void
	{
		$ua = nf_push_cle_privee_brute(nf_push_b64u_decoder(self::UA_PRIVEE), nf_push_b64u_decoder(self::UA_PUBLIQUE));

		self::assertSame(self::MESSAGE, nf_push_dechiffrer(nf_push_b64u_decoder(self::CORPS), $ua, nf_push_b64u_decoder(self::AUTH)));
	}

	public function test_le_chiffrement_reproduit_la_rfc_au_bit_pres(): void
	{
		$as    = nf_push_cle_privee_brute(nf_push_b64u_decoder(self::AS_PRIVEE), nf_push_b64u_decoder(self::AS_PUBLIQUE));
		$corps = nf_push_chiffrer(self::MESSAGE, nf_push_b64u_decoder(self::UA_PUBLIQUE), nf_push_b64u_decoder(self::AUTH), $as, nf_push_b64u_decoder(self::SEL));

		self::assertSame(self::CORPS, nf_push_b64u_encoder($corps));
	}

	public function test_deux_envois_du_meme_message_ne_se_ressemblent_pas(): void
	{
		$this->creation_de_cles();

		$ua    = nf_push_paire();
		$auth  = random_bytes(16);
		$un    = nf_push_chiffrer('bonjour', nf_push_point_public($ua), $auth);
		$deux  = nf_push_chiffrer('bonjour', nf_push_point_public($ua), $auth);

		self::assertNotSame($un, $deux, 'une clé éphémère et un sel neufs à chaque message');
		self::assertSame('bonjour', nf_push_dechiffrer($un, $ua, $auth));
		self::assertSame('bonjour', nf_push_dechiffrer($deux, $ua, $auth));
	}

	public function test_le_jeton_vapid_est_un_jwt_es256_que_la_cle_publique_verifie(): void
	{
		$this->creation_de_cles();

		$cle = nf_push_paire();
		openssl_pkey_export($cle, $pem);

		$jeton = nf_push_jeton('https://fcm.googleapis.com/fcm/send/abc', (string) $pem, 'https://exemple.org');
		[$entete, $charge, $signature] = explode('.', $jeton);

		self::assertSame(['typ' => 'JWT', 'alg' => 'ES256'], json_decode(nf_push_b64u_decoder($entete), TRUE));

		$revendications = json_decode(nf_push_b64u_decoder($charge), TRUE);
		self::assertSame('https://fcm.googleapis.com', $revendications['aud'], 'l\'ORIGINE du service, pas l\'adresse entière');
		self::assertSame('https://exemple.org', $revendications['sub']);
		self::assertGreaterThan(time(), $revendications['exp']);
		self::assertLessThanOrEqual(time() + 24 * 3600, $revendications['exp'], 'la RFC 8292 plafonne à vingt-quatre heures');

		// La signature brute R‖S (64 octets) se remet en DER pour qu'OpenSSL la vérifie.
		$brute = nf_push_b64u_decoder($signature);
		self::assertSame(64, strlen($brute));

		$entier = static function (string $octets): string {
			$octets = ltrim($octets, "\0");
			if ($octets === '' || ord($octets[0]) & 0x80) { $octets = "\0".$octets; }
			return "\x02".chr(strlen($octets)).$octets;
		};
		$der = $entier(substr($brute, 0, 32)).$entier(substr($brute, 32));
		$der = "\x30".chr(strlen($der)).$der;

		self::assertSame(1, openssl_verify($entete.'.'.$charge, $der, openssl_pkey_get_details($cle)['key'], OPENSSL_ALGO_SHA256));
	}

	public function test_seuls_les_services_de_push_connus_recoivent_un_envoi(): void
	{
		foreach (['https://fcm.googleapis.com/fcm/send/x', 'https://updates.push.services.mozilla.com/wpush/v2/x',
		          'https://web.push.apple.com/x', 'https://db5p.notify.windows.com/w/?token=x'] as $bonne)
		{
			self::assertTrue(nf_push_adresse_recue($bonne), $bonne);
		}

		// Le serveur ENVOIE vers cette adresse : un membre ne doit pas pouvoir la pointer sur le réseau interne.
		foreach (['http://fcm.googleapis.com/fcm/send/x', 'https://127.0.0.1/x', 'https://localhost/x',
		          'https://fcm.googleapis.com.evil.org/x', 'https://evilfcm.googleapis.com/x', 'https://fcm.googleapis.com:8443/x',
		          'https://user@fcm.googleapis.com/x', 'file:///etc/passwd', 'pas une adresse'] as $mauvaise)
		{
			self::assertFalse(nf_push_adresse_recue($mauvaise), $mauvaise);
		}
	}
}
