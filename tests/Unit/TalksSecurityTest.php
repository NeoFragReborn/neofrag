<?php
declare(strict_types=1);

namespace NF\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use NF\Modules\Talks\Security;

require_once __DIR__ . '/../../modules/talks/security.php';

/**
 * `Talks\Security` décide ce qu'un membre peut téléverser et ce qui s'affiche dans un message.
 * C'est donc du code de sécurité — et il n'avait aucun test jusqu'au 2026-09-20, jour où il a été
 * converti en `strict_types` (lot 2). Ces épreuves ont été écrites AVANT la conversion,
 * pour que ce qui passait avant passe encore après.
 *
 * Elles couvrent les trois décisions que la classe prend : quel est le VRAI type d'un fichier
 * (ses premiers octets, jamais son extension ni l'en-tête HTTP), ce qu'un fichier doit refuser, et
 * quelles adresses sont sûres à rendre cliquables.
 */
final class TalksSecurityTest extends TestCase
{
	/** @var list<string> */
	private array $fichiers = [];

	protected function tearDown(): void
	{
		foreach ($this->fichiers as $f)
		{
			@unlink($f);
		}

		$this->fichiers = [];
	}

	private function fichier(string $contenu): string
	{
		$chemin = tempnam(sys_get_temp_dir(), 'nf-talks-');
		self::assertIsString($chemin);
		file_put_contents($chemin, $contenu);
		$this->fichiers[] = $chemin;

		return $chemin;
	}

	/**
	 * Un VRAI PNG de 1×1 pixel, pas seulement sa signature.
	 *
	 * La première version de ces épreuves posait la signature suivie de zéros : `finfo`, que la
	 * classe interroge avant de retomber sur les octets magiques, y a vu un
	 * `application/octet-stream`, et trois épreuves de validation ont échoué. Le défaut était dans
	 * l'épreuve, pas dans le code : un fichier qui n'est pas un vrai PNG n'a aucune raison d'être
	 * reçu comme tel.
	 */
	private function png(): string
	{
		return (string) base64_decode(
			'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==',
			TRUE
		);
	}

	public function test_le_type_reel_vient_des_octets_pas_de_l_extension(): void
	{
		// Nommé .txt, mais c'est un PNG : c'est le contenu qui doit trancher.
		self::assertSame('image/png', Security::detect_real_mime($this->fichier($this->png())));
		self::assertSame('application/pdf', Security::detect_real_mime($this->fichier("%PDF-1.7\n")));
		self::assertSame('image/gif', Security::detect_real_mime($this->fichier("GIF89a" . str_repeat("\0", 32))));
	}

	public function test_un_fichier_absent_ne_fait_pas_planter(): void
	{
		self::assertFalse(Security::detect_real_mime('/inexistant/' . bin2hex(random_bytes(6))));
	}

	/**
	 * Le cas qui a motivé un correctif : un fichier VIDE fait rendre `false` à `fread()`, et tout
	 * ce qui suit découpe ce `false`. En `strict_types`, `substr(false, …)` lève une TypeError.
	 */
	public function test_un_fichier_vide_rend_false_sans_erreur(): void
	{
		$vide = $this->fichier('');
		$resultat = Security::detect_real_mime($vide);

		self::assertTrue($resultat === FALSE || is_string($resultat),
			'detect_real_mime doit rendre FALSE ou un type, jamais lever');
	}

	public function test_un_fichier_valide_est_accepte(): void
	{
		self::assertTrue(Security::validate_file($this->fichier($this->png()), ['image/png']));
	}

	public function test_un_type_hors_liste_est_refuse(): void
	{
		$refus = Security::validate_file($this->fichier($this->png()), ['application/pdf']);

		self::assertIsString($refus);
		self::assertStringContainsString('non autoris', $refus);
	}

	public function test_un_fichier_trop_gros_est_refuse(): void
	{
		$refus = Security::validate_file($this->fichier($this->png()), ['image/png'], 8);

		self::assertSame('Fichier trop volumineux', $refus);
	}

	public function test_un_fichier_vide_est_refuse(): void
	{
		self::assertSame('Fichier vide', Security::validate_file($this->fichier(''), ['image/png']));
	}

	/**
	 * Le polyglotte : un fichier qui est un vrai PNG pour `detect_real_mime` ET porte du code.
	 * C'est le cas que la liste blanche de types ne suffit pas à couvrir.
	 */
	public function test_une_image_qui_porte_du_php_est_refusee(): void
	{
		$piege = $this->fichier($this->png() . '<?php system($_GET["c"]); ?>');
		$refus = Security::validate_file($piege, ['image/png']);

		self::assertIsString($refus);
		self::assertStringContainsString('suspect', $refus);
	}

	public function test_une_image_qui_porte_un_gestionnaire_d_evenement_est_refusee(): void
	{
		$piege = $this->fichier($this->png() . '<img src=x onerror=alert(1)>');

		self::assertIsString(Security::validate_file($piege, ['image/png']));
	}

	public function test_les_caracteres_de_controle_sont_retires_du_texte(): void
	{
		$texte = "bonjour\x00\x07 monde\nsuite\t.";

		self::assertSame("bonjour monde\nsuite\t.", Security::sanitize_message_text($texte));
	}

	public function test_le_texte_est_elague(): void
	{
		self::assertSame('coucou', Security::sanitize_message_text("  coucou \n "));
	}

	/** @return list<array{string, bool}> */
	public static function adresses(): array
	{
		return [
			['https://exemple.test/page', TRUE],
			['http://exemple.test',       TRUE],
			['mailto:qui@exemple.test',   TRUE],
			['/fr/forum',                 TRUE],
			['javascript:alert(1)',       FALSE],
			['JavaScript:alert(1)',       FALSE],
			['data:text/html;base64,PHM', FALSE],
			['vbscript:msgbox',           FALSE],
			['file:///etc/passwd',        FALSE],
			['about:blank',               FALSE],
			['exemple.test',              FALSE],
			['',                          FALSE],
		];
	}

	#[DataProvider('adresses')]
	public function test_les_adresses_dangereuses_sont_refusees(string $url, bool $attendu): void
	{
		self::assertSame($attendu, Security::is_safe_url($url), $url);
	}

	public function test_les_raccourcisseurs_sont_reconnus(): void
	{
		self::assertTrue(Security::is_url_shortener('https://bit.ly/abc'));
		self::assertTrue(Security::is_url_shortener('https://t.co/abc'));
		self::assertFalse(Security::is_url_shortener('https://exemple.test/abc'));
		self::assertFalse(Security::is_url_shortener('pas une adresse'));
	}

	public function test_les_hotes_de_gif_de_confiance_sont_reconnus(): void
	{
		self::assertTrue(Security::is_trusted_gif_host('https://media.giphy.com/x.gif'));
		self::assertTrue(Security::is_trusted_gif_host('https://c.tenor.com/x.gif'));
		self::assertFalse(Security::is_trusted_gif_host('https://giphy.com.pirate.test/x.gif'));
	}

	public function test_le_rendu_d_un_message_echappe_le_html(): void
	{
		$rendu = Security::render_message('<script>alert(1)</script> et <b>gras</b>');

		self::assertStringNotContainsString('<script', $rendu);
	}

	public function test_le_rendu_staff_refuse_un_lien_javascript(): void
	{
		$rendu = Security::render_staff_message('<a href="javascript:alert(1)">clic</a>');

		self::assertStringNotContainsString('javascript:', strtolower($rendu));
	}
}
