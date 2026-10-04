<?php
declare(strict_types=1);

/**
 * profils — ce qu'un site installé selon un profil doit servir, et le vérifier en le frappant.
 *
 * Diffusion : publique
 *
 * Pourquoi
 * --------
 * Deux contrôles installent chaque profil pour de vrai : `check-install-profiles` par la
 * bibliothèque de l'installeur, `check-assistant` par l'assistant web, comme un visiteur. Tous deux
 * frappent ensuite les mêmes routes, avec la même règle. La liste ne vit qu'ici : un module ajouté à
 * l'une et oublié dans l'autre ferait mentir la seconde.
 *
 * La règle est stricte. Une route du cœur rend ce qu'elle rend toujours ; celle d'un module PRÉSENT
 * rend 200, celle d'un module ABSENT un 404 propre — jamais un 5xx, jamais un 404 pour un module
 * installé. Jusqu'au 2026-10-04, seul un 5xx comptait : `/fr/games` rendait 404 sur les profils qui
 * installent les Jeux, et personne ne le voyait. Le module n'a pas de page publique (c'est un
 * catalogue d'administration, lu par les équipes et les événements) : il a quitté la liste.
 *
 * Usage
 * -----
 *   $echecs = nf_frapper_profil($serveur->base, $profil['module']);    // une ligne par route
 *   $intrus = nf_tables_hors_profil($db, $profil['module'], $tous);    // tables d'un module absent
 */

require_once __DIR__.'/outil.php';
require_once __DIR__.'/serveur.php';
require_once __DIR__.'/site.php';

/** Les routes du cœur, et le code que chacune doit rendre (sans suivre les redirections). */
const NF_ROUTES_COEUR = [
    '/'                  => 302,
    '/fr'                => 200,
    '/fr/contact'        => 200,
    '/fr/members'        => 200,
    '/fr/sitemap.xml'    => 200,
    '/fr/robots.txt'     => 200,
    '/admin'             => 302,
    '/fr/introuvable-xyz'=> 404,
];

/** La page publique de chaque module optionnel qui en a une : 200 s'il est installé, 404 sinon. */
const NF_ROUTES_MODULES = [
    'news' => '/fr/news', 'forum' => '/fr/forum', 'gallery' => '/fr/gallery', 'teams' => '/fr/teams',
    'events' => '/fr/events', 'calendar' => '/fr/calendar', 'awards' => '/fr/awards', 'recruits' => '/fr/recruits',
    'partners' => '/fr/partners', 'articles' => '/fr/blog', 'wiki' => '/fr/wiki',
    'faq' => '/fr/faq', 'downloads' => '/fr/downloads', 'shop' => '/fr/shop', 'guestbook' => '/fr/guestbook',
    'links' => '/fr/links', 'surveys' => '/fr/surveys', 'classifieds' => '/fr/classifieds', 'bugtracker' => '/fr/bugtracker',
];

/**
 * Frappe les routes du cœur et celles des modules, et imprime une ligne par route.
 *
 * @param  list<string> $modules  les modules installés en plus du cœur
 * @return int                    le nombre de routes qui n'ont pas rendu le code attendu
 */
function nf_frapper_profil(string $base, array $modules, ?NfBocal $bocal = NULL): int
{
    $routes = [];

    foreach (NF_ROUTES_COEUR as $route => $code)
    {
        $routes[$route] = [$code, 'cœur'];
    }

    foreach (NF_ROUTES_MODULES as $module => $route)
    {
        $routes[$route] = in_array($module, $modules, TRUE) ? [200, 'présent'] : [404, 'ABSENT du profil'];
    }

    $echecs = 0;

    foreach ($routes as $route => [$attendu, $role])
    {
        $reponse = nf_http($base.$route, ['suivre' => 0, 'timeout' => 25, 'bocal' => $bocal]);
        $code    = $reponse['code'];
        $ko      = $code !== $attendu;
        $echecs += $ko ? 1 : 0;

        // Une redirection dit où elle mène ; une absence de réponse dit pourquoi (délai, connexion).
        $detail = $code >= 300 && $code < 400 ? ' → '.($reponse['entetes']['location'] ?? '?')
                : ($code === 0 && $reponse['raison'] !== '' ? ' ('.$reponse['raison'].')' : '');

        printf("    %s %-22s %s%s  %s%s\n", $ko ? '✗' : '·', $route, $code ?: 'pas de réponse', $detail, $role,
            $ko ? sprintf(' — attendu %d', $attendu) : '');

        if ($ko && $code >= 500 && $reponse['corps'] !== '')
        {
            nf_avertir('        '.trim(substr(strip_tags($reponse['corps']), 0, 300)));
        }
    }

    return $echecs;
}

/**
 * Les tables de modules HORS profil présentes dans la base : une installation qui en a n'est pas
 * celle du profil, et rien de ce qu'on y mesure ne prouverait quoi que ce soit. On regarde la table
 * `nf_<module>` de chaque module optionnel (`Installer::presets()`, tous profils confondus).
 *
 * @param  list<string> $modules  ceux du profil
 * @param  list<string> $tous     tous les modules optionnels
 * @return list<string>
 */
function nf_tables_hors_profil(mysqli $db, array $modules, array $tous): array
{
    $intrus = [];

    foreach (array_unique($tous) as $module)
    {
        if (!in_array($module, $modules, TRUE) && nf_table_existe($db, 'nf_'.$module))
        {
            $intrus[] = 'nf_'.$module;
        }
    }

    return $intrus;
}
