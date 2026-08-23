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
 * NeoFrag Reborn — fige le contenu du wiki (documentation) en install/wiki.sql.
 *
 * Le contenu wiki = la doc transférée par tools/seed-wiki-docs.php (docs/guide/*.md → nf_wiki_pages).
 * install/wiki.sql est chargé par l'installeur (les 2 paquets) pour que /wiki soit peuplé dès
 * l'install — sinon le module wiki serait installé mais vide (docs/ et le tool sont hors paquet FTP).
 *
 * Usage : docker compose exec -T web php tools/seed-wiki-docs.php   (peuple le wiki)
 *         docker compose exec -T web php tools/dump-wiki.php        (fige en install/wiki.sql)
 */

const WIKI_OUT  = __DIR__ . '/../install/wiki.sql';
const CONFIG_DB = __DIR__ . '/../config/db.php';

main();

function main(): void
{
    $db = connect();

    $res = $db->query('SELECT * FROM `nf_wiki_pages` ORDER BY `parent_id` IS NOT NULL, `sort_order`, `id`');
    if (!$res || $res->num_rows === 0) {
        fwrite(STDERR, "nf_wiki_pages est vide — lance d'abord tools/seed-wiki-docs.php.\n");
        exit(1);
    }

    $cols    = array_map(static fn($f) => $f->name, $res->fetch_fields());
    $colList = '`' . implode('`, `', $cols) . '`';

    $rows = [];
    while ($row = $res->fetch_assoc()) {
        $values = array_map(static function ($v) use ($db): string {
            return $v === null ? 'NULL' : "'" . $db->real_escape_string((string) $v) . "'";
        }, array_values($row));
        $rows[] = '(' . implode(', ', $values) . ')';
    }

    $out  = "-- NeoFrag Reborn — contenu du wiki (documentation). Chargé à l'install (les 2 paquets).\n";
    $out .= "-- Généré par tools/dump-wiki.php (après tools/seed-wiki-docs.php). NE PAS éditer à la main.\n\n";
    $out .= "SET FOREIGN_KEY_CHECKS = 0;\n";
    $out .= "SET NAMES utf8mb4;\n\n";
    $out .= "TRUNCATE TABLE `nf_wiki_pages`;\n";
    $out .= "INSERT INTO `nf_wiki_pages` ({$colList}) VALUES\n" . implode(",\n", $rows) . ";\n\n";
    $out .= "SET FOREIGN_KEY_CHECKS = 1;\n";

    file_put_contents(WIKI_OUT, $out);
    fwrite(STDOUT, 'install/wiki.sql écrit (' . count($rows) . " pages).\n");
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
