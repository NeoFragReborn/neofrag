<?php
declare(strict_types=1);

namespace NF\Tests\Unit;

use PHPUnit\Framework\TestCase;
use NF\NeoFrag\Installer;

require_once __DIR__ . '/../../neofrag/installer.php';

/**
 * Tests UNITAIRES de l'installeur : uniquement les fonctions pures (pas de base).
 * Le flux DB complet (import schéma, migrations, admin) est couvert par
 * tests/Integration/InstallerDbTest.php.
 */
final class InstallerTest extends TestCase
{
	private string $tmp;

	protected function setUp(): void
	{
		$this->tmp = sys_get_temp_dir() . '/nf_install_unit_' . bin2hex(random_bytes(6));
		mkdir($this->tmp, 0775, true);
	}

	protected function tearDown(): void
	{
		foreach (glob($this->tmp . '/*') ?: [] as $f)
		{
			@unlink($f);
		}
		@rmdir($this->tmp);
	}

	public function testRandomSecretLengthCharsetAndUniqueness(): void
	{
		$a = Installer::random_secret(99);
		$b = Installer::random_secret(99);

		$this->assertGreaterThanOrEqual(120, strlen($a), 'Le secret doit être assez long');
		$this->assertMatchesRegularExpression('/^[A-Za-z0-9]+$/', $a, 'Pas de quote/backslash/+/= à échapper');
		$this->assertNotSame($a, $b, 'Deux secrets consécutifs doivent différer');
	}

	public function testWriteConfigProducesValidLoadableFiles(): void
	{
		$cfg = ['hostname' => 'db', 'username' => 'neofrag', 'password' => "p'a\\ss", 'database' => 'neofrag', 'port' => 3306];

		Installer::write_config($this->tmp, $cfg);

		$this->assertFileExists($this->tmp . '/db.php');
		$this->assertFileExists($this->tmp . '/crypt.php');
		$this->assertFileExists($this->tmp . '/password.php');

		// db.php se recharge et reflète la config (var_export échappe le mot de passe).
		$db = [];
		require $this->tmp . '/db.php';
		$this->assertSame('db', $db[0]['hostname']);
		$this->assertSame("p'a\\ss", $db[0]['password'], 'Le mot de passe à caractères spéciaux doit être préservé');
		$this->assertSame('mysqli', $db[0]['driver']);

		$crypt = [];
		require $this->tmp . '/crypt.php';
		$this->assertNotEmpty($crypt['key']);

		$password = [];
		require $this->tmp . '/password.php';
		$this->assertNotEmpty($password['salt']);
	}

	public function testWriteConfigRegeneratesRandomSecretsEachTime(): void
	{
		$cfg = ['hostname' => 'db', 'username' => 'u', 'password' => '', 'database' => 'd'];

		Installer::write_config($this->tmp, $cfg);
		$key1 = file_get_contents($this->tmp . '/crypt.php');

		Installer::write_config($this->tmp, $cfg);
		$key2 = file_get_contents($this->tmp . '/crypt.php');

		$this->assertNotSame($key1, $key2, 'La clé crypt doit être régénérée à chaque écriture');
	}

	public function testReadDbConfigReturnsNullWhenAbsentAndArrayWhenPresent(): void
	{
		$this->assertNull(Installer::read_db_config($this->tmp), 'Aucun db.php → NULL');

		Installer::write_config($this->tmp, ['hostname' => 'h', 'username' => 'u', 'password' => 'p', 'database' => 'd']);
		$cfg = Installer::read_db_config($this->tmp);

		$this->assertIsArray($cfg);
		$this->assertSame('h', $cfg['hostname']);
	}

	public function testTestDbReturnsErrorOnUnreachableServer(): void
	{
		// Loopback port 1 : connexion refusée immédiatement (pas de timeout réseau).
		$result = Installer::test_db(['hostname' => '127.0.0.1', 'username' => 'x', 'password' => 'y', 'database' => '', 'port' => 1]);

		$this->assertFalse($result['ok']);
		$this->assertNotNull($result['error']);
	}

	public function testIsAlreadyInstalledFalseWithoutConfig(): void
	{
		$this->assertFalse(Installer::is_already_installed($this->tmp), 'Pas de config/db.php → non installé');
	}
}
