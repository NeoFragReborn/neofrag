<?php
declare(strict_types=1);

namespace NF\Tests\Unit;

use NF\NeoFrag\Libraries\Editeur_Images;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../neofrag/libraries/editeur_images.php';

/**
 * Les images collées ou glissées dans l'éditeur riche (TinyMCE) : ce que le site accepte d'en recevoir,
 * et ce qu'il en écrit. Signalé le 2026-10-04 : une image collée dans une réponse du forum s'affichait
 * cassée, puis disparaissait à l'enregistrement — rien ne l'envoyait au site.
 *
 * Ces épreuves tiennent les décisions de sécurité de `Editeur_Images` : le visiteur est refusé avant tout,
 * le VRAI type se lit dans les octets, un polyglotte image + PHP est refusé, la taille et les dimensions
 * sont bornées avant tout décodage, et ce qui est écrit n'est plus que des pixels. Plus le contrat avec
 * l'assainisseur (`sanitize_html`) : l'adresse d'une image envoyée survit, une `data:` ou une `blob:`
 * non. Plus une garde de FAMILLE : tout `tinymce.init` du produit porte les réglages d'envoi.
 *
 * Les charges « PHP » de ces épreuves sont INERTES (un `echo` d'un marqueur) : il suffit qu'elles aient
 * la forme d'un code pour prouver qu'elles sont refusées ou retirées.
 */
final class EditeurImagesTest extends TestCase
{
	private const MARQUEUR = 'NF_CHARGE_INERTE_C0FFEE';

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

	private function chemin(string $extension = 'bin'): string
	{
		return $this->fichiers[] = sys_get_temp_dir() . '/nf-editeur-' . bin2hex(random_bytes(6)) . '.' . $extension;
	}

	private function fichier(string $contenu, string $extension = 'bin'): string
	{
		file_put_contents($chemin = $this->chemin($extension), $contenu);

		return $chemin;
	}

	private function gd(): void
	{
		if (!extension_loaded('gd'))
		{
			$this->markTestSkipped('Extension GD indisponible');
		}
	}

	/** Une vraie image, de la couleur donnée, encodée par GD. */
	private function image(string $type, int $largeur, int $hauteur): string
	{
		$this->gd();

		// Un GD compilé sans WebP (celui de la CI) ne sait pas en fabriquer : une image WebP de 40 × 30
		// toute faite, que `controler()` lit par ses octets et getimagesize() — sans GD WebP.
		if ($type === 'webp' && !function_exists('imagewebp') && [$largeur, $hauteur] === [40, 30])
		{
			return $this->fichier((string) base64_decode('UklGRkgAAABXRUJQVlA4IDwAAAAwAwCdASooAB4APm02l0ikIyIhJWgAgA2JZwB2AABX74bgAP7rZF/61C7byP//s7v/p3f/Tu/vfAAAAAA='), 'webp');
		}

		$im = imagecreatetruecolor($largeur, $hauteur);
		imagefill($im, 0, 0, (int) imagecolorallocate($im, 10, 120, 200));
		ob_start();

		match ($type)
		{
			'png'  => imagepng($im),
			'jpg'  => imagejpeg($im, NULL, 90),
			'gif'  => imagegif($im),
			'webp' => imagewebp($im),
		};

		return $this->fichier((string) ob_get_clean(), $type);
	}

	// ── Qui peut envoyer ─────────────────────────────────────────────────────────────────────────────

	public function test_le_visiteur_est_refuse_avant_tout(): void
	{
		// Même avec un jeton « valide » et hors démonstration : sans compte, rien ne passe.
		self::assertSame('anonyme', Editeur_Images::refus_requete(FALSE, FALSE, TRUE));
		self::assertSame('anonyme', Editeur_Images::refus_requete(FALSE, TRUE, FALSE));
		self::assertSame(403, Editeur_Images::statut('anonyme'));
	}

	public function test_la_demonstration_puis_le_jeton(): void
	{
		self::assertSame('demo', Editeur_Images::refus_requete(TRUE, TRUE, TRUE));
		self::assertSame('jeton', Editeur_Images::refus_requete(TRUE, FALSE, FALSE));
		self::assertNull(Editeur_Images::refus_requete(TRUE, FALSE, TRUE));
		self::assertSame(403, Editeur_Images::statut('demo'));
		self::assertSame(403, Editeur_Images::statut('jeton'));
	}

	public function test_chaque_refus_a_son_message(): void
	{
		foreach (['anonyme', 'demo', 'jeton', 'debit', 'absent', 'taille', 'type', 'code', 'dimensions', 'ecriture', 'illisible'] as $code)
		{
			self::assertNotSame('', Editeur_Images::message($code), $code);
		}

		self::assertStringContainsString('5 MB', Editeur_Images::message('taille'));
		self::assertSame(429, Editeur_Images::statut('debit'));
	}

	// ── Ce qu'est une image acceptable ───────────────────────────────────────────────────────────────

	public function test_les_quatre_types_sont_reconnus_a_leurs_octets(): void
	{
		foreach (['png' => 'image/png', 'jpg' => 'image/jpeg', 'gif' => 'image/gif', 'webp' => 'image/webp'] as $extension => $type)
		{
			$controle = Editeur_Images::controler($this->image($extension, 40, 30));

			self::assertIsArray($controle, $extension);
			self::assertSame(['type' => $type, 'largeur' => 40, 'hauteur' => 30], $controle);
		}
	}

	public function test_le_non_image_est_refuse_quelle_que_soit_son_extension(): void
	{
		// L'extension ne dit rien : un texte nommé .png, un script nommé .jpg, un SVG (qui porte du script).
		self::assertSame('type', Editeur_Images::controler($this->fichier('Bonjour, je ne suis pas une image.', 'png')));
		self::assertSame('type', Editeur_Images::controler($this->fichier('<?php echo "' . self::MARQUEUR . '";', 'jpg')));
		self::assertSame('type', Editeur_Images::controler($this->fichier('<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>', 'svg')));
		self::assertSame('type', Editeur_Images::controler($this->fichier("%PDF-1.7\n", 'png')));
		self::assertSame('type', Editeur_Images::controler($this->fichier("BM\x00\x00\x00\x00", 'bmp')));
	}

	public function test_le_polyglotte_image_et_php_est_refuse(): void
	{
		// Une vraie image PNG, avec du « PHP » collé en queue : getimagesize() l'accepterait.
		$queue = $this->image('png', 20, 20);
		file_put_contents($queue, '<?php echo "' . self::MARQUEUR . '"; ?>', FILE_APPEND);
		self::assertNotFalse(getimagesize($queue));
		self::assertSame('code', Editeur_Images::controler($queue));

		// Le classique : un en-tête GIF suivi de PHP.
		self::assertSame('code', Editeur_Images::controler($this->fichier("GIF89a\x01\x00\x01\x00<?php echo '" . self::MARQUEUR . "'; ?>", 'gif')));

		// Du PHP caché dans un commentaire JPEG (segment COM), au début du fichier, en balise courte.
		$jpeg   = (string) file_get_contents($this->image('jpg', 20, 20));
		$charge = '<?=`' . self::MARQUEUR . '`?>';
		$com    = "\xFF\xFE" . pack('n', strlen($charge) + 2) . $charge;
		self::assertSame('code', Editeur_Images::controler($this->fichier(substr($jpeg, 0, 2) . $com . substr($jpeg, 2), 'jpg')));

		// Et du script, pour une image qu'on servirait comme du HTML.
		$script = $this->image('png', 20, 20);
		file_put_contents($script, '<script>alert(1)</script>', FILE_APPEND);
		self::assertSame('code', Editeur_Images::controler($script));
	}

	public function test_le_trop_gros_est_refuse_avant_d_etre_lu(): void
	{
		// Un en-tête PNG valide, et de quoi dépasser 5 Mo d'un octet.
		$gros = $this->fichier("\x89PNG\r\n\x1A\n" . str_repeat("\0", Editeur_Images::TAILLE_MAX - 7), 'png');
		self::assertSame(Editeur_Images::TAILLE_MAX + 1, filesize($gros));
		self::assertSame('taille', Editeur_Images::controler($gros));
		self::assertSame(413, Editeur_Images::statut('taille'));

		// Celui que PHP a déjà refusé (upload_max_filesize), et le fichier absent.
		self::assertSame('taille', Editeur_Images::controler('', UPLOAD_ERR_INI_SIZE));
		self::assertSame('absent', Editeur_Images::controler('', UPLOAD_ERR_NO_FILE));
		self::assertSame('absent', Editeur_Images::controler(sys_get_temp_dir() . '/nf-editeur-introuvable.png'));
		self::assertSame('absent', Editeur_Images::controler($this->fichier('', 'png')));
	}

	public function test_la_bombe_de_decompression_est_refusee_avant_le_decodage(): void
	{
		// Quelques dizaines d'octets qui se déclarent 9000 × 9000 : décodée, elle réclamerait 400 Mo.
		$ihdr  = pack('NNCCCCC', 9000, 9000, 8, 2, 0, 0, 0);
		$bombe = "\x89PNG\r\n\x1A\n" . pack('N', 13) . 'IHDR' . $ihdr . pack('N', crc32('IHDR' . $ihdr))
			. pack('N', 0) . 'IEND' . pack('N', crc32('IEND'));

		self::assertSame('dimensions', Editeur_Images::controler($this->fichier($bombe, 'png')));

		// Sous la limite de côté mais au-delà des pixels permis (5000 × 5000 = 25 Mpx).
		$ihdr  = pack('NNCCCCC', 5000, 5000, 8, 2, 0, 0, 0);
		$large = "\x89PNG\r\n\x1A\n" . pack('N', 13) . 'IHDR' . $ihdr . pack('N', crc32('IHDR' . $ihdr))
			. pack('N', 0) . 'IEND' . pack('N', crc32('IEND'));

		self::assertSame('dimensions', Editeur_Images::controler($this->fichier($large, 'png')));
	}

	public function test_un_entete_d_image_sans_image_est_illisible(): void
	{
		self::assertSame('illisible', Editeur_Images::controler($this->fichier("\xFF\xD8\xFF\xE0 rien d'autre", 'jpg')));
	}

	// ── Ce qui est écrit ─────────────────────────────────────────────────────────────────────────────

	public function test_le_reencodage_ne_garde_que_les_pixels(): void
	{
		$source = $this->image('png', 60, 40);
		file_put_contents($source, self::MARQUEUR, FILE_APPEND);

		$sortie = $this->chemin('png');
		self::assertTrue(Editeur_Images::reencoder($source, 'image/png', $sortie));
		self::assertStringNotContainsString(self::MARQUEUR, (string) file_get_contents($sortie));
		self::assertSame([60, 40, 'image/png'], [getimagesize($sortie)[0], getimagesize($sortie)[1], getimagesize($sortie)['mime']]);
	}

	public function test_les_metadonnees_exif_disparaissent_et_l_orientation_est_appliquee(): void
	{
		// Une photo « couchée » : 80 × 40 pixels, que l'EXIF demande de tourner d'un quart de tour (6).
		$jpeg = (string) file_get_contents($this->image('jpg', 80, 40));
		$exif = $this->bloc_exif(6, self::MARQUEUR);
		$photo = $this->fichier(substr($jpeg, 0, 2) . $exif . substr($jpeg, 2), 'jpg');

		self::assertSame(6, Editeur_Images::orientation_jpeg($photo));
		self::assertIsArray(Editeur_Images::controler($photo));

		$sortie = $this->chemin('jpg');
		self::assertTrue(Editeur_Images::reencoder($photo, 'image/jpeg', $sortie));
		self::assertStringNotContainsString(self::MARQUEUR, (string) file_get_contents($sortie), 'EXIF non retiré');
		self::assertStringNotContainsString('Exif', (string) file_get_contents($sortie), 'EXIF non retiré');
		self::assertSame(1, Editeur_Images::orientation_jpeg($sortie));
		self::assertSame([40, 80], array_slice((array) getimagesize($sortie), 0, 2), 'orientation non appliquée');
	}

	public function test_l_orientation_se_lit_en_gros_et_en_petit_boutiste(): void
	{
		$jpeg = (string) file_get_contents($this->image('jpg', 10, 10));

		foreach ([3, 8] as $orientation)
		{
			foreach ([TRUE, FALSE] as $intel)
			{
				$photo = $this->fichier(substr($jpeg, 0, 2) . $this->bloc_exif($orientation, '', $intel) . substr($jpeg, 2), 'jpg');
				self::assertSame($orientation, Editeur_Images::orientation_jpeg($photo));
			}
		}

		// Sans EXIF, ou pas un JPEG : rien à faire.
		self::assertSame(1, Editeur_Images::orientation_jpeg($this->image('jpg', 10, 10)));
		self::assertSame(1, Editeur_Images::orientation_jpeg($this->image('png', 10, 10)));
	}

	public function test_la_grande_image_est_reduite_sans_jamais_agrandir_la_petite(): void
	{
		$grande = $this->image('png', 3000, 1500);
		$sortie = $this->chemin('png');
		self::assertTrue(Editeur_Images::reencoder($grande, 'image/png', $sortie));
		self::assertSame([2000, 1000], array_slice((array) getimagesize($sortie), 0, 2));

		$petite = $this->image('png', 30, 20);
		$sortie = $this->chemin('png');
		self::assertTrue(Editeur_Images::reencoder($petite, 'image/png', $sortie));
		self::assertSame([30, 20], array_slice((array) getimagesize($sortie), 0, 2));
	}

	public function test_un_gif_devient_un_png_et_garde_sa_transparence(): void
	{
		$this->gd();

		$gif = imagecreate(10, 10);
		$fond = (int) imagecolorallocate($gif, 255, 0, 0);
		imagecolortransparent($gif, $fond);
		imagefilledrectangle($gif, 0, 0, 4, 9, (int) imagecolorallocate($gif, 0, 0, 255));
		$source = $this->chemin('gif');
		imagegif($gif, $source);

		$sortie = $this->chemin('png');
		self::assertTrue(Editeur_Images::reencoder($source, 'image/gif', $sortie));
		self::assertSame('image/png', getimagesize($sortie)['mime']);

		$relue = imagecreatefrompng($sortie);
		self::assertSame(127, imagecolorsforindex($relue, imagecolorat($relue, 8, 5))['alpha'], 'transparence perdue');
		self::assertSame(0, imagecolorsforindex($relue, imagecolorat($relue, 1, 5))['alpha']);
	}

	public function test_rien_n_est_ecrit_quand_l_image_ne_se_decode_pas(): void
	{
		$sortie = $this->chemin('png');
		self::assertFalse(Editeur_Images::reencoder($this->fichier("\x89PNG\r\n\x1A\nabimee", 'png'), 'image/png', $sortie));
		self::assertFileDoesNotExist($sortie);
		self::assertFalse(Editeur_Images::reencoder($this->image('png', 5, 5), 'image/svg+xml', $sortie));
	}

	public function test_le_chemin_est_aleatoire_date_et_d_extension_choisie_par_le_site(): void
	{
		$instant = (int) mktime(12, 0, 0, 10, 4, 2026);

		self::assertMatchesRegularExpression('#^upload/editeur/2026/10/[0-9a-f]{32}\.jpg$#', Editeur_Images::chemin('image/jpeg', $instant));
		self::assertMatchesRegularExpression('#^upload/editeur/2026/10/[0-9a-f]{32}\.png$#', Editeur_Images::chemin('image/gif', $instant));
		self::assertMatchesRegularExpression('#^upload/editeur/2026/10/[0-9a-f]{32}\.webp$#', Editeur_Images::chemin('image/webp', $instant));
		self::assertNotSame(Editeur_Images::chemin('image/png', $instant), Editeur_Images::chemin('image/png', $instant));
		self::assertLessThanOrEqual(100, strlen(Editeur_Images::chemin('image/webp', $instant)), 'nf_file.path : 100 caractères');

		// L'empreinte fixe le nom : la même image renvoyée par l'API retrouve le même fichier.
		$empreinte = hash('sha256', 'une image');
		self::assertSame('upload/editeur/2026/10/'.substr($empreinte, 0, 32).'.jpg', Editeur_Images::chemin('image/jpeg', $instant, $empreinte));
		self::assertSame(Editeur_Images::chemin('image/jpeg', $instant, $empreinte), Editeur_Images::chemin('image/jpeg', $instant, $empreinte));
		// Une empreinte qui n'en est pas une ne choisit rien : le nom redevient aléatoire.
		self::assertNotSame(Editeur_Images::chemin('image/png', $instant, '../x'), Editeur_Images::chemin('image/png', $instant, '../x'));
	}

	public function test_le_nom_d_origine_est_nettoye(): void
	{
		self::assertSame('capture écran 1.png', Editeur_Images::nom('capture écran 1.png', 'image/png'));
		self::assertSame('x.php.png', Editeur_Images::nom('../../x.php.png', 'image/png'));
		self::assertSame('script.jpg', Editeur_Images::nom('<script>alert(1)</script>.jpg', 'image/jpeg'));
		self::assertSame('image.webp', Editeur_Images::nom('', 'image/webp'));
		self::assertSame(100, mb_strlen(Editeur_Images::nom(str_repeat('a', 300) . '.png', 'image/png')));
	}

	// ── Le contrat avec l'assainisseur ───────────────────────────────────────────────────────────────

	public function test_l_adresse_d_une_image_envoyee_survit_a_l_assainisseur(): void
	{
		$html = '<p>Voici <img src="/upload/editeur/2026/10/0123456789abcdef0123456789abcdef.png" alt="" width="300" height="200" /></p>';

		self::assertStringContainsString('src="/upload/editeur/2026/10/0123456789abcdef0123456789abcdef.png"', sanitize_html($html));

		// Un site installé dans un sous-dossier : l'adresse porte sa base.
		self::assertStringContainsString('src="/site/upload/editeur/2026/10/a.webp"', sanitize_html('<img src="/site/upload/editeur/2026/10/a.webp" alt="" />'));
	}

	public function test_une_image_data_ou_blob_ne_survit_pas(): void
	{
		$data = sanitize_html('<p>a<img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==" alt="" /></p>');
		$blob = sanitize_html('<p>b<img src="blob:https://exemple.org/5f1c2a9e-0000-4000-8000-000000000000" alt="" /></p>');

		self::assertStringNotContainsString('data:', $data);
		self::assertStringNotContainsString('<img', $data);
		self::assertStringNotContainsString('blob:', $blob);
		self::assertStringNotContainsString('<img', $blob);
	}

	// ── Les réglages TinyMCE ─────────────────────────────────────────────────────────────────────────

	public function test_les_reglages_tinymce_envoient_et_n_ecrivent_rien_de_dangereux(): void
	{
		$js = Editeur_Images::options_tinymce(['url' => '/fr/ajax/user/editeur-image', 'jeton' => 'abc', 'refus' => '', 'echec' => 'Échec </script><script>alert(1)</script>']);

		foreach (['images_upload_handler: function', 'automatic_uploads: true', 'paste_data_images: true', 'images_reuse_filename: false', 'convert_urls: false', 'NFEditeur.televerser'] as $attendu)
		{
			self::assertStringContainsString($attendu, $js);
		}

		// Le message est une donnée JSON : il ne ferme pas le bloc <script> qui le porte.
		self::assertStringNotContainsString('</script>', $js);
		self::assertStringEndsWith(', ', $js, 'les réglages se placent en tête de l\'objet : ils finissent par une virgule');
	}

	/**
	 * La FAMILLE du défaut : un `tinymce.init` sans les réglages d'envoi laisserait une image collée
	 * cassée, puis perdue. Il y en avait cinq, écrits à cinq endroits ; un sixième ne passera pas inaperçu.
	 */
	public function test_tout_tinymce_init_du_produit_porte_les_reglages_d_envoi(): void
	{
		$racine   = dirname(__DIR__, 2);
		$trouves  = 0;
		$dossiers = ['neofrag', 'modules', 'widgets', 'themes', 'js'];

		foreach ($dossiers as $dossier)
		{
			$fichiers = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($racine . '/' . $dossier, \FilesystemIterator::SKIP_DOTS));

			foreach ($fichiers as $fichier)
			{
				$chemin = str_replace('\\', '/', (string) $fichier);

				if (!preg_match('/\.(php|js)$/', $chemin) || str_contains($chemin, '/js/tinymce/'))
				{
					continue;
				}

				$source = (string) file_get_contents($chemin);

				// Un commentaire PHP qui en parle n'est pas un éditeur.
				if (str_ends_with($chemin, '.php'))
				{
					$source = implode('', array_map(static fn ($t): string => is_array($t) ? (in_array($t[0], [T_COMMENT, T_DOC_COMMENT], TRUE) ? '' : $t[1]) : $t, token_get_all($source)));
				}

				$appels = substr_count($source, 'tinymce.init(');

				if ($appels === 0)
				{
					continue;
				}

				$trouves += $appels;
				self::assertSame($appels, substr_count($source, 'Editeur_Images::tinymce()'), substr($chemin, strlen($racine) + 1) . ' : un tinymce.init sans Editeur_Images::tinymce()');
			}
		}

		self::assertGreaterThanOrEqual(5, $trouves, 'les cinq éditeurs connus ont disparu : la recherche est-elle encore juste ?');
	}

	/** Un segment APP1 « Exif » minimal : un répertoire, une entrée Orientation, et un commentaire. */
	private function bloc_exif(int $orientation, string $commentaire, bool $intel = TRUE): string
	{
		[$court, $long] = $intel ? ['v', 'V'] : ['n', 'N'];

		$entrees = [pack($court, 0x0112) . pack($court, 3) . pack($long, 1) . pack($court, $orientation) . "\0\0"];

		if ($commentaire !== '')
		{
			// ImageDescription (0x010E), ASCII, rangée après le répertoire.
			$entrees[] = pack($court, 0x010E) . pack($court, 2) . pack($long, strlen($commentaire) + 1) . pack($long, 8 + 2 + 12 * 2 + 4);
		}

		$tiff = ($intel ? 'II' : 'MM') . pack($court, 42) . pack($long, 8)
			. pack($court, count($entrees)) . implode('', $entrees) . pack($long, 0)
			. ($commentaire !== '' ? $commentaire . "\0" : '');

		$app1 = "Exif\0\0" . $tiff;

		return "\xFF\xE1" . pack('n', strlen($app1) + 2) . $app1;
	}
}
