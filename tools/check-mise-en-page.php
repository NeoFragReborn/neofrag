<?php
declare(strict_types=1);

/**
 * check-mise-en-page — chaque page publique et d'administration, dans chaque thème, chaque mode et à chaque largeur : aucun défaut visuel que le navigateur sait constater.
 *
 * Famille : cible
 * Diffusion : publique
 *
 * Pourquoi
 * --------
 * Le 2026-09-22, deux captures d'écran montraient des boutons « Modifier / Supprimer »
 * en escalier et la valeur de la jauge « Stockage » posée sur son arc ; la capture suivante montrait
 * « Informations serveu », tronqué net. Aucun contrôle ne pouvait les voir : `check-responsive` ne
 * rendait que onze pages, dans le thème par défaut, à trois largeurs, et ne mesurait que le
 * débordement horizontal ; `check-contraste` quatre pages à une seule largeur. La demande
 * suivante : « toutes les pages publiques ou admin, avec et sans contenu, sur tous les thèmes et sur
 * toutes les dimensions d'écran ».
 *
 * Ce que l'outil fait
 * -------------------
 *   1. il parcourt le site (`lib/parcours.php`, le parcours de `check-liens`) et REGROUPE les pages
 *      par modèle d'adresse — `/fr/news/{n}/{s}` : une actualité suffit à juger le gabarit de
 *      toutes les actualités (`--par-modele=` pour en rendre davantage) ;
 *   2. il rend les pages d'administration dans le thème d'administration, et les pages publiques
 *      dans CHAQUE thème public installé, à chaque largeur — 360 px (petit téléphone) à 2 560 px ;
 *   3. dans chaque rendu, `tests/MiseEnPage/sonde.js` mesure le débordement horizontal, les boutons
 *      en escalier, le texte tronqué net, le texte posé sur un trait SVG ou sur un autre texte — puis,
 *      sur demande (« pas uniquement ces éléments-ci mais tout problème visuel, ou
 *      autre ») : les images cassées ou déformées, les icônes inconnues, le texte technique affiché
 *      par erreur, le contraste WCAG AA, le texte coupé par le bord gauche, les cibles trop petites
 *      pour un doigt ; et le pilote relève les erreurs JavaScript, les erreurs de la console et les
 *      fichiers du site introuvables ;
 *   3 bis. chaque thème est rendu dans ses DEUX modes, clair et sombre (`--modes=`), et les pages
 *      publiques le sont DEUX fois : connecté en administrateur, et en VISITEUR — la barre de nebula
 *      débordait pour l'un et pas pour l'autre, et c'est le visiteur que voit presque tout le
 *      public (`--profils=`) ;
 *   4. il lit ce que chaque thème a écrit au journal pendant qu'il rendait ses pages ;
 *   5. avec `--vierge`, il refait tout sur une installation NEUVE — sans contenu —, montée pour
 *      l'occasion dans une base jetable, puis détruite.
 *
 * Le navigateur est piloté par `tests/MiseEnPage/pilote.js` (Playwright, le Chrome installé) : une
 * page est chargée une fois, puis la fenêtre prend chaque largeur. Les constats sont regroupés par
 * défaut — un même élément fautif sur vingt pages et trois thèmes est UN défaut, dont on donne les
 * lieux.
 *
 * Usage
 * -----
 *   php tools/check-mise-en-page.php                       l'installation : tous les thèmes, 8 largeurs
 *   php tools/check-mise-en-page.php --vierge              et une installation neuve, sans contenu
 *   php tools/check-mise-en-page.php --themes=nebula,forge --largeurs=360,1280 --modes=sombre
 *   php tools/check-mise-en-page.php --pages=/fr/admin/news,/fr/admin/monitoring    sans parcours
 *   php tools/check-mise-en-page.php --langue=en --largeurs=1440 --modes=clair      le site en anglais
 *
 * `--langue=en` parcourt le site dans cette langue, et la sonde y relève en plus tout texte resté en
 * FRANÇAIS — écrit en dur, hors des traductions. « Tout le CMS doit être multilingue »
 * (2026-09-23) ; `check-langs` ne voit que ce qui passe par `lang()`.
 *   php tools/check-mise-en-page.php --par-modele=2 --max=1000 --parallele=3 --detail
 */

require __DIR__.'/lib/outil.php';
require __DIR__.'/lib/depot.php';
require __DIR__.'/lib/site.php';
require __DIR__.'/lib/serveur.php';
require __DIR__.'/lib/journal.php';
require __DIR__.'/lib/parcours.php';
require __DIR__.'/lib/vierge.php';

[$o] = nf_options([
    'themes'     => '',
    'largeurs'   => '360,414,768,1024,1280,1440,1920,2560',
    'pages'      => '',
    'par-modele' => 1,
    'max'        => 1000,
    'parallele'  => 4,
    'vierge'     => FALSE,
    'modes'      => 'clair,sombre',
    'profils'    => 'admin,visiteur',
    'langue'     => 'fr',
    'detail'     => FALSE,
    'port'       => 0,
]);

/*
 * Interrompu (Ctrl-C, `kill`), l'outil doit quand même tout remettre : la première version, arrêtée
 * par un signal, a laissé le thème du site d'essai sur « blockcraft », un serveur fantôme sur son port,
 * la base et la copie vierges. `exit()` déclenche les fonctions d'arrêt ; un signal non intercepté,
 * non. (`nf_theme_temporaire()` pose ensuite son propre gestionnaire, qui restaure puis sort.)
 */
if (function_exists('pcntl_async_signals'))
{
    pcntl_async_signals(TRUE);

    foreach ([SIGINT, SIGTERM, SIGHUP] as $signal)
    {
        pcntl_signal($signal, static function (): void { exit(130); });
    }
}

/*
 * Priorité basse, pour lui et tout ce qu'il lance (serveurs, navigateur) : le site d'essai partage sa
 * machine avec la production, et ce contrôle occupe les quatre processeurs pendant une demi-heure.
 * Mesuré pendant le premier passage complet : la production répondait toujours en 0,1 s.
 */
if (function_exists('proc_nice'))
{
    @proc_nice(10);
}

$racine   = nf_racine();
$largeurs = array_values(array_filter(array_map('intval', explode(',', $o['largeurs'])), fn (int $l): bool => $l >= 240));

if (!$largeurs)
{
    nf_refus('--largeurs= : aucune largeur exploitable');
}

if (!is_dir($racine.'/node_modules/@playwright/test'))
{
    nf_refus('Playwright absent (node_modules/@playwright/test) — `npm install` à la racine du dépôt');
}

$node = trim((string) @shell_exec(stripos(PHP_OS, 'WIN') === 0 ? 'where node 2>NUL' : 'command -v node 2>/dev/null'));
$node = $node === '' ? '' : explode("\n", $node)[0];

if ($node === '' || !is_file($node))
{
    nf_refus('node introuvable — installer Node.js');
}

/** Le modèle d'une adresse : ses identifiants et le segment qui suit un identifiant sont effacés. */
function modele(string $chemin): string
{
    $segments = explode('/', trim($chemin, '/'));
    $apres_id = FALSE;

    foreach ($segments as $i => $s)
    {
        if (ctype_digit($s))
        {
            $segments[$i] = '{n}';
            $apres_id     = TRUE;
        }
        else if ($apres_id)
        {
            $segments[$i] = '{s}';
            $apres_id     = FALSE;
        }
    }

    return '/'.implode('/', $segments);
}

/**
 * Les pages à rendre : `--par-modele` représentantes par modèle d'adresse, séparées en deux familles.
 *
 * @param  list<string> $chemins
 * @return array{admin: list<string>, public: list<string>, modeles: int}
 */
function representantes(array $chemins, int $par_modele): array
{
    $par = [];

    foreach ($chemins as $c)
    {
        $m = modele($c);

        if (count($par[$m] ?? []) < $par_modele)
        {
            $par[$m][] = $c;
        }
    }

    $admin = $public = [];

    foreach ($par as $m => $liste)
    {
        foreach ($liste as $c)
        {
            preg_match('#^/[a-z]{2}/admin(/|$)#', $c) ? $admin[] = $c : $public[] = $c;
        }
    }

    return ['admin' => $admin, 'public' => $public, 'modeles' => count($par)];
}

/**
 * Rend des pages à toutes les largeurs par le pilote Playwright, et rend ses résultats.
 *
 * @param  list<string> $pages
 * @param  list<int>    $largeurs
 * @return list<array{chemin: string, code: int, erreur: string, mesures: list<array<string, mixed>>}>
 */
function piloter(string $node, string $base, array $pages, array $largeurs, int $parallele): array
{
    if (!$pages)
    {
        return [];
    }

    $travail  = nf_temp('mise-en-page-travail.json');
    $resultat = nf_temp('mise-en-page-resultat.json');
    @unlink($resultat);

    file_put_contents($travail, (string) json_encode([
        'base' => rtrim($base, '/'), 'pages' => $pages, 'largeurs' => $largeurs,
        'hauteur' => 900, 'parallele' => $parallele, 'delai' => 120,
    ]));

    $sortie = nf_temp('mise-en-page-pilote.log');
    $code   = 0;
    $cmd    = sprintf('cd %s && %s tests/MiseEnPage/pilote.js %s %s > %s 2>&1',
        escapeshellarg(nf_racine()), escapeshellarg($node), escapeshellarg($travail), escapeshellarg($resultat), escapeshellarg($sortie));

    exec($cmd, $rien, $code);

    $json = is_file($resultat) ? json_decode((string) file_get_contents($resultat), TRUE) : NULL;

    if (!is_array($json))
    {
        nf_refus('le pilote n\'a rien rendu (code '.$code.') : '.trim((string) @file_get_contents($sortie)));
    }

    return $json;
}

/**
 * Mesure UN site : parcours, puis rendu des pages d'administration et des pages publiques dans
 * chaque thème, dans chaque mode. Rend les constats bruts et ce qui n'a pas pu être mesuré.
 *
 * @param  list<int> $largeurs
 * @return array{constats: list<array<string, mixed>>, muettes: list<string>, journal: array<string, array>, pages: int, modeles: int, rendus: int, themes: list<string>, absents: list<string>}
 */
function mesurer_site(string $nom, string $site, mysqli $db, int $port, string $node, array $o, array $largeurs): array
{
    $session = nf_session_admin($db);
    $journal = $site.'/logs/php.log';
    $avec_journal = nf_journal_preparer($journal);

    // Le thème par défaut est un RÉGLAGE du site : `nf_theme_temporaire()` le rétablit quoi qu'il
    // arrive, interruption comprise. L'installation vierge, elle, est détruite à la fin.
    $theme_initial = $nom === 'avec contenu' ? nf_theme_temporaire($db) : (nf_reglage($db, 'nf_default_theme') ?? '');

    $installes = nf_themes_installes($db);
    $publics   = array_values(array_intersect(nf_themes_publics(), $installes));

    if ($o['themes'] !== '')
    {
        $publics = array_values(array_intersect($publics, array_map('trim', explode(',', $o['themes']))));
    }

    $absents = array_values(array_diff(nf_themes_publics(), $installes));

    // Les modes : le routeur des outils écrit la préférence dans la clé de CHAQUE thème (cf.
    // `routeur-outil.php`) — un serveur par mode, puisque c'est une variable du serveur.
    $modes = [];

    foreach (array_filter(array_map('trim', explode(',', (string) $o['modes']))) as $m)
    {
        $modes[$m] = ['clair' => 'light', 'sombre' => 'dark'][$m] ?? nf_refus("--modes=$m : « clair » ou « sombre »");
    }

    if ($o['pages'] !== '')
    {
        $chemins = array_values(array_filter(array_map('trim', explode(',', $o['pages']))));
    }
    else
    {
        printf("[%s] parcours du site…\n", $nom);
        $serveur = nf_serveur($port, ['NF_OUTIL_SESSION' => $session], $site);
        $langue  = preg_match('/^[a-z]{2}$/', (string) $o['langue']) ? (string) $o['langue'] : 'fr';
        $chemins = nf_parcourir_site($serveur->base, ['/'.$langue, '/'.$langue.'/admin'], (int) $o['max'])['html'];
        $serveur->arreter();
    }

    $choix = representantes($chemins, max(1, (int) $o['par-modele']));

    printf("[%s] %d page(s), %d modèle(s) : %d d'administration, %d publique(s) × %d thème(s) (%s) × %d mode(s) × %d profil(s) × %d largeur(s)\n",
        $nom, count($chemins), $choix['modeles'], count($choix['admin']), count($choix['public']), count($publics), implode(', ', $publics), count($modes), count(explode(',', (string) $o['profils'])), count($largeurs));

    $constats  = [];
    $muettes   = [];
    $reservees = [];
    $journaux = [];
    $rendus   = 0;

    $profils = array_values(array_intersect(['admin', 'visiteur'], array_map('trim', explode(',', (string) $o['profils']))));

    if (!$profils)
    {
        nf_refus('--profils= : « admin », « visiteur », ou les deux');
    }

    foreach ($modes as $mode => $valeur)
    {
        foreach ($profils as $profil)
        {
            // Le serveur intégré ne sert qu'une requête à la fois, sauf si on lui donne des ouvriers :
            // sans eux, les onglets parallèles du pilote attendaient leur tour. Le VISITEUR n'a pas
            // de session : c'est tout ce qui le distingue.
            $env = ['NF_OUTIL_THEME' => $valeur, 'PHP_CLI_SERVER_WORKERS' => (string) max(2, (int) $o['parallele'])];

            if ($profil === 'admin')
            {
                $env['NF_OUTIL_SESSION'] = $session;
            }

            $serveur = nf_serveur($port, $env, $site);
            $lots    = $profil === 'admin' ? [['admin', $choix['admin'], NULL]] : [];

            foreach ($publics as $theme)
            {
                $lots[] = [$theme, $choix['public'], $theme];
            }

            foreach ($lots as [$theme_nom, $pages, $theme])
            {
                if (!$pages)
                {
                    continue;
                }

                if ($theme !== NULL)
                {
                    nf_reglage_poser($db, 'nf_default_theme', $theme);
                }

                $etiquette = $theme_nom.' '.$mode.($profil === 'visiteur' ? ' visiteur' : '');
                $debut     = microtime(TRUE);
                $avant     = nf_journal_taille($journal);
                $sortie    = piloter($node, $serveur->base, $pages, $largeurs, max(1, (int) $o['parallele']));

                if ($avec_journal)
                {
                    $journaux[$etiquette] = nf_journal_classer(nf_journal_depuis_octet($journal, $avant));
                }

                foreach ($sortie as $r)
                {
                    // Une page de membre refusée au VISITEUR (401, 403) a répondu ce qu'il fallait :
                    // ce n'est pas une page qu'on n'a pas pu mesurer. Comptées comme telles, les
                    // discussions privées suffisaient à faire refuser un passage en visiteur seul.
                    if ($profil === 'visiteur' && $r['erreur'] === '' && in_array((int) $r['code'], [401, 403], TRUE))
                    {
                        $reservees[$r['chemin']] = TRUE;
                        continue;
                    }

                    if ($r['erreur'] !== '' || $r['code'] >= 400 || count($r['mesures']) !== count($largeurs))
                    {
                        $muettes[] = sprintf('%s %s — %s', $etiquette, $r['chemin'], $r['erreur'] !== '' ? $r['erreur'] : 'HTTP '.$r['code']);
                        continue;
                    }

                    $lieu_page = ['site' => $nom, 'theme' => $etiquette, 'largeur' => 0, 'modele' => modele($r['chemin']), 'chemin' => $r['chemin']];

                    // Ce que le PILOTE a vu, une fois par page : erreurs JavaScript et de console,
                    // fichiers du site introuvables.
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
                        $lieu = ['largeur' => $largeurs[$i]] + $lieu_page;

                        foreach (constats_de_la_sonde($m) as $c)
                        {
                            $constats[] = $lieu + $c;
                        }
                    }
                }

                printf("  %-26s %3d page(s) × %d largeur(s) en %ds\n", $etiquette, count($pages), count($largeurs), (int) round(microtime(TRUE) - $debut));
            }

            $serveur->arreter();
        }
    }

    if ($theme_initial !== '')
    {
        nf_reglage_poser($db, 'nf_default_theme', $theme_initial);
    }

    return ['constats' => $constats, 'muettes' => $muettes, 'reservees' => array_keys($reservees), 'journal' => $journaux, 'pages' => count($chemins),
            'modeles' => $choix['modeles'], 'rendus' => $rendus, 'themes' => $publics, 'absents' => $absents];
}

/**
 * Le verdict d'une sonde, traduit en constats : un type, une CLÉ (ce qui identifie le défaut d'une
 * page à l'autre) et un détail lisible.
 *
 * @param  array<string, mixed> $m
 * @return list<array{type: string, cle: string, detail: string}>
 */
function constats_de_la_sonde(array $m): array
{
    $c = [];

    if (!empty($m['deborde']))
    {
        $coupables = implode(', ', array_map(fn (array $x): string => $x['el'].' (+'.$x['depasse'].' px)', $m['coupables'] ?? []));
        $c[] = ['type' => 'débordement', 'cle' => $coupables !== '' ? $coupables : 'la page', 'detail' => $m['deborde'].' px trop large'];
    }

    foreach ($m['tronques'] ?? [] as $t)
    {
        $c[] = ['type' => 'texte tronqué', 'cle' => $t['el'], 'detail' => '« '.$t['texte'].' » dépasse de '.$t['depasse'].' px'];
    }

    foreach ($m['escaliers'] ?? [] as $e)
    {
        $c[] = ['type' => 'boutons en escalier', 'cle' => $e['el'], 'detail' => $e['boutons'].' boutons sur '.$e['lignes'].' lignes'];
    }

    foreach ($m['chevauchements'] ?? [] as $x)
    {
        $c[] = ['type' => 'chevauchement', 'cle' => $x['el'].' sur '.$x['sur'], 'detail' => '« '.$x['texte'].' »'];
    }

    foreach ($m['images'] ?? [] as $x)
    {
        $c[] = ['type' => 'image '.explode(' ', $x['defaut'])[0], 'cle' => $x['el'].' '.$x['src'], 'detail' => $x['src'].' — '.$x['defaut']];
    }

    foreach ($m['icones'] ?? [] as $x)
    {
        $c[] = ['type' => 'icône inconnue', 'cle' => $x['classes'], 'detail' => $x['el'].' — classes « '.$x['classes'].' »'];
    }

    foreach ($m['techniques'] ?? [] as $x)
    {
        $c[] = ['type' => 'texte technique', 'cle' => $x['defaut'].' : '.$x['el'], 'detail' => $x['defaut'].' — « '.$x['texte'].' »'];
    }

    foreach ($m['contrastes'] ?? [] as $x)
    {
        $c[] = ['type' => 'contraste', 'cle' => $x['el'].' '.$x['couleur'].' / '.$x['fond'], 'detail' => sprintf('« %s » %s:1 (exigé %s:1) — %s sur %s', $x['texte'], $x['ratio'], $x['exige'], $x['couleur'], $x['fond'])];
    }

    foreach ($m['horsEcran'] ?? [] as $x)
    {
        $c[] = ['type' => 'texte hors écran', 'cle' => $x['el'], 'detail' => '« '.$x['texte'].' » coupé de '.$x['depasse'].' px par le bord gauche'];
    }

    foreach ($m['cibles'] ?? [] as $x)
    {
        $c[] = ['type' => 'cible trop petite', 'cle' => $x['el'], 'detail' => '« '.($x['texte'] ?? '').' » '.$x['taille'].' — 24×24 px au moins sur un téléphone'];
    }

    // La clé est le TEXTE : le même libellé oublié se retrouve sur cent pages, c'est un seul oubli.
    foreach ($m['francais'] ?? [] as $x)
    {
        $c[] = ['type' => 'texte en français', 'cle' => '« '.$x['texte'].' »'.($x['ou'] !== 'texte' ? ' ('.$x['ou'].')' : ''), 'detail' => $x['el'], 'texte' => $x['texte']];
    }

    return $c;
}

// ── Les sites mesurés ───────────────────────────────────────────────────────────────────
$sites = [['avec contenu', $racine, nf_connexion(), nf_port((int) $o['port'])]];

if ($o['vierge'])
{
    echo "Installation neuve, sans contenu, dans une base jetable…\n";
    $vierge  = nf_site_vierge();   // lib/vierge.php : copie, base jetable, ci-install ; détruits à la fin
    $sites[] = ['sans contenu', $vierge['racine'], $vierge['db'], NF_PORTS['check-mise-en-page-vierge']];
}

$bilans = [];

foreach ($sites as [$nom, $site, $db, $port])
{
    $bilans[$nom] = mesurer_site($nom, $site, $db, $port, $node, $o, $largeurs);
    echo "\n";
}

/**
 * D'où vient un texte resté en français ? La réponse fait le tri entre un défaut et du contenu.
 *
 * Sur un site d'essai peuplé, beaucoup de textes français sont du CONTENU de démonstration — la catégorie de forum
 * « Présentations », écrite par un membre, n'a pas à être traduite par le produit. Un texte présent
 * dans le CODE, en revanche, est écrit en dur : c'est le défaut, et l'outil dit où. Présent dans les
 * données livrées (`install/*.sql`), il arrive à chaque installation. Présent seulement comme valeur
 * française d'un fichier de langue, c'est sa traduction qui manque.
 *
 * @return array{genre: string, lieu: string}  genre : code | donnee | traduction | contenu
 */
function origine_du_texte(string $texte): array
{
    static $sources = NULL;

    if ($sources === NULL)
    {
        $sources = [];

        foreach (nf_fichiers(array_merge(NF_DOSSIERS_PRODUIT, ['js', 'install']), ['php', 'js', 'sql']) as $relatif => $fichier)
        {
            $sources[$relatif] = (string) file_get_contents($fichier);
        }
    }

    // Le texte relevé peut être tronqué, et coupé par une balise dans la source : on cherche son début.
    $debut    = mb_substr(trim($texte), 0, 40);
    $variantes = array_unique([$debut, htmlentities($debut, ENT_QUOTES, 'UTF-8'), htmlspecialchars($debut, ENT_QUOTES, 'UTF-8'), addslashes($debut)]);
    $trouves  = ['code' => NULL, 'donnee' => NULL, 'traduction' => NULL];

    foreach ($sources as $relatif => $contenu)
    {
        foreach ($variantes as $v)
        {
            if ($v === '' || ($position = strpos($contenu, $v)) === FALSE)
            {
                continue;
            }

            $lieu  = $relatif.':'.(substr_count(substr($contenu, 0, $position), "\n") + 1);
            $genre = str_contains($relatif, '/langs/') ? 'traduction' : (str_ends_with($relatif, '.sql') ? 'donnee' : 'code');
            $trouves[$genre] ??= $lieu;
            break;
        }
    }

    foreach (['code', 'donnee', 'traduction'] as $genre)
    {
        if ($trouves[$genre] !== NULL)
        {
            return ['genre' => $genre, 'lieu' => $trouves[$genre]];
        }
    }

    return ['genre' => 'contenu', 'lieu' => ''];
}

// ── Le rapport : un défaut, ses lieux ───────────────────────────────────────────────────
$defauts = [];
$contenus = 0;

foreach ($bilans as $b)
{
    foreach ($b['constats'] as $c)
    {
        $cle = $c['type'].'|'.$c['cle'];

        if ($c['type'] === 'texte en français' && !isset($defauts[$cle]))
        {
            $origine = origine_du_texte((string) ($c['texte'] ?? ''));

            if ($origine['genre'] === 'contenu')
            {
                $contenus++;
                continue;
            }

            $c['detail'] = ['code' => 'écrit en dur : ', 'donnee' => 'donnée livrée : ', 'traduction' => 'traduction manquante (clé française) : '][$origine['genre']].$origine['lieu'].' — '.$c['detail'];
        }

        $d   = &$defauts[$cle];
        $d['type']  = $c['type'];
        $d['cle']   = $c['cle'];
        $d['detail'] ??= $c['detail'];
        $d['sites'][$c['site']] = TRUE;
        $d['themes'][$c['theme']] = TRUE;
        if ($c['largeur'])
        {
            $d['largeurs'][$c['largeur']] = TRUE;
        }
        $d['modeles'][$c['modele']] = $c['chemin'];
        unset($d);
    }
}

uasort($defauts, fn (array $a, array $b): int => [$a['type'], -count($b['modeles'])] <=> [$b['type'], -count($a['modeles'])]);

$rendus  = array_sum(array_column($bilans, 'rendus'));
$muettes = array_merge(...array_values(array_map(fn (array $b): array => $b['muettes'], $bilans)));

foreach ($bilans as $nom => $b)
{
    printf("%-14s %d rendu(s) mesuré(s) — %d modèle(s) de page, thèmes : %s%s\n", $nom, $b['rendus'], $b['modeles'],
        implode(', ', array_merge(['admin'], $b['themes'])), $b['absents'] ? ' — non installés, non éprouvés : '.implode(', ', $b['absents']) : '');
}

if ($contenus)
{
    printf("(%d texte(s) français écartés : du CONTENU saisi en base, pas l'interface.)\n", $contenus);
}

echo "\n";

foreach ($defauts as $d)
{
    $d['largeurs'] ??= [];
    ksort($d['largeurs']);
    printf("  ✗ %-20s %s\n", $d['type'], mb_substr($d['cle'], 0, 120));
    printf("      %s\n", mb_substr($d['detail'], 0, 160));
    printf("      %s · %s%s\n", implode(' + ', array_keys($d['sites'])), implode(', ', array_keys($d['themes'])), $d['largeurs'] ? ' · '.implode(', ', array_keys($d['largeurs'])).' px' : '');

    $modeles = array_values($d['modeles']);
    printf("      %d page(s) : %s%s\n", count($modeles), implode(', ', array_slice($modeles, 0, $o['detail'] ? 50 : 4)), count($modeles) > 4 && !$o['detail'] ? ' …' : '');
}

// Le journal, thème par thème.
$fautes_journal = 0;

foreach ($bilans as $nom => $b)
{
    foreach ($b['journal'] as $etiquette => $classe)
    {
        if ($classe['php'] || $classe['produit'])
        {
            printf("\n  ✗ journal — %s, thème %s :\n", $nom, $etiquette);
            $fautes_journal += nf_journal_montrer($classe, $sites[array_search($nom, array_column($sites, 0), TRUE)][1], 8);
        }
    }
}

$reservees = array_values(array_unique(array_merge(...array_values(array_map(fn (array $b): array => $b['reservees'], $bilans)))));

if ($reservees)
{
    printf("\n%d page(s) réservée(s) aux membres, refusée(s) au visiteur comme il se doit : %s\n", count($reservees), implode(', ', array_slice($reservees, 0, 6)));
}

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

if (count($muettes) > max(5, $rendus / count($largeurs) / 10))
{
    nf_refus(sprintf('%d page(s) non mesurée(s) sur %d : trop pour conclure', count($muettes), (int) ($rendus / count($largeurs)) + count($muettes)));
}

if ($defauts || $fautes_journal)
{
    nf_echec(sprintf('%d défaut(s) de mise en page%s, sur %d rendu(s) mesuré(s)', count($defauts), $fautes_journal ? sprintf(' et %d message(s) au journal', $fautes_journal) : '', $rendus));
}

nf_ok(sprintf('%d rendu(s) mesuré(s) — ni débordement, ni escalier, ni texte tronqué ou chevauchant, journal muet', $rendus));
