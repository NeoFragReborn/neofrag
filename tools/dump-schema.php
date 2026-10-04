<?php
declare(strict_types=1);

/**
 * dump-schema — régénère le schéma de référence et le seed d'installation depuis la base vive, en cœur lean.
 *
 * Famille : outil
 * Diffusion : publique
 *
 * Ce qu'il produit
 * ----------------
 * Le paquet livre un cœur lean : `install/schema.sql` et `install/seed.sql` ne contiennent QUE le
 * Tier 0 (cf. tools/lib/addons-manifest.php). Les tables et l'enregistrement `nf_addon` des autres
 * modules, widgets et thèmes sont apportés à l'installation par leur `install/install.sql`.
 *
 *   - install/schema.sql : structure des SEULES tables cœur (table-map.php['core']) + données de
 *                          `nf_migrations` (historique complet → install up-only) ;
 *   - install/seed.sql   : configuration cœur uniquement — `nf_addon` filtré au Tier 0,
 *                          dispositions et widgets du SEUL thème nebula, réglages (default_theme →
 *                          nebula, default_page → pages), groupes, rôles, permissions globales,
 *                          gabarits d'e-mail. AUCUNE donnée membre ni contenu. Secrets neutralisés.
 *
 * La base de développement n'est pas modifiée. À relancer à chaque release.
 *
 * Usage
 * -----
 *   php tools/dump-schema.php
 */

require __DIR__.'/lib/outil.php';
require __DIR__.'/lib/site.php';
require __DIR__.'/lib/sql.php';

const SCHEMA_OUT = __DIR__.'/../install/schema.sql';
const SEED_OUT   = __DIR__.'/../install/seed.sql';

/** Thèmes gardés en cœur (les autres partent au marketplace ou hors distribution). */
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

/** nf_migrations : son historique va dans schema.sql (pas dans le seed). */
const HISTORY_TABLE = 'nf_migrations';

/** Settings dont la valeur est vidée (jamais committer un secret). */
const SENSITIVE_SETTING = '/(password|passwd|secret|private|api[_-]?key|[_-]key$|token|salt|smtp|stripe|paypal|recaptcha|oauth|webhook|client[_-]?secret)/i';

/** Settings forcés à une valeur cœur-safe dans le seed lean (le preset les surcharge). */
const SETTING_OVERRIDES = [
    'nf_default_theme' => 'nebula', // la vitrine n'est pas distribuée → nebula par défaut
    'nf_default_page'  => 'pages',  // module cœur : jamais de 404 home avant qu'un preset pose news / pages/bienvenue
];

$db       = nf_connexion();
$manifest = require __DIR__.'/lib/addons-manifest.php';
$map      = require __DIR__.'/lib/table-map.php';
$core     = $map['core'] ?? [];
$all      = nf_sql_tables($db);

// Schéma cœur : on ne dumpe QUE les tables de table-map['core'] présentes en base.
$core_present = array_values(array_filter($core, static fn (string $t): bool => in_array($t, $all, TRUE)));
$missing      = array_diff($core, $core_present);

if ($missing)
{
    nf_avertir('AVERTISSEMENT : tables cœur absentes de la base vive : '.implode(', ', $missing));
}

/** id type_id → nom ('module'/'theme'/'widget'/'language'/'authenticator'), mémoïsé. */
function addon_type_name(mysqli $db, int $type_id): string
{
    static $types = NULL;

    if ($types === NULL)
    {
        $types = [];
        $res   = $db->query('SELECT id, name FROM nf_addon_type');

        while ($res && ($row = $res->fetch_assoc()))
        {
            $types[(int) $row['id']] = $row['name'];
        }
    }

    return $types[$type_id] ?? '';
}

/**
 * Un addon nf_addon est-il cœur (Tier 0) ? Cœur = ni identité (Tier 1) ni optionnel (Tier 2) au
 * manifeste ; thèmes limités à CORE_THEMES ; widget vitrine exclu ; langues + authenticators
 * sociaux = cœur (décision 2026-06-06).
 */
function is_core_addon(array $manifest, ?string $type, string $name): bool
{
    if ($type === NULL || $type === '' || in_array($type, ['language', 'authenticator'], TRUE))
    {
        return TRUE;
    }

    if ($type === 'theme')
    {
        return in_array($name, CORE_THEMES, TRUE);
    }

    if ($type === 'widget' && in_array($name, NON_CORE_ORPHAN_WIDGETS, TRUE))
    {
        return FALSE;
    }

    return !in_array($name, $manifest['identity'][$type] ?? [], TRUE) && !in_array($name, $manifest['optional'][$type] ?? [], TRUE);
}

/**
 * widget_id référencés par les dispositions CŒUR (thème nebula), sans charger les classes du
 * framework. Gère les DEUX formats de stockage : JSON (depuis la migration 2026_06, objets widget
 * `{"id":<id>, …}`) et l'ancien PHP sérialisé (`s:10:"\0*\0_widget";i:<id>;`).
 */
function kept_widget_ids(mysqli $db, array $manifest): array
{
    $ids = [];
    $res = $db->query('SELECT `theme`, `disposition` FROM `nf_dispositions`');

    while ($res && ($row = $res->fetch_assoc()))
    {
        if (!is_core_addon($manifest, 'theme', (string) $row['theme']))
        {
            continue;
        }

        $disposition = (string) $row['disposition'];

        if ($disposition !== '' && ($disposition[0] === '[' || $disposition[0] === '{'))
        {
            if (preg_match_all('/"id"\s*:\s*(\d+)/', $disposition, $m))
            {
                foreach ($m[1] as $id)
                {
                    $ids[(int) $id] = TRUE;
                }
            }

            continue;
        }

        if (preg_match_all('/_widget";i:(\d+);/', $disposition, $m))
        {
            foreach ($m[1] as $id)
            {
                $ids[(int) $id] = TRUE;
            }
        }
    }

    return array_keys($ids);
}

/** Neutralise les settings sensibles + force les défauts cœur-safe (thème/page d'accueil). */
function transform_settings(array $row, array $cols): array
{
    if (!isset($row['name']))
    {
        return $row;
    }

    if (array_key_exists($row['name'], SETTING_OVERRIDES))
    {
        $row['value'] = SETTING_OVERRIDES[$row['name']];
    }
    elseif (isset($row['value']) && preg_match(SENSITIVE_SETTING, (string) $row['name']))
    {
        $row['value'] = $row['value'] === NULL ? NULL : '';
    }

    return $row;
}

// ── install/schema.sql ────────────────────────────────────────────────────────
$out  = nf_sql_entete('dump-schema', 'schéma de référence CŒUR LEAN (Tier 0) — structure des tables cœur + historique des migrations');
$out .= "SET FOREIGN_KEY_CHECKS = 0;\n";
$out .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
$out .= "SET NAMES utf8mb4;\n\n";

foreach ($core_present as $table)
{
    $out .= "DROP TABLE IF EXISTS `{$table}`;\n".nf_sql_show_create($db, $table).";\n\n";
}

// Historique des migrations : permet l'install up-only (n'appliquer que le neuf).
$out .= "-- Historique des migrations (état appliqué à la génération de ce dump).\n";
$out .= nf_sql_inserts($db, HISTORY_TABLE)."\n";
$out .= "\nSET FOREIGN_KEY_CHECKS = 1;\n";

file_put_contents(SCHEMA_OUT, $out);

// ── install/seed.sql ──────────────────────────────────────────────────────────
$out  = nf_sql_entete('dump-schema', 'seed d\'installation CŒUR LEAN (Tier 0) — configuration cœur uniquement (aucune donnée membre)');
$out .= "SET FOREIGN_KEY_CHECKS = 0;\n";
$out .= "SET NAMES utf8mb4;\n\n";

// Widgets référencés par les dispositions cœur (nebula) : seules ces instances sont gardées.
$kept_widget_ids = kept_widget_ids($db, $manifest);

foreach (SEED_TABLES as $table)
{
    if (!nf_table_existe($db, $table))
    {
        continue;
    }

    $filter = match ($table) {
        'nf_addon'            => static fn (array $row): bool => is_core_addon($manifest, $row['type_id'] === NULL ? NULL : addon_type_name($db, (int) $row['type_id']), (string) $row['name']),
        'nf_dispositions'     => static fn (array $row): bool => is_core_addon($manifest, 'theme', (string) $row['theme']),
        'nf_widgets'          => static fn (array $row): bool => in_array((int) $row['widget_id'], $kept_widget_ids, TRUE),
        // Permissions : on ne garde que les défauts GLOBAUX (scope 0). Les ACL de contenu (scope > 0)
        // ne valent que pour du contenu inexistant en lean ; les presets / l'admin recréent les leurs.
        'nf_role_permissions' => static fn (array $row): bool => (int) $row['scope_id'] === 0,
        default               => NULL,
    };

    $transform = $table === 'nf_settings' ? transform_settings(...) : NULL;
    $sql       = nf_sql_inserts($db, $table, '', $transform, $filter);
    $out      .= str_starts_with($sql, '-- ') ? "-- {$table} : aucune donnée (Tier 0).\n" : $sql."\n";
}

$out .= "\nSET FOREIGN_KEY_CHECKS = 1;\n";

file_put_contents(SEED_OUT, $out);

printf("install/schema.sql : %d tables cœur (Tier 0) + données %s\n", count($core_present), HISTORY_TABLE);
echo "install/seed.sql   : configuration cœur (nf_addon Tier 0, dispositions nebula)\n";
