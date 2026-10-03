<?php
declare(strict_types=1);
/**
 * check-demo-lock — le verrou de la démonstration et sa remise à zéro se répondent, et l'instantané publié n'emporte aucun secret.
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
 *      croire que le sujet est traité ;
 *   4. tout réglage que l'instantané exclut (ou déclare public) existe dans le code : la liste de
 *      `dump-demo` citait huit noms inventés le 2026-10-02, un neuvième le 2026-10-03 ;
 *   5. tout réglage du code dont le nom désigne un secret (NF_DEMO_MOTIF_SECRET) est exclu, ou
 *      déclaré public avec sa raison — la liste et le motif vivent dans `tools/lib/demo.php` ;
 *   6. `install/demo.sql`, le fichier PUBLIÉ, ne porte ni un réglage exclu ni une clé dans les
 *      réglages d'un widget.
 *
 * Usage
 * -----
 *   php tools/check-demo-lock.php
 */

require __DIR__.'/lib/outil.php';
require __DIR__.'/lib/depot.php';
require __DIR__.'/lib/sql.php';
require __DIR__.'/lib/demo.php';

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

// ── 4 à 6. Les secrets que l'instantané n'emporte pas (tools/lib/demo.php) ──
// Les réglages que le code nomme : lus (`config->nom`) ou écrits (`config('nom'`, `_poser('nom'`).
// Les commentaires ne comptent pas : un `@property` ne prouve pas qu'un code s'en sert.
$noms_du_code = [];
$sources      = nf_fichiers(['neofrag', 'modules', 'widgets', 'themes', 'addons'], ['php']) + ['index.php' => $racine.'/index.php'];

foreach ($sources as $chemin)
{
    preg_match_all('/(?:config\(\s*|_poser\(\s*)[\'"]([a-z][a-z0-9_]+)[\'"]|config->([a-z][a-z0-9_]+)/',
        nf_sans_commentaires((string) file_get_contents($chemin)), $m, PREG_SET_ORDER);

    foreach ($m as $x)
    {
        $noms_du_code[$x[1] !== '' ? $x[1] : $x[2]] = TRUE;
    }
}

// 4. Rien d'inventé : une liste qui nomme un réglage absent protège un fantôme.
foreach (['NF_DEMO_REGLAGES_EXCLUS' => NF_DEMO_REGLAGES_EXCLUS, 'NF_DEMO_REGLAGES_PUBLICS' => NF_DEMO_REGLAGES_PUBLICS] as $liste => $noms)
{
    foreach (array_keys($noms) as $nom)
    {
        if (!isset($noms_du_code[$nom]))
        {
            $anomalies[] = sprintf("%s figure dans %s, mais aucun code ne le lit ni ne l'écrit — nom inventé ou périmé.", $nom, $liste);
        }
    }
}

// 5. Rien d'oublié : un réglage dont le nom désigne un secret est exclu, ou déclaré public.
foreach (array_keys($noms_du_code) as $nom)
{
    if (preg_match(NF_DEMO_MOTIF_SECRET, $nom) && !isset(NF_DEMO_REGLAGES_EXCLUS[$nom]) && !isset(NF_DEMO_REGLAGES_PUBLICS[$nom]))
    {
        $anomalies[] = sprintf("%s a le nom d'un secret, et l'instantané de la démo l'emporterait.\n"
            ."                 → l'ajouter à NF_DEMO_REGLAGES_EXCLUS, ou à NF_DEMO_REGLAGES_PUBLICS avec sa raison (tools/lib/demo.php).", $nom);
    }
}

// 6. Le fichier produit : ce qui est publié est ce qui compte. Un instantané pris avant que la liste
// change porte encore ce qu'elle exclut désormais — c'est ainsi que la clé secrète du captcha y était
// restée après la correction du 2026-10-02.
$instantane = (string) @file_get_contents($racine.'/install/demo.sql');

if ($instantane === '')
{
    nf_refus('install/demo.sql est introuvable : le fichier produit ne peut pas être relu');
}

$reglages = nf_sql_tuples($instantane, 'nf_settings');
$col_nom  = array_search('name', $reglages['colonnes'], TRUE);

foreach ($reglages['tuples'] as $tuple)
{
    if ($col_nom !== FALSE && isset(NF_DEMO_REGLAGES_EXCLUS[(string) $tuple['valeurs'][$col_nom]]))
    {
        $anomalies[] = sprintf("install/demo.sql porte le réglage %s, que l'instantané doit exclure — le régénérer (php tools/dump-demo.php).",
            $tuple['valeurs'][$col_nom]);
    }
}

$widgets      = nf_sql_tuples($instantane, 'nf_widgets');
$col_reglages = array_search('settings', $widgets['colonnes'], TRUE);

foreach ($widgets['tuples'] as $tuple)
{
    $json = $col_reglages !== FALSE ? $tuple['valeurs'][$col_reglages] : NULL;

    if ($json !== NULL && nf_demo_reglages_widget($json) !== $json)
    {
        $anomalies[] = sprintf("install/demo.sql porte un secret dans les réglages du widget n°%s (%s) — le régénérer (php tools/dump-demo.php).",
            $tuple['valeurs'][0], $tuple['valeurs'][1]);
    }
}

printf("Instantané : %d réglage(s) que le code nomme, dont %d exclu(s) et %d public(s) ; %d réglage(s) du site et %d widget(s) relus dans install/demo.sql.\n\n",
    count($noms_du_code), count(NF_DEMO_REGLAGES_EXCLUS), count(NF_DEMO_REGLAGES_PUBLICS), count($reglages['tuples']), count($widgets['tuples']));

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

nf_ok(sprintf('les %d module(s) qui touchent aux addons ou aux rôles sont verrouillés, ce que les autres écrivent est rétabli par la remise à zéro, et install/demo.sql ne porte aucun des %d réglages exclus ni aucune clé de widget', count($verrouilles), count(NF_DEMO_REGLAGES_EXCLUS)));
