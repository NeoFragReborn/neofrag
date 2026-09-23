<?php
declare(strict_types=1);

namespace NF\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Sécurité upload : image_normalize() ré-encode l'image via GD et doit purger tout octet qui n'est
 * pas de l'image (le mécanisme exact qui neutralise un faux .png/.jpg cachant des données ajoutées),
 * tout en réduisant à la boîte cible (borne le stockage) sans jamais agrandir. image_normalize est
 * chargé par le bootstrap (neofrag/helpers/file.php). Test sauté si l'extension GD est indisponible.
 *
 * NB : on colle un marqueur INERTE (pas de code) en fin de fichier — il suffit d'octets arbitraires
 * pour prouver qu'ils disparaissent au ré-encodage ; inutile (et indésirable) d'embarquer une vraie
 * signature de webshell dans le dépôt.
 */
final class ImageNormalizeTest extends TestCase
{
	private const MARKER = 'NF_TRAILING_BYTES_C0FFEE';

	protected function setUp(): void
	{
		if (!extension_loaded('gd'))
		{
			$this->markTestSkipped('Extension GD indisponible');
		}
	}

	private function png(int $w, int $h): string
	{
		$path = sys_get_temp_dir() . '/nf_img_' . bin2hex(random_bytes(6)) . '.png';
		$im   = imagecreatetruecolor($w, $h);
		imagefill($im, 0, 0, imagecolorallocate($im, 10, 120, 200));
		imagepng($im, $path);

		return $path;
	}

	public function test_reencode_strips_appended_bytes_and_downscales(): void
	{
		$file = $this->png(600, 600);
		file_put_contents($file, self::MARKER, FILE_APPEND);

		// Le fichier reste un PNG valide (magic-bytes OK) ET porte des octets ajoutés en fin.
		$this->assertNotFalse(getimagesize($file));
		$this->assertStringContainsString(self::MARKER, file_get_contents($file));

		$this->assertTrue(image_normalize($file, 250, 250));

		$this->assertStringNotContainsString(self::MARKER, file_get_contents($file), 'octets ajoutés non purgés');
		[$w, $h] = getimagesize($file);
		$this->assertSame(250, $w);
		$this->assertSame(250, $h);

		@unlink($file);
	}

	public function test_fits_a_free_form_image_within_the_box_keeping_ratio(): void
	{
		// Cas galerie : image libre (non carrée) bornée à une boîte max, ratio préservé, octets purgés.
		$file = $this->png(3000, 1000);
		file_put_contents($file, self::MARKER, FILE_APPEND);

		$this->assertTrue(image_normalize($file, 1600, 1600));

		$this->assertStringNotContainsString(self::MARKER, file_get_contents($file));
		[$w, $h] = getimagesize($file);
		$this->assertSame(1600, $w);
		$this->assertSame(533, $h); // 3000x1000 -> tient dans 1600 de large, ratio 3:1 conservé

		@unlink($file);
	}

	public function test_does_not_upscale_a_small_image(): void
	{
		$file = $this->png(100, 100);

		image_normalize($file, 250, 250);

		[$w, $h] = getimagesize($file);
		$this->assertSame(100, $w);
		$this->assertSame(100, $h);

		@unlink($file);
	}
}
