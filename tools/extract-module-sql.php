<?php
declare(strict_types=1);

/**
 * extract-module-sql — génère le SQL d'install/désinstall embarqué de chaque module.
 *
 * Famille : outil
 * Diffusion : publique
 *
 * Ce qu'il produit
 * ----------------
 * Pour chaque module packageable possédant des tables (cf. tools/lib/table-map.php), écrit :
 *   - modules/<name>/install/install.sql   : CREATE TABLE IF NOT EXISTS (idempotent, sûr au reset) ;
 *   - modules/<name>/install/uninstall.sql : DROP TABLE IF EXISTS (ordre inverse).
 * Ils sont joués par Loadables\Addon::install()/uninstall() à l'install ZIP ou au scan, et
 * embarqués dans le zip par tools/package-addons.php.
 *
 * Garde-fou : la partition modules ∪ core de table-map.php doit recouvrir EXACTEMENT les tables
 * vives — toute table non classée fait échouer le script (anti-oubli). `build-release` le joue en
 * mode `--check` avant de packager.
 *
 * Les commentaires écrits au-dessus d'une colonne dans un install.sql (pourquoi `datetime` et non
 * `timestamp`, par exemple) sont reportés à la régénération (nf_sql_commentaires_de_colonnes()).
 *
 * Usage
 * -----
 *   php tools/extract-module-sql.php
 *   php tools/extract-module-sql.php --check    valide la partition, n'écrit rien
 */

require __DIR__.'/lib/outil.php';
require __DIR__.'/lib/site.php';
require __DIR__.'/lib/sql.php';

[$o] = nf_options(['check' => FALSE]);

$map  = require __DIR__.'/lib/table-map.php';
$db   = nf_connexion();
$live = nf_sql_tables($db);

// ── La partition modules ∪ core doit recouvrir EXACTEMENT les tables vives ────
//   - table vive non classée  → erreur (ajouter au mapping)
//   - table classée absente   → avertissement (table supprimée depuis ?)
//   - table dans 2 modules / module+core → erreur
$owners = [];

foreach ($map['modules'] as $module => $tables)
{
    foreach ($tables as $t)
    {
        $owners[$t][] = $module;
    }
}

foreach ($map['core'] as $t)
{
    $owners[$t][] = 'core';
}

$errors = [];

foreach ($owners as $t => $who)
{
    if (count($who) > 1)
    {
        $errors[] = "table `{$t}` revendiquée par plusieurs : ".implode(', ', $who);
    }
}

$classified = array_keys($owners);

foreach ($live as $t)
{
    if (!in_array($t, $classified, TRUE))
    {
        $errors[] = "table vive `{$t}` NON classée (ni module ni core) — compléter tools/lib/table-map.php";
    }
}

foreach ($classified as $t)
{
    if (!in_array($t, $live, TRUE))
    {
        nf_avertir("  avertissement : table classée `{$t}` absente de la base vive (ignorée)");
    }
}

if ($errors)
{
    nf_echec("mapping table→module incohérent :\n  - ".implode("\n  - ", $errors));
}

if ($o['check'])
{
    nf_ok('table-map valide : la partition modules ∪ core recouvre exactement les tables vives');
}

$written = 0;
$skipped = [];

foreach ($map['modules'] as $module => $tables)
{
    if (!$tables)
    {
        $skipped[] = $module;
        continue;
    }

    $dir = nf_racine()."/modules/{$module}/install";

    if (!is_dir($dir) && !mkdir($dir, 0775, TRUE) && !is_dir($dir))
    {
        nf_refus("impossible de créer {$dir}");
    }

    // install.sql : CREATE TABLE IF NOT EXISTS (idempotent, rejoué à chaque reset/scan sans
    // détruire les données), FK désactivées le temps du batch. Les commentaires écrits au-dessus d'une
    // colonne dans le fichier déjà livré sont reportés : SHOW CREATE TABLE ne les connaît pas.
    $commentaires = is_file("{$dir}/install.sql") ? nf_sql_commentaires_de_colonnes((string) file_get_contents("{$dir}/install.sql")) : [];

    $install  = nf_sql_entete('extract-module-sql', "install du module « {$module} » — tables propres au module");
    $install .= "SET FOREIGN_KEY_CHECKS = 0;\nSET NAMES utf8mb4;\n\n";

    foreach ($tables as $table)
    {
        $install .= nf_sql_reposer_commentaires(nf_sql_show_create($db, $table, TRUE), $table, $commentaires).";\n\n";
    }

    $install .= "SET FOREIGN_KEY_CHECKS = 1;\n";

    // uninstall.sql : DROP TABLE IF EXISTS, ordre inverse, FK désactivées.
    $uninstall  = nf_sql_entete('extract-module-sql', "désinstall du module « {$module} » — supprime ses tables (données perdues)");
    $uninstall .= "SET FOREIGN_KEY_CHECKS = 0;\nSET NAMES utf8mb4;\n\n";

    foreach (array_reverse($tables) as $table)
    {
        $uninstall .= "DROP TABLE IF EXISTS `{$table}`;\n";
    }

    $uninstall .= "\nSET FOREIGN_KEY_CHECKS = 1;\n";

    file_put_contents("{$dir}/install.sql", $install);
    file_put_contents("{$dir}/uninstall.sql", $uninstall);

    printf("  %-13s %d table(s)\n", $module, count($tables));
    $written++;
}

if ($skipped)
{
    echo '  (sans table, ignorés : '.implode(', ', $skipped).")\n";
}

nf_ok("{$written} modules avec SQL d'install généré");
