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
 * NeoFrag — runner de migrations SQL.
 *
 * Applique/annule les fichiers migrations/*.up.sql / *.down.sql et trace
 * l'état dans la table nf_migrations. Conçu pour combler l'absence de runner
 * (les .sql étaient appliqués à la main, sans suivi → risque de drift).
 *
 * Usage :
 *   php tools/migrate.php status
 *   php tools/migrate.php up [--pretend]
 *   php tools/migrate.php down [--step=N] [--pretend] [--force]
 *
 * Connexion : lue depuis config/db.php ($db[0]), surchargée par les variables
 * d'environnement NF_DB_HOST, NF_DB_PORT, NF_DB_USER, NF_DB_PASS, NF_DB_NAME.
 * (Le hostname 'db' du compose n'est résoluble que dans le réseau Docker ;
 *  depuis l'hôte, exporter NF_DB_HOST=127.0.0.1 NF_DB_PORT=<port exposé>.)
 */

const MIGRATIONS_DIR = __DIR__ . '/../migrations';
const CONFIG_DB      = __DIR__ . '/../config/db.php';
const TABLE          = 'nf_migrations';

main($argv);

function main(array $argv): void
{
    $args    = array_slice($argv, 1);
    $command = $args[0] ?? 'status';
    $flags   = parse_flags($args);

    $db = connect();
    ensure_table($db);

    switch ($command) {
        case 'status':
            cmd_status($db);
            break;
        case 'up':
            cmd_up($db, (bool) ($flags['pretend'] ?? false));
            break;
        case 'down':
            cmd_down($db, (int) ($flags['step'] ?? 1), (bool) ($flags['pretend'] ?? false), (bool) ($flags['force'] ?? false));
            break;
        case 'baseline':
            cmd_baseline($db, isset($flags['until']) && is_string($flags['until']) ? $flags['until'] : null);
            break;
        default:
            fwrite(STDERR, "Commande inconnue : {$command}\n");
            fwrite(STDERR, "Usage : status | up [--pretend] | down [--step=N] [--pretend] [--force] | baseline [--until=NAME]\n");
            exit(2);
    }
}

function parse_flags(array $args): array
{
    $flags = [];
    foreach ($args as $a) {
        if (preg_match('/^--([a-z]+)(?:=(.*))?$/', $a, $m)) {
            $flags[$m[1]] = $m[2] ?? true;
        }
    }
    return $flags;
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

    // Surcharge par l'environnement (déploiement hôte / CI).
    $env = [
        'hostname' => 'NF_DB_HOST',
        'port'     => 'NF_DB_PORT',
        'username' => 'NF_DB_USER',
        'password' => 'NF_DB_PASS',
        'database' => 'NF_DB_NAME',
    ];
    foreach ($env as $key => $var) {
        $val = getenv($var);
        if ($val !== false && $val !== '') {
            $cfg[$key] = $val;
        }
    }

    mysqli_report(MYSQLI_REPORT_OFF);
    $conn = @new mysqli($cfg['hostname'], $cfg['username'], (string) $cfg['password'], $cfg['database'], (int) $cfg['port']);

    if ($conn->connect_errno) {
        fail("Connexion BDD impossible ({$cfg['username']}@{$cfg['hostname']}:{$cfg['port']}/{$cfg['database']}) : {$conn->connect_error}");
    }
    $conn->set_charset('utf8mb4');
    return $conn;
}

function ensure_table(mysqli $db): void
{
    $db->query(
        'CREATE TABLE IF NOT EXISTS ' . TABLE . ' (
            id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
            name       VARCHAR(255) NOT NULL,
            batch      INT UNSIGNED NOT NULL,
            applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uniq_name (name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    ) || fail('Création table ' . TABLE . ' : ' . $db->error);
}

/** @return string[] noms de migration (sans .up.sql), triés chronologiquement */
function discover(): array
{
    $files = glob(MIGRATIONS_DIR . '/*.up.sql') ?: [];
    $names = array_map(static fn(string $f): string => basename($f, '.up.sql'), $files);
    sort($names); // préfixe daté → ordre chronologique
    return $names;
}

/** @return array<string,int> name => batch */
function applied(mysqli $db): array
{
    $out = [];
    $res = $db->query('SELECT name, batch FROM ' . TABLE . ' ORDER BY id ASC');
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $out[$row['name']] = (int) $row['batch'];
        }
    }
    return $out;
}

function cmd_status(mysqli $db): void
{
    $all     = discover();
    $applied = applied($db);
    $pending = array_values(array_diff($all, array_keys($applied)));
    $orphans = array_values(array_diff(array_keys($applied), $all));

    echo "Migrations (" . count($all) . " fichier(s), " . count($applied) . " appliquée(s), " . count($pending) . " en attente)\n\n";
    foreach ($all as $name) {
        if (isset($applied[$name])) {
            printf("  [x] %-60s (batch %d)\n", $name, $applied[$name]);
        } else {
            $down = is_file(MIGRATIONS_DIR . "/{$name}.down.sql") ? '' : '  ⚠ pas de .down.sql';
            printf("  [ ] %-60s PENDING%s\n", $name, $down);
        }
    }
    foreach ($orphans as $name) {
        printf("  [?] %-60s APPLIQUÉE mais .up.sql absent\n", $name);
    }
}

function cmd_up(mysqli $db, bool $pretend): void
{
    $all     = discover();
    $applied = applied($db);
    $pending = array_values(array_diff($all, array_keys($applied)));

    if (!$pending) {
        echo "Rien à appliquer — base à jour.\n";
        return;
    }

    $batch = next_batch($db);
    echo count($pending) . " migration(s) à appliquer (batch {$batch})" . ($pretend ? " [PRETEND]" : "") . " :\n";

    foreach ($pending as $name) {
        $file = MIGRATIONS_DIR . "/{$name}.up.sql";
        echo "  → {$name} ... ";
        if ($pretend) {
            echo "(pretend)\n";
            continue;
        }
        run_sql_file($db, $file, $name);
        $stmt = $db->prepare('INSERT INTO ' . TABLE . ' (name, batch) VALUES (?, ?)');
        $stmt->bind_param('si', $name, $batch);
        $stmt->execute() || fail("Enregistrement {$name} : " . $db->error);
        echo "OK\n";
    }
    echo "Terminé.\n";
}

function cmd_down(mysqli $db, int $step, bool $pretend, bool $force): void
{
    $step = max(1, $step);
    $res  = $db->query('SELECT name FROM ' . TABLE . ' ORDER BY id DESC LIMIT ' . $step);
    $targets = [];
    while ($res && $row = $res->fetch_assoc()) {
        $targets[] = $row['name'];
    }

    if (!$targets) {
        echo "Rien à annuler.\n";
        return;
    }

    echo count($targets) . " migration(s) à annuler" . ($pretend ? " [PRETEND]" : "") . " :\n";
    foreach ($targets as $name) {
        $file = MIGRATIONS_DIR . "/{$name}.down.sql";
        echo "  ← {$name} ... ";
        if (!is_file($file)) {
            if (!$force) {
                echo "\n";
                fail("Pas de .down.sql pour {$name} (migration non réversible). Utiliser --force pour la dé-tracer sans rollback SQL.");
            }
            echo "(pas de .down.sql, --force → dé-traçage seul) ";
        } elseif (!$pretend) {
            run_sql_file($db, $file, $name);
        }
        if ($pretend) {
            echo "(pretend)\n";
            continue;
        }
        $stmt = $db->prepare('DELETE FROM ' . TABLE . ' WHERE name = ?');
        $stmt->bind_param('s', $name);
        $stmt->execute() || fail("Dé-traçage {$name} : " . $db->error);
        echo "OK\n";
    }
    echo "Terminé.\n";
}

function cmd_baseline(mysqli $db, ?string $until): void
{
    // Marque des migrations comme appliquées SANS exécuter leur SQL (batch 0).
    // Usage : adopter une base déjà migrée (ex. dump importé) pour que le runner
    // n'essaie pas de ré-appliquer des migrations dont les tables existent déjà.
    $applied = applied($db);
    $toMark  = [];
    foreach (discover() as $name) {
        if (isset($applied[$name])) {
            continue;
        }
        if ($until !== null && strcmp($name, $until) > 0) {
            continue;
        }
        $toMark[] = $name;
    }

    if (!$toMark) {
        echo "Rien à baseliner.\n";
        return;
    }

    echo count($toMark) . " migration(s) marquée(s) appliquée(s) sans exécution (baseline, batch 0) :\n";
    foreach ($toMark as $name) {
        $stmt = $db->prepare('INSERT INTO ' . TABLE . ' (name, batch) VALUES (?, 0)');
        $stmt->bind_param('s', $name);
        $stmt->execute() || fail("Baseline {$name} : " . $db->error);
        echo "  ✓ {$name}\n";
    }
    echo "Terminé.\n";
}

function run_sql_file(mysqli $db, string $file, string $name): void
{
    $sql = file_get_contents($file);
    if ($sql === false || trim($sql) === '') {
        fail("Fichier vide ou illisible : {$file}");
    }

    if (!$db->multi_query($sql)) {
        fail("Échec {$name} : " . $db->error . " (la base peut être partiellement migrée — le DDL MySQL auto-commit)");
    }
    // Drainer tous les result sets pour détecter une erreur en milieu de batch.
    do {
        if ($result = $db->store_result()) {
            $result->free();
        }
        if ($db->errno) {
            fail("Échec {$name} (statement) : " . $db->error . " (base potentiellement partiellement migrée)");
        }
    } while ($db->more_results() && $db->next_result());
}

function next_batch(mysqli $db): int
{
    $res = $db->query('SELECT COALESCE(MAX(batch), 0) + 1 AS b FROM ' . TABLE);
    $row = $res ? $res->fetch_assoc() : null;
    return (int) ($row['b'] ?? 1);
}

function fail(string $msg): never
{
    fwrite(STDERR, "ERREUR : {$msg}\n");
    exit(1);
}
