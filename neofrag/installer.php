<?php
declare(strict_types=1);

/**
 * NeoFrag — cœur de l'installeur web (fonctions pures, sans framework).
 *
 * Il sert aussi au site en service, bien après l'installation : la mise à jour du cœur, la
 * sauvegarde et sa restauration, la place de marché, les migrations. C'est pourquoi il vit dans le
 * cœur et non plus dans `install/` (2026-10-01) : la mise à jour ne réécrivait jamais `install/`, si
 * bien qu'un site gardait le code de mise à jour du jour de son installation — ses corrections ne
 * l'atteignaient pas —, et un site qui supprimait `install/` après l'installation, comme on le
 * conseille souvent, perdait sa mise à jour et sa place de marché. `neofrag/` est réécrit à chaque
 * mise à jour, et le Monitoring en vérifie l'intégrité.
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

namespace NF\NeoFrag;

use mysqli;
use RuntimeException;

// Les messages d'erreur de l'installeur remontent jusqu'à l'écran — de l'assistant, ou de l'administration
// quand elle met à jour le CMS, restaure une sauvegarde ou installe un addon. Leurs traductions sont
// dans `install/langs/` : sans dossier `install/`, ils restent en français (cf. self::lang()).
if (is_file($nf_langue_installeur = dirname(__DIR__).'/install/lib/langue.php'))
{
	require_once $nf_langue_installeur;
}

final class Installer
{
	const MIGRATIONS_TABLE = 'nf_migrations';

	/**
	 * Le texte dans la langue de l'assistant ou du site (`install/lib/langue.php`), et le texte
	 * français tel quel si le site a supprimé son dossier `install/` — même pluriel `a|b`, même
	 * `sprintf`, pour que le message reste juste.
	 */
	private static function lang(string $texte, ...$arguments): string
	{
		if (\function_exists('lang'))
		{
			return \lang($texte, ...$arguments);
		}

		if (str_contains($texte, '|') && $arguments && is_numeric($arguments[0]))
		{
			$formes = explode('|', $texte);
			$n      = (int) array_shift($arguments);
			$texte  = $formes[$n <= 1 ? 0 : min($n - 1, count($formes) - 1)];
		}

		return $arguments ? vsprintf($texte, $arguments) : $texte;
	}

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
			throw new RuntimeException(self::lang('Connexion à la base impossible : %s', $db->connect_error));
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
			throw new RuntimeException(self::lang('Création de la base impossible : %s', $server->error));
		}

		if (!$server->select_db($name))
		{
			throw new RuntimeException(self::lang('Sélection de la base impossible : %s', $server->error));
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
			throw new RuntimeException(self::lang('Dossier de configuration non inscriptible : %s', $dir));
		}

		$db = [
			'hostname' => (string) ($cfg['hostname'] ?? '127.0.0.1'),
			'username' => (string) ($cfg['username'] ?? 'root'),
			'password' => (string) ($cfg['password'] ?? ''),
			'database' => (string) ($cfg['database'] ?? 'neofrag'),
		];

		// Port : persisté UNIQUEMENT s'il diffère du défaut MySQL (3306), pour garder les config
		// standards propres. Le runtime (neofrag/drivers/mysqli.php) le lit et le passe à mysqli().
		if (!empty($cfg['port']) && (int) $cfg['port'] !== 3306)
		{
			$db['port'] = (int) $cfg['port'];
		}

		$db['driver'] = 'mysqli';

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

	/**
	 * Origine canonique (scheme://host) dérivée de la requête d'INSTALLATION — le seul moment
	 * où le Host est de confiance (c'est l'admin qui installe). Figée ensuite dans config/url.php
	 * pour que les URLs absolues (e-mails de reset, callbacks OAuth, retours de paiement) ne
	 * dépendent plus jamais du Host des requêtes, forgeable.
	 */
	public static function request_origin(array $server): string
	{
		$host = (string) ($server['HTTP_HOST'] ?? '');

		if ($host === '' || !preg_match('/^[A-Za-z0-9.\[\]:-]+$/', $host))
		{
			return '';
		}

		$https = (!empty($server['HTTPS']) && strtolower((string) $server['HTTPS']) !== 'off')
			|| (!empty($server['HTTP_X_FORWARDED_PROTO']) && strtolower(explode(',', (string) $server['HTTP_X_FORWARDED_PROTO'])[0]) === 'https');

		return ($https ? 'https' : 'http').'://'.$host;
	}

	/** Écrit config/url.php (origine canonique du site). Ne fait rien si $origin est vide. */
	public static function write_site_url(string $config_dir, string $origin): void
	{
		$origin = rtrim(trim($origin), '/');

		if ($origin === '')
		{
			return;
		}

		$php = "<?php\n\n"
			."// Origine canonique du site, figée à l'installation. Toutes les URLs absolues\n"
			."// (liens d'e-mails : reset de mot de passe, validation ; callbacks OAuth ; retours\n"
			."// de paiement) sont construites dessus — jamais sur le Host de la requête, forgeable.\n"
			."// À modifier à la main si le site change de domaine.\n"
			."\$url['site'] = ".var_export($origin, TRUE).";\n";

		self::write_file(rtrim($config_dir, '/\\').'/url.php', $php);
	}

	/**
	 * Écrit config/webmaster.php avec le hash argon2id du mot de passe webmaster (sudo des actions
	 * sensibles). Même format que lit NF\NeoFrag\Libraries\Webmaster. Ne touche à rien si $plain est vide.
	 */
	public static function set_webmaster_password(string $config_dir, string $plain): void
	{
		$plain = trim($plain);

		if ($plain === '')
		{
			return;
		}

		$php = "<?php\n\n"
			."// Hash argon2id du mot de passe webmaster (sudo). Généré par NeoFrag — ne pas éditer à la main.\n"
			."\$webmaster['hash'] = ".var_export(password_hash($plain, PASSWORD_ARGON2ID), TRUE).";\n";

		self::write_file(rtrim($config_dir, '/\\').'/webmaster.php', $php);
		@chmod(rtrim($config_dir, '/\\').'/webmaster.php', 0600);
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
	/**
	 * Les erreurs MySQL qui disent « c'est déjà fait » : table, colonne, index ou clé déjà présents,
	 * élément déjà supprimé. Une migration appliquée à la main sans être enregistrée — ou une base
	 * restaurée en partie — n'est pas une panne : relevé le 2026-10-01 sur l'atelier, où le rappel du
	 * calendrier était dans le schéma sans être marqué, et sa migration rejouée échouait. Sur un site,
	 * cela aurait annulé la mise à jour entière.
	 */
	private const DEJA_EN_PLACE = [1022, 1050, 1060, 1061, 1091, 1826];

	/**
	 * Applique une migration instruction par instruction, en ignorant seulement ce qui est déjà en
	 * place ; toute autre erreur lève, comme import_sql_file(). Les migrations sont de simples
	 * suites d'instructions séparées par `;` en fin de ligne, sans procédure ni délimiteur.
	 */
	private static function appliquer_migration(mysqli $db, string $path): void
	{
		$sql = (string) @file_get_contents($path);
		$sql = (string) preg_replace('/^\s*--.*$/m', '', $sql);

		foreach (preg_split('/;\s*$/m', $sql) ?: [] as $instruction)
		{
			if (trim($instruction) === '')
			{
				continue;
			}

			if (!$db->query($instruction) && !in_array($db->errno, self::DEJA_EN_PLACE, TRUE))
			{
				self::sql_import_error(basename($path), $db->error);
			}
		}
	}

	public static function import_sql_file(mysqli $db, string $path): void
	{
		$sql = @file_get_contents($path);
		if ($sql === FALSE || trim($sql) === '')
		{
			throw new RuntimeException(self::lang('Fichier SQL vide ou illisible : %s', $path));
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

		throw new RuntimeException(self::lang(
			'L\'import de la base a échoué (%s). Vérifiez que la base est vide et que l\'utilisateur MySQL dispose des droits nécessaires. Détails techniques dans logs/php.log.',
			$file
		));
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
			self::appliquer_migration($db, $migrations_dir.'/'.$name.'.up.sql');
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
		) || self::throw_db(self::lang('Création de la table %s', self::MIGRATIONS_TABLE), $db);
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
			"INSERT INTO nf_user (username, password, salt, email, registration_date, last_activity_date, admin, language, data, deleted)
			 VALUES (?, ?, '', ?, NOW(), NOW(), '1', NULL, '', '0')"
		);
		$stmt->bind_param('sss', $username, $hash, $email);
		$stmt->execute() || self::throw_db(self::lang('Création du compte administrateur'), $db);

		$id = (int) $db->insert_id;

		$first = $enc($admin['first_name'] ?? '');
		$last  = $enc($admin['last_name'] ?? '');

		$stmt = $db->prepare(
			"INSERT INTO nf_user_profile
			 (id, first_name, last_name, signature, country, location, quote, website, linkedin, github, instagram, twitch)
			 VALUES (?, ?, ?, '', '', '', '', '', '', '', '', '')"
		);
		$stmt->bind_param('iss', $id, $first, $last);
		$stmt->execute() || self::throw_db(self::lang('Création du profil administrateur'), $db);

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

	// === Installation des addons (modèle « tout bundlé ») =====================

	/**
	 * Modèle « tout bundlé » (option C) : installe TOUS les modules/widgets/thèmes présents dans le paquet
	 * (chacun via son install.sql local s'il en a un) et les enregistre activés. Aucune dépendance au
	 * marketplace. Per-module try/catch : un module fautif n'abandonne pas l'install. FK désactivées le
	 * temps de l'import massif (ordre des install.sql non garanti vs FKs croisées).
	 */
	/**
	 * Déclarations des addons, lues STATIQUEMENT dans leur `__info()`.
	 *
	 * Chaque addon déclare `core` (livré toujours, non désinstallable), `presets` (les profils
	 * d'installation qui le pré-cochent) et `requires` (les modules sans lesquels il casse).
	 * Cf. tools/check-addon-declarations.php, qui refuse un addon muet ou incohérent.
	 *
	 * Lecture par expression régulière et non par instanciation : l'installeur tourne AVANT que le
	 * framework ne soit amorçable (pas de base, pas d'autoloader d'addons).
	 *
	 * @return array<string,array{type:string,name:string,core:bool,presets:string[],requires:string[],title:string}>
	 */
	public static function addon_declarations(string $root): array
	{
		static $cache = [];

		if (isset($cache[$root]))
		{
			return $cache[$root];
		}

		$out = [];

		foreach (['module' => 'modules', 'widget' => 'widgets', 'theme' => 'themes'] as $type => $dossier)
		{
			foreach (glob($root.'/'.$dossier.'/*', GLOB_ONLYDIR) ?: [] as $dir)
			{
				$name = basename($dir);

				if (!is_file($fichier = $dir.'/'.$name.'.php'))
				{
					continue;
				}

				$src = (string) file_get_contents($fichier);

				// Le titre se cherche DANS le bloc __info(), pas dans tout le fichier : ailleurs,
				// des littéraux comme ->select('title') ou 'order_by' => 'title' arrivaient avant
				// et le module s'appelait « title ».
				$bloc = ($pos = strpos($src, '__info()')) !== FALSE ? substr($src, $pos, 2000) : $src;

				$liste = static function (string $cle) use ($src): array {
					if (!preg_match("/'".$cle."'\s*=>\s*\[([^\]]*)\]/", $src, $m))
					{
						return [];
					}
					preg_match_all("/'([^']+)'/", $m[1], $v);
					return $v[1];
				};

				$out[$type.':'.$name] = [
					'type'     => $type,
					'name'     => $name,
					'core'     => (bool) preg_match("/'core'\s*=>\s*TRUE/", $src),
					// 'distributed' => FALSE : l'addon n'est pas une offre (cas du theme `vitrine`, qui
					// est notre propre site). Il ne doit figurer dans aucun profil d'installation.
					'distributed' => !preg_match("/'distributed'\s*=>\s*FALSE/", $src),
					'presets'  => $liste('presets'),
					'requires' => $liste('requires'),
					// Le titre est soit un litteral, soit $this->lang('...') : on prend le premier
					// litteral qui suit la cle, sans chercher a interpreter l'appel.
					'title'    => preg_match("/'title'\s*=>[^']*'([^']+)'/", $bloc, $m) ? $m[1] : ucfirst($name),
				];
			}
		}

		return $cache[$root] = $out;
	}

	/**
	 * Profils d'installation : la présentation vient de install/lib/presets.php, la COMPOSITION
	 * des déclarations de chaque addon (`'presets' => [...]`). Aucune liste d'addons n'est écrite
	 * à la main — c'est ce qui faisait diverger la version de juin 2026.
	 *
	 * @return array<string,array{title:string,tagline:string,icon:string,module:string[],widget:string[],theme:string[]}>
	 */
	public static function presets(string $root): array
	{
		$meta = require $root.'/install/lib/presets.php';
		$decl = self::addon_declarations($root);
		$out  = [];

		foreach ($meta as $cle => $p)
		{
			$membres = ['module' => [], 'widget' => [], 'theme' => []];

			foreach ($decl as $a)
			{
				if ($a['core'])
				{
					continue; // le cœur n'est jamais un choix : le seed l'installe toujours
				}

				if (empty($a['distributed']))
				{
					continue; // non diffusable : jamais proposé ni installé par un profil
				}

				$retenu = $cle === 'complete'
					? TRUE
					: ($cle === 'core' ? FALSE : in_array($p['tag'], $a['presets'], TRUE));

				if ($retenu)
				{
					$membres[$a['type']][] = $a['name'];
				}
			}

			$out[$cle] = $p + $membres;
		}

		return $out;
	}

	/**
	 * Ferme une sélection de modules sur ses dépendances déclarées.
	 *
	 * Sans cela, cocher « Palmarès » sans « Équipes » produirait un module qui interroge au premier
	 * affichage une table absente — exactement le 500 qui a fait abandonner le paquet allégé en
	 * juin 2026. On ajoute donc silencieusement ce qui manque, et on dit lesquels.
	 *
	 * @param  string[] $modules  noms de modules choisis
	 * @return array{0:string[],1:string[]} [sélection fermée, modules ajoutés d'office]
	 */
	public static function close_requires(array $modules, string $root): array
	{
		$decl    = self::addon_declarations($root);
		$retenus = array_fill_keys($modules, TRUE);
		$ajoutes = [];

		// Point fixe : une dépendance peut elle-même en avoir. Borné par le nombre de modules.
		for ($tour = 0; $tour < 20; $tour++)
		{
			$avant = count($retenus);

			foreach (array_keys($retenus) as $nom)
			{
				foreach ($decl['module:'.$nom]['requires'] ?? [] as $dep)
				{
					if (!isset($retenus[$dep]) && isset($decl['module:'.$dep]) && !$decl['module:'.$dep]['core'])
					{
						$retenus[$dep] = TRUE;
						$ajoutes[]     = $dep;
					}
				}
			}

			if (count($retenus) === $avant)
			{
				break;
			}
		}

		return [array_keys($retenus), array_values(array_unique($ajoutes))];
	}

	/**
	 * Installe les addons livrés dans le paquet.
	 *
	 * @param array<string,string[]>|null $selection  null = TOUT (modèle « tout bundlé », comportement
	 *   historique). Sinon ['module'=>[…], 'widget'=>[…], 'theme'=>[…]] : seuls ces addons-là sont
	 *   installés en plus du cœur, que install/seed.sql a déjà enregistré. Les addons `core` sont
	 *   TOUJOURS installés, quelle que soit la sélection — ils ne sont pas un choix.
	 */
	public static function install_complete(mysqli $db, string $root, ?array $selection = NULL): array
	{
		$decl = self::addon_declarations($root);

		/** Un addon est-il retenu ? Le cœur l'est toujours ; hors cœur, seulement s'il est coché. */
		$retenu = static function (string $type, string $name) use ($selection, $decl): bool {
			if ($selection === NULL)
			{
				return TRUE;
			}

			if (!empty($decl[$type.':'.$name]['core']))
			{
				return TRUE;
			}

			return in_array($name, $selection[$type] ?? [], TRUE);
		};

		$type_ids = self::addon_type_ids($db);
		$errors   = [];
		$modules  = $widgets = $themes = [];

		$db->query('SET FOREIGN_KEY_CHECKS=0');

		foreach (glob($root.'/modules/*', GLOB_ONLYDIR) ?: [] as $dir)
		{
			$name = basename($dir);

			if (!$retenu('module', $name))
			{
				continue;
			}

			try
			{
				$sql = $dir.'/install/install.sql';
				if (is_file($sql))
				{
					self::import_sql_file($db, $sql);
				}
				self::register_addon($db, $type_ids['module'], $name);
				self::baseline_addon_migrations($db, 'module', $name, $root);
				$modules[] = $name;
			}
			catch (\Throwable $e)
			{
				error_log("[install] complete module {$name} : ".$e->getMessage());
				$errors[] = "Module « {$name} » : ".$e->getMessage();
			}
		}

		// Widgets : pas de SQL ; tous les modules étant présents, aucun widget n'est orphelin.
		foreach (glob($root.'/widgets/*', GLOB_ONLYDIR) ?: [] as $dir)
		{
			if (!$retenu('widget', basename($dir)))
			{
				continue;
			}

			self::register_addon($db, $type_ids['widget'], basename($dir));
			self::baseline_addon_migrations($db, 'widget', basename($dir), $root);
			$widgets[] = basename($dir);
		}

		if (isset($type_ids['theme']))
		{
			foreach (glob($root.'/themes/*', GLOB_ONLYDIR) ?: [] as $dir)
			{
				if (!$retenu('theme', basename($dir)))
				{
					continue;
				}

				self::register_addon($db, $type_ids['theme'], basename($dir));
				self::baseline_addon_migrations($db, 'theme', basename($dir), $root);
				$themes[] = basename($dir);
			}
		}

		$db->query('SET FOREIGN_KEY_CHECKS=1');

		// Page d'accueil sûre : news si installé, sinon la page cœur (jamais de 404).
		$default_page = in_array('news', $modules, TRUE) ? 'news' : 'pages';
		self::set_setting($db, 'nf_default_page', $default_page);

		return [
			'installed_modules' => $modules,
			'installed_widgets' => $widgets,
			'installed_themes'  => $themes,
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
	 * BASELINE des migrations d'un addon (install neuf) : marque <type>s/<name>/install/migrations/*.up.sql
	 * comme appliquées (batch 0) SANS les exécuter — install.sql porte déjà le schéma à jour. Les deltas
	 * postérieurs seront joués par l'updater admin (Addon::update). Miroir standalone d'Addon::migrate('baseline').
	 */
	public static function baseline_addon_migrations(mysqli $db, string $type, string $name, string $root): void
	{
		$files = glob($root.'/'.$type.'s/'.$name.'/install/migrations/*.up.sql') ?: [];
		if (!$files)
		{
			return;
		}

		self::ensure_addon_migrations_table($db);

		$stmt = $db->prepare('INSERT IGNORE INTO nf_addon_migrations (type, name, migration, batch) VALUES (?, ?, ?, 0)');
		foreach ($files as $file)
		{
			$migration = basename($file, '.up.sql');
			$stmt->bind_param('sss', $type, $name, $migration);
			$stmt->execute() || self::throw_db("baseline migration {$migration}", $db);
		}
	}

	/**
	 * Les migrations en attente de chaque addon INSTALLÉ (`nf_addon`), dans l'ordre de leur préfixe
	 * daté — ce que fait `Addon::migrate('up')`, pour tous à la fois.
	 *
	 * Jusqu'au 2026-10-01, seule la mise à jour d'un addon par la place de marché appelait son
	 * runner. Les modules livrés avec le cœur — forum, actualités, calendrier… — recevaient leur code
	 * neuf par la mise à jour du cœur, et jamais leurs migrations : nos sites les ont eues parce que
	 * leurs déploiements les posaient à la main.
	 *
	 * S'arrête à la première erreur, en levant : la migration fautive et les suivantes ne sont pas
	 * marquées, et la mise à jour qui l'appelle peut annuler.
	 *
	 * @return string[] `type/nom/migration` de chaque migration appliquée
	 */
	public static function run_addon_migrations(mysqli $db, string $root): array
	{
		if (!self::table_exists($db, 'nf_addon') || !self::table_exists($db, 'nf_addon_type'))
		{
			return [];
		}

		self::ensure_addon_migrations_table($db);

		$res     = $db->query('SELECT t.name AS type, a.name FROM nf_addon a JOIN nf_addon_type t ON t.id = a.type_id ORDER BY t.name, a.name');
		$faites  = [];

		foreach ($res ? $res->fetch_all(MYSQLI_ASSOC) : [] as $addon)
		{
			$type = (string) $addon['type'];
			$nom  = (string) $addon['name'];

			if (!preg_match('/^[a-z0-9_]+$/', $type.$nom) || !($fichiers = glob($root.'/'.$type.'s/'.$nom.'/install/migrations/*.up.sql')))
			{
				continue;
			}

			$migrations = array_map(static fn (string $f): string => basename($f, '.up.sql'), $fichiers);
			sort($migrations);

			$appliquees = [];
			$lot        = 0;
			$stmt       = $db->prepare('SELECT migration, batch FROM nf_addon_migrations WHERE type = ? AND name = ?');
			$stmt->bind_param('ss', $type, $nom);
			$stmt->execute();

			foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $ligne)
			{
				$appliquees[$ligne['migration']] = TRUE;
				$lot = max($lot, (int) $ligne['batch']);
			}

			foreach ($migrations as $migration)
			{
				if (isset($appliquees[$migration]))
				{
					continue;
				}

				self::appliquer_migration($db, $root.'/'.$type.'s/'.$nom.'/install/migrations/'.$migration.'.up.sql');

				$suivant = $lot + 1;
				$marque  = $db->prepare('INSERT INTO nf_addon_migrations (type, name, migration, batch) VALUES (?, ?, ?, ?)');
				$marque->bind_param('sssi', $type, $nom, $migration, $suivant);
				$marque->execute() || self::throw_db("migration {$type}/{$nom}/{$migration}", $db);

				$faites[] = $type.'/'.$nom.'/'.$migration;
			}
		}

		return $faites;
	}

	private static function ensure_addon_migrations_table(mysqli $db): void
	{
		$db->query(
			'CREATE TABLE IF NOT EXISTS `nf_addon_migrations` (
				`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
				`type` varchar(32) NOT NULL,
				`name` varchar(100) NOT NULL,
				`migration` varchar(191) NOT NULL,
				`batch` int(10) unsigned NOT NULL DEFAULT 0,
				`applied_at` timestamp NOT NULL DEFAULT current_timestamp(),
				PRIMARY KEY (`id`),
				UNIQUE KEY `uniq_addon_migration` (`type`,`name`,`migration`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
		) || self::throw_db(self::lang('Création de la table %s', 'nf_addon_migrations'), $db);
	}

	private static function scalar(mysqli $db, string $sql)
	{
		$res = $db->query($sql);
		$row = $res ? $res->fetch_row() : NULL;
		return $row ? $row[0] : NULL;
	}

	// === Marketplace distant (Tier 2) — fetch / download / install sécurisés ===
	//
	// Sécurité (spec §7) : HTTPS strict, vérification SHA-256, anti-zip-slip, tailles/timeout
	// bornés, origine FIXE (anti-SSRF : jamais d'URL saisie par l'utilisateur), aucune exécution
	// de code distant (on extrait + on joue install.sql idempotent, comme un addon local).
	// Standalone (curl + FS + mysqli) → utilisable par l'étape installeur ET l'admin.

	const MARKETPLACE_URL_DEFAULT = 'https://neofrag-reborn.xyz/marketplace';
	const MARKETPLACE_HOSTS       = ['neofrag-reborn.xyz', 'www.neofrag-reborn.xyz']; // origines autorisées (www toléré)
	const MARKETPLACE_MAX_ZIP     = 10485760; // 10 Mo par zip
	const MARKETPLACE_MAX_CATALOG = 2097152;  // 2 Mo pour le catalogue
	const MARKETPLACE_TIMEOUT     = 15;       // secondes (connexion + transfert)

	// Mise a jour du coeur : MEME origine et MEME allow-list que le marketplace. C'est deliberement
	// un seul jeu d'hotes autorises — une seconde allow-list serait une seconde chose a garder a jour,
	// donc une seconde occasion de laisser passer une origine que l'on ne controle pas.
	const UPDATE_URL_DEFAULT = 'https://neofrag-reborn.xyz/update';

	/**
	 * Nom sous lequel la copie de la base voyage DANS l'archive de sauvegarde.
	 *
	 * Les deux moitiés du dispositif — celle qui fabrique l'archive et celle qui la remet en place —
	 * vivent dans deux fichiers differents. Une constante partagee est ce qui les empeche de deriver
	 * en silence : une archive dont le dump s'appellerait autrement se restaurerait « avec succes »
	 * en laissant la base intacte, c'est-a-dire en ne restaurant rien du tout.
	 */
	const BACKUP_SQL_ENTRY = 'DATABASE.sql';
	const UPDATE_MAX_MANIFEST = 4194304;   // 4 Mo : checksum.json porte une empreinte par fichier livre
	const UPDATE_MAX_PACKAGE  = 41943040;  // 40 Mo : le paquet de mise a jour, vendor/ inclus

	/**
	 * URL de base du marketplace (origine FIXE — anti-SSRF). nf_marketplace_url peut surcharger le défaut
	 * MAIS uniquement vers un hôte AUTORISÉ, en HTTPS, port 443, sans userinfo : une valeur injectée en
	 * base (DB compromise) ne peut pas rediriger les fetchs vers un hôte interne/arbitraire. Toute valeur
	 * invalide retombe sur le défaut codé.
	 */
	public static function marketplace_url(?mysqli $db = NULL): string
	{
		$v = $db !== NULL ? self::scalar($db, "SELECT value FROM nf_settings WHERE name = 'nf_marketplace_url'") : NULL;

		return self::sanitize_marketplace_url(is_string($v) ? $v : NULL);
	}

	/**
	 * URL de base des paquets de mise a jour du coeur (version.json, checksum.json, le zip). Meme
	 * garantie que marketplace_url() : `nf_monitoring_check_url` ne peut surcharger le defaut que vers
	 * un hote de l'allow-list, en HTTPS/443, sans userinfo.
	 *
	 * Ce reglage existait deja mais etait VIDE par defaut et declare nulle part — ni schema, ni seed,
	 * ni champ d'administration. Consequence : version.json n'etait jamais telecharge, donc
	 * Theme\Admin::update() ne rendait jamais rien, donc le bouton de mise a jour ne s'affichait
	 * jamais. Lui donner un defaut valide est ce qui remet la chaine entiere en marche.
	 */
	public static function sanitize_update_url(?string $value): string
	{
		return self::sanitize_origin($value, self::UPDATE_URL_DEFAULT);
	}

	/**
	 * Manifeste de version : quelle version est publiee, quel paquet la porte, et son empreinte.
	 * NULL si injoignable ou mal forme — le Monitoring continue alors sans proposer de mise a jour.
	 */
	public static function fetch_version_manifest(string $base_url): ?array
	{
		return self::fetch_update_manifest($base_url, 'version.json', [self::class, 'is_version_manifest']);
	}

	/** Forme attendue de version.json : un bloc `neofrag` portant au moins une version non vide. */
	public static function is_version_manifest(array $d): bool
	{
		return isset($d['neofrag']['version'])
			&& is_string($d['neofrag']['version'])
			&& $d['neofrag']['version'] !== '';
	}

	/**
	 * Manifeste d'integrite : une empreinte MD5 par fichier livre. NULL si injoignable ou mal forme
	 * — le Monitoring bascule alors en MODE DEGRADE (arbre local seul, aucune fausse alerte).
	 */
	public static function fetch_checksum_manifest(string $base_url): ?array
	{
		return self::fetch_update_manifest($base_url, 'checksum.json', [self::class, 'is_checksum_manifest']);
	}

	/** Forme attendue de checksum.json : une table non vide de `chemin => empreinte MD5`. */
	public static function is_checksum_manifest(array $d): bool
	{
		if (!$d)
		{
			return FALSE;
		}

		foreach ($d as $chemin => $md5)
		{
			if (!is_string($chemin) || !is_string($md5) || !preg_match('/^[a-f0-9]{32}$/i', $md5))
			{
				return FALSE;
			}
		}

		return TRUE;
	}

	/**
	 * Recupere et VALIDE LA FORME d'un manifeste. La validation n'est pas un luxe : le site de
	 * distribution repond 200 avec {"redirect":"/fr/..."} sur un chemin que son routeur intercepte.
	 * Un simple is_array() aurait pris ce corps pour un manifeste, l'aurait mis en cache, et le
	 * theme d'administration aurait ensuite lu ->neofrag->version sur un objet qui n'existe pas.
	 *
	 * @param callable(array):bool $valide forme attendue
	 */
	private static function fetch_update_manifest(string $base_url, string $name, callable $valide): ?array
	{
		try
		{
			$json = self::http_get(rtrim($base_url, '/').'/'.$name, self::UPDATE_MAX_MANIFEST);
		}
		catch (\Throwable $e)
		{
			// Une origine qui répond 404, 410 ou par une redirection n'a RIEN PUBLIÉ : c'est l'état
			// normal tant qu'aucune version n'est sortie, et le site conclut « à jour ».
			// Le journaliser écrivait deux lignes à chaque passage du Monitoring, en production comme
			// sur toute installation neuve (check-mise-en-page, 2026-09-23). Seule une vraie panne —
			// réseau, TLS, erreur 5xx — reste écrite.
			$code = (int) $e->getCode();

			if (!(($code >= 300 && $code < 400) || $code === 404 || $code === 410))
			{
				error_log('[update] '.$name.' injoignable : '.$e->getMessage());
			}

			return NULL;
		}

		$data = json_decode($json, TRUE);

		if (!is_array($data) || !$valide($data))
		{
			error_log('[update] '.$name.' ignore : contenu inattendu a cette origine');
			return NULL;
		}

		return $data;
	}

	/**
	 * Telecharge le paquet de mise a jour vers $dest et verifie son empreinte SHA-256 AVANT que le
	 * moindre fichier du site ne soit touche. C'est la seule barriere qui reste une fois l'origine
	 * validee : $this->network()->stream() suit les redirections, ne regarde pas le code HTTP et
	 * ecrirait une page d'erreur 404 dans le fichier .zip sans rien signaler.
	 *
	 * $progress recoit (octets recus, total attendu) pour alimenter la barre de progression.
	 *
	 * @throws RuntimeException reseau, taille, ecriture, ou empreinte non conforme.
	 */
	public static function download_update(string $url, string $dest, string $sha256, ?callable $progress = NULL): void
	{
		if (($fh = @fopen($dest, 'w+b')) === FALSE)
		{
			throw new RuntimeException(self::lang('Impossible d\'écrire %s', $dest));
		}

		try
		{
			self::http_get($url, self::UPDATE_MAX_PACKAGE, static function (string $chunk) use ($fh): void {
				if (@fwrite($fh, $chunk) === FALSE)
				{
					throw new RuntimeException(self::lang('Écriture interrompue (disque plein ?).'));
				}
			}, $progress);
		}
		catch (\Throwable $e)
		{
			// Ne jamais laisser un telechargement partiel derriere soi : un .zip tronque sur le
			// disque serait pris pour un paquet valide par le prochain passage. Constate en test :
			// une origine qui repond 302 laissait un fichier de zero octet en place.
			fclose($fh);
			@unlink($dest);

			throw $e;
		}

		fclose($fh);

		if (!hash_equals(strtolower($sha256), hash_file('sha256', $dest)))
		{
			@unlink($dest);
			throw new RuntimeException(self::lang('Empreinte SHA-256 invalide : paquet de mise à jour rejeté.'));
		}
	}

	/**
	 * Applique un paquet de mise a jour deja telecharge et verifie, sous $root.
	 *
	 * Le paquet est PLAT : chaque entree se pose a son propre chemin. C'est ce qui distingue
	 * `neofrag-reborn-update-<v>.zip` des paquets d'installation, qui rangent tout sous un dossier
	 * `neofrag-reborn/` — appliquer l'un a la place de l'autre creerait un sous-dossier de ce nom au
	 * lieu de remplacer quoi que ce soit, et la mise a jour « reussirait » sans rien mettre a jour.
	 *
	 * Trois regles d'application :
	 *
	 *   - un fichier a la RACINE n'est jamais superpose, sauf index.php. Le paquet embarque des
	 *     fichiers de depot (.editorconfig, COPYING...) qui n'ont rien a faire dans la mise a jour
	 *     d'un site en service ;
	 *   - config/ et install/ ne sont poses que s'ils n'existent pas deja. La configuration d'un
	 *     site en service n'est jamais reecrite ; un fichier d'installation NOUVEAU, lui, arrive ;
	 *   - les fichiers de neofrag/ absents du paquet sont des vestiges d'une version anterieure et
	 *     sont retires — MAIS seulement si le paquet en livrait au moins un. Sans cette garde, un
	 *     paquet tronque effacerait le framework entier.
	 *
	 * @return array{written:int,removed:int}
	 * @throws RuntimeException archive illisible, entree non sure, ou paquet sans fichier applicable.
	 */
	public static function apply_update_package(string $zip_path, string $root, ?callable $progress = NULL): array
	{
		$root = rtrim(str_replace('\\', '/', $root), '/');
		$zip  = new \ZipArchive();

		if ($zip->open($zip_path) !== TRUE)
		{
			throw new RuntimeException(self::lang('Le paquet de mise à jour est illisible.'));
		}

		try
		{
			// Anti-zip-slip : chemin absolu, « .. », antislash, symlink -> rejet GLOBAL de l'archive.
			if (!self::zip_entries_safe($zip))
			{
				throw new RuntimeException(self::lang('Le paquet contient une entrée non sûre : il est rejeté.'));
			}

			// Le manifeste du paquet, s'il en porte un.
			//
			// Il DECLARE ce que la mise a jour protege et ce qu'elle supprime, la ou le balayage
			// historique le DEDUIT de ce que le paquet livre. La difference compte : un paquet
			// tronque, ou d'une autre nature, faisait deduire « tout le reste est un vestige » — il
			// a failli effacer le framework entier, et ne porte une garde que depuis.
			//
			// Facultatif, et c'est voulu : un paquet construit avant cette version n'en a pas, et
			// doit continuer de s'appliquer. Sans manifeste, on retombe sur le balayage deduit.
			$manifeste = self::read_package_manifest($zip);

			$entries = [];

			for ($i = 0; $i < $zip->numFiles; $i++)
			{
				$entry = $zip->getNameIndex($i);

				if ($entry === FALSE || substr($entry, -1) === '/')
				{
					continue; // dossier : cree implicitement a l'ecriture
				}
				if ($entry === self::UPDATE_MANIFEST)
				{
					continue; // le manifeste decrit le paquet, il ne s'installe pas
				}
				if (!preg_match('#/|^index\.php$#', $entry))
				{
					continue; // fichier de racine autre qu'index.php
				}
				if ((str_starts_with($entry, 'config/') && file_exists($root.'/'.$entry)) || $entry === 'install/db.txt')
				{
					// La configuration en place et le verrou d'installation appartiennent au site. Le
					// reste d'`install/` est du code du produit : il suit les versions (2026-10-01).
					continue;
				}
				if (self::path_is_protected($entry, $manifeste))
				{
					continue; // declare protege par le paquet lui-meme
				}

				$entries[$entry] = $i;
			}

			if (!($total = count($entries)))
			{
				throw new RuntimeException(self::lang('Le paquet ne contient aucun fichier applicable.'));
			}

			$n = 0;

			foreach ($entries as $entry => $i)
			{
				$dir = $root.'/'.dirname($entry);

				if (!is_dir($dir) && !@mkdir($dir, 0755, TRUE) && !is_dir($dir))
				{
					throw new RuntimeException(self::lang('Impossible de créer %s', $dir));
				}

				if (@file_put_contents($root.'/'.$entry, $zip->getFromIndex($i)) === FALSE)
				{
					throw new RuntimeException(self::lang('Impossible d\'écrire %s', $entry));
				}

				$n++;

				if ($progress !== NULL)
				{
					$progress($n, $total);
				}
			}
		}
		finally
		{
			$zip->close();
		}

		// Une liste DECLAREE ne peut pas se tromper comme un balayage deduit : quand le paquet en
		// porte une, elle fait foi, et le balayage ne s'execute pas.
		$removed = $manifeste !== NULL
			? self::remove_declared($manifeste, $root)
			: self::sweep_stale_core($entries, $root);

		return ['written' => $total, 'removed' => $removed];
	}

	/** Nom du manifeste, a la racine de l'archive de mise a jour. */
	public const UPDATE_MANIFEST = 'nf-manifest.json';

	/**
	 * Lit le manifeste du paquet, ou NULL s'il n'en porte pas.
	 *
	 * Un manifeste illisible est traite comme absent, et non comme une erreur : mieux vaut appliquer
	 * la mise a jour a l'ancienne que la refuser pour un fichier annexe. En revanche il doit avoir la
	 * forme attendue, sans quoi on ne saurait pas ce qu'on protege ni ce qu'on efface.
	 *
	 * @return array{protected: list<string>, remove: list<string>}|null
	 */
	private static function read_package_manifest(\ZipArchive $zip): ?array
	{
		$brut = $zip->getFromName(self::UPDATE_MANIFEST);

		if ($brut === FALSE)
		{
			return NULL;
		}

		$data = json_decode((string) $brut, TRUE);

		if (!is_array($data))
		{
			return NULL;
		}

		$liste = static function ($valeur): array {
			if (!is_array($valeur))
			{
				return [];
			}

			$propre = [];

			foreach ($valeur as $chemin)
			{
				if (!is_string($chemin))
				{
					continue;
				}

				$chemin = ltrim(str_replace('\\', '/', trim($chemin)), '/');

				// Un chemin qui remonte, absolu, ou vide, n'a rien a faire dans un manifeste : le
				// paquet decrirait alors des fichiers hors du site.
				if ($chemin === '' || strpos($chemin, '../') !== FALSE || $chemin === '..' || preg_match('#^[A-Za-z]:|^/#', $chemin))
				{
					continue;
				}

				$propre[] = $chemin;
			}

			return $propre;
		};

		return [
			'protected' => $liste($data['protected'] ?? []),
			'remove'    => $liste($data['remove'] ?? []),
		];
	}

	/**
	 * Le paquet declare-t-il ce chemin comme protege ?
	 *
	 * Une entree finissant par `/` protege tout un dossier ; sinon c'est un fichier exact.
	 *
	 * @param array{protected: list<string>, remove: list<string>}|null $manifeste
	 */
	private static function path_is_protected(string $entry, ?array $manifeste): bool
	{
		if ($manifeste === NULL)
		{
			return FALSE;
		}

		foreach ($manifeste['protected'] as $motif)
		{
			if (substr($motif, -1) === '/')
			{
				if (strpos($entry, $motif) === 0)
				{
					return TRUE;
				}
			}
			else if ($entry === $motif)
			{
				return TRUE;
			}
		}

		return FALSE;
	}

	/**
	 * Supprime les fichiers que le paquet declare retires, et RIEN d'autre.
	 *
	 * Deux refus deliberes : on ne supprime jamais un dossier (un manifeste errone effacerait une
	 * arborescence entiere), et on ne sort jamais de la racine (verifie apres resolution, parce
	 * qu'un lien symbolique pointe ou il veut).
	 *
	 * @param array{protected: list<string>, remove: list<string>} $manifeste
	 */
	private static function remove_declared(array $manifeste, string $root): int
	{
		$removed = 0;
		$reel    = realpath($root);

		if ($reel === FALSE)
		{
			return 0;
		}

		$reel = rtrim(str_replace('\\', '/', $reel), '/').'/';

		foreach ($manifeste['remove'] as $chemin)
		{
			$cible = $root.'/'.$chemin;

			if (!is_file($cible))
			{
				continue;
			}

			$resolu = realpath($cible);

			if ($resolu === FALSE || strpos(str_replace('\\', '/', $resolu), $reel) !== 0)
			{
				continue;
			}

			if (@unlink($cible))
			{
				$removed++;
			}
		}

		return $removed;
	}

	/**
	 * Retire de $root/neofrag les fichiers que le paquet ne livre pas (vestiges d'une version
	 * anterieure). Ne fait RIEN si le paquet n'a livre aucun fichier de neofrag/ : un paquet
	 * tronque ou d'une autre nature effacerait sinon le framework entier.
	 *
	 * @param array<string,int> $entries entrees appliquees (chemin => index dans l'archive)
	 */
	private static function sweep_stale_core(array $entries, string $root): int
	{
		$livres = [];

		foreach (array_keys($entries) as $entry)
		{
			if (strpos($entry, 'neofrag/') === 0)
			{
				$livres[$entry] = TRUE;
			}
		}

		if (!$livres || !is_dir($dir = $root.'/neofrag'))
		{
			return 0;
		}

		$removed = 0;
		$it      = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)
		);

		foreach ($it as $file)
		{
			if (!$file->isFile())
			{
				continue;
			}

			$rel = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));

			if (!isset($livres[$rel]) && @unlink($file->getPathname()))
			{
				$removed++;
			}
		}

		return $removed;
	}

	/**
	 * Remet en place une sauvegarde produite par le panneau de surveillance.
	 *
	 * C'est la moitie qui manquait. Le CMS savait prendre une archive complete avant d'ecrire — il ne
	 * savait pas s'en reservir. Une mise a jour qui echouait a mi-parcours laissait donc un site
	 * mi-ancien mi-neuf, et l'archive posee a cote, inerte.
	 *
	 * L'archive est celle de `Monitoring_admin_ajax::_backup()` : les fichiers y sont a plat, chacun
	 * a son chemin relatif, plus un `DATABASE.sql` a la racine de l'archive.
	 *
	 * Quatre ecarts VOLONTAIRES avec une restauration « a l'identique ». Chacun repare un degat que
	 * la restauration naive causerait :
	 *
	 *   - `DATABASE.sql` n'est JAMAIS ecrit dans l'arborescence du site. Il part vers $sql_dest, que
	 *     l'appelant choisit et protege, parce qu'il porte toute la base en clair ;
	 *   - `config/` n'est pas restaure. Une mise a jour ne reecrit jamais la configuration d'un site
	 *     en service (cf. apply_update_package), il n'y a donc rien a y annuler — tandis que rendre
	 *     a un site les identifiants de base d'il y a trois semaines le couperait de sa propre base ;
	 *   - `logs/` n'est pas restaure. Les journaux de l'incident qu'on est en train de reparer sont
	 *     la seule trace de ce qui a echoue ; les ecraser par ceux d'avant efface le constat ;
	 *   - `cache/` n'est pas restaure. Un cache se reconstruit, et melanger des artefacts compiles
	 *     par deux versions differentes est pire que de repartir de zero.
	 *
	 * Comme pour une mise a jour, les fichiers de `neofrag/` que l'archive ne contient PAS sont des
	 * vestiges de la version qu'on annule, et sont retires — sous la meme garde : rien n'est balaye
	 * si l'archive ne livrait aucun fichier du coeur.
	 *
	 * Deux limites connues, assumees, de meme nature — on remet ce que l'archive contient, on ne
	 * devine pas ce qu'elle ignore :
	 *
	 *   - une table CREEE par la version annulee survit a la restauration. Le dump reconstruit ce
	 *     qu'il porte ; une table inconnue de l'ancien code lui est inerte, tandis que supprimer des
	 *     tables dont on ne sait rien serait, lui, un risque reel de perte de donnees ;
	 *   - hors de `neofrag/`, un FICHIER ajoute par la version annulee survit lui aussi — une
	 *     dependance apparue sous `vendor/`, par exemple. L'autoloader restaure etant l'ancien, ces
	 *     fichiers ne sont charges par rien. Le balayage ne vise que le coeur, ou l'inventaire est
	 *     connu et complet.
	 *
	 * @return array{restored:int,removed:int,sql:bool}
	 * @throws RuntimeException archive illisible, entree non sure, ou archive sans fichier restaurable.
	 */
	public static function restore_backup_package(string $zip_path, string $root, ?string $sql_dest = NULL, ?callable $progress = NULL): array
	{
		$root = rtrim(str_replace('\\', '/', $root), '/');
		$zip  = new \ZipArchive();

		if ($zip->open($zip_path) !== TRUE)
		{
			throw new RuntimeException(self::lang('La sauvegarde est illisible.'));
		}

		try
		{
			// Meme garde que pour un paquet de mise a jour : chemin absolu, « .. », antislash ou
			// symlink -> rejet GLOBAL. Une sauvegarde arrive de backups/, mais rien n'interdit a un
			// administrateur d'y deposer un fichier a lui.
			if (!self::zip_entries_safe($zip))
			{
				throw new RuntimeException(self::lang('La sauvegarde contient une entrée non sûre : elle est rejetée.'));
			}

			$entries = [];
			$sql     = FALSE;

			for ($i = 0; $i < $zip->numFiles; $i++)
			{
				$entry = $zip->getNameIndex($i);

				if ($entry === FALSE || substr($entry, -1) === '/')
				{
					continue; // dossier : cree implicitement a l'ecriture
				}
				if ($entry === self::BACKUP_SQL_ENTRY)
				{
					$sql = $i;
					continue; // la base ne se restaure pas en posant un fichier
				}
				if (preg_match('#^(cache|config|logs|backups)/#', $entry))
				{
					continue; // les quatre exceptions documentees ci-dessus
				}

				$entries[$entry] = $i;
			}

			if (!$entries)
			{
				throw new RuntimeException(self::lang('La sauvegarde ne contient aucun fichier restaurable.'));
			}

			// Refuser MAINTENANT, avant d'avoir touche un seul fichier : une restauration de fichiers
			// sans la base derriere laisserait un site plus incoherent qu'il ne l'etait.
			if ($sql_dest !== NULL && $sql === FALSE)
			{
				throw new RuntimeException(self::lang('La sauvegarde ne contient pas de copie de la base (%s).', self::BACKUP_SQL_ENTRY));
			}

			$total = count($entries) + ($sql_dest !== NULL ? 1 : 0);
			$n     = 0;

			foreach ($entries as $entry => $i)
			{
				$dir = $root.'/'.dirname($entry);

				if (!is_dir($dir) && !@mkdir($dir, 0755, TRUE) && !is_dir($dir))
				{
					throw new RuntimeException(self::lang('Impossible de créer %s', $dir));
				}

				if (@file_put_contents($root.'/'.$entry, $zip->getFromIndex($i)) === FALSE)
				{
					throw new RuntimeException(self::lang('Impossible de restaurer %s', $entry));
				}

				$n++;

				if ($progress !== NULL)
				{
					$progress($n, $total);
				}
			}

			if ($sql_dest !== NULL && $sql !== FALSE)
			{
				if (@file_put_contents($sql_dest, $zip->getFromIndex($sql)) === FALSE)
				{
					throw new RuntimeException(self::lang('Impossible d\'extraire %s vers %s', self::BACKUP_SQL_ENTRY, $sql_dest));
				}

				// Le dump porte toute la base en clair, mots de passe hashes et jetons compris.
				@chmod($sql_dest, 0600);

				if ($progress !== NULL)
				{
					$progress(++$n, $total);
				}
			}
		}
		finally
		{
			$zip->close();
		}

		return [
			'restored' => count($entries),
			'removed'  => self::sweep_stale_core($entries, $root),
			'sql'      => $sql_dest !== NULL
		];
	}

	/**
	 * Valide un override d'URL marketplace (depuis nf_settings) contre l'allow-list — fonction pure,
	 * réutilisable au runtime (le framework lit la config via le service-locator, pas un mysqli brut).
	 * Toute valeur vide/invalide retombe sur le défaut codé : une valeur injectée en base (DB compromise)
	 * ne peut pas rediriger les fetchs vers un hôte interne/arbitraire (anti-SSRF).
	 */
	public static function sanitize_marketplace_url(?string $value): string
	{
		return self::sanitize_origin($value, self::MARKETPLACE_URL_DEFAULT);
	}

	/**
	 * Coeur commun des deux precedentes : rend $value si elle vise un hote autorise en HTTPS/443 sans
	 * userinfo, et $defaut dans TOUS les autres cas — valeur vide, schema autre, hote inconnu, port
	 * detourne, identifiants dans l'URL. Aucun cas ne leve : une base compromise doit degrader vers
	 * l'origine codee en dur, jamais interrompre le site.
	 */
	private static function sanitize_origin(?string $value, string $defaut): string
	{
		if ($value !== NULL && $value !== '')
		{
			$value = rtrim($value, '/');
			$p     = parse_url($value);

			if (is_array($p)
				&& ($p['scheme'] ?? '') === 'https'
				&& in_array($p['host'] ?? '', self::MARKETPLACE_HOSTS, TRUE)
				&& (int) ($p['port'] ?? 443) === 443
				&& !isset($p['user']) && !isset($p['pass']))
			{
				return $value;
			}
		}

		return $defaut;
	}

	/**
	 * Le titre ou la description d'un addon du catalogue, dans la langue courante.
	 *
	 * Le catalogue porte le texte français (`title`, `description`) et ses traductions, relevées au
	 * packaging dans les fichiers de langue de l'addon (`i18n.<code>.<champ>`, tools/package-addons.php).
	 * Un catalogue plus ancien, sans `i18n`, rend le français : rien ne casse, rien ne manque.
	 */
	public static function texte_catalogue(array $addon, string $champ): string
	{
		$code = \function_exists('nf_install_langue') ? nf_install_langue() : 'fr';

		return (string) ($addon['i18n'][$code][$champ] ?? $addon[$champ] ?? '');
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
			throw new RuntimeException(self::lang('Empreinte SHA-256 invalide (zip rejeté).'));
		}

		$tmp_zip = tempnam(sys_get_temp_dir(), 'nfdl');
		if (@file_put_contents($tmp_zip, $zip_data) === FALSE)
		{
			throw new RuntimeException(self::lang('Écriture temporaire impossible.'));
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
				throw new RuntimeException(self::lang('Métadonnées d\'addon incomplètes.'));
			}
		}

		$type = $meta['type'];
		$name = $meta['name'];

		if (!in_array($type, ['module', 'widget', 'theme'], TRUE) || !preg_match('/^[a-z0-9_]+$/', $name))
		{
			throw new RuntimeException(self::lang('Addon refusé (type/nom invalide).'));
		}

		// Chemin de fichier RELATIF sûr : <type>s/<name>.zip. Bloque host/chemin injectés via le catalogue.
		if (!preg_match('#^(modules|widgets|themes|addons)/[a-z0-9_]+\.zip$#', $meta['file']) || strpos($meta['file'], '..') !== FALSE)
		{
			throw new RuntimeException(self::lang('Chemin de fichier d\'addon refusé.'));
		}

		return [$type, $name];
	}

	/** GET HTTPS strict : TLS vérifié, pas de redirection (anti-SSRF), timeout + taille bornés. */
	private static function http_get(string $url, int $max_bytes, ?callable $sink = NULL, ?callable $progress = NULL): string
	{
		if (stripos($url, 'https://') !== 0)
		{
			throw new RuntimeException(self::lang('URL non-HTTPS refusée.'));
		}
		if (!function_exists('curl_init'))
		{
			throw new RuntimeException(self::lang('Extension curl absente.'));
		}

		$buf     = '';
		$written = 0;
		$total   = 0;
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
			// Cap de taille : on vérifie AVANT d'accumuler → rien ne dépasse jamais max_bytes. Avec un
			// $sink, les octets vont au fichier au lieu du buffer : le paquet de mise à jour fait des
			// dizaines de Mo, qu'il serait absurde de tenir en mémoire pour les réécrire ensuite.
			CURLOPT_WRITEFUNCTION   => function ($ch, string $chunk) use (&$buf, &$written, &$total, &$too_big, $max_bytes, $sink, $progress): int {
				if ($written + strlen($chunk) > $max_bytes)
				{
					$too_big = TRUE;
					return 0; // abort
				}

				if ($sink !== NULL)
				{
					$sink($chunk);
				}
				else
				{
					$buf .= $chunk;
				}

				$written += strlen($chunk);

				if ($progress !== NULL)
				{
					if ($total === 0)
					{
						$total = (int) curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
					}

					$progress($written, $total > 0 ? $total : $max_bytes);
				}

				return strlen($chunk);
			},
		]);

		$ok   = curl_exec($ch);
		$code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$err  = curl_error($ch);

		if ($too_big)
		{
			throw new RuntimeException(self::lang('Réponse trop volumineuse (> %d octets).', $max_bytes));
		}
		if ($ok === FALSE)
		{
			throw new RuntimeException('curl : '.($err ?: self::lang('échec réseau')));
		}
		if ($code !== 200)
		{
			// Le code HTTP est aussi le code de l'exception : l'appelant distingue ainsi « rien n'est
			// publié ici » (404, redirection) d'une vraie panne.
			throw new RuntimeException('HTTP '.$code, $code);
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
			throw new RuntimeException(self::lang('Archive illisible.'));
		}

		try
		{
			for ($i = 0; $i < $zip->numFiles; $i++)
			{
				$entry = $zip->getNameIndex($i);

				if ($entry === FALSE || $entry === '' || $entry[0] === '/'
					|| strpos($entry, '..') !== FALSE || strpos($entry, '\\') !== FALSE)
				{
					throw new RuntimeException(self::lang('Entrée d\'archive non sûre : %s', $entry));
				}
				// Structure attendue (package-addons) : tout sous <name>/.
				if ($entry !== $name && strpos($entry, $name.'/') !== 0)
				{
					throw new RuntimeException(self::lang('Entrée hors de l\'addon : %s', $entry));
				}
				// Symlink (Unix) : pointerait hors de l'addon malgré un nom valide → rejet.
				if (self::entry_is_symlink($zip, $i))
				{
					throw new RuntimeException(self::lang('Entrée symlink interdite : %s', $entry));
				}
			}

			$parent = $root.'/'.$type.'s';
			if (!$zip->extractTo($parent))
			{
				throw new RuntimeException(self::lang('Extraction impossible.'));
			}
		}
		finally
		{
			$zip->close();
		}

		if (!is_dir($root.'/'.$type.'s/'.$name))
		{
			throw new RuntimeException(self::lang('Addon absent après extraction.'));
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
			throw new RuntimeException(self::lang('Écriture impossible : %s', $path));
		}
	}

	private static function throw_db(string $context, mysqli $db): never
	{
		throw new RuntimeException($context.' : '.$db->error);
	}
}
