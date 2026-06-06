<?php
declare(strict_types=1);

/**
 * NeoFrag Reborn — régénère le schéma de référence et le seed d'installation depuis la
 * base vive, en mode CŒUR LEAN (Tier 0 uniquement). À relancer à chaque release.
 *
 * Le paquet livre un cœur lean : schema.sql + seed.sql ne contiennent QUE le Tier 0
 * (cf. tools/addons-manifest.php). Les tables et l'enregistrement nf_addon des modules
 * Tier 1 (presets) / Tier 2 (marketplace) sont apportés à l'install par leur
 * modules/<name>/install/install.sql (cf. install/lib/installer.php::apply_preset()).
 *
 * Produit (depuis la base vive cœur-riche, filtrée par tier — la base de dev n'est pas
 * modifiée) :
 *   - install/schema.sql : structure des SEULES tables cœur (table-map.php['core']) +
 *                          données de nf_migrations (historique complet → install up-only).
 *   - install/seed.sql   : données de configuration cœur uniquement :
 *                          • nf_addon filtré au Tier 0 (modules/widgets cœur + thèmes
 *                            admin/nebula + langues + authenticators sociaux) ;
 *                          • dispositions/widgets du SEUL thème nebula ;
 *                          • settings (default_theme→nebula, default_page→pages cœur-safe) ;
 *                          • groupes/rôles/permissions/templates email (défauts inertes
 *                            pour les modules absents, réactivés tels quels au preset).
 *                          AUCUNE donnée membre/contenu. Secrets neutralisés.
 *
 * Connexion : config/db.php ($db[0]) surchargé par NF_DB_* (cf. tools/migrate.php).
 *
 * Usage : docker compose exec web php tools/dump-schema.php
 */

const SCHEMA_OUT   = __DIR__ . '/../install/schema.sql';
const SEED_OUT     = __DIR__ . '/../install/seed.sql';
const CONFIG_DB    = __DIR__ . '/../config/db.php';
const TABLE_MAP    = __DIR__ . '/table-map.php';
const MANIFEST     = __DIR__ . '/addons-manifest.php';

/** Thèmes gardés en cœur (les autres — granite/blockcraft/forge/vitrine — partent au marketplace / hors distribution). */
const CORE_THEMES = ['admin', 'nebula'];

/** Widgets sans module, hors cœur (widget de la vitrine) → jamais dans le seed lean. */
const NON_CORE_ORPHAN_WIDGETS = ['landing'];

/** Tables dont les DONNÉES partent dans seed.sql (configuration / valeurs par défaut). */
const SEED_TABLES = [
    'nf_addon', 'nf_addon_type',
    'nf_settings',
    'nf_groups', 'nf_groups_lang',
    'nf_roles', 'nf_roles_lang', 'nf_role_permissions', 'nf_groups_roles',
    'nf_widgets', 'nf_dispositions',
    'nf_email_templates', 'nf_email_template_translations',
];

/**
 * Tables de DONNÉES appartenant à des modules Tier 1/2 : exclues du seed lean (leur
 * table n'existe pas dans le schema cœur → l'INSERT échouerait). Réintroduites par
 * l'install.sql du module quand un preset / le marketplace l'installe.
 */
const MODULE_DATA_TABLES = [
    'nf_classifieds_categories', 'nf_payment_packs', 'nf_shop_items',
];

/** nf_migrations : son historique va dans schema.sql (pas dans le seed). */
const HISTORY_TABLE = 'nf_migrations';

/** Settings dont la valeur est vidée (jamais committer un secret). */
const SENSITIVE_SETTING = '/(password|passwd|secret|private|api[_-]?key|[_-]key$|token|salt|smtp|stripe|paypal|recaptcha|oauth|webhook|client[_-]?secret)/i';

/** Settings forcés à une valeur cœur-safe dans le seed lean (le preset les surcharge). */
const SETTING_OVERRIDES = [
    'nf_default_theme' => 'nebula', // la vitrine n'est pas distribuée → nebula par défaut
    'nf_default_page'  => 'pages',  // module cœur : jamais de 404 home avant qu'un preset pose news / pages/bienvenue
];

main();

function main(): void
{
    $db       = connect();
    $core     = core_tables();
    $manifest = require MANIFEST;

    // Toutes les tables vives, triées, hors résidus de migration.
    $all = [];
    $res = $db->query('SHOW TABLES');
    while ($row = $res->fetch_row()) {
        $all[] = $row[0];
    }
    sort($all);
    $all = array_values(array_filter($all, static fn(string $t): bool =>
        !str_starts_with($t, '_backup_') && !str_starts_with($t, '_tmp')
    ));

    // Schéma cœur : on ne dumpe QUE les tables de table-map['core'] présentes en base.
    $core_present = array_values(array_filter($core, static fn(string $t): bool => in_array($t, $all, true)));
    $missing      = array_diff($core, $core_present);
    if ($missing) {
        fwrite(STDERR, "AVERTISSEMENT : tables cœur absentes de la base vive : " . implode(', ', $missing) . "\n");
    }

    write_schema($db, $core_present);
    write_seed($db, $manifest);

    fwrite(STDOUT, "install/schema.sql : " . count($core_present) . " tables cœur (Tier 0) + données " . HISTORY_TABLE . "\n");
    fwrite(STDOUT, "install/seed.sql   : configuration cœur (nf_addon Tier 0, dispositions nebula)\n");
}

/** Liste des tables cœur (Tier 0), source de vérité = tools/table-map.php['core']. */
function core_tables(): array
{
    $map = require TABLE_MAP;
    return $map['core'] ?? [];
}

/**
 * Un addon nf_addon est-il cœur (Tier 0) ? Cœur = ni identité (Tier 1) ni optionnel
 * (Tier 2) au manifeste ; thèmes limités à CORE_THEMES ; widget vitrine exclu ;
 * langues + authenticators sociaux = cœur (décision 2026-06-06).
 */
function is_core_addon(array $manifest, ?string $type, string $name): bool
{
    if ($type === null || $type === '' || in_array($type, ['language', 'authenticator'], true)) {
        return true; // ancre 'authenticator' (type NULL), langues, connecteurs sociaux
    }

    if ($type === 'theme') {
        return in_array($name, CORE_THEMES, true);
    }

    if ($type === 'widget' && in_array($name, NON_CORE_ORPHAN_WIDGETS, true)) {
        return false;
    }

    $identity = $manifest['identity'][$type] ?? [];
    $optional = $manifest['optional'][$type] ?? [];

    return !in_array($name, $identity, true) && !in_array($name, $optional, true);
}

function write_schema(mysqli $db, array $tables): void
{
    $out  = header_block('schéma de référence CŒUR LEAN (Tier 0) — structure des tables cœur + historique des migrations');
    $out .= "SET FOREIGN_KEY_CHECKS = 0;\n";
    $out .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
    $out .= "SET NAMES utf8mb4;\n\n";

    foreach ($tables as $table) {
        $create = show_create($db, $table);
        // Retire le compteur AUTO_INCREMENT : un schéma de référence ne fige pas un id de départ.
        $create = preg_replace('/ AUTO_INCREMENT=\d+/', '', $create);

        $out .= "DROP TABLE IF EXISTS `{$table}`;\n{$create};\n\n";
    }

    // Historique des migrations : permet l'install up-only (n'appliquer que le neuf).
    $out .= "-- Historique des migrations (état appliqué à la génération de ce dump).\n";
    $out .= dump_inserts($db, HISTORY_TABLE);

    $out .= "\nSET FOREIGN_KEY_CHECKS = 1;\n";

    file_put_contents(SCHEMA_OUT, $out);
}

function write_seed(mysqli $db, array $manifest): void
{
    $out  = header_block('seed d\'installation CŒUR LEAN (Tier 0) — configuration cœur uniquement (aucune donnée membre)');
    $out .= "SET FOREIGN_KEY_CHECKS = 0;\n";
    $out .= "SET NAMES utf8mb4;\n\n";

    // Widgets référencés par les dispositions cœur (nebula) : seules ces instances sont gardées.
    $kept_widget_ids = kept_widget_ids($db, $manifest);

    foreach (SEED_TABLES as $table) {
        if (!table_exists($db, $table)) {
            continue;
        }

        $filter = match ($table) {
            'nf_addon'            => static fn(array $row): bool => is_core_addon($manifest, $row['type_id'] === null ? null : addon_type_name((int) $row['type_id']), (string) $row['name']),
            'nf_dispositions'     => static fn(array $row): bool => is_core_addon($manifest, 'theme', (string) $row['theme']),
            'nf_widgets'          => static fn(array $row): bool => in_array((int) $row['widget_id'], $kept_widget_ids, true),
            // Permissions : on ne garde que les défauts GLOBAUX (scope 0). Les ACL de contenu
            // (scope > 0 : catégories forum, pages, recrutements… de la base de dev) ne valent que
            // pour du contenu inexistant en lean ; les presets / l'admin recréent les leurs.
            'nf_role_permissions' => static fn(array $row): bool => (int) $row['scope_id'] === 0,
            default               => null,
        };

        $transform = $table === 'nf_settings' ? transform_settings(...) : null;

        $out .= dump_inserts($db, $table, $transform, $filter);
    }

    $out .= "\nSET FOREIGN_KEY_CHECKS = 1;\n";

    file_put_contents(SEED_OUT, $out);
}

/** id type_id → nom ('module'/'theme'/'widget'/'language'/'authenticator'), mémoïsé. */
function addon_type_name(int $type_id): string
{
    static $types = null;
    if ($types === null) {
        $types = [];
        $res = connect()->query('SELECT id, name FROM nf_addon_type');
        while ($res && $row = $res->fetch_assoc()) {
            $types[(int) $row['id']] = $row['name'];
        }
    }
    return $types[$type_id] ?? '';
}

/**
 * widget_id référencés par les dispositions CŒUR (thème nebula). Les dispositions
 * sérialisent des arbres Array_/Row/Col/Widget ; on extrait les id par regex
 * (s:10:"\0*\0_widget";i:<id>;) sans charger les classes du framework.
 */
function kept_widget_ids(mysqli $db, array $manifest): array
{
    $ids = [];
    $res = $db->query('SELECT `theme`, `disposition` FROM `nf_dispositions`');
    while ($res && $row = $res->fetch_assoc()) {
        if (!is_core_addon($manifest, 'theme', (string) $row['theme'])) {
            continue;
        }
        if (preg_match_all('/_widget";i:(\d+);/', (string) $row['disposition'], $m)) {
            foreach ($m[1] as $id) {
                $ids[(int) $id] = true;
            }
        }
    }
    return array_keys($ids);
}

/**
 * Génère les INSERT d'une table.
 *   $transform : closure(array $row, string[] $cols): array — neutralise/force des valeurs.
 *   $filter    : closure(array $row): bool — ne garde que les lignes vraies (null = toutes).
 */
function dump_inserts(mysqli $db, string $table, ?callable $transform = null, ?callable $filter = null): string
{
    $res = $db->query("SELECT * FROM `{$table}`");
    if (!$res || $res->num_rows === 0) {
        return "-- {$table} : aucune donnée.\n";
    }

    $cols    = array_map(static fn($f) => $f->name, $res->fetch_fields());
    $colList = '`' . implode('`, `', $cols) . '`';

    $rows = [];
    while ($row = $res->fetch_assoc()) {
        if ($filter && !$filter($row)) {
            continue;
        }
        if ($transform) {
            $row = $transform($row, $cols);
        }

        $values = array_map(static function ($v) use ($db): string {
            return $v === null ? 'NULL' : "'" . $db->real_escape_string((string) $v) . "'";
        }, array_values($row));

        $rows[] = '(' . implode(', ', $values) . ')';
    }

    if (!$rows) {
        return "-- {$table} : aucune donnée (Tier 0).\n";
    }

    return "INSERT INTO `{$table}` ({$colList}) VALUES\n" . implode(",\n", $rows) . ";\n\n";
}

/** Neutralise les settings sensibles + force les défauts cœur-safe (thème/page d'accueil). */
function transform_settings(array $row, array $cols): array
{
    if (!isset($row['name'])) {
        return $row;
    }

    if (array_key_exists($row['name'], SETTING_OVERRIDES)) {
        $row['value'] = SETTING_OVERRIDES[$row['name']];
    } elseif (isset($row['value']) && preg_match(SENSITIVE_SETTING, (string) $row['name'])) {
        $row['value'] = ($row['value'] === null) ? null : '';
    }

    return $row;
}

function show_create(mysqli $db, string $table): string
{
    $res = $db->query("SHOW CREATE TABLE `{$table}`");
    $row = $res->fetch_row();
    return $row[1];
}

function table_exists(mysqli $db, string $table): bool
{
    $res = $db->query("SHOW TABLES LIKE '" . $db->real_escape_string($table) . "'");
    return (bool) ($res && $res->num_rows);
}

function header_block(string $what): string
{
    return "-- NeoFrag {$what}.\n"
        . "-- Généré par tools/dump-schema.php depuis la base vive (filtrée par tier). NE PAS éditer à la main.\n"
        . "-- Régénérer : docker compose exec web php tools/dump-schema.php\n\n";
}

function connect(): mysqli
{
    static $conn = null;
    if ($conn instanceof mysqli) {
        return $conn;
    }

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
