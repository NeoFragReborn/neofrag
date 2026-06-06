<?php
declare(strict_types=1);

/**
 * NeoFrag — tâches de maintenance périodiques (à lancer par un cron externe ;
 * NeoFrag n'a pas d'ordonnanceur interne, les modules tournent en contexte HTTP).
 *
 * Tâches :
 *   - trash    : purge définitive du contenu soft-deleted (deleted_at) plus vieux que
 *                nf_trash_retention_days jours (défaut 30). Balaie TOUTES les tables
 *                ayant une colonne deleted_at ; les FK ON DELETE CASCADE nettoient les
 *                tables liées (langues, etc.).
 *   - accounts : purge des comptes jamais confirmés (last_activity_date IS NULL) inscrits
 *                il y a plus de nf_unconfirmed_retention_days jours (défaut 7). Ne tourne
 *                QUE si la validation d'inscription est active (nf_registration_validation),
 *                sinon last_activity_date NULL = simple compte jamais connecté (à garder).
 *                Les admins ne sont jamais purgés.
 *
 * Connexion : config/db.php ($db[0]) surchargé par NF_DB_* (cf. tools/migrate.php).
 *
 * Usage :
 *   docker compose exec web php tools/maintenance.php [trash|accounts|all] [--pretend]
 *   (cron) * /15 * * * *  php /var/www/html/tools/maintenance.php all >> /var/log/nf-maint.log 2>&1
 *
 * Voir aussi : la PARUTION du contenu programmé (news/articles à l'heure réelle) n'est PAS ici —
 * elle nécessite le framework (webhooks/gamification/notifications) → endpoint HTTP gardé par token
 *   (cron) * /5 * * * *  curl -fsS "https://<site>/monitoring/cron?key=<nf_cron_key>" >/dev/null 2>&1
 * (URL + clé affichées dans l'admin Monitoring).
 */

const CONFIG_DB                = __DIR__ . '/../config/db.php';
const TRASH_DEFAULT_DAYS       = 30;
const UNCONFIRMED_DEFAULT_DAYS = 7;

main($argv);

function main(array $argv): void
{
    $args    = array_slice($argv, 1);
    $task    = '';
    $pretend = false;

    foreach ($args as $a) {
        if ($a === '--pretend') {
            $pretend = true;
        } else if ($task === '') {
            $task = $a;
        }
    }

    if ($task === '') {
        $task = 'all';
    }

    if (!in_array($task, ['trash', 'accounts', 'all'], true)) {
        fwrite(STDERR, "Tâche inconnue : {$task}\nUsage : maintenance.php [trash|accounts|all] [--pretend]\n");
        exit(2);
    }

    $db = connect();

    if ($pretend) {
        fwrite(STDOUT, "-- MODE --pretend : aucune suppression réelle.\n");
    }

    if ($task === 'trash' || $task === 'all') {
        purge_trash($db, retention_days($db, 'nf_trash_retention_days', TRASH_DEFAULT_DAYS), $pretend);
    }

    if ($task === 'accounts' || $task === 'all') {
        purge_unconfirmed($db, retention_days($db, 'nf_unconfirmed_retention_days', UNCONFIRMED_DEFAULT_DAYS), $pretend);
    }
}

function purge_trash(mysqli $db, int $days, bool $pretend): void
{
    $tables = tables_with_column($db, 'deleted_at');

    if (!$tables) {
        fwrite(STDOUT, "trash : aucune table soft-delete.\n");
        return;
    }

    $total = 0;

    foreach ($tables as $table) {
        $where = "`deleted_at` IS NOT NULL AND `deleted_at` < (NOW() - INTERVAL {$days} DAY)";
        $count = (int) scalar($db, "SELECT COUNT(*) FROM `{$table}` WHERE {$where}");

        if ($count > 0 && !$pretend) {
            $db->query("DELETE FROM `{$table}` WHERE {$where}");
        }

        if ($count > 0) {
            fwrite(STDOUT, "trash : {$table} — {$count} élément(s) " . ($pretend ? "à purger\n" : "purgé(s)\n"));
            $total += $count;
        }
    }

    fwrite(STDOUT, "trash : {$total} élément(s) au total (rétention {$days} j).\n");
}

function purge_unconfirmed(mysqli $db, int $days, bool $pretend): void
{
    if (!(int) scalar($db, "SELECT value FROM `nf_settings` WHERE name = 'nf_registration_validation'")) {
        fwrite(STDOUT, "accounts : validation d'inscription désactivée — purge ignorée (last_activity NULL ≠ non confirmé).\n");
        return;
    }

    $where = "`last_activity_date` IS NULL AND `admin` = '0' AND `registration_date` < (NOW() - INTERVAL {$days} DAY)";
    $count = (int) scalar($db, "SELECT COUNT(*) FROM `nf_user` WHERE {$where}");

    if ($count > 0 && !$pretend) {
        $db->query("DELETE FROM `nf_user` WHERE {$where}");
    }

    fwrite(STDOUT, "accounts : {$count} compte(s) non confirmé(s) " . ($pretend ? "à purger" : "purgé(s)") . " (rétention {$days} j).\n");
}

/** Rétention (jours) depuis un setting, bornée à >= 1, avec défaut. */
function retention_days(mysqli $db, string $setting, int $default): int
{
    $value = scalar($db, "SELECT value FROM `nf_settings` WHERE name = '" . $db->real_escape_string($setting) . "'");

    return $value === null || (int) $value < 1 ? $default : (int) $value;
}

function tables_with_column(mysqli $db, string $column): array
{
    $tables = [];
    $res = $db->query(
        "SELECT table_name FROM information_schema.columns
         WHERE table_schema = DATABASE() AND column_name = '" . $db->real_escape_string($column) . "'
         ORDER BY table_name"
    );

    while ($row = $res->fetch_row()) {
        $tables[] = $row[0];
    }

    return $tables;
}

function scalar(mysqli $db, string $sql)
{
    $res = $db->query($sql);
    if (!$res) {
        return null;
    }
    $row = $res->fetch_row();
    return $row ? $row[0] : null;
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
