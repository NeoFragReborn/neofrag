<?php
declare(strict_types=1);

/**
 * check-admin-back — chaque sous-page d'administration offre un retour au module, fil d'Ariane ou bouton, dans le HTML servi.
 *
 * Famille : navigateur
 * Diffusion : publique
 *
 * Pourquoi
 * --------
 * Le défaut a été signalé sur la newsletter : « bouton retour manquant sur les onglets ». Le comptage
 * fait à la main à ce moment-là — par `grep` des méthodes rendant du HTML sans `admin_back` —
 * annonçait 36 sous-pages sur 97. Ce comptage ne pouvait pas être juste : il ne voyait que l'un
 * des deux chemins de retour. Le fil d'Ariane en offre un second, et il a justement été corrigé
 * depuis pour rendre le nom du module cliquable. Aucun `grep` ne peut trancher, parce que la
 * réponse dépend de ce que la page REND, pas de ce que le contrôleur écrit.
 *
 * Cet outil ne lit donc pas le code : il charge la page et cherche, dans le HTML servi, l'un des
 * deux retours possibles — un lien du fil d'Ariane vers `admin/<module>`, ou un bouton
 * `.settings-section-back`. C'est le critère que vit l'utilisateur.
 *
 * Les routes à paramètres (`admin/roles/edit/{id}`) sont tentées avec des valeurs plausibles.
 * Quand la cible n'existe pas sur l'installation, la page ne rend pas 200 : elle est alors
 * déclarée NON JUGÉE, jamais comptée comme bonne. Un rapport qui annoncerait « 47 pages sur 47 »
 * en ayant silencieusement écarté les 160 autres serait pire qu'inutile.
 *
 * Usage
 * -----
 *   php tools/check-admin-back.php
 *   php tools/check-admin-back.php --verbeux
 *   php tools/check-admin-back.php --port=8092
 */

require __DIR__.'/lib/outil.php';
require __DIR__.'/lib/depot.php';
require __DIR__.'/lib/site.php';
require __DIR__.'/lib/serveur.php';

[$o] = nf_options(['verbeux' => FALSE, 'port' => 0]);

// ── 1. Les routes d'administration déclarées par chaque module ───────────────
// Chaque module publie ses routes dans son manifeste : 'admin/xxx' => '_methode'. C'est la source
// d'autorité — la même que celle qu'emploie le routeur.
$routes     = [];
$parametree = [];
$index      = [];

foreach (nf_addons('module') as $module => $dossier)
{
    // L'accueil d'administration de chaque module : c'est la cible du retour, jamais un cas à corriger.
    if (is_file($dossier.'/controllers/admin.php'))
    {
        $index['admin/'.$module] = TRUE;
    }

    $source = (string) file_get_contents($dossier.'/'.$module.'.php');

    if (!preg_match("/'routes'\s*=>\s*\[(.*?)\n\t*\]/s", $source, $bloc))
    {
        continue;
    }

    preg_match_all("/'(admin\/[^']*)'\s*=>\s*'([^']+)'/", $bloc[1], $trouves, PREG_SET_ORDER);

    foreach ($trouves as $t)
    {
        // Les points d'entrée AJAX ne sont pas des pages : ils n'ont ni fil d'Ariane ni bouton.
        if (str_contains($t[1], '/ajax/'))
        {
            continue;
        }

        // 'admin/roles' déclaré par le module `access` s'ouvre à l'adresse /admin/access/roles.
        $chemin = 'admin/'.$module.'/'.substr($t[1], strlen('admin/'));

        // Une route à paramètres est tout de même TENTÉE, avec des valeurs plausibles. Si la cible
        // n'existe pas, la page répond autre chose que 200 et elle est déclarée non jugée — jamais
        // comptée comme bonne. C'est préférable à l'écarter d'avance : sur cette installation, une
        // bonne partie répond réellement.
        if (str_contains($chemin, '{') || str_contains($chemin, '('))
        {
            $essai = (string) preg_replace(['/\{id\}/', '/\{url_title\}/', '/\([^)]*\)/', '/\{[^}]*\}/'], ['1', 'x', '', ''], $chemin);
            $essai = rtrim(str_replace('//', '/', $essai), '/');

            $parametree[$essai] = $module.' : '.$chemin;
            $routes[$essai]     = $module;
            continue;
        }

        $routes[$chemin] = $module;
    }
}

ksort($routes);

if (!$routes)
{
    nf_refus("aucune route d'administration trouvée : le format des manifestes a-t-il changé ?");
}

// ── 2. Session d'administrateur temporaire et serveur ───────────────────────
$db      = nf_connexion();
$serveur = nf_serveur(nf_port($o['port']), ['NF_OUTIL_SESSION' => nf_session_admin($db)]);
$base    = $serveur->base.'/fr/';

// ── 3. Le contrôle ──────────────────────────────────────────────────────────
$sans      = [];
$muettes   = [];
$controles = 0;

// Les racines de section, telles que l'utilisateur les voit : les entrées du menu latéral. On les
// lit dans le HTML SERVI plutôt que dans `themes/admin/admin.php`, pour la même raison que le reste
// de cet outil ne lit pas le code : ce qui compte est ce que la page rend à l'utilisateur.
//
// Tous les modules n'ont pas d'accueil : `access` n'expose aucune route `admin/access` — ses quatre
// pages sont des entrées DIRECTES du menu latéral, donc des racines de section, pas des sous-pages.
$menu    = nf_http($base.'admin', ['suivre' => 4]);
$racines = 0;

if ($menu['code'] === 200 && preg_match_all('#<a[^>]+class="nf-sb-item[^"]*"[^>]+href="([^"]+)"#', $menu['corps'], $liens))
{
    foreach ($liens[1] as $href)
    {
        $chemin_menu = ltrim((string) parse_url($href, PHP_URL_PATH), '/');
        $chemin_menu = (string) preg_replace('#^[a-z]{2}(/|$)#', '', $chemin_menu);

        if ($chemin_menu !== '' && str_starts_with($chemin_menu, 'admin') && !isset($index[$chemin_menu]))
        {
            $index[$chemin_menu] = TRUE;
            $racines++;
        }
    }
}
else
{
    // Sans le menu, l'outil jugerait des racines comme des sous-pages : on le dit plutôt que de
    // rendre un verdict bancal en silence.
    nf_avertir("Le menu d'administration n'a pas pu être lu (HTTP {$menu['code']}) : les racines de\n"
        ."section ne seront pas reconnues, et le verdict sera trop sévère.\n");
}

printf("%d route(s) d'administration à ouvrir, sur %d module(s) — dont %d à paramètres ;\n"
    ."%d racine(s) de section reconnue(s) au menu.\n\n",
    count($routes), count(array_unique($routes)), count($parametree), $racines);

foreach ($routes as $chemin => $module)
{
    if (isset($index[$chemin]))
    {
        continue; // accueil de module ou racine de section : c'est la destination du retour
    }

    // Suivre les redirections est indispensable : une route `…/{id}/{url_title}` ouverte avec un
    // intitulé approximatif renvoie vers son adresse canonique. Mais suivre aveuglément serait
    // pire : un point d'ACTION agit puis renvoie à l'accueil du module — qui, lui, offre bien un
    // retour. D'où l'adresse d'arrivée : on refuse de juger une page qui n'est pas celle demandée.
    $reponse = nf_http($base.$chemin, ['suivre' => 4]);
    $suffixe = isset($parametree[$chemin]) ? ' — route à paramètres' : '';

    if ($reponse['code'] !== 200 || $reponse['corps'] === '')
    {
        // Un 404 : la cible n'existe pas sur cette installation. Ce n'est pas un verdict.
        $muettes[] = sprintf('%s (HTTP %d)%s', $chemin, $reponse['code'], $suffixe);
        continue;
    }

    // Un TÉLÉCHARGEMENT n'est pas une page : le journal ou la trace du Monitoring, servis en texte avec
    // `Content-Disposition: attachment`. Le navigateur l'enregistre, l'administrateur reste sur la page
    // d'où il l'a demandé. Sur une installation où le fichier existe, il était jugé comme une page sans
    // retour (2026-10-03, sur un site d'essai) ; en CI, le fichier n'existe pas, et la réponse était un 404.
    $type = strtolower($reponse['entetes']['content-type'] ?? 'text/html');

    if (!str_starts_with($type, 'text/html') || str_contains(strtolower($reponse['entetes']['content-disposition'] ?? ''), 'attachment'))
    {
        $muettes[] = sprintf('%s (%s) : téléchargement, pas une page%s', $chemin, strtok($type, ';'), $suffixe);
        continue;
    }

    // Trois arrivées trahissent un point d'ACTION plutôt qu'une page : l'accueil du module, celui de
    // l'administration, ou le site public (« voir le site comme ce membre » active le mode aperçu
    // puis renvoie à l'accueil). Juger la page d'arrivée serait un faux verdict.
    $chemin_arrivee = ltrim((string) parse_url($reponse['arrivee'], PHP_URL_PATH), '/');
    $chemin_arrivee = (string) preg_replace('#^[a-z]{2}(/|$)#', '', $chemin_arrivee);

    if ($chemin_arrivee !== $chemin
        && (isset($index[$chemin_arrivee]) || $chemin_arrivee === 'admin' || !str_starts_with($chemin_arrivee, 'admin')))
    {
        $muettes[] = sprintf('%s → %s : point d\'action, pas une page%s', $chemin, $chemin_arrivee ?: '/', $suffixe);
        continue;
    }

    $controles++;

    // Les deux retours possibles, tels que l'utilisateur les voit.
    $fil    = (bool) preg_match('#<a[^>]+class="nf-breadcrumb-current"[^>]+href="[^"]*/admin/'.preg_quote($module, '#').'"#', $reponse['corps']);
    $bouton = str_contains($reponse['corps'], 'settings-section-back');

    if ($fil || $bouton)
    {
        if ($o['verbeux'])
        {
            printf("  ok  %-46s %s\n", $chemin, $fil && $bouton ? 'fil + bouton' : ($fil ? 'fil d\'Ariane' : 'bouton'));
        }

        continue;
    }

    $sans[] = $chemin;
}

// ── 4. Le rapport ───────────────────────────────────────────────────────────
if ($muettes)
{
    printf("\n%d page(s) n'ont pas répondu — non jugées :\n", count($muettes));

    foreach ($muettes as $m)
    {
        echo '    '.$m."\n";
    }
}

if ($sans)
{
    printf("\n%d sous-page(s) SANS aucun moyen de remonter au module :\n", count($sans));

    foreach ($sans as $s)
    {
        echo '  ✗ '.$s."\n";
    }

    nf_echec(sprintf('%d sur %d page(s) réellement jugée(s)', count($sans), $controles));
}

nf_ok(sprintf('les %d page(s) réellement jugée(s) offrent un retour au module (%d non jugées, jamais comptées comme bonnes)',
    $controles, count($muettes)));
