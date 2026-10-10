<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * nf_texte_et_images() (neofrag/helpers/markdown.php, m10, 2026-10-10) : le texte simple d'un ticket du Bugtracker reste
 * du texte, sauf la marque d'une image que le site garde (`![nom](/upload/editeur/…)`), où le bot range l'image jointe
 * sur Discord. Rien d'autre ne devient du HTML.
 */
final class TexteEtImagesTest extends TestCase
{
	protected function setUp(): void
	{
		require_once __DIR__.'/../../neofrag/helpers/markdown.php';
	}

	public function test_l_image_gardee_par_le_site_s_affiche(): void
	{
		$html = nf_texte_et_images("Le bouton déborde :\n\n![capture.png](/upload/editeur/2026/10/ab12cd.png)", '/');

		$this->assertStringContainsString('<img src="/upload/editeur/2026/10/ab12cd.png" alt="capture.png"', $html);
		$this->assertStringContainsString('Le bouton déborde :<br />', $html);
	}

	public function test_un_site_installe_dans_un_dossier_garde_son_dossier(): void
	{
		$this->assertStringContainsString('<img src="/club/upload/editeur/x.webp"', nf_texte_et_images('![x](/club/upload/editeur/x.webp)', '/club/'));
		$this->assertStringNotContainsString('<img', nf_texte_et_images('![x](/upload/editeur/x.webp)', '/club/'), 'hors du dossier du site');
	}

	public function test_une_image_d_ailleurs_reste_du_texte(): void
	{
		foreach (['![x](https://exemple.fr/piege.png)', '![x](/upload/forum/x.png)', '![x](/upload/editeur/x.svg)', '![x](/upload/editeur/../config/db.php)', '![x](/upload/editeur/../../etc/x.png)', '![x](javascript:alert(1))'] as $texte)
		{
			$this->assertStringNotContainsString('<img', nf_texte_et_images($texte, '/'), $texte);
		}
	}

	public function test_le_texte_reste_echappe(): void
	{
		$html = nf_texte_et_images('<script>alert(1)</script> ![a"b<c](/upload/editeur/x.png)', '/');

		$this->assertStringNotContainsString('<script>', $html);
		$this->assertStringContainsString('alt="a&quot;b&lt;c"', $html, 'le nom de l’image, échappé');
	}
}
