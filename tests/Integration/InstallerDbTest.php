<?php
declare(strict_types=1);

namespace NF\Tests\Integration;

use PHPUnit\Framework\TestCase;
use NF\Install\Lib\Installer;
use mysqli;

require_once __DIR__ . '/../../install/lib/installer.php';

/**
 * Test d'INTÉGRATION de l'installeur sur une base JETABLE.
 *
 * N'étend PAS IntegrationTestCase : l'installeur fait du DDL (CREATE TABLE), or ce
 * socle interdit la DDL (transaction rollback sur la base partagée). On crée donc
 * une base dédiée éphémère via root, on y déroule l'install, puis on la DROP. Skip
 * si root est injoignable (ex. exécution hors conteneur).
 *
 * La mécanique de migrations (baseline / up / idempotence) est validée sur un
 * répertoire de migrations FACTICE — déterministe et indépendant de l'état réel de
 * schema.sql (qui est désynchronisé des migrations, à régénérer séparément).
 *
 *   docker compose exec web composer test:integration
 */
final class InstallerDbTest extends TestCase
{
	private const DBNAME = 'neofrag_install_test';

	private static ?mysqli $root = null;
	private static array $cfg = [];

	public static function setUpBeforeClass(): void
	{
		mysqli_report(MYSQLI_REPORT_OFF);

		$host = getenv('NF_TEST_DB_HOST') ?: 'db';
		$user = getenv('NF_TEST_DB_ROOT_USER') ?: 'root';
		$pass = getenv('NF_TEST_DB_ROOT_PASS') ?: 'rootpass';
		$port = (int) (getenv('NF_TEST_DB_PORT') ?: 3306);

		$root = @new mysqli($host, $user, $pass, '', $port);
		if ($root->connect_errno)
		{
			self::markTestSkipped('Root MySQL injoignable (' . $host . ') : ' . $root->connect_error);
		}
		$root->set_charset('utf8mb4');
		$root->query('DROP DATABASE IF EXISTS `' . self::DBNAME . '`');

		self::$root = $root;
		self::$cfg  = [
			'hostname' => $host,
			'username' => $user,
			'password' => $pass,
			'database' => self::DBNAME,
			'port'     => $port,
		];
	}

	public static function tearDownAfterClass(): void
	{
		if (self::$root)
		{
			foreach ([self::DBNAME, 'neofrag_install_test_simple', 'neofrag_install_test_gaming', 'neofrag_install_test_mkt'] as $db)
			{
				self::$root->query('DROP DATABASE IF EXISTS `' . $db . '`');
			}
			self::$root->close();
			self::$root = null;
		}
	}

	public function testFullInstallFlowOnBlankDatabase(): void
	{
		$schema     = __DIR__ . '/../../install/schema.sql';
		$seed       = __DIR__ . '/../../install/seed.sql';
		$migrations = __DIR__ . '/../../migrations';

		// 1. Connexion serveur (sans base).
		$test = Installer::test_db(self::$cfg);
		$this->assertTrue($test['ok'], 'Connexion serveur : ' . ($test['error'] ?? ''));
		$this->assertNotEmpty($test['server']);

		// 2. Création de la base + connexion.
		Installer::ensure_database(self::$root, self::DBNAME);
		$db = Installer::connect(self::$cfg);

		// 3. Garde « déjà installé » : base vide → non installé.
		$conf = $this->tempDir('flow');
		Installer::write_config($conf, self::$cfg);
		$this->assertFalse(Installer::is_already_installed($conf), 'Base sans tables → non installé');

		// 4. Import du schéma → toutes les tables + historique des migrations.
		Installer::import_sql_file($db, $schema);
		foreach (['nf_user', 'nf_user_profile', 'nf_groups', 'nf_settings', 'nf_addon', 'nf_reactions', 'nf_notifications'] as $table)
		{
			$this->assertSame(1, $this->tableExists($db, $table), "Table {$table} manquante après import");
		}
		// Cœur LEAN : le schéma ne crée QUE les tables Tier 0 (≈ 52) — pas les tables des modules
		// Tier 1/2 (apportées par leur install/install.sql à l'install d'un preset / depuis le marketplace).
		$this->assertGreaterThanOrEqual(45, $this->tableCount($db), 'Le schéma cœur doit créer les tables Tier 0');
		$this->assertSame(0, $this->tableExists($db, 'nf_news'),  'Lean : nf_news (Tier 1) ne doit PAS être dans le schéma cœur');
		$this->assertSame(0, $this->tableExists($db, 'nf_shop_items'), 'Lean : nf_shop_items (Tier 2) ne doit PAS être dans le schéma cœur');

		$discovered = count(Installer::discover_migrations($migrations));
		$tracked    = (int) $this->scalar($db, 'SELECT COUNT(*) FROM nf_migrations');
		$this->assertSame($discovered, $tracked, 'schema.sql doit embarquer l\'historique complet de nf_migrations');

		// 5. Import du seed de configuration → site exploitable (addons, settings, etc.).
		Installer::import_sql_file($db, $seed);
		$this->assertGreaterThan(0, (int) $this->scalar($db, "SELECT COUNT(*) FROM nf_settings WHERE name = 'nf_default_theme'"), 'Le seed doit fixer le thème par défaut');
		$this->assertGreaterThan(0, (int) $this->scalar($db, 'SELECT COUNT(*) FROM nf_addon'), 'Le seed doit installer les addons');
		$this->assertGreaterThan(0, (int) $this->scalar($db, 'SELECT COUNT(*) FROM nf_widgets'), 'Le seed doit placer les widgets');
		$this->assertGreaterThanOrEqual(2, (int) $this->scalar($db, 'SELECT COUNT(*) FROM nf_groups'), 'Le seed doit créer les groupes');
		// Aucun secret ne doit subsister dans le seed (valeurs sensibles neutralisées).
		$this->assertSame(0, (int) $this->scalar($db, "SELECT COUNT(*) FROM nf_settings WHERE name LIKE '%_key' AND value <> ''"), 'Les clés/secrets doivent être neutralisés');

		// 6. Migrations en mode up-only : l'historique est déjà importé → rien à rejouer.
		$noop = Installer::run_migrations($db, $migrations, null);
		$this->assertSame([], $noop['baselined']);
		$this->assertSame([], $noop['applied'], 'Une install fraîche depuis schema.sql ne doit rejouer aucune migration');

		// 7. Compte admin : super-admin (flag '1'), mot de passe argon2id vérifiable.
		$id = Installer::create_admin($db, [
			'username'   => 'admin',
			'password'   => 'S3cret-Install!',
			'email'      => 'admin@example.test',
			'first_name' => 'Site',
			'last_name'  => 'Admin',
		]);
		$this->assertGreaterThan(0, $id);

		$row = $this->row($db, 'SELECT username, password, salt, admin FROM nf_user WHERE id = ' . $id);
		$this->assertSame('admin', $row['username']);
		$this->assertSame('1', $row['admin'], 'Le compte doit être super-admin');
		$this->assertSame('', $row['salt'], 'Format moderne : salt vide');
		$this->assertStringStartsWith('$argon2', $row['password'], 'Hash argon2id attendu');
		$this->assertTrue(password_verify('S3cret-Install!', $row['password']), 'Le hash doit vérifier le mot de passe');
		$this->assertSame($id, (int) $this->scalar($db, 'SELECT id FROM nf_user_profile WHERE id = ' . $id));

		// 8. Garde « déjà installé » : un admin existe → installé.
		$this->assertTrue(Installer::is_already_installed($conf), 'Admin présent → installé');

		// 9. Mécanique des migrations sur un répertoire FACTICE (déterministe).
		$this->assertMigrationMechanics($db);

		$this->rmTree($conf);
		$db->close();
	}

	/**
	 * Étape installeur « Modules » : applique un preset sur un cœur lean et vérifie que les
	 * modules/widgets Tier 1 retenus sont enregistrés + leurs tables créées, la page d'accueil
	 * sûre fixée, et (Site simple) la page « Bienvenue » publique générée.
	 */
	public function testPresetApplicationOnLeanCore(): void
	{
		$root = dirname(__DIR__, 2);

		// Garde-fou : aucun preset ne référence du cœur ou du Tier 2.
		Installer::assert_presets_in_manifest($root);

		// — Site simple : news + page d'accueil « Bienvenue » —
		$db = $this->freshLeanDb('neofrag_install_test_simple', $root);
		$r  = Installer::apply_preset($db, $root, 'simple', ['news']);

		$this->assertSame([], $r['errors'], 'apply_preset(simple) ne doit pas faillir');
		$this->assertSame('pages/bienvenue', $r['default_page']);
		$this->assertSame(1, $this->tableExists($db, 'nf_news'), 'Le preset installe la table nf_news');
		$this->assertGreaterThan(0, (int) $this->scalar($db, "SELECT COUNT(*) FROM nf_addon a JOIN nf_addon_type t ON a.type_id = t.id WHERE t.name = 'module' AND a.name = 'news'"), 'news enregistré comme module');
		$this->assertGreaterThan(0, (int) $this->scalar($db, "SELECT COUNT(*) FROM nf_addon a JOIN nf_addon_type t ON a.type_id = t.id WHERE t.name = 'widget' AND a.name = 'news'"), 'widget news enregistré');
		$this->assertGreaterThan(0, (int) $this->scalar($db, "SELECT COUNT(*) FROM nf_pages WHERE name = 'bienvenue'"), 'Page Bienvenue créée');
		$this->assertGreaterThanOrEqual(2, (int) $this->scalar($db, "SELECT COUNT(*) FROM nf_role_permissions WHERE permission = 'pages.access_page'"), 'Accès public à la page Bienvenue (member + visitor)');
		$this->assertSame('pages/bienvenue', $this->scalar($db, "SELECT value FROM nf_settings WHERE name = 'nf_default_page'"));
		$this->assertSame(0, $this->tableExists($db, 'nf_forum'), 'Site simple n\'installe pas le forum');
		$db->close();
		self::$root->query('DROP DATABASE IF EXISTS `neofrag_install_test_simple`');

		// — Gaming : tout le Tier 1 (page d'accueil = news) —
		$db = $this->freshLeanDb('neofrag_install_test_gaming', $root);
		$r  = Installer::apply_preset($db, $root, 'gaming', ['news', 'forum', 'gallery', 'teams', 'events', 'calendar', 'awards', 'recruits', 'games', 'partners', 'gamification']);

		$this->assertSame([], $r['errors'], 'apply_preset(gaming) ne doit pas faillir');
		$this->assertSame('news', $r['default_page']);
		foreach (['nf_news', 'nf_forum', 'nf_teams', 'nf_events', 'nf_awards', 'nf_partners'] as $t)
		{
			$this->assertSame(1, $this->tableExists($db, $t), "Gaming installe {$t}");
		}
		$this->assertGreaterThan(0, (int) $this->scalar($db, "SELECT COUNT(*) FROM nf_addon a JOIN nf_addon_type t ON a.type_id = t.id WHERE t.name = 'widget' AND a.name = 'about'"), 'widget about (apparié à teams) enregistré');
		$db->close();
		self::$root->query('DROP DATABASE IF EXISTS `neofrag_install_test_gaming`');
	}

	/**
	 * Anti-SSRF : nf_marketplace_url ne surcharge le défaut QUE vers un hôte autorisé, en HTTPS,
	 * port 443, sans userinfo. Une valeur injectée (interne / userinfo / http) retombe sur le défaut.
	 */
	public function testMarketplaceUrlRejectsMaliciousOverride(): void
	{
		$root = dirname(__DIR__, 2);
		$db   = $this->freshLeanDb('neofrag_install_test_mkt', $root);

		$default = Installer::MARKETPLACE_URL_DEFAULT;
		$set = function (string $v) use ($db): void {
			Installer::set_setting($db, 'nf_marketplace_url', $v);
		};

		// Valeurs malveillantes → on retombe sur le défaut.
		foreach ([
			'https://attacker.example/marketplace',            // hôte non autorisé
			'https://www.neofrag-reborn.xyz@127.0.0.1/x',      // userinfo → host réel = 127.0.0.1
			'http://www.neofrag-reborn.xyz/marketplace',       // downgrade http
			'https://www.neofrag-reborn.xyz:8443/marketplace', // port non standard
			'file:///etc/passwd',                              // schéma non https
		] as $bad) {
			$set($bad);
			$this->assertSame($default, Installer::marketplace_url($db), "Override refusé : {$bad}");
		}

		// Override légitime (hôte autorisé, https, 443) → accepté.
		$set('https://neofrag-reborn.xyz/marketplace/');
		$this->assertSame('https://neofrag-reborn.xyz/marketplace', Installer::marketplace_url($db), 'Override légitime accepté (sans slash final)');

		$db->close();
		self::$root->query('DROP DATABASE IF EXISTS `neofrag_install_test_mkt`');
	}

	/** Crée une base éphémère avec le cœur lean importé (schema + seed + migrations up-only). */
	private function freshLeanDb(string $name, string $root): mysqli
	{
		self::$root->query('DROP DATABASE IF EXISTS `' . $name . '`');
		Installer::ensure_database(self::$root, $name);

		$cfg = array_merge(self::$cfg, ['database' => $name]);
		$db  = Installer::connect($cfg);
		Installer::import_sql_file($db, $root . '/install/schema.sql');
		Installer::import_sql_file($db, $root . '/install/seed.sql');
		Installer::run_migrations($db, $root . '/migrations', null);

		return $db;
	}

	/**
	 * Valide baseline (marque sans exécuter), up (exécute), up-only (NULL) et
	 * idempotence, sur deux puis trois migrations jetables.
	 */
	private function assertMigrationMechanics(mysqli $db): void
	{
		$dir = $this->tempDir('mig');
		file_put_contents($dir . '/2020_01_01_alpha.up.sql', 'CREATE TABLE nf_it_alpha (id INT);');
		file_put_contents($dir . '/2020_01_02_beta.up.sql',  'CREATE TABLE nf_it_beta (id INT);');

		// baseline ≤ alpha (non exécuté), up le reste (beta exécuté). La borne est un
		// nom de migration complet (cf. `migrate baseline --until=NAME`).
		$r = Installer::run_migrations($db, $dir, '2020_01_01_alpha');
		$this->assertSame(['2020_01_01_alpha'], $r['baselined']);
		$this->assertSame(['2020_01_02_beta'], $r['applied']);
		$this->assertSame(0, $this->tableExists($db, 'nf_it_alpha'), 'baseline ne doit PAS exécuter le SQL');
		$this->assertSame(1, $this->tableExists($db, 'nf_it_beta'),  'up doit exécuter le SQL');
		$this->assertSame(2, (int) $this->scalar($db, "SELECT COUNT(*) FROM nf_migrations WHERE name LIKE '2020_01_%'"));

		// Idempotence : rien à refaire.
		$again = Installer::run_migrations($db, $dir, '2020_01_01_alpha');
		$this->assertSame([], $again['baselined']);
		$this->assertSame([], $again['applied']);

		// Mode up-only (NULL) : une nouvelle migration est appliquée, jamais baseline.
		file_put_contents($dir . '/2020_01_03_gamma.up.sql', 'CREATE TABLE nf_it_gamma (id INT);');
		$up = Installer::run_migrations($db, $dir, null);
		$this->assertSame([], $up['baselined'], 'En mode up-only, aucun baseline');
		$this->assertSame(['2020_01_03_gamma'], $up['applied']);
		$this->assertSame(1, $this->tableExists($db, 'nf_it_gamma'));

		$db->query('DROP TABLE IF EXISTS nf_it_beta, nf_it_gamma');
		$this->rmTree($dir);
	}

	// === Helpers ===============================================================

	private function tempDir(string $prefix): string
	{
		$dir = sys_get_temp_dir() . '/nf_install_' . $prefix . '_' . bin2hex(random_bytes(6));
		mkdir($dir, 0775, true);
		return $dir;
	}

	private function rmTree(string $dir): void
	{
		foreach (glob($dir . '/*') ?: [] as $f)
		{
			@unlink($f);
		}
		@rmdir($dir);
	}

	private function tableExists(mysqli $db, string $table): int
	{
		$res = $db->query("SHOW TABLES LIKE '" . $db->real_escape_string($table) . "'");
		return $res ? $res->num_rows : 0;
	}

	private function tableCount(mysqli $db): int
	{
		$res = $db->query('SHOW TABLES');
		return $res ? $res->num_rows : 0;
	}

	private function scalar(mysqli $db, string $sql)
	{
		$res = $db->query($sql);
		$row = $res ? $res->fetch_row() : null;
		return $row ? $row[0] : null;
	}

	private function row(mysqli $db, string $sql): array
	{
		$res = $db->query($sql);
		return ($res && ($row = $res->fetch_assoc())) ? $row : [];
	}
}
