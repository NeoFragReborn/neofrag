<?php
declare(strict_types=1);
/**
 * check-demo-lock — le verrou du site de démonstration et sa remise à zéro se répondent : rien de modifiable n'est irréversible.
 *
 * Famille : statique
 *
 * Pourquoi cet outil existe
 * -------------------------
 * Le site de démo repose sur un équilibre entre deux listes écrites à deux endroits :
 *
 *   - `NF_DEMO_MODULES_VERROUILLES` (neofrag/helpers/system.php) dit ce qu'un visiteur NE PEUT PAS
 *     modifier ;
 *   - `CONTENT_TABLES` et la section des réglages de `tools/dump-demo.php` disent ce que la remise
 *     à zéro horaire SAIT rétablir.
 *
 * Tout ce qu'un visiteur peut changer sans que la remise à zéro le rétablisse se dégrade
 * définitivement, une visite après l'autre. Et rien, dans le code, ne rend cet écart visible : la
 * démo a l'air de fonctionner jusqu'au jour où elle est méconnaissable.
 *
 * Le contrôle a trouvé un écart réel dès sa première exécution : `classifieds`, `gamification` et
 * `moderation` écrivaient dans `nf_settings` alors que l'instantané ne restaurait pas cette table.
 *
 * Ce qu'il vérifie
 * ----------------
 *   1. tout module d'administration qui touche `nf_addon`, `nf_addon_type`, `nf_roles` ou
 *      `nf_users_roles` est verrouillé — ces tables ne sont PAS dans l'instantané, et les addons
 *      écrivent en plus des fichiers sur le disque. (`nf_role_permissions` y figurait jusqu'au
 *      2026-10-02 : l'instantané la rétablit depuis, parce que créer une galerie ou une page en écrit) ;
 *   2. si un module NON verrouillé écrit des réglages, alors `nf_settings` doit être restauré par
 *      l'instantané ;
 *   3. tout module verrouillé existe réellement — sinon la liste protège un fantôme et laisse
 *      croire que le sujet est traité.
 *
 * Usage
 * -----
 *   php tools/check-demo-lock.php
 */

require __DIR__.'/lib/outil.php';

nf_options([]);

$racine = nf_racine();

// ── Les deux listes, lues à la source ───────────────────────────────────────
require_once $racine.'/neofrag/helpers/system.php';

if (!defined('NF_DEMO_MODULES_VERROUILLES'))
{
    nf_refus('NF_DEMO_MODULES_VERROUILLES est introuvable dans neofrag/helpers/system.php');
}

$verrouilles = NF_DEMO_MODULES_VERROUILLES;
$dump        = (string) file_get_contents($racine.'/tools/dump-demo.php');

$reglages_restaures = str_contains($dump, "nf_sql_upserts(\$db, 'nf_settings'");

printf("%d module(s) verrouillé(s) en démo. Réglages restaurés par l'instantané : %s\n\n",
    count($verrouilles), $reglages_restaures ? 'oui' : 'NON');

$anomalies = [];

// ── 1 & 2. Ce que chaque module d'administration écrit ──────────────────────
$modules = [];

foreach (glob($racine.'/modules/*/controllers/admin*.php') as $fichier)
{
    $module = basename(dirname(dirname($fichier)));
    $modules[$module][] = $fichier;
}

// Les modèles comptent autant que les contrôleurs : c'est souvent là que l'écriture a lieu.
foreach (glob($racine.'/modules/*/models/*.php') as $fichier)
{
    $module = basename(dirname(dirname($fichier)));

    if (isset($modules[$module]))
    {
        $modules[$module][] = $fichier;
    }
}

ksort($modules);

foreach ($modules as $module => $fichiers)
{
    $source = '';

    foreach ($fichiers as $f)
    {
        $source .= (string) file_get_contents($f);
    }

    $verrouille = in_array($module, $verrouilles, TRUE);

    // Tables dont la modification n'est jamais rattrapée.
    $intouchables = [];

    foreach (['nf_addon', 'nf_addon_type', 'nf_roles', 'nf_users_roles'] as $table)
    {
        // `nf_addons_xxx` ne doit pas déclencher `nf_addon` : on exige une fin de mot.
        if (preg_match('/\b'.preg_quote($table, '/').'\b(?!_)/', $source))
        {
            $intouchables[] = $table;
        }
    }

    if ($intouchables && !$verrouille)
    {
        $anomalies[] = sprintf(
            "%-14s écrit %s — table(s) que la remise à zéro ne rétablit PAS.\n"
            ."                 → ajouter « %s » à NF_DEMO_MODULES_VERROUILLES.",
            $module, implode(', ', $intouchables), $module);
    }

    // Écriture de réglages : `->config('nom', $valeur)` à deux arguments.
    $ecrit_reglages = (bool) preg_match('/->config\(\s*[\'"][a-z0-9_]+[\'"]\s*,/i', $source);

    if ($ecrit_reglages && !$verrouille && !$reglages_restaures)
    {
        $anomalies[] = sprintf(
            "%-14s écrit des réglages sans être verrouillé, et l'instantané ne restaure pas\n"
            ."                 `nf_settings` : les changements d'un visiteur seraient définitifs.",
            $module);
    }
}

// ── 3. Un module verrouillé qui n'existe pas protège un fantôme ─────────────
foreach ($verrouilles as $module)
{
    if (!is_dir($racine.'/modules/'.$module))
    {
        $anomalies[] = sprintf("%-14s est verrouillé mais n'existe pas dans modules/ — liste périmée.", $module);
    }
}

// ── Rapport ─────────────────────────────────────────────────────────────────
if ($anomalies)
{
    printf("%d anomalie(s) :\n\n", count($anomalies));

    foreach ($anomalies as $a)
    {
        echo '  ✗ '.$a."\n\n";
    }

    nf_echec(count($anomalies).' anomalie(s) entre le verrou de la démo et sa remise à zéro');
}

nf_ok(sprintf('les %d module(s) qui touchent aux addons ou aux rôles sont verrouillés, et ce que les autres écrivent est rétabli par la remise à zéro', count($verrouilles)));
