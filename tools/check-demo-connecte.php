<?php
declare(strict_types=1);

/**
 * check-demo-connecte — la démonstration telle que les gens la voient : chaque thème, en visiteur puis connecté avec le compte de son bandeau, de jour et de nuit, au téléphone et à l'ordinateur.
 *
 * Famille : cible
 * Diffusion : publique
 *
 * Pourquoi
 * --------
 * Un thème ne se dit fini qu'une fois vu sur la DÉMONSTRATION, en visiteur et connecté avec le compte public qu'annonce
 * son bandeau (2026-10-06) : le site d'essai n'a ni ce bandeau, ni ces contenus, ni ce compte. Cette revue se faisait
 * par un script écrit pour l'occasion, photos à l'appui ; « Connexion » collé à « Inscription » dans Chronique y a été
 * vu, et rien de permanent ne l'aurait revu. Elle devient un contrôle, avec les sondes de `check-mise-en-page`
 * (débordement, texte tronqué, chevauchements, boutons collés, contraste, cibles…) et ce qui passe sous le bandeau
 * de la démo, page défilée.
 *
 * Ce que le contrôle fait
 * -----------------------
 *   1. il lit sur l'accueil le sélecteur de thème : les thèmes proposés, le nom de son cookie et son « époque » ;
 *   2. il parcourt la démo en visiteur, et garde une page par modèle d'adresse (`--max` pages lues au plus) ;
 *   3. pour chaque thème, de jour puis de nuit, il fait rendre ces pages par le pilote de `check-mise-en-page`, en
 *      VISITEUR, puis CONNECTÉ par le vrai formulaire de l'accueil — jamais une session posée à la main — ; une page
 *      notée `@` (`--pages=@/fr/user`) ne se voit que connecté ;
 *   4. il vérifie qu'on est connecté sur chaque page du passage connecté, et sur aucune du visiteur.
 * Rien n'est écrit : on regarde, puis on se déconnecte.
 *
 * Usage
 * -----
 *   php tools/check-demo-connecte.php
 *   php tools/check-demo-connecte.php --themes=chronique,forge --modes=nuit --largeurs=390
 *   php tools/check-demo-connecte.php --pages=/fr,/fr/forum,@/fr/user --detail
 *   php tools/check-demo-connecte.php --site=https://demo.exemple.org --compte=demo --motdepasse=demo
 */

require __DIR__.'/lib/outil.php';
require __DIR__.'/lib/depot.php';
require __DIR__.'/lib/serveur.php';
require __DIR__.'/lib/parcours.php';
require __DIR__.'/lib/mise-en-page.php';

[$o] = nf_options([
    'site'       => 'https://demo.neofrag-reborn.xyz',
    'compte'     => 'demo',
    'motdepasse' => 'demo',
    'themes'     => '',
    'modes'      => 'jour,nuit',
    'largeurs'   => '390,1440',
    'pages'      => '',
    'max'        => 60,
    'parallele'  => 3,
    'detail'     => FALSE,
]);

$base     = rtrim((string) $o['site'], '/');
$largeurs = array_values(array_filter(array_map('intval', explode(',', (string) $o['largeurs'])), fn (int $l): bool => $l >= 240));
$modes    = [];

foreach (array_filter(array_map('trim', explode(',', (string) $o['modes']))) as $m)
{
    $modes[$m] = ['jour' => 'light', 'nuit' => 'dark'][$m] ?? nf_refus("--modes=$m : « jour » ou « nuit »");
}

if (!$largeurs || !$modes)
{
    nf_refus('--largeurs= et --modes= : au moins une valeur exploitable');
}

$node = nf_mep_node();

// ── 1. Le sélecteur de thème de l'accueil ──────────────────────────────────────────────
$accueil = nf_http($base.'/fr');

if ($accueil['code'] !== 200)
{
    nf_refus(sprintf('%s/fr répond %d %s', $base, $accueil['code'], $accueil['raison']));
}

if (!preg_match('/class="nf-theme-switch[^"]*"[^>]*data-nf-cookie="([^"]+)"[^>]*data-nf-epoque="(\d+)"/', $accueil['corps'], $selecteur))
{
    nf_refus('aucun sélecteur de thème sur l\'accueil : la démo ne propose pas de choisir son thème');
}

preg_match_all('/data-theme-pick="([a-z0-9_]+)"/', $accueil['corps'], $proposes);
$themes = array_values(array_unique($proposes[1]));

if ($o['themes'] !== '')
{
    $themes = array_values(array_intersect($themes, array_map('trim', explode(',', (string) $o['themes']))));
}

if (!$themes)
{
    nf_refus('aucun thème à éprouver');
}

// ── 2. Les pages ───────────────────────────────────────────────────────────────────────
if ($o['pages'] !== '')
{
    $pages = array_values(array_filter(array_map('trim', explode(',', (string) $o['pages']))));
}
else
{
    printf("Parcours de %s en visiteur…\n", $base);
    $lues  = nf_parcourir_site($base, ['/fr'], max(1, (int) $o['max']))['html'];
    $pages = nf_mep_representantes($lues, 1)['public'];
    $pages[] = '@/fr/user';
}

$publiques = array_values(array_filter($pages, fn (string $p): bool => $p[0] !== '@'));
$membres   = array_values(array_map(fn (string $p): string => ltrim($p, '@'), $pages));

printf("%s — %d thème(s) (%s) × %d mode(s) × 2 profils × %d largeur(s) ; %d page(s) en visiteur, %d connecté\n\n",
    $base, count($themes), implode(', ', $themes), count($modes), count($largeurs), count($publiques), count($membres));

// ── 3. Les rendus ──────────────────────────────────────────────────────────────────────
$constats = [];
$muettes  = [];
$rendus   = 0;

foreach ($themes as $theme)
{
    foreach ($modes as $mode => $valeur)
    {
        $stockage = ['nf-theme' => $valeur, 'nf-'.$theme.'-theme' => $valeur];
        $cookies  = [
            ['name' => $selecteur[1], 'value' => $theme],
            ['name' => $selecteur[1].'_epoch', 'value' => $selecteur[2]],
            // Le bandeau des cookies accepté, comme le ferait un visiteur : il ne couvre plus le bas des pages.
            ['name' => 'nf_consent', 'value' => 'essentials'],
        ];

        foreach (['visiteur' => NULL, $o['compte'] => ['chemin' => '/fr', 'login' => (string) $o['compte'], 'motdepasse' => (string) $o['motdepasse']]] as $profil => $connexion)
        {
            $liste     = $connexion === NULL ? $publiques : $membres;
            $etiquette = $theme.' '.$mode.' '.$profil;
            $debut     = microtime(TRUE);
            $sortie    = nf_mep_piloter($node, $base, $liste, $largeurs, max(1, (int) $o['parallele']),
                ['bandeau' => -1, 'cookies' => $cookies, 'stockage' => $stockage, 'connexion' => $connexion]);

            foreach ($sortie as $r)
            {
                if ($r['erreur'] !== '' || $r['code'] >= 400 || count($r['mesures']) !== count($largeurs))
                {
                    $muettes[] = sprintf('%s %s — %s', $etiquette, $r['chemin'], $r['erreur'] !== '' ? $r['erreur'] : 'HTTP '.$r['code']);
                    continue;
                }

                $lieu_page = ['site' => 'démo', 'theme' => $etiquette, 'largeur' => 0, 'modele' => nf_mep_modele($r['chemin']), 'chemin' => $r['chemin']];

                if (($connexion !== NULL) !== (bool) ($r['connecte'] ?? FALSE))
                {
                    $constats[] = $lieu_page + ['type' => 'connexion', 'cle' => $connexion !== NULL ? 'déconnecté en route' : 'connecté sans le vouloir', 'detail' => $connexion !== NULL ? 'la page ne montre plus le lien de déconnexion' : 'un visiteur voit un lien de déconnexion'];
                }

                foreach ($r['console'] ?? [] as $texte)
                {
                    $constats[] = $lieu_page + ['type' => str_starts_with($texte, 'erreur JavaScript') ? 'erreur JavaScript' : 'erreur de console', 'cle' => preg_replace('/\d+/', 'N', $texte), 'detail' => $texte];
                }

                foreach ($r['ressources'] ?? [] as $texte)
                {
                    $constats[] = $lieu_page + ['type' => 'fichier introuvable', 'cle' => $texte, 'detail' => $texte];
                }

                foreach ($r['mesures'] as $i => $m)
                {
                    $rendus++;

                    foreach (nf_mep_constats($m) as $c)
                    {
                        $constats[] = ['largeur' => $largeurs[$i]] + $lieu_page + $c;
                    }
                }

                foreach ($r['bandeau'] ?? [] as $x)
                {
                    $constats[] = ['largeur' => (int) $x['largeur']] + $lieu_page
                        + ['type' => 'sous le bandeau', 'cle' => $x['el'].($x['bande'] !== '' ? ' sous '.$x['bande'] : ''), 'detail' => $x['detail']];
                }
            }

            printf("  %-34s %3d page(s) × %d largeur(s) en %ds\n", $etiquette, count($liste), count($largeurs), (int) round(microtime(TRUE) - $debut));
        }
    }
}

// ── 4. Le rapport ──────────────────────────────────────────────────────────────────────
// Un texte resté en français n'est pas jugé ici : sur la démo, c'est le plus souvent du contenu d'exemple, que
// `check-mise-en-page --langue=en` rattache à son origine sur le site d'essai.
$defauts = nf_mep_regrouper($constats, fn (array $c): ?array => $c['type'] === 'texte en français' ? NULL : $c);

echo "\n";
nf_mep_montrer($defauts, (bool) $o['detail']);

if ($muettes)
{
    printf("\n%d page(s) non mesurée(s) :\n", count($muettes));

    foreach (array_slice($muettes, 0, 12) as $m)
    {
        echo '  · '.$m."\n";
    }
}

if ($rendus === 0)
{
    nf_refus('aucun rendu mesuré : le contrôle n\'a rien vu');
}

if ($defauts || $muettes)
{
    nf_echec(sprintf('%d défaut(s) sur la démo, %d page(s) non mesurée(s), sur %d rendu(s)', count($defauts), count($muettes), $rendus));
}

nf_ok(sprintf('%d rendu(s) de la démo, en visiteur et connecté — rien à reprendre', $rendus));
