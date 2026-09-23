<?php
declare(strict_types=1);

namespace NF\Tests\Unit;

use PHPUnit\Framework\TestCase;
use NF\NeoFrag\Libraries\File_Jail;

require_once __DIR__ . '/../../neofrag/libraries/file_jail.php';

/**
 * Sécurité du gestionnaire de fichiers webmaster : le jail ne doit JAMAIS laisser résoudre un chemin
 * hors de la racine (`..`, chemin absolu), et les zones protégées (config/, logs/, backups/) doivent
 * être refusées en lecture comme en écriture.
 */
final class FileJailTest extends TestCase
{
	private string $root;

	protected function setUp(): void
	{
		$this->root = sys_get_temp_dir() . '/nf_jail_' . bin2hex(random_bytes(6));
		mkdir($this->root . '/themes/vitrine', 0777, true);
		mkdir($this->root . '/config', 0777, true);
		mkdir($this->root . '/logs', 0777, true);
		file_put_contents($this->root . '/themes/vitrine/style.css', 'body{}');
		file_put_contents($this->root . '/config/db.php', '<?php $secret=1;');
		file_put_contents($this->root . '/index.php', '<?php');
	}

	protected function tearDown(): void
	{
		/*
		 * L'ancienne version faisait `glob($root.'/**' . '/*')` puis `unlink()` : ce motif ne
		 * descend que d'un niveau et ne rend aucun DOSSIER. Les trois arborescences creees par
		 * `setUp()` et la racine elle-meme survivaient donc a chaque test.
		 *
		 * Le 2026-09-21, l'atelier portait 1 043 dossiers `nf_jail_*` abandonnes dans /tmp. Ils
		 * ne pesaient pas grand-chose, mais ils ont contribue a remplir un tmpfs de 2 Go — et un
		 * /tmp plein fait echouer PHPStan sans un mot : il rend zero ligne, ce qui se lit comme
		 * un verdict vert.
		 */
		self::effacer($this->root);
	}

	/** Supprime une arborescence entiere : les fichiers d'abord, les dossiers a la remontee. */
	private static function effacer(string $chemin): void
	{
		if (!is_dir($chemin))
		{
			@unlink($chemin);
			return;
		}

		foreach (array_diff(scandir($chemin) ?: [], ['.', '..']) as $entree)
		{
			self::effacer($chemin . '/' . $entree);
		}

		@rmdir($chemin);
	}

	public function test_resolves_a_legit_path_inside_the_root(): void
	{
		$abs = File_Jail::resolve($this->root, 'themes/vitrine/style.css');
		$this->assertNotNull($abs);
		$this->assertStringEndsWith('/themes/vitrine/style.css', $abs);
	}

	public function test_allows_a_new_file_when_its_parent_is_inside_the_jail(): void
	{
		$abs = File_Jail::resolve($this->root, 'themes/vitrine/new.css');
		$this->assertNotNull($abs); // n'existe pas encore, mais le parent est dans le jail
	}

	public function test_rejects_parent_traversal(): void
	{
		$this->assertNull(File_Jail::resolve($this->root, '../../../etc/passwd'));
		$this->assertNull(File_Jail::resolve($this->root, 'themes/../../escape.txt'));
	}

	public function test_rejects_absolute_paths(): void
	{
		$this->assertNull(File_Jail::resolve($this->root, '/etc/passwd'));
	}

	public function test_protected_zones_are_refused(): void
	{
		$protected = ['config', 'logs', 'backups'];
		$cfg = File_Jail::resolve($this->root, 'config/db.php');
		$this->assertNotNull($cfg);
		$this->assertTrue(File_Jail::is_protected($this->root, $cfg, $protected), 'config/ doit être protégé');

		$css = File_Jail::resolve($this->root, 'themes/vitrine/style.css');
		$this->assertFalse(File_Jail::is_protected($this->root, $css, $protected), 'themes/ ne doit pas être protégé');
	}

	public function test_binary_detection(): void
	{
		$this->assertTrue(File_Jail::is_binary("PNG\0\0data"));
		$this->assertFalse(File_Jail::is_binary("<?php echo 1; ?>\nplain text"));
	}

	public function test_editor_mode_by_extension(): void
	{
		$this->assertSame('application/x-httpd-php', File_Jail::editor_mode('a.php'));
		$this->assertSame('application/x-httpd-php', File_Jail::editor_mode('header.tpl.php'));
		$this->assertSame('css', File_Jail::editor_mode('style.css'));
		$this->assertSame('javascript', File_Jail::editor_mode('app.js'));
		$this->assertSame('sql', File_Jail::editor_mode('dump.sql'));
		$this->assertSame('markdown', File_Jail::editor_mode('README.md'));
		$this->assertSame('text/plain', File_Jail::editor_mode('data.bin'));
	}
}
