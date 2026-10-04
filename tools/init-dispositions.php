<?php
declare(strict_types=1);

/**
 * init-dispositions — donne sa mise en page par défaut à chaque thème installé qui n'en a pas.
 *
 * Famille : outil
 * Diffusion : publique
 *
 * Pourquoi
 * --------
 * Un thème enregistré dans `nf_addon` n'a pas forcément de lignes dans `nf_dispositions`. C'est le
 * cas de tous ceux qu'une installation enregistre par BALAYAGE DU DISQUE : l'enregistrement pose
 * l'addon, il ne lance pas son `install()`. Activer un tel thème affiche alors un site **vide** —
 * les zones existent, aucun widget dedans.
 *
 * L'interface a bien un filet : `Theme::enable()` lance `install()` quand le thème n'a aucune
 * disposition. Mais il ne se déclenche qu'au moment où quelqu'un active le thème. Sur le site de
 * démonstration, où le visiteur peut changer de thème pour voir à quoi ils ressemblent, quatre
 * thèmes sur six rendaient donc une page vide (constaté le 2026-09-16).
 *
 * Ce que fait cet outil : il emprunte exactement le chemin du produit. Il lève un serveur local,
 * ouvre la page « Thèmes & addons » avec une session d'administrateur, y lit le lien « Activer »
 * de chaque thème — jeton CSRF compris — et le suit. C'est `enable()` qui travaille, donc
 * `install()` du thème lui-même : aucune disposition n'est fabriquée ici, et rien ne peut diverger
 * de ce que l'interface produirait. Le thème par défaut d'origine est RÉTABLI à la fin, y compris
 * si une activation échoue ou si l'outil est interrompu.
 *
 * Usage
 * -----
 *   php tools/init-dispositions.php            n'agit que sur les thèmes sans disposition
 *   php tools/init-dispositions.php --liste    dit ce qu'il ferait, sans rien changer
 *   php tools/init-dispositions.php --port=8104
 */

require __DIR__.'/lib/outil.php';
require __DIR__.'/lib/site.php';
require __DIR__.'/lib/serveur.php';

[$o] = nf_options(['liste' => FALSE, 'port' => 0]);

$db = nf_connexion();

// ── Qui manque de dispositions ? ────────────────────────────────────────────
$installes = array_values(array_diff(nf_themes_installes($db), ['admin']));
$avec      = array_map('strval', nf_colonne($db, 'SELECT DISTINCT theme FROM nf_dispositions'));
$manquants = array_values(array_diff($installes, $avec));
$origine   = nf_reglage($db, 'nf_default_theme') ?? '';

printf("Thèmes installés   : %s\n", implode(', ', $installes) ?: '(aucun)');
printf("Déjà pourvus       : %s\n", implode(', ', array_intersect($installes, $avec)) ?: '(aucun)');
printf("Sans disposition   : %s\n", implode(', ', $manquants) ?: '(aucun)');
printf("Thème par défaut   : %s\n\n", $origine);

if (!$manquants)
{
    nf_ok('rien à faire, chaque thème installé a déjà sa mise en page');
}

if ($o['liste'])
{
    nf_ok('--liste : rien n\'a été modifié');
}

// Le thème d'origine et son époque reviennent quoi qu'il arrive.
nf_theme_temporaire($db);

$serveur = nf_serveur(nf_port($o['port']), ['NF_OUTIL_SESSION' => nf_session_admin($db)]);
$base    = $serveur->base;

/** Même translittération que `url_title()` du cœur, sans avoir à booter le framework. */
function url_slug(string $texte): string
{
    $texte = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texte) ?: $texte;
    $texte = strtolower((string) preg_replace('/[^a-zA-Z0-9]+/', '-', $texte));

    return trim($texte, '-');
}

$page = nf_http($base.'/fr/admin/addons', ['timeout' => 60]);

if (stripos($page['corps'], 'addon-card') === FALSE)
{
    nf_refus("la page « Thèmes & addons » n'a pas été servie (session refusée ?). Journal : {$serveur->journal}");
}

// Les liens d'activation portent le jeton CSRF : on les LIT plutôt que de le reconstruire.
// Forme réelle (cf. modules/addons/views/admin.tpl.php) : `admin/addons/enable/{id}/{slug}?_={jeton}`.
// Les actions qui demandent confirmation portent leur adresse dans `data-modal-ajax` et gardent
// `href="#"` — chercher seulement `href` ne trouvait donc RIEN.
preg_match_all('#(?:href|data-modal-ajax)="([^"]*admin/addons/enable/[^"]*)"#i', $page['corps'], $trouves);

$liens = array_unique($trouves[1] ?? []);

if (!$liens)
{
    // Un refus doit dire POURQUOI : une page vide, une session refusée, un mode démo qui masque les
    // actions et une forme d'URL qui a changé se corrigent différemment.
    $copie = nf_temp('page.html');
    file_put_contents($copie, $page['corps']);

    nf_refus(sprintf("aucun lien d'activation de thème sur la page des addons (%d octets, %d occurrence(s) de « enable »).\n"
        ."  Les actions sont masquées quand le module `addons` est verrouillé (mode démo). Copie de la page : %s",
        strlen($page['corps']), substr_count($page['corps'], 'enable'), $copie));
}

echo "Activation de chaque thème sans disposition, puis retour au thème d'origine :\n\n";

$faits = [];
$rates = [];

foreach ($manquants as $theme)
{
    $lien_theme = NULL;

    foreach ($liens as $url)
    {
        if (preg_match('#admin/addons/enable/\d+/'.preg_quote(url_slug($theme), '#').'(\?|$)#i', $url))
        {
            $lien_theme = $url;
            break;
        }
    }

    printf('  %-12s ', $theme);

    if ($lien_theme === NULL)
    {
        echo "lien d'activation introuvable\n";
        $rates[] = $theme;
        continue;
    }

    nf_http(str_starts_with($lien_theme, 'http') ? $lien_theme : $base.'/'.ltrim(html_entity_decode($lien_theme), '/'), ['timeout' => 60]);

    $compte = (int) nf_scalar($db, "SELECT COUNT(*) FROM nf_dispositions WHERE theme = '".$db->real_escape_string($theme)."'");

    if ($compte > 0)
    {
        printf("%d disposition(s)\n", $compte);
        $faits[] = $theme;
    }
    else
    {
        echo "aucune disposition créée\n";
        $rates[] = $theme;
    }
}

printf("\n%d thème(s) pourvu(s). Thème par défaut rétabli : %s\n", count($faits), $origine);

if ($rates)
{
    nf_echec('échecs : '.implode(', ', $rates)." — journal du serveur : {$serveur->journal}");
}

nf_ok(sprintf('%d thème(s) pourvu(s) de leur mise en page', count($faits)));
