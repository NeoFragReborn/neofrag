<?php
declare(strict_types=1);

namespace NF\Tests\Unit;

use PHPUnit\Framework\TestCase;
use NF\Install\Lib\Installer;
use ZipArchive;
use RuntimeException;

require_once __DIR__ . '/../../install/lib/installer.php';

/**
 * Garde-fous SÉCURITÉ du marketplace distant (spec §7), sans réseau ni base : les rejets
 * (anti-zip-slip, HTTPS strict, chemin de fichier sûr) se produisent AVANT tout I/O.
 * Le chemin nominal (download + SHA-256 + install réel) est couvert en e2e contre le domaine.
 */
final class MarketplaceSecurityTest extends TestCase
{
	/** Construit un zip jetable avec des noms d'entrées arbitraires (y compris malveillants). */
	private function makeZip(array $entries): string
	{
		$path = tempnam(sys_get_temp_dir(), 'nfz');
		$zip  = new ZipArchive();
		$zip->open($path, ZipArchive::OVERWRITE);
		foreach ($entries as $name => $content)
		{
			$zip->addFromString($name, $content);
		}
		$zip->close();

		return $path;
	}

	private function zipIsSafe(array $entries): bool
	{
		$path = $this->makeZip($entries);
		$zip  = new ZipArchive();
		$zip->open($path);
		$safe = Installer::zip_entries_safe($zip);
		$zip->close();
		@unlink($path);

		return $safe;
	}

	public function testZipSafeAcceptsNormalAddon(): void
	{
		$this->assertTrue($this->zipIsSafe([
			'wiki/wiki.php'              => '<?php',
			'wiki/install/install.sql'   => 'CREATE TABLE x (id INT);',
		]));
	}

	public function testZipSafeRejectsParentTraversal(): void
	{
		$this->assertFalse($this->zipIsSafe(['../evil.php' => 'x']));
		$this->assertFalse($this->zipIsSafe(['foo/../../etc/passwd' => 'x']));
	}

	public function testZipSafeRejectsAbsolutePath(): void
	{
		$this->assertFalse($this->zipIsSafe(['/etc/passwd' => 'x']));
	}

	public function testZipSafeRejectsSymlinkEntry(): void
	{
		// Entrée au nom valide (sous wiki/) MAIS marquée symlink Unix → doit être rejetée (pointerait
		// hors de l'addon à l'extraction).
		$path = tempnam(sys_get_temp_dir(), 'nfz');
		$zip  = new ZipArchive();
		$zip->open($path, ZipArchive::OVERWRITE);
		$zip->addFromString('wiki/evil', '../../config/db.php');
		$zip->setExternalAttributesIndex(0, ZipArchive::OPSYS_UNIX, (0xA000 | 0777) << 16); // S_IFLNK
		$zip->close();

		$check = new ZipArchive();
		$check->open($path);
		$safe = Installer::zip_entries_safe($check);
		$check->close();
		@unlink($path);

		$this->assertFalse($safe, 'Une entrée symlink doit être rejetée');
	}

	public function testDownloadRejectsNonHttps(): void
	{
		// file valide → on dépasse la validation des métadonnées ; http_get rejette http:// (schéma)
		// AVANT toute connexion réseau.
		$this->expectException(RuntimeException::class);
		Installer::download_and_extract(
			['type' => 'module', 'name' => 'wiki', 'file' => 'modules/wiki.zip', 'sha256' => 'deadbeef'],
			'http://example.test/marketplace',
			sys_get_temp_dir()
		);
	}

	public function testDownloadRejectsUnsafeFilePath(): void
	{
		$this->expectException(RuntimeException::class);
		Installer::download_and_extract(
			['type' => 'module', 'name' => 'wiki', 'file' => '../../etc/passwd', 'sha256' => 'deadbeef'],
			'https://example.test/marketplace',
			sys_get_temp_dir()
		);
	}

	public function testDownloadRejectsInvalidType(): void
	{
		$this->expectException(RuntimeException::class);
		Installer::download_and_extract(
			['type' => 'evil', 'name' => 'wiki', 'file' => 'modules/wiki.zip', 'sha256' => 'deadbeef'],
			'https://example.test/marketplace',
			sys_get_temp_dir()
		);
	}

	public function testMarketplaceUrlDefaultsToHttps(): void
	{
		$this->assertStringStartsWith('https://', Installer::marketplace_url());
	}
}
