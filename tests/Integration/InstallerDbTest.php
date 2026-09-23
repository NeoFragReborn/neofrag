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

	/*
	 * Pourquoi le saut n'est pas prononcé ici. Un markTestSkipped() dans setUpBeforeClass() saute la
	 * classe entière, et PHPUnit 11 le compte comme une SUITE sautée, pas comme des tests sautés :
	 * `--fail-on-skipped` n'y voit rien. La CI a affiché « OK, but some tests were skipped! Tests: 504,
	 * Skipped: 127 » avec un code de sortie zéro, pendant des semaines. On mémorise donc la raison, et
	 * c'est setUp() qui saute, test par test — ce que le drapeau transforme bien en échec.
	 */
	private static ?string $indisponible = null;

	public static function setUpBeforeClass(): void
	{
		self::$indisponible = null;

		if (!extension_loaded('mysqli'))
		{
			self::$indisponible = 'Extension mysqli non chargée (test d\'intégration DB ignoré).';

			return;
		}

		mysqli_report(MYSQLI_REPORT_OFF);

		// Même défaut que IntegrationTestCase : « db » était le nom du service Docker de l'ancienne CI, et
		// sur toute autre machine la classe se sautait en silence — quatre tests de moins, verdict « OK ».
		/*
		 * Trois sources, comme les autres suites d'intégration : l'environnement, puis
		 * `config/db-test.php` qu'écrit `tools/prepare-test-db.php`, puis les valeurs par défaut.
		 *
		 * Ce test-ci est le seul à exiger un compte capable de CRÉER et de SUPPRIMER une base : il
		 * déroule une installation complète dans une base éphémère. Il réclamait `root` avec le mot
		 * de passe `rootpass` — ceux de l'ancienne pile Docker, disparue. Sur une MariaDB de
		 * serveur, `root` s'authentifie par la socket Unix et refuse le TCP : ce test ne pouvait
		 * donc réussir nulle part ailleurs que dans ce conteneur, et se sautait en silence.
		 */
		$local = [];

		if (is_file($fichier = dirname(__DIR__, 2).'/config/db-test.php'))
		{
			$local = (array) (require $fichier);
		}

		$reglage = static function (string $cle, string $defaut) use ($local): string {
			return (string) (getenv('NF_TEST_DB_'.strtoupper($cle)) ?: ($local[$cle] ?? $defaut));
		};

		$host = $reglage('host', '127.0.0.1');
		$user = $reglage('root_user', 'root');
		$pass = $reglage('root_pass', 'rootpass');
		$port = (int) $reglage('port', '3306');

		$root = @new mysqli($host, $user, $pass, '', $port);
		if ($root->connect_errno)
		{
			self::$indisponible = 'Root MySQL injoignable (' . $host . ') : ' . $root->connect_error;

			return;
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

	protected function setUp(): void
	{
		if (self::$indisponible !== null)
		{
			self::markTestSkipped(self::$indisponible);
		}
	}

	public static function tearDownAfterClass(): void
	{
		if (self::$root)
		{
			foreach ([self::DBNAME, 'neofrag_install_test_complete', 'neofrag_install_test_mkt', 'neofrag_install_test_addonmig'] as $db)
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
		foreach (['nf_user', 'nf_user_profile', 'nf_groups', 'nf_settings', 'nf_addon', 'nf_addon_migrations', 'nf_reactions', 'nf_notifications'] as $table)
		{
			$this->assertSame(1, $this->tableExists($db, $table), "Table {$table} manquante après import");
		}
		// Cœur LEAN : le schéma ne crée QUE les tables Tier 0 (≈ 52) — pas les tables des modules
		// Tier 1/2 (apportées par leur install/install.sql à l'install — install_complete — ou via le marketplace).
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
	 * Modèle « tout bundlé » (option C) : install_complete() installe TOUS les modules/widgets/thèmes
	 * locaux sur un cœur lean. Vérifie que les tables de modules sont créées (via leur install.sql),
	 * les addons enregistrés (module + widget) et la page d'accueil sûre fixée (news).
	 */
	public function testCompleteInstallOnLeanCore(): void
	{
		$root = dirname(__DIR__, 2);

		$db = $this->freshLeanDb('neofrag_install_test_complete', $root);
		$r  = Installer::install_complete($db, $root);

		$this->assertSame([], $r['errors'], 'install_complete() ne doit pas faillir');
		$this->assertSame('news', $r['default_page'], 'Page d\'accueil = news (module présent)');
		$this->assertSame('news', $this->scalar($db, "SELECT value FROM nf_settings WHERE name = 'nf_default_page'"));

		// Tables de modules (Tier 1/2) créées par leur install.sql.
		foreach (['nf_news', 'nf_forum', 'nf_teams'] as $t)
		{
			$this->assertSame(1, $this->tableExists($db, $t), "install_complete crée {$t}");
		}

		// Addons enregistrés : module news + widget news.
		$this->assertGreaterThan(0, (int) $this->scalar($db, "SELECT COUNT(*) FROM nf_addon a JOIN nf_addon_type t ON a.type_id = t.id WHERE t.name = 'module' AND a.name = 'news'"), 'news enregistré comme module');
		$this->assertGreaterThan(0, (int) $this->scalar($db, "SELECT COUNT(*) FROM nf_addon a JOIN nf_addon_type t ON a.type_id = t.id WHERE t.name = 'widget' AND a.name = 'news'"), 'widget news enregistré');

		// Le résumé reflète l'install massive (modules + thèmes du paquet).
		$this->assertContains('news', $r['installed_modules']);
		$this->assertContains('forum', $r['installed_modules']);
		$this->assertNotEmpty($r['installed_themes'], 'install_complete enregistre les thèmes du paquet');

		$db->close();
		self::$root->query('DROP DATABASE IF EXISTS `neofrag_install_test_complete`');
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

	/**
	 * Migrations PAR ADDON (Phase 2) : baseline marque les *.up.sql présents comme appliqués (batch 0)
	 * SANS les exécuter, et est idempotent. (Le mode 'up' s'exécute côté framework via Addon::migrate.)
	 */
	public function testAddonMigrationsBaseline(): void
	{
		$project_root = dirname(__DIR__, 2);   // racine du repo (pour install/schema.sql)
		$root = $this->tempDir('addonmig');    // racine fixture (le faux addon + ses migrations)
		$mig  = $root . '/modules/fakeaddon/install/migrations';
		mkdir($mig, 0775, true);
		// SQL volontairement « destructeur » : s'il était EXÉCUTÉ (au lieu d'être baseliné), le test casserait.
		file_put_contents($mig . '/2026_01_01_a.up.sql', 'CREATE TABLE nf_should_not_exist (id INT);');
		file_put_contents($mig . '/2026_01_02_b.up.sql', 'CREATE TABLE nf_should_not_exist (id INT);');

		$db = $this->freshLeanDb('neofrag_install_test_addonmig', $project_root);

		// La table de suivi est livrée par schema.sql (cœur Tier 0).
		$this->assertSame(1, $this->tableExists($db, 'nf_addon_migrations'), 'schema.sql doit créer nf_addon_migrations');

		Installer::baseline_addon_migrations($db, 'module', 'fakeaddon', $root);

		$this->assertSame(2, (int) $this->scalar($db, "SELECT COUNT(*) FROM nf_addon_migrations WHERE type = 'module' AND name = 'fakeaddon'"), 'Les 2 migrations sont enregistrées');
		$this->assertSame(0, (int) $this->scalar($db, "SELECT COUNT(*) FROM nf_addon_migrations WHERE batch <> 0"), 'Baseline = batch 0');
		$this->assertSame(0, $this->tableExists($db, 'nf_should_not_exist'), 'Baseline ne doit PAS exécuter le SQL des migrations');

		// Idempotence : rejouer ne duplique pas (INSERT IGNORE sur la clé unique type+name+migration).
		Installer::baseline_addon_migrations($db, 'module', 'fakeaddon', $root);
		$this->assertSame(2, (int) $this->scalar($db, "SELECT COUNT(*) FROM nf_addon_migrations WHERE name = 'fakeaddon'"), 'Baseline idempotent');

		$db->close();
		self::$root->query('DROP DATABASE IF EXISTS `neofrag_install_test_addonmig`');

		@unlink($mig . '/2026_01_01_a.up.sql');
		@unlink($mig . '/2026_01_02_b.up.sql');
		@rmdir($mig);
		@rmdir($root . '/modules/fakeaddon/install');
		@rmdir($root . '/modules/fakeaddon');
		@rmdir($root . '/modules');
		@rmdir($root);
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
