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
 * NeoFrag Reborn — génère le SQL d'install/désinstall embarqué de chaque module.
 *
 * Pour chaque module packageable possédant des tables (cf. tools/table-map.php), écrit :
 *   - modules/<name>/install/install.sql   : CREATE TABLE IF NOT EXISTS (idempotent, sûr au reset)
 *   - modules/<name>/install/uninstall.sql : DROP TABLE IF EXISTS (ordre inverse)
 * jouées par Loadables\Addon::install()/uninstall() à l'install ZIP/scan. Le packaging
 * (tools/package-addons.php) embarque ensuite ce dossier install/ dans le zip.
 *
 * Garde-fou : la partition modules ∪ core de table-map.php doit recouvrir EXACTEMENT
 * les tables vives (toute table non classée fait échouer le script — anti-oubli).
 *
 * Connexion : config/db.php ($db[0]) surchargé par NF_DB_* (cf. tools/dump-schema.php).
 * Usage : docker compose exec -T web php tools/extract-module-sql.php
 */

const CONFIG_DB = __DIR__ . '/../config/db.php';
const MODULES_DIR = __DIR__ . '/../modules';

main();

function main(): void
{
    $check_only = in_array('--check', $_SERVER['argv'] ?? [], true);

    $map = require __DIR__ . '/table-map.php';
    $db  = connect();

    $live = live_tables($db);

    // Garde-fou : échoue si une table vive n'est classée ni en module ni en core (oubli au table-map).
    assert_partition($map, $live);

    if ($check_only) {
        echo "✓ table-map valide : la partition modules ∪ core recouvre exactement les tables vives.\n";
        return;
    }

    $written = 0;
    $skipped = [];

    foreach ($map['modules'] as $module => $tables) {
        if (!$tables) {
            $skipped[] = $module;
            continue;
        }

        $dir = MODULES_DIR . "/{$module}/install";
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            fwrite(STDERR, "  KO: impossible de créer {$dir}\n");
            exit(1);
        }

        file_put_contents("{$dir}/install.sql", build_install($db, $module, $tables));
        file_put_contents("{$dir}/uninstall.sql", build_uninstall($module, $tables));

        printf("  %-13s %d table(s)\n", $module, count($tables));
        $written++;
    }

    if ($skipped) {
        echo "  (sans table, ignorés : " . implode(', ', $skipped) . ")\n";
    }
    echo "\n✓ {$written} modules avec SQL d'install généré.\n";
}

/** install.sql : CREATE TABLE IF NOT EXISTS, FK désactivées le temps du batch. */
function build_install(mysqli $db, string $module, array $tables): string
{
    $out  = header_block("install du module « {$module} » — tables propres au module");
    $out .= "SET FOREIGN_KEY_CHECKS = 0;\nSET NAMES utf8mb4;\n\n";

    foreach ($tables as $table) {
        $create = show_create($db, $table);
        // Idempotent (rejoué à chaque reset/scan sans détruire les données) + pas d'id de départ figé.
        $create = preg_replace('/^CREATE TABLE `/', 'CREATE TABLE IF NOT EXISTS `', $create, 1);
        $create = preg_replace('/ AUTO_INCREMENT=\d+/', '', $create);
        $create = portable_collation($create);
        $out .= "{$create};\n\n";
    }

    $out .= "SET FOREIGN_KEY_CHECKS = 1;\n";
    return $out;
}

/** uninstall.sql : DROP TABLE IF EXISTS, ordre inverse, FK désactivées. */
function build_uninstall(string $module, array $tables): string
{
    $out  = header_block("désinstall du module « {$module} » — supprime ses tables (données perdues)");
    $out .= "SET FOREIGN_KEY_CHECKS = 0;\nSET NAMES utf8mb4;\n\n";

    foreach (array_reverse($tables) as $table) {
        $out .= "DROP TABLE IF EXISTS `{$table}`;\n";
    }

    $out .= "\nSET FOREIGN_KEY_CHECKS = 1;\n";
    return $out;
}

/**
 * La partition modules ∪ core doit recouvrir EXACTEMENT les tables vives.
 * - table vive non classée  → erreur (ajouter au mapping)
 * - table classée absente   → avertissement (table supprimée depuis ?)
 * - table dans 2 modules / module+core → erreur
 */
function assert_partition(array $map, array $live): void
{
    $owners = [];
    foreach ($map['modules'] as $module => $tables) {
        foreach ($tables as $t) {
            $owners[$t][] = $module;
        }
    }
    foreach ($map['core'] as $t) {
        $owners[$t][] = 'core';
    }

    $errors = [];

    foreach ($owners as $t => $who) {
        if (count($who) > 1) {
            $errors[] = "table `{$t}` revendiquée par plusieurs : " . implode(', ', $who);
        }
    }

    $classified = array_keys($owners);

    foreach ($live as $t) {
        if (!in_array($t, $classified, true)) {
            $errors[] = "table vive `{$t}` NON classée (ni module ni core) — compléter tools/table-map.php";
        }
    }

    foreach ($classified as $t) {
        if (!in_array($t, $live, true)) {
            fwrite(STDERR, "  avertissement : table classée `{$t}` absente de la base vive (ignorée)\n");
        }
    }

    if ($errors) {
        fwrite(STDERR, "Mapping table→module incohérent :\n  - " . implode("\n  - ", $errors) . "\n");
        exit(1);
    }
}

function live_tables(mysqli $db): array
{
    $tables = [];
    $res = $db->query('SHOW TABLES');
    while ($row = $res->fetch_row()) {
        $t = $row[0];
        if (!str_starts_with($t, '_backup_') && !str_starts_with($t, '_tmp')) {
            $tables[] = $t;
        }
    }
    sort($tables);
    return $tables;
}

function show_create(mysqli $db, string $table): string
{
    $res = $db->query("SHOW CREATE TABLE `{$table}`");
    $row = $res->fetch_row();
    return $row[1];
}

/**
 * Normalise les collations MariaDB-11-only (uca1400) vers une collation UNIVERSELLE
 * (utf8mb*_unicode_ci, supportée par MySQL 5.7+/8 ET MariaDB 10+/11). La base de dev tourne
 * sous MariaDB 11 → SHOW CREATE TABLE émet uca1400 ; sans normalisation le paquet est
 * ININSTALLABLE sur la plupart des hébergements (MySQL 8, MariaDB 10.6 LTS Plesk) — chaque
 * CREATE TABLE échoue en « Unknown collation » et les tables manquent.
 */
function portable_collation(string $sql): string
{
    return preg_replace('/(utf8mb[34])_uca1400_ai_ci/', '$1_unicode_ci', $sql);
}

function header_block(string $what): string
{
    return "-- NeoFrag Reborn — {$what}.\n"
        . "-- Généré par tools/extract-module-sql.php depuis la base vive. NE PAS éditer à la main.\n"
        . "-- Régénérer : docker compose exec -T web php tools/extract-module-sql.php\n\n";
}

function connect(): mysqli
{
    $cfg = ['hostname' => '127.0.0.1', 'port' => 3306, 'username' => 'root', 'password' => '', 'database' => 'neofrag'];

    if (is_file(CONFIG_DB)) {
        $db = [];
        require CONFIG_DB;
        if (!empty($db[0]) && is_array($db[0])) {
            $cfg = array_merge($cfg, $db[0]);
        }
    }

    foreach (['hostname' => 'NF_DB_HOST', 'port' => 'NF_DB_PORT', 'username' => 'NF_DB_USER', 'password' => 'NF_DB_PASS', 'database' => 'NF_DB_NAME'] as $key => $var) {
        $val = getenv($var);
        if ($val !== false && $val !== '') {
            $cfg[$key] = $val;
        }
    }

    mysqli_report(MYSQLI_REPORT_OFF);
    $conn = @new mysqli($cfg['hostname'], $cfg['username'], (string) $cfg['password'], $cfg['database'], (int) $cfg['port']);
    if ($conn->connect_errno) {
        fwrite(STDERR, "Connexion BDD impossible : {$conn->connect_error}\n");
        exit(1);
    }
    $conn->set_charset('utf8mb4');
    return $conn;
}
