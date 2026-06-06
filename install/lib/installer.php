<?php
declare(strict_types=1);

/**
 * NeoFrag — cœur de l'installeur web (fonctions pures, sans framework).
 *
 * Volontairement autonome : l'installeur tourne AVANT que le framework soit
 * bootable (pas de DB, pas de config, pas de service locator). On ne dépend donc
 * que de mysqli + du système de fichiers. Aucune méthode ne fait echo/exit : tout
 * remonte par valeur de retour ou exception, pour rester testable en intégration.
 *
 * La logique de migrations (discover/baseline/up) reflète tools/migrate.php — elle
 * est ré-implémentée ici en version silencieuse plutôt que d'inclure le script CLI
 * (qui echo/exit et n'est pas réutilisable proprement).
 */

namespace NF\Install\Lib;

use mysqli;
use RuntimeException;

final class Installer
{
	const MIGRATIONS_TABLE = 'nf_migrations';

	// === Détection d'une install existante (garde « déjà installé ») ===========

	/**
	 * Sonde DB : une install fonctionnelle est présente si config/db.php existe,
	 * que la connexion passe, que la table nf_user existe et contient ≥ 1 membre.
	 * Une config présente mais base vide/injoignable → FALSE (install incomplète,
	 * l'installeur doit pouvoir reprendre).
	 */
	public static function is_already_installed(string $config_dir): bool
	{
		$cfg = self::read_db_config($config_dir);
		if ($cfg === null)
		{
			return FALSE;
		}

		try
		{
			$db = self::connect($cfg, TRUE);
		}
		catch (RuntimeException $e)
		{
			return FALSE;
		}

		$res       = @$db->query('SELECT COUNT(*) FROM nf_user');
		$installed = FALSE;
		if ($res)
		{
			$row       = $res->fetch_row();
			$installed = $row && (int) $row[0] >= 1;
		}
		$db->close();

		return $installed;
	}

	/** Lit config/db.php et retourne $db[0] (ou NULL si absent/invalide). */
	public static function read_db_config(string $config_dir): ?array
	{
		$file = rtrim($config_dir, '/\\').'/db.php';
		if (!is_file($file))
		{
			return NULL;
		}

		$db = [];
		require $file;

		return (!empty($db[0]) && is_array($db[0])) ? $db[0] : NULL;
	}

	// === Connexion / base ======================================================

	/**
	 * Connexion mysqli. $with_database = FALSE pour se connecter au serveur seul
	 * (avant que la base existe). Lève RuntimeException si la connexion échoue.
	 */
	public static function connect(array $cfg, bool $with_database = TRUE): mysqli
	{
		mysqli_report(MYSQLI_REPORT_OFF);

		$db = @new mysqli(
			(string) ($cfg['hostname'] ?? '127.0.0.1'),
			(string) ($cfg['username'] ?? 'root'),
			(string) ($cfg['password'] ?? ''),
			$with_database ? (string) ($cfg['database'] ?? '') : '',
			(int) ($cfg['port'] ?? 3306)
		);

		if ($db->connect_errno)
		{
			throw new RuntimeException('Connexion BDD impossible : '.$db->connect_error);
		}

		$db->set_charset('utf8mb4');

		return $db;
	}

	/**
	 * Teste une connexion serveur (sans base) — pour l'étape « config DB » de l'UI.
	 * Retourne ['ok'=>bool, 'error'=>?string, 'server'=>?string]. Ne lève jamais.
	 */
	public static function test_db(array $cfg): array
	{
		try
		{
			$db     = self::connect($cfg, FALSE);
			$server = $db->server_info;
			$db->close();

			return ['ok' => TRUE, 'error' => NULL, 'server' => $server];
		}
		catch (RuntimeException $e)
		{
			return ['ok' => FALSE, 'error' => $e->getMessage(), 'server' => NULL];
		}
	}

	/** Crée la base si absente puis la sélectionne. */
	public static function ensure_database(mysqli $server, string $database): void
	{
		$name = str_replace('`', '', $database);

		if (!$server->query("CREATE DATABASE IF NOT EXISTS `{$name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"))
		{
			throw new RuntimeException('Création de la base impossible : '.$server->error);
		}

		if (!$server->select_db($name))
		{
			throw new RuntimeException('Sélection de la base impossible : '.$server->error);
		}
	}

	// === Écriture de la configuration ==========================================

	/**
	 * Écrit config/db.php et régénère config/crypt.php + config/password.php avec
	 * des secrets ALÉATOIRES (jamais les valeurs commitées). Ces 3 fichiers sont
	 * gitignored : l'installeur écrit donc des fichiers non suivis.
	 */
	public static function write_config(string $config_dir, array $cfg): void
	{
		$dir = rtrim($config_dir, '/\\');
		if (!is_dir($dir) && !@mkdir($dir, 0775, TRUE) && !is_dir($dir))
		{
			throw new RuntimeException("Dossier de config non inscriptible : {$dir}");
		}

		$db = [
			'hostname' => (string) ($cfg['hostname'] ?? '127.0.0.1'),
			'username' => (string) ($cfg['username'] ?? 'root'),
			'password' => (string) ($cfg['password'] ?? ''),
			'database' => (string) ($cfg['database'] ?? 'neofrag'),
			'driver'   => 'mysqli',
		];

		$db_php = "<?php\n\n\$db[] = [\n";
		foreach ($db as $key => $value)
		{
			$db_php .= "\t'".$key."' => ".var_export($value, TRUE).",\n";
		}
		$db_php .= "];\n";

		self::write_file($dir.'/db.php', $db_php);

		// base64(random_bytes) → alphabet [A-Za-z0-9+/], sans quote ni backslash :
		// aucune échappement nécessaire dans le PHP généré.
		self::write_file($dir.'/crypt.php',    "<?php\n\n\$crypt['key'] = '".self::random_secret(99)."';\n");
		self::write_file($dir.'/password.php', "<?php\n\n\$password['salt'] = '".self::random_secret(48)."';\n");

		// config/email.php généré ICI (pas embarqué dans le paquet) : ainsi un redéploiement ne
		// réécrase pas un SMTP configuré. Hôte vide => PHP mail() (fonctionne en mutualisé). Non
		// écrasé s'il existe déjà.
		if (!is_file($dir.'/email.php'))
		{
			self::write_file($dir.'/email.php', self::email_config_template());
		}
	}

	/** Modèle config/email.php : hôte SMTP vide => mail() PHP (mutualisé). Cf. build-release (doc SMTP). */
	private static function email_config_template(): string
	{
		return "<?php\n\n"
			."// Envoi des e-mails (PHPMailer). Hôte SMTP VIDE => NeoFrag utilise la fonction mail() de PHP,\n"
			."// qui fonctionne d'office sur la plupart des hébergements mutualisés (Plesk/cPanel : MTA local).\n"
			."// Pour une meilleure délivrabilité, renseigner un SMTP transactionnel (Brevo, Resend, SendGrid…)\n"
			."// ou la boîte mail de l'hébergeur : host, port, secure ('tls'/'ssl'), username, password.\n"
			."\$email['smtp'] = [\n"
			."\t'host'     => '',\n"
			."\t'username' => '',\n"
			."\t'password' => '',\n"
			."\t'port'     => 0,\n"
			."\t'secure'   => '',\n"
			."];\n";
	}

	/** Secret aléatoire imprimable (base64 url-safe sans padding). */
	public static function random_secret(int $bytes): string
	{
		return rtrim(strtr(base64_encode(random_bytes(max(1, $bytes))), '+/', 'AB'), '=');
	}

	// === Import SQL ============================================================

	/**
	 * Importe un fichier SQL via multi_query (robuste : pas de split naïf sur « ; »
	 * qui casserait sur un « ; » présent dans une valeur de seed). Le DDL MySQL
	 * auto-commit : en cas d'erreur la base peut être partiellement créée — l'appelant
	 * doit alors DROP/recréer la base avant de réessayer.
	 */
	public static function import_sql_file(mysqli $db, string $path): void
	{
		$sql = @file_get_contents($path);
		if ($sql === FALSE || trim($sql) === '')
		{
			throw new RuntimeException("Fichier SQL vide ou illisible : {$path}");
		}

		$file = basename($path);

		if (!$db->multi_query($sql))
		{
			self::sql_import_error($file, $db->error);
		}

		// Drain COMPLET : on capture la 1re erreur mais on continue à vider les résultats, sinon
		// next_result() sort la boucle sur l'erreur suivante et laisse la connexion « out of sync ».
		$error = NULL;
		do
		{
			if ($error === NULL && $db->errno)
			{
				$error = $db->error;
			}
			if ($res = $db->store_result())
			{
				$res->free();
			}
		}
		while ($db->more_results() && $db->next_result());

		if ($error === NULL && $db->errno)
		{
			$error = $db->error;
		}

		if ($error !== NULL)
		{
			self::sql_import_error($file, $error);
		}
	}

	/**
	 * Journalise l'erreur SQL BRUTE (logs/php.log) et lève un message GÉNÉRIQUE : on ne renvoie pas
	 * la structure de la base ni le détail MySQL dans la réponse HTTP (fuite d'info pendant l'install).
	 */
	private static function sql_import_error(string $file, string $raw): void
	{
		error_log("[install] import {$file} : {$raw}");

		throw new RuntimeException(
			"L'import de la base a échoué ({$file}). Vérifiez que la base est vide et que l'utilisateur "
			."MySQL dispose des droits nécessaires. Détails techniques dans logs/php.log."
		);
	}

	// === Migrations (miroir silencieux de tools/migrate.php) ===================

	/**
	 * Applique les migrations en attente, et marque optionnellement un préfixe
	 * d'historique sans l'exécuter (baseline).
	 *
	 * - $baseline_until = NULL (défaut) : « up only ». Cas d'une install moderne où
	 *   schema.sql embarque déjà la table nf_migrations peuplée — on n'applique que
	 *   les fichiers postérieurs (nouveaux). C'est le mode normal de l'installeur.
	 * - $baseline_until = 'YYYY_MM_DD…' : adoption d'une base legacy / dump figé —
	 *   marque (batch 0, sans exécuter) toutes les migrations ≤ cette borne, puis
	 *   applique le reste. Équivaut à `migrate baseline --until` + `migrate up`.
	 *
	 * Retourne ['baselined'=>string[], 'applied'=>string[]].
	 */
	public static function run_migrations(mysqli $db, string $migrations_dir, ?string $baseline_until = NULL): array
	{
		self::ensure_migrations_table($db);

		$all       = self::discover_migrations($migrations_dir);
		$applied   = self::applied_migrations($db);
		$baselined = [];

		foreach ($all as $name)
		{
			if (isset($applied[$name]) || $baseline_until === NULL)
			{
				continue;
			}
			if (strcmp($name, $baseline_until) > 0)
			{
				continue;
			}

			self::mark_migration($db, $name, 0);
			$baselined[] = $name;
		}

		$applied = self::applied_migrations($db);
		$pending = array_values(array_diff($all, array_keys($applied)));
		$batch   = self::next_batch($db);
		$done    = [];

		foreach ($pending as $name)
		{
			self::import_sql_file($db, $migrations_dir.'/'.$name.'.up.sql');
			self::mark_migration($db, $name, $batch);
			$done[] = $name;
		}

		return ['baselined' => $baselined, 'applied' => $done];
	}

	private static function ensure_migrations_table(mysqli $db): void
	{
		$db->query(
			'CREATE TABLE IF NOT EXISTS '.self::MIGRATIONS_TABLE.' (
				id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
				name       VARCHAR(255) NOT NULL,
				batch      INT UNSIGNED NOT NULL,
				applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (id),
				UNIQUE KEY uniq_name (name)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
		) || self::throw_db('Création table '.self::MIGRATIONS_TABLE, $db);
	}

	/** @return string[] noms (sans .up.sql), triés chronologiquement (préfixe daté). */
	public static function discover_migrations(string $dir): array
	{
		$files = glob(rtrim($dir, '/\\').'/*.up.sql') ?: [];
		$names = array_map(static fn(string $f): string => basename($f, '.up.sql'), $files);
		sort($names);

		return $names;
	}

	/** @return array<string,int> name => batch */
	private static function applied_migrations(mysqli $db): array
	{
		$out = [];
		$res = $db->query('SELECT name, batch FROM '.self::MIGRATIONS_TABLE.' ORDER BY id ASC');
		if ($res)
		{
			while ($row = $res->fetch_assoc())
			{
				$out[$row['name']] = (int) $row['batch'];
			}
		}

		return $out;
	}

	private static function mark_migration(mysqli $db, string $name, int $batch): void
	{
		$stmt = $db->prepare('INSERT INTO '.self::MIGRATIONS_TABLE.' (name, batch) VALUES (?, ?)');
		$stmt->bind_param('si', $name, $batch);
		$stmt->execute() || self::throw_db("Enregistrement migration {$name}", $db);
	}

	private static function next_batch(mysqli $db): int
	{
		$res = $db->query('SELECT COALESCE(MAX(batch), 0) + 1 AS b FROM '.self::MIGRATIONS_TABLE);
		$row = $res ? $res->fetch_assoc() : NULL;

		return (int) ($row['b'] ?? 1);
	}

	// === Compte administrateur =================================================

	/**
	 * Crée le super-administrateur. Le flag nf_user.admin = '1' suffit à court-circuiter
	 * toutes les permissions (cf. core/access.php) — pas besoin d'un groupe admin.
	 * Mot de passe : argon2id, salt vide (format moderne). Retourne l'id créé.
	 *
	 * $admin attend : username, password, email, et optionnellement first_name/last_name.
	 */
	public static function create_admin(mysqli $db, array $admin): int
	{
		foreach (['username', 'password', 'email'] as $required)
		{
			if (empty($admin[$required]))
			{
				throw new RuntimeException("create_admin : champ « {$required} » manquant.");
			}
		}

		// Le framework (form2/form) encode TOUS les inputs via utf8_htmlentities(trim()) avant
		// stockage ET avant vérification au login. L'installeur étant standalone, il DOIT appliquer
		// le MÊME encodage — sinon un mot de passe ou identifiant avec accent/caractère spécial
		// (é, â, &, <, "…) ne se vérifierait jamais (hash brut côté install vs vérif encodée côté
		// login → « mot de passe incorrect » juste après l'installation).
		$enc = static fn($v): string => htmlentities(trim((string) $v), ENT_COMPAT, 'UTF-8');

		$username = $enc($admin['username']);
		$email    = $enc($admin['email']);
		$hash     = password_hash($enc($admin['password']), PASSWORD_ARGON2ID);

		$stmt = $db->prepare(
			"INSERT INTO nf_user (username, password, salt, email, admin, language, data, deleted)
			 VALUES (?, ?, '', ?, '1', NULL, '', '0')"
		);
		$stmt->bind_param('sss', $username, $hash, $email);
		$stmt->execute() || self::throw_db('Création du compte admin', $db);

		$id = (int) $db->insert_id;

		$first = $enc($admin['first_name'] ?? '');
		$last  = $enc($admin['last_name'] ?? '');

		$stmt = $db->prepare(
			"INSERT INTO nf_user_profile
			 (id, first_name, last_name, signature, country, location, quote, website, linkedin, github, instagram, twitch)
			 VALUES (?, ?, ?, '', '', '', '', '', '', '', '', '')"
		);
		$stmt->bind_param('iss', $id, $first, $last);
		$stmt->execute() || self::throw_db('Création du profil admin', $db);

		return $id;
	}

	/** Vrai si la table existe et contient au moins une ligne (détection install existante). */
	public static function table_has_rows(mysqli $db, string $table): bool
	{
		$res = @$db->query('SELECT 1 FROM `' . str_replace('`', '', $table) . '` LIMIT 1');

		return $res && $res->num_rows > 0;
	}

	/** Vrai si la table existe (indépendamment de son contenu). */
	public static function table_exists(mysqli $db, string $table): bool
	{
		$res = @$db->query("SHOW TABLES LIKE '" . $db->real_escape_string($table) . "'");

		return (bool) ($res && $res->num_rows);
	}

	/** Écrit un réglage global (nf_settings, scope site/lang vides). Insert ou update. */
	public static function set_setting(mysqli $db, string $name, string $value, string $type = 'string'): void
	{
		$stmt = $db->prepare(
			"INSERT INTO nf_settings (name, site, lang, value, type) VALUES (?, '', '', ?, ?)
			 ON DUPLICATE KEY UPDATE value = VALUES(value)"
		);
		$stmt->bind_param('sss', $name, $value, $type);
		$stmt->execute() || self::throw_db("set_setting {$name}", $db);
	}

	// === Presets (étape installeur « Modules ») ================================

	/** Définition des presets (install/lib/presets.php), mémoïsée (require ne renvoie le tableau qu'une fois). */
	public static function presets(): array
	{
		static $presets = NULL;
		if ($presets === NULL)
		{
			$presets = require __DIR__.'/presets.php';
		}
		return $presets;
	}

	/**
	 * Applique un preset sur une base déjà initialisée (schema + seed + admin). Standalone :
	 * uniquement mysqli + FS (le framework n'est pas bootable tant que l'install n'est pas finie).
	 *
	 * Pour chaque module RETENU (sous-ensemble coché du preset) : enregistre nf_addon (module +
	 * widgets appariés) puis joue modules/<name>/install/install.sql (idempotent, FK désactivées).
	 * Ajoute les widgets sans module du preset (extra_widgets), recalcule une page d'accueil SÛRE
	 * (jamais 404), et — pour « Site simple » — crée la page « Bienvenue » (module pages, cœur).
	 *
	 * @param string[] $selected_modules sous-ensemble des modules du preset (cases cochées)
	 * @return array{installed_modules:string[], installed_widgets:string[], default_page:string, errors:string[]}
	 */
	public static function apply_preset(mysqli $db, string $root, string $preset_key, array $selected_modules): array
	{
		$presets = self::presets();
		if (!isset($presets[$preset_key]))
		{
			throw new RuntimeException("Preset inconnu : {$preset_key}");
		}
		$preset = $presets[$preset_key];

		// On ne retient que des modules réellement déclarés par le preset (anti-injection).
		$modules  = array_values(array_intersect(array_keys($preset['modules']), $selected_modules));
		$type_ids = self::addon_type_ids($db);
		$errors   = [];
		$installed_modules = $installed_widgets = [];

		foreach ($modules as $name)
		{
			$sql = $root.'/modules/'.$name.'/install/install.sql';
			try
			{
				// On joue l'install.sql AVANT d'enregistrer module + widgets : si la création des tables
				// échoue, rien n'est enregistré (ni module ni widgets) → pas d'addon ni de widget orphelin.
				if (is_file($sql))
				{
					self::import_sql_file($db, $sql);
				}

				self::register_addon($db, $type_ids['module'], $name);
				$installed_modules[] = $name;

				foreach ($preset['modules'][$name] as $widget)
				{
					self::register_addon($db, $type_ids['widget'], $widget);
					$installed_widgets[] = $widget;
				}
			}
			catch (\Throwable $e)
			{
				// Tier 1 = source locale de confiance : un échec est un vrai bug, mais on n'abandonne
				// pas toute l'install (les autres modules s'installent ; l'erreur est remontée).
				error_log("[install] preset {$preset_key} module {$name} : ".$e->getMessage());
				$errors[] = "Module « {$name} » : ".$e->getMessage();
			}
		}

		foreach ($preset['extra_widgets'] ?? [] as $widget)
		{
			self::register_addon($db, $type_ids['widget'], $widget);
			$installed_widgets[] = $widget;
		}

		// Page d'accueil : welcome > news > forum > gallery > pages (module cœur → jamais de 404).
		if (!empty($preset['welcome_page']))
		{
			self::create_welcome_page($db);
			$default_page = 'pages/bienvenue';
		}
		else
		{
			$default_page = 'pages';
			foreach (['news', 'forum', 'gallery'] as $candidate)
			{
				if (in_array($candidate, $installed_modules, TRUE))
				{
					$default_page = $candidate;
					break;
				}
			}
		}
		self::set_setting($db, 'nf_default_page', $default_page);

		return [
			'installed_modules' => $installed_modules,
			'installed_widgets' => array_values(array_unique($installed_widgets)),
			'default_page'      => $default_page,
			'errors'            => $errors,
		];
	}

	/** @return array<string,int> nom de type → id (module/theme/widget/language/authenticator). */
	private static function addon_type_ids(mysqli $db): array
	{
		$out = [];
		$res = $db->query('SELECT id, name FROM nf_addon_type');
		while ($res && $row = $res->fetch_assoc())
		{
			$out[$row['name']] = (int) $row['id'];
		}
		return $out;
	}

	/** Enregistre un addon comme installé+activé (INSERT IGNORE : clé unique (name, type_id)). */
	private static function register_addon(mysqli $db, int $type_id, string $name): void
	{
		$data = serialize(['enabled' => TRUE]); // a:1:{s:7:"enabled";b:1;}
		$stmt = $db->prepare('INSERT IGNORE INTO nf_addon (type_id, name, data) VALUES (?, ?, ?)');
		$stmt->bind_param('iss', $type_id, $name, $data);
		$stmt->execute() || self::throw_db("register addon {$name}", $db);
	}

	/**
	 * Crée la page « Bienvenue » (module pages, cœur) + ses droits d'accès publics, et la rend
	 * servable en page d'accueil via nf_default_page='pages/bienvenue'. Réentrant (no-op si déjà là).
	 */
	private static function create_welcome_page(mysqli $db): void
	{
		if (self::scalar($db, "SELECT page_id FROM nf_pages WHERE name = 'bienvenue'") !== NULL)
		{
			return;
		}

		$db->query("INSERT INTO nf_pages (name, published, layout) VALUES ('bienvenue', '1', 'default')")
			|| self::throw_db('création page bienvenue', $db);
		$page_id = (int) $db->insert_id;

		$title    = 'Bienvenue';
		$subtitle = 'Votre site est en ligne';
		$content  = self::welcome_content();

		// Le checker pages résout par `name` (lang-agnostique) ; on insère néanmoins une traduction
		// par langue activée pour que la page apparaisse aussi dans la liste /pages de chaque langue.
		$langs = [];
		$res = $db->query("SELECT a.name FROM nf_addon a JOIN nf_addon_type t ON a.type_id = t.id WHERE t.name = 'language'");
		while ($res && $row = $res->fetch_assoc())
		{
			$langs[] = $row['name'];
		}
		if (!$langs)
		{
			$langs = ['fr'];
		}

		$stmt = $db->prepare('INSERT INTO nf_pages_lang (page_id, lang, title, subtitle, content) VALUES (?, ?, ?, ?, ?)');
		foreach ($langs as $lang)
		{
			$stmt->bind_param('issss', $page_id, $lang, $title, $subtitle, $content);
			$stmt->execute() || self::throw_db('création page bienvenue (lang)', $db);
		}

		// Accès public : le checker exige pages.access_page au scope = page_id. On l'accorde aux
		// rôles built-in member + visitor (super_admin a déjà *.* allow au scope 0).
		foreach (self::role_ids($db, ['member', 'visitor']) as $role_id)
		{
			$stmt = $db->prepare("INSERT INTO nf_role_permissions (role_id, permission, scope_id, authorized) VALUES (?, 'pages.access_page', ?, 'allow')");
			$stmt->bind_param('ii', $role_id, $page_id);
			$stmt->execute() || self::throw_db('droit accès page bienvenue', $db);
		}
	}

	/** Contenu par défaut de la page d'accueil « Site simple » (rendu par bbcode() — texte simple). */
	private static function welcome_content(): string
	{
		return "Félicitations, votre site NeoFrag Reborn est en ligne !\n\n"
			."Cette page d'accueil est un exemple : modifiez-la (ou remplacez-la) depuis l'administration, "
			."section « Pages ». Vous pouvez choisir n'importe quelle page ou module comme page d'accueil "
			."dans « Réglages → Préférences générales ».\n\n"
			."Publiez vos premières actualités, agencez le menu et les widgets via l'éditeur en direct, puis "
			."installez des modules supplémentaires depuis le marketplace. Bonne personnalisation !";
	}

	/** @param string[] $names @return int[] role_ids des rôles built-in nommés. */
	private static function role_ids(mysqli $db, array $names): array
	{
		$out = [];
		foreach ($names as $name)
		{
			$id = self::scalar($db, "SELECT role_id FROM nf_roles WHERE name = '".$db->real_escape_string($name)."'");
			if ($id !== NULL)
			{
				$out[] = (int) $id;
			}
		}
		return $out;
	}

	private static function scalar(mysqli $db, string $sql)
	{
		$res = $db->query($sql);
		$row = $res ? $res->fetch_row() : NULL;
		return $row ? $row[0] : NULL;
	}

	/**
	 * Garde-fou (tests) : chaque module/widget de presets.php doit appartenir au Tier 1 (`identity`)
	 * du manifeste — jamais au cœur (déjà installé) ni au Tier 2 (marketplace distant).
	 */
	public static function assert_presets_in_manifest(string $root): void
	{
		$presets  = self::presets();
		$manifest = require $root.'/tools/addons-manifest.php';
		$id_mod   = $manifest['identity']['module'] ?? [];
		$id_wid   = $manifest['identity']['widget'] ?? [];

		foreach ($presets as $key => $preset)
		{
			foreach (array_keys($preset['modules']) as $m)
			{
				if (!in_array($m, $id_mod, TRUE))
				{
					throw new RuntimeException("Preset {$key} : module « {$m} » hors Tier 1 (identity).");
				}
			}
			foreach ($preset['modules'] as $widgets)
			{
				foreach ($widgets as $w)
				{
					if (!in_array($w, $id_wid, TRUE))
					{
						throw new RuntimeException("Preset {$key} : widget « {$w} » hors Tier 1 (identity).");
					}
				}
			}
			foreach ($preset['extra_widgets'] ?? [] as $w)
			{
				if (!in_array($w, $id_wid, TRUE))
				{
					throw new RuntimeException("Preset {$key} : widget extra « {$w} » hors Tier 1 (identity).");
				}
			}
		}
	}

	// === Marketplace distant (Tier 2) — fetch / download / install sécurisés ===
	//
	// Sécurité (spec §7) : HTTPS strict, vérification SHA-256, anti-zip-slip, tailles/timeout
	// bornés, origine FIXE (anti-SSRF : jamais d'URL saisie par l'utilisateur), aucune exécution
	// de code distant (on extrait + on joue install.sql idempotent, comme un addon local).
	// Standalone (curl + FS + mysqli) → utilisable par l'étape installeur ET l'admin.

	const MARKETPLACE_URL_DEFAULT = 'https://www.neofrag-reborn.xyz/marketplace';
	const MARKETPLACE_HOSTS       = ['www.neofrag-reborn.xyz', 'neofrag-reborn.xyz']; // origines autorisées
	const MARKETPLACE_MAX_ZIP     = 10485760; // 10 Mo par zip
	const MARKETPLACE_MAX_CATALOG = 2097152;  // 2 Mo pour le catalogue
	const MARKETPLACE_TIMEOUT     = 15;       // secondes (connexion + transfert)

	/**
	 * URL de base du marketplace (origine FIXE — anti-SSRF). nf_marketplace_url peut surcharger le défaut
	 * MAIS uniquement vers un hôte AUTORISÉ, en HTTPS, port 443, sans userinfo : une valeur injectée en
	 * base (DB compromise) ne peut pas rediriger les fetchs vers un hôte interne/arbitraire. Toute valeur
	 * invalide retombe sur le défaut codé.
	 */
	public static function marketplace_url(?mysqli $db = NULL): string
	{
		if ($db !== NULL && ($v = self::scalar($db, "SELECT value FROM nf_settings WHERE name = 'nf_marketplace_url'")) && is_string($v) && $v !== '')
		{
			$v = rtrim($v, '/');
			$p = parse_url($v);

			if (is_array($p)
				&& ($p['scheme'] ?? '') === 'https'
				&& in_array($p['host'] ?? '', self::MARKETPLACE_HOSTS, TRUE)
				&& (int) ($p['port'] ?? 443) === 443
				&& !isset($p['user']) && !isset($p['pass']))
			{
				return $v;
			}
		}

		return self::MARKETPLACE_URL_DEFAULT;
	}

	/**
	 * Récupère le catalogue distant (HTTPS, timeout, taille bornée). Retourne le tableau décodé
	 * ou NULL si injoignable/invalide → MODE DÉGRADÉ (l'install ne bloque jamais).
	 */
	public static function fetch_catalog(string $base_url): ?array
	{
		try
		{
			$json = self::http_get(rtrim($base_url, '/').'/catalog.json', self::MARKETPLACE_MAX_CATALOG);
		}
		catch (\Throwable $e)
		{
			error_log('[marketplace] catalogue injoignable : '.$e->getMessage());
			return NULL;
		}

		$data = json_decode($json, TRUE);

		return (is_array($data) && isset($data['addons']) && is_array($data['addons'])) ? $data : NULL;
	}

	/**
	 * Télécharge + vérifie (SHA-256) + extrait (anti-zip-slip) + installe un addon Tier 2 distant.
	 * Retourne ['ok'=>bool, 'error'=>?string, 'widgets'=>string[]]. Ne lève jamais (l'appelant
	 * peut sauter l'addon et continuer).
	 */
	/**
	 * @param array<string,array> $widget_metas widgets du catalogue indexés par nom — pour
	 *   TÉLÉCHARGER les fichiers des widgets appariés (zip séparé) plutôt que d'enregistrer une
	 *   coquille vide. Un widget annoncé mais absent du catalogue est simplement ignoré (zéro orphelin).
	 */
	public static function install_remote_addon(mysqli $db, string $root, array $meta, string $base_url, array $widget_metas = []): array
	{
		$installed_widgets = [];

		try
		{
			$r        = self::download_and_extract($meta, $base_url, $root);
			$type_ids = self::addon_type_ids($db);

			// Addon principal : enregistrement + install.sql (idempotent). Aucune exécution de code distant.
			self::register_addon($db, $type_ids[$r['type']], $r['name']);
			$sql = $root.'/'.$r['type'].'s/'.$r['name'].'/install/install.sql';
			if (is_file($sql))
			{
				self::import_sql_file($db, $sql);
			}

			// Widgets appariés : on télécharge LEURS fichiers (zip séparé) + ligne + install.sql.
			// L'échec d'UN widget n'annule PAS l'install du module (déjà persisté) — on saute ce widget.
			foreach ($r['widgets'] as $w)
			{
				if (!isset($widget_metas[$w]))
				{
					continue; // pas dans le catalogue → on n'enregistre pas un widget sans fichiers
				}
				try
				{
					$wr = self::download_and_extract($widget_metas[$w], $base_url, $root);
					self::register_addon($db, $type_ids['widget'], $wr['name']);
					$wsql = $root.'/widgets/'.$wr['name'].'/install/install.sql';
					if (is_file($wsql))
					{
						self::import_sql_file($db, $wsql);
					}
					$installed_widgets[] = $wr['name'];
				}
				catch (\Throwable $e)
				{
					error_log('[marketplace] widget '.$w.' (de '.($meta['name'] ?? '?').') : '.$e->getMessage());
				}
			}
		}
		catch (\Throwable $e)
		{
			// Échec de l'addon PRINCIPAL → install ratée.
			return ['ok' => FALSE, 'error' => $e->getMessage(), 'widgets' => []];
		}

		return ['ok' => TRUE, 'error' => NULL, 'widgets' => $installed_widgets];
	}

	/**
	 * Télécharge + vérifie SHA-256 + extrait (anti-zip-slip) un addon vers <type>s/<name>/, SANS
	 * toucher la base. Pour le framework (admin) qui enchaîne avec SA propre registration d'addon.
	 * Lève en cas d'échec (réseau, intégrité, zip-slip). @return array{type:string,name:string,widgets:string[]}.
	 */
	public static function download_and_extract(array $meta, string $base_url, string $root): array
	{
		[$type, $name] = self::validate_addon_meta($meta);

		$zip_data = self::http_get(rtrim($base_url, '/').'/'.$meta['file'], self::MARKETPLACE_MAX_ZIP);

		// Intégrité : empreinte SHA-256 du zip == celle annoncée par le catalogue (rejet sinon).
		if (!hash_equals(strtolower((string) $meta['sha256']), hash('sha256', $zip_data)))
		{
			throw new RuntimeException('Empreinte SHA-256 invalide (zip rejeté).');
		}

		$tmp_zip = tempnam(sys_get_temp_dir(), 'nfdl');
		if (@file_put_contents($tmp_zip, $zip_data) === FALSE)
		{
			throw new RuntimeException('Écriture temporaire impossible.');
		}

		try
		{
			self::extract_addon_safe($tmp_zip, $type, $name, $root);
		}
		finally
		{
			@unlink($tmp_zip);
		}

		$widgets = [];
		foreach ((array) ($meta['provides_widgets'] ?? []) as $w)
		{
			if (is_string($w) && preg_match('/^[a-z0-9_]+$/', $w))
			{
				$widgets[] = $w;
			}
		}

		return ['type' => $type, 'name' => $name, 'widgets' => $widgets];
	}

	/** Valide les métadonnées d'un addon du catalogue. @return array{0:string,1:string} [type, name]. */
	private static function validate_addon_meta(array $meta): array
	{
		foreach (['type', 'name', 'file', 'sha256'] as $k)
		{
			if (empty($meta[$k]) || !is_string($meta[$k]))
			{
				throw new RuntimeException('Métadonnées d\'addon incomplètes.');
			}
		}

		$type = $meta['type'];
		$name = $meta['name'];

		if (!in_array($type, ['module', 'widget', 'theme'], TRUE) || !preg_match('/^[a-z0-9_]+$/', $name))
		{
			throw new RuntimeException('Addon refusé (type/nom invalide).');
		}

		// Chemin de fichier RELATIF sûr : <type>s/<name>.zip. Bloque host/chemin injectés via le catalogue.
		if (!preg_match('#^(modules|widgets|themes|addons)/[a-z0-9_]+\.zip$#', $meta['file']) || strpos($meta['file'], '..') !== FALSE)
		{
			throw new RuntimeException('Chemin de fichier d\'addon refusé.');
		}

		return [$type, $name];
	}

	/** GET HTTPS strict : TLS vérifié, pas de redirection (anti-SSRF), timeout + taille bornés. */
	private static function http_get(string $url, int $max_bytes): string
	{
		if (stripos($url, 'https://') !== 0)
		{
			throw new RuntimeException('URL non-HTTPS refusée.');
		}
		if (!function_exists('curl_init'))
		{
			throw new RuntimeException('Extension curl absente.');
		}

		$buf     = '';
		$too_big = FALSE;
		$ch      = curl_init($url);
		curl_setopt_array($ch, [
			CURLOPT_FOLLOWLOCATION  => FALSE, // origine canonique imposée — pas de rebond (anti-SSRF)
			CURLOPT_CONNECTTIMEOUT  => self::MARKETPLACE_TIMEOUT,
			CURLOPT_TIMEOUT         => self::MARKETPLACE_TIMEOUT,
			CURLOPT_SSL_VERIFYPEER  => TRUE,
			CURLOPT_SSL_VERIFYHOST  => 2,
			CURLOPT_PROTOCOLS       => CURLPROTO_HTTPS,
			CURLOPT_USERAGENT       => 'NeoFrag-Reborn-Installer/'.(defined('NEOFRAG_VERSION') ? NEOFRAG_VERSION : '1.0'),
			// Cap de taille : on vérifie AVANT d'accumuler → le buffer ne dépasse jamais max_bytes.
			CURLOPT_WRITEFUNCTION   => function ($ch, string $chunk) use (&$buf, &$too_big, $max_bytes): int {
				if (strlen($buf) + strlen($chunk) > $max_bytes)
				{
					$too_big = TRUE;
					return 0; // abort
				}
				$buf .= $chunk;
				return strlen($chunk);
			},
		]);

		$ok   = curl_exec($ch);
		$code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$err  = curl_error($ch);
		curl_close($ch);

		if ($too_big)
		{
			throw new RuntimeException('Réponse trop volumineuse (> '.$max_bytes.' octets).');
		}
		if ($ok === FALSE)
		{
			throw new RuntimeException('curl : '.($err ?: 'échec réseau'));
		}
		if ($code !== 200)
		{
			throw new RuntimeException('HTTP '.$code);
		}

		return $buf;
	}

	/**
	 * Extraction ZIP ANTI-ZIP-SLIP vers <type>s/<name>/ : chaque entrée doit rester sous <name>/
	 * (pas de chemin absolu, pas de « .. », pas d'antislash). Rejet global au moindre écart.
	 */
	private static function extract_addon_safe(string $zip_path, string $type, string $name, string $root): void
	{
		$zip = new \ZipArchive();
		if ($zip->open($zip_path) !== TRUE)
		{
			throw new RuntimeException('Archive illisible.');
		}

		try
		{
			for ($i = 0; $i < $zip->numFiles; $i++)
			{
				$entry = $zip->getNameIndex($i);

				if ($entry === FALSE || $entry === '' || $entry[0] === '/'
					|| strpos($entry, '..') !== FALSE || strpos($entry, '\\') !== FALSE)
				{
					throw new RuntimeException('Entrée d\'archive non sûre : '.$entry);
				}
				// Structure attendue (package-addons) : tout sous <name>/.
				if ($entry !== $name && strpos($entry, $name.'/') !== 0)
				{
					throw new RuntimeException('Entrée hors de l\'addon : '.$entry);
				}
				// Symlink (Unix) : pointerait hors de l'addon malgré un nom valide → rejet.
				if (self::entry_is_symlink($zip, $i))
				{
					throw new RuntimeException('Entrée symlink interdite : '.$entry);
				}
			}

			$parent = $root.'/'.$type.'s';
			if (!$zip->extractTo($parent))
			{
				throw new RuntimeException('Extraction impossible.');
			}
		}
		finally
		{
			$zip->close();
		}

		if (!is_dir($root.'/'.$type.'s/'.$name))
		{
			throw new RuntimeException('Addon absent après extraction.');
		}
	}

	/**
	 * Anti-zip-slip GÉNÉRAL (pour l'upload ZIP admin) : aucune entrée ne doit pouvoir s'échapper du
	 * dossier d'extraction. Rejet si chemin absolu (unix/windows), antislash, « .. », ou symlink (Unix).
	 */
	public static function zip_entries_safe(\ZipArchive $zip): bool
	{
		for ($i = 0; $i < $zip->numFiles; $i++)
		{
			$e = $zip->getNameIndex($i);

			if ($e === FALSE || $e === '' || $e[0] === '/' || $e[0] === '\\'
				|| strpos($e, '..') !== FALSE || strpos($e, '\\') !== FALSE
				|| preg_match('#^[a-zA-Z]:#', $e)
				|| self::entry_is_symlink($zip, $i))
			{
				return FALSE;
			}
		}

		return TRUE;
	}

	/**
	 * Vrai si l'entrée d'index $i est un lien symbolique Unix (mode S_IFLNK dans les attributs externes).
	 * Un symlink passerait la validation par nom mais pointerait hors de l'addon à l'extraction.
	 */
	private static function entry_is_symlink(\ZipArchive $zip, int $i): bool
	{
		$opsys = $attr = 0;
		if (@$zip->getExternalAttributesIndex($i, $opsys, $attr) && $opsys === \ZipArchive::OPSYS_UNIX)
		{
			return (($attr >> 16) & 0xF000) === 0xA000; // S_IFLNK
		}

		return FALSE;
	}

	// === Helpers ===============================================================

	private static function write_file(string $path, string $contents): void
	{
		if (@file_put_contents($path, $contents) === FALSE)
		{
			throw new RuntimeException("Écriture impossible : {$path}");
		}
	}

	private static function throw_db(string $context, mysqli $db): never
	{
		throw new RuntimeException($context.' : '.$db->error);
	}
}
