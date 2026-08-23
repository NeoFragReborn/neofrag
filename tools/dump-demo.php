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
 * NeoFrag Reborn — fige l'état de la démo (après tools/seed-demo.php) en install/demo.sql.
 *
 * demo.sql est : (1) la charge de l'AUTO-RESET du site de démo (rechargée périodiquement via
 * le cron gardé), et (2) le contenu initial du site de démo. Il NE touche PAS le compte admin
 * (créé par l'installeur), ni les secrets, ni la config sensible — en mode démo ces éléments
 * sont verrouillés par les gardes. Il restaure : config d'affichage démo (nebula, sans vitrine),
 * membres démo (non-admin), et tout le contenu (news/forum/galerie/gaming/à-la-carte).
 *
 * Connexion : config/db.php ($db[0]) surchargé par NF_DB_* (cf. dump-schema.php).
 * Usage : docker compose exec -T web php tools/dump-demo.php  (après seed-demo.php)
 */

const DEMO_OUT  = __DIR__ . '/../install/demo.sql';
const CONFIG_DB = __DIR__ . '/../config/db.php';

/** Tables de CONTENU rechargées à chaque reset (TRUNCATE + INSERT). */
const CONTENT_TABLES = [
    'nf_news_categories', 'nf_news_categories_lang', 'nf_news', 'nf_news_lang',
    'nf_articles_categories', 'nf_articles_categories_lang', 'nf_articles', 'nf_articles_lang',
    'nf_forum_categories', 'nf_forum', 'nf_forum_url', 'nf_forum_topics', 'nf_forum_messages',
    'nf_gallery_categories', 'nf_gallery_categories_lang', 'nf_gallery', 'nf_gallery_lang', 'nf_gallery_images',
    'nf_games', 'nf_games_lang', 'nf_teams', 'nf_teams_lang', 'nf_teams_users',
    'nf_events_types', 'nf_events', 'nf_awards',
    'nf_comment', 'nf_reactions',
    // nf_wiki_pages exclu : le wiki (doc) vit dans install/wiki.sql, chargé à l'install des 2 paquets
    // (donc partagé, pas réinitialisé par le reset démo).
    'nf_faq_categories', 'nf_faq_questions',
    'nf_downloads_categories', 'nf_downloads', 'nf_links_categories', 'nf_links',
    'nf_partners', 'nf_partners_lang', 'nf_guestbook',
    'nf_surveys', 'nf_surveys_options', 'nf_surveys_votes', 'nf_classifieds',
    'nf_recruits', 'nf_recruits_fields', 'nf_calendar_events',
    'nf_bug_tickets', 'nf_bug_comments', 'nf_donations_campaigns', 'nf_donations',
    'nf_ads', 'nf_newsletter_subscribers',
];

main();

function main(): void
{
    $db = connect();

    $theme_t  = type_id($db, 'theme');
    $widget_t = type_id($db, 'widget');

    $out  = "-- NeoFrag Reborn — instantané du site de DÉMO (config affichage + membres + contenu).\n";
    $out .= "-- Généré par tools/dump-demo.php. Rechargé par l'auto-reset démo. NE PAS éditer à la main.\n";
    $out .= "-- Ne touche pas le compte admin ni les secrets (verrouillés en mode démo).\n\n";
    $out .= "SET FOREIGN_KEY_CHECKS = 0;\n";
    $out .= "SET NAMES utf8mb4;\n\n";

    // 1. Config d'affichage démo (idempotent) : nebula par défaut, vitrine + landing retirés.
    $out .= "-- Config démo (idempotent)\n";
    $out .= "UPDATE `nf_settings` SET `value` = 'nebula' WHERE `name` = 'nf_default_theme';\n";
    $out .= "DELETE FROM `nf_addon` WHERE `name` = 'vitrine' AND `type_id` = {$theme_t};\n";
    $out .= "DELETE FROM `nf_dispositions` WHERE `theme` = 'vitrine';\n";
    $out .= "DELETE FROM `nf_addon` WHERE `name` = 'landing' AND `type_id` = {$widget_t};\n";
    $out .= "DELETE FROM `nf_widgets` WHERE `widget` = 'landing';\n";
    // Forum lisible par les VISITEURS (rôle 3) : le seed crée les catégories en SQL sans grant de
    // lecture → 403 sur le détail d'un forum. La permission est `forum.category_read` (module.action,
    // cf. access::__invoke), scope 0 = global (toutes catégories). Validé : guest → 200.
    $out .= "DELETE FROM `nf_role_permissions` WHERE `role_id` = 3 AND `permission` = 'forum.category_read';\n";
    $out .= "INSERT INTO `nf_role_permissions` (`role_id`, `permission`, `scope_id`, `authorized`) VALUES (3, 'forum.category_read', 0, 'allow');\n\n";

    // 2. Membres démo (garde l'admin et tout compte admin existant).
    $out .= "-- Membres démo (l'admin est préservé)\n";
    $out .= "DELETE FROM `nf_user_profile` WHERE `id` NOT IN (SELECT `id` FROM `nf_user` WHERE `admin` = '1');\n";
    $out .= "DELETE FROM `nf_user` WHERE `admin` = '0';\n";
    $out .= "TRUNCATE TABLE `nf_user_points`;\n";
    $out .= "TRUNCATE TABLE `nf_karma`;\n";
    $out .= dump_inserts($db, 'nf_user', "WHERE `admin` = '0'");
    $out .= dump_inserts($db, 'nf_user_profile', "WHERE `id` NOT IN (SELECT `id` FROM (SELECT `id` FROM `nf_user` WHERE `admin` = '1') t)");
    $out .= dump_inserts($db, 'nf_user_points');
    $out .= dump_inserts($db, 'nf_karma');

    // 3. Contenu (TRUNCATE + INSERT).
    $out .= "\n-- Contenu\n";
    foreach (CONTENT_TABLES as $table) {
        if (!table_exists($db, $table)) {
            continue;
        }
        $out .= "TRUNCATE TABLE `{$table}`;\n";
        $out .= dump_inserts($db, $table);
    }

    $out .= "\nSET FOREIGN_KEY_CHECKS = 1;\n";

    file_put_contents(DEMO_OUT, $out);
    fwrite(STDOUT, "install/demo.sql écrit (" . (count(CONTENT_TABLES) + 4) . " tables, config démo incluse).\n");
}

function dump_inserts(mysqli $db, string $table, string $where = ''): string
{
    $res = $db->query("SELECT * FROM `{$table}` {$where}");
    if (!$res || $res->num_rows === 0) {
        return "-- {$table} : aucune donnée.\n";
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
    return "INSERT INTO `{$table}` ({$colList}) VALUES\n" . implode(",\n", $rows) . ";\n";
}

function type_id(mysqli $db, string $name): int
{
    $r = $db->query("SELECT id FROM nf_addon_type WHERE name = '" . $db->real_escape_string($name) . "'");
    return (int) ($r->fetch_row()[0] ?? 0);
}

function table_exists(mysqli $db, string $table): bool
{
    $res = $db->query("SHOW TABLES LIKE '" . $db->real_escape_string($table) . "'");
    return (bool) ($res && $res->num_rows);
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
