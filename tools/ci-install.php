<?php
declare(strict_types=1);
// Outil d'administration : jamais servi en HTTP (sinon maintenance/migrations/dumps
// seraient executables par n'importe qui si tools/ etait expose par erreur).
if (PHP_SAPI !== 'cli')
{
	http_response_code(404);
	exit;
}


/**
 * Installation non-interactive — pour la CI (smoke E2E) ou un montage local rapide.
 *
 * Monte une instance NeoFrag Reborn INSTALLÉE (tous les modules/widgets/thèmes + un admin) sans passer
 * par l'assistant web. Réutilise l'Installer standalone (mysqli + FS, pas de boot framework).
 * L'admin créé fournit un membre avec email → permet au smoke d'exercer le flux anti_flood.
 *
 * Paramètres via variables d'environnement (défauts = stack docker-compose) :
 *   NF_DB_HOST (db) · NF_DB_PORT (3306) · NF_DB_USER (neofrag) · NF_DB_PASS (neofragpass) · NF_DB_NAME (neofrag)
 *   NF_ADMIN_EMAIL (ci-admin@example.test) · NF_ADMIN_PASS (CI-Smoke-2026!)
 *
 * Conçu pour une base FRAÎCHE (la CI part d'une DB vide). Usage :
 *   docker compose exec -T -e NF_DB_HOST=db web php tools/ci-install.php
 */

require __DIR__ . '/../install/lib/installer.php';

use NF\Install\Lib\Installer;

$root = dirname(__DIR__);

$host  = getenv('NF_DB_HOST') ?: 'db';
$port  = (int) (getenv('NF_DB_PORT') ?: 3306);
$user  = getenv('NF_DB_USER') ?: 'neofrag';
$pass  = getenv('NF_DB_PASS') ?: 'neofragpass';
$name  = getenv('NF_DB_NAME') ?: 'neofrag';
$email = getenv('NF_ADMIN_EMAIL') ?: 'ci-admin@example.test';
$apass = getenv('NF_ADMIN_PASS') ?: 'CI-Smoke-2026!';

echo "[ci-install] config + connexion ($user@$host:$port/$name)…\n";

// 1. Fichiers de config (db/crypt/password/email) + neofrag.php (defines runtime).
Installer::write_config($root . '/config', [
    'hostname' => $host,
    'username' => $user,
    'password' => $pass,
    'database' => $name,
]);
file_put_contents($root . '/config/neofrag.php',
    "<?php\n\n"
    . "define('NEOFRAG_DEBUG_BAR', FALSE);\n"
    . "define('NEOFRAG_SAFE_MODE', FALSE);\n"
    . "define('NEOFRAG_DEMO',      FALSE);\n"
    . "define('NEOFRAG_LOGS',      FALSE);\n"
    . "define('NEOFRAG_LOGS_DB',   FALSE);\n"
    . "define('NEOFRAG_LOGS_I18N', FALSE);\n"
);

$db = @mysqli_connect($host, $user, $pass, $name, $port);
if (!$db) {
    fwrite(STDERR, '[ci-install] connexion DB échouée : ' . mysqli_connect_error() . "\n");
    exit(1);
}

// 2. Schéma + seed du cœur (Tier 0) + migrations. (Les modules apportent leurs tables à l'étape 3.)
echo "[ci-install] schéma + seed + migrations…\n";
Installer::import_sql_file($db, $root . '/install/schema.sql');
Installer::import_sql_file($db, $root . '/install/seed.sql');
Installer::run_migrations($db, $root . '/migrations', null);

// 3. Tout bundlé : installe TOUS les modules/widgets/thèmes locaux (modèle WordPress) → des pages
//    publiques réelles (news/forum/…) pour le smoke.
echo "[ci-install] install complète (tous les modules)…\n";
$summary = Installer::install_complete($db, $root);
if (!empty($summary['errors'])) {
    fwrite(STDERR, "[ci-install] erreurs install : " . implode(' | ', $summary['errors']) . "\n");
    exit(1);
}

// 4. Admin (fournit un membre avec email pour le flux anti_flood du smoke).
echo "[ci-install] admin…\n";
Installer::create_admin($db, ['username' => 'ci-admin', 'email' => $email, 'password' => $apass]);

echo "[ci-install] OK — install complète + admin {$email}\n";
echo "  → smoke : php tools/smoke-test.php http://localhost:8080 --email={$email}\n";
