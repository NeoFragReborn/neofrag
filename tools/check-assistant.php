<?php
declare(strict_types=1);

/**
 * check-assistant — l'assistant d'installation web, joué de bout en bout comme un visiteur, profil par profil.
 *
 * Famille : cible
 * Diffusion : publique
 *
 * Pourquoi
 * --------
 * Celui qui installe NeoFrag Reborn passe par l'assistant web : il ouvre son site, lit les prérequis,
 * choisit un profil, donne sa base, crée son compte. Aucun contrôle ne jouait ce chemin-là. La CI
 * installe par `ci-install`, `check-install-profiles` par la bibliothèque de l'installeur : tous deux
 * sautent les écrans, la session, le jeton anti-falsification, les refus, l'import du wiki et le nom
 * du site. Un assistant cassé n'aurait été vu que par le premier inconnu à l'essayer.
 *
 * Celui-ci joue l'assistant en HTTP, avec ses cookies, comme un navigateur sans JavaScript, sur une
 * copie faite comme le paquet d'installation (ou sur le vrai paquet, ou sur un site déjà servi par
 * Apache, nginx ou Caddy). Pour chaque profil :
 *
 *   1. les prérequis : tous remplis, chacun de `Installer::PREREQUIS` affiché ; l'écran dans chacune
 *      des six langues (une fois) ;
 *   2. les refus : un envoi sans jeton, un profil inventé, l'écran administrateur demandé trop tôt,
 *      un mauvais mot de passe de base, une base qui porte déjà une installation, un compte
 *      invalide — chacun refusé avec son message, sans rien écrire ;
 *   3. le profil : les modules qu'il affiche sont ceux du profil ; une dépendance décochée revient
 *      d'office et l'écran final le dit ; un module ajouté au formulaire hors profil n'est PAS
 *      installé (une fois chacun) ;
 *   4. l'arrivée : verrou posé, configuration écrite (adresse du site comprise), compte unique et
 *      administrateur, nom du site appliqué, wiki importé quand le module est là, aucune table
 *      d'un module absent ; l'assistant ne revient plus ; chaque route rend ce qu'elle doit ;
 *   5. la connexion par le vrai formulaire, avec le mot de passe saisi, ouvre l'administration ;
 *   6. le journal PHP du site est resté muet.
 *
 * Il crée et détruit ses bases (`nfassistant_<profil>_install_test`) : il lui faut un compte qui en
 * a le droit — `NF_DB_HOST`, `NF_DB_PORT`, `NF_DB_USER`, `NF_DB_PASS`, à défaut le compte
 * administrateur de `config/db-test.php` (`root_user`). Sur un site servi par un autre (`--url`), il
 * ne joue que si le site est vierge, et n'efface que ce que l'assistant a écrit.
 *
 * Usage
 * -----
 *   php tools/check-assistant.php                       copie faite comme le paquet public, tous les profils
 *   php tools/check-assistant.php --profil=core         un seul profil
 *   php tools/check-assistant.php --variante=demo       comme le paquet de démonstration (contenu de démo)
 *   php tools/check-assistant.php --paquet=dist/neofrag-reborn-public-1.2.24.zip    le vrai paquet
 *   php tools/check-assistant.php --url=http://localhost:8080 --racine=/var/www/html  site servi par un autre
 *   php tools/check-assistant.php --garder              garde la copie et les bases, pour regarder
 *   php tools/check-assistant.php --trace               chaque requête
 */

require __DIR__.'/lib/outil.php';
require __DIR__.'/lib/depot.php';
require __DIR__.'/lib/site.php';
require __DIR__.'/lib/serveur.php';
require __DIR__.'/lib/paquet.php';
require __DIR__.'/lib/profils.php';
require __DIR__.'/lib/journal.php';
require nf_racine().'/neofrag/installer.php';

use NF\NeoFrag\Installer;

[$o] = nf_options([
    'profil' => '', 'variante' => 'public', 'paquet' => '', 'url' => '', 'racine' => '',
    'port' => 0, 'garder' => FALSE, 'trace' => FALSE,
]);

if (!in_array($o['variante'], ['public', 'demo'], TRUE))
{
    nf_refus("variante inconnue : {$o['variante']} — public ou demo");
}

if (($o['url'] === '') !== ($o['racine'] === ''))
{
    nf_refus('--url et --racine vont ensemble : l\'adresse du site servi, et le dossier qu\'il sert');
}

// ── Le compte de base de données ──────────────────────────────────────────────
if (getenv('NF_DB_USER') !== FALSE)
{
    $acces = [
        'hostname' => getenv('NF_DB_HOST') ?: '127.0.0.1',
        'port'     => (int) (getenv('NF_DB_PORT') ?: 3306),
        'username' => (string) getenv('NF_DB_USER'),
        'password' => (string) getenv('NF_DB_PASS'),
    ];
}
else
{
    $test = @include nf_racine().'/config/db-test.php';

    if (!is_array($test) || empty($test['root_user']))
    {
        nf_refus("il faut un compte capable de créer et détruire des bases : NF_DB_HOST, NF_DB_PORT, NF_DB_USER, NF_DB_PASS,\n"
            .'  ou config/db-test.php avec root_user — php tools/prepare-test-db.php');
    }

    $acces = [
        'hostname' => (string) ($test['host'] ?? '127.0.0.1'),
        'port'     => (int) ($test['port'] ?? 3306),
        'username' => (string) $test['root_user'],
        'password' => (string) ($test['root_pass'] ?? ''),
    ];
}

$sql = nf_connexion_admin($acces, 'aux bases jetables');

// ── L'arbre joué ──────────────────────────────────────────────────────────────
$copie   = '';
$serveur = NULL;

if ($o['racine'] !== '')
{
    $racine = rtrim(str_replace('\\', '/', (string) realpath($o['racine'])), '/');
    $base   = rtrim($o['url'], '/');

    if ($racine === '' || !is_file($racine.'/index.php') || !is_file($racine.'/install/index.php'))
    {
        nf_refus("--racine ne désigne pas un site NeoFrag Reborn avec son assistant : {$o['racine']}");
    }

    if (is_file($racine.'/config/db.php') || is_file($racine.'/install/db.txt'))
    {
        nf_refus("le site servi est déjà installé ({$racine}) : check-assistant ne joue que sur un site vierge,\n"
            .'  et n\'efface jamais une installation');
    }
}
else
{
    $copie = (is_dir('/var/tmp') ? '/var/tmp' : sys_get_temp_dir()).'/nf-assistant-'.bin2hex(random_bytes(3));

    if (!@mkdir($copie, 0775, TRUE))
    {
        nf_refus("impossible de créer la copie jetable {$copie}");
    }

    // Le serveur s'arrête AVANT qu'on efface ce qu'il sert : sous Windows, un fichier servi reste tenu.
    register_shutdown_function(static function () use ($copie, $o, &$serveur): void {
        $serveur?->arreter();

        if (!$o['garder'])
        {
            nf_supprimer($copie);
        }
    });

    if ($o['paquet'] !== '')
    {
        $zip = new ZipArchive();

        if (!is_file($o['paquet']) || $zip->open($o['paquet']) !== TRUE)
        {
            nf_refus("paquet illisible : {$o['paquet']}");
        }

        $zip->extractTo($copie);
        $zip->close();

        // Le paquet range le site dans un dossier (`neofrag-reborn/`) : c'est son contenu qu'on téléverse.
        $dedans = array_values(array_diff(scandir($copie) ?: [], ['.', '..']));
        $racine = count($dedans) === 1 && is_file($copie.'/'.$dedans[0].'/index.php') ? $copie.'/'.$dedans[0] : $copie;

        if (!is_file($racine.'/install/index.php'))
        {
            nf_refus('le paquet ne porte pas l\'assistant d\'installation (install/index.php)');
        }
    }
    else
    {
        // Ce que porte le paquet, et rien d'autre : la même règle que `build-release`. On ne parcourt
        // que les entrées admises à la racine — `dist/` ou `node_modules/` n'ont rien à y faire.
        $racine = $copie;
        $copies = 0;

        foreach (NF_PAQUET_RACINE as $entree)
        {
            $source   = nf_racine().'/'.$entree;
            $fichiers = is_dir($source) ? nf_parcourir($source) : (is_file($source) ? [basename($source) => new SplFileInfo($source)] : []);

            foreach ($fichiers as $relatif => $fichier)
            {
                $relatif = is_dir($source) ? $entree.'/'.$relatif : $entree;

                if (nf_paquet_exclu($relatif, $o['variante']))
                {
                    continue;
                }

                $cible = $racine.'/'.$relatif;

                if (!is_dir(dirname($cible)))
                {
                    @mkdir(dirname($cible), 0775, TRUE);
                }

                if (!@copy($fichier->getPathname(), $cible))
                {
                    nf_refus("copie impossible : {$relatif}");
                }

                $copies++;
            }
        }

        file_put_contents($racine.'/config/neofrag.php', Installer::config_neofrag($o['variante'] === 'demo'));

        printf("Copie faite comme le paquet %s : %d fichiers, dans %s\n", $o['variante'], $copies, $racine);
    }

    foreach (['cache', 'logs', 'upload', 'backups'] as $dossier)
    {
        @mkdir($racine.'/'.$dossier, 0775, TRUE);
    }

    $serveur = nf_serveur(nf_port($o['port']), [], $racine);
    $base    = $serveur->base;
}

// Ce que l'arbre contient AVANT : entre deux profils, on efface tout ce qui est apparu depuis — et
// seulement cela — dans les dossiers où l'installation écrit.
$ecrits = ['config', 'install', 'cache', 'upload', 'backups'];
$avant  = [];

foreach ($ecrits as $dossier)
{
    if (is_dir($racine.'/'.$dossier))
    {
        foreach (nf_parcourir($racine.'/'.$dossier) as $relatif => $_)
        {
            $avant[$dossier.'/'.$relatif] = TRUE;
        }
    }
}

$remettre_a_zero = static function () use ($racine, $ecrits, $avant): void {
    foreach ($ecrits as $dossier)
    {
        if (!is_dir($racine.'/'.$dossier))
        {
            continue;
        }

        foreach (iterator_to_array(nf_parcourir($racine.'/'.$dossier)) as $relatif => $fichier)
        {
            if (!isset($avant[$dossier.'/'.$relatif]))
            {
                @unlink($fichier->getPathname());
            }
        }
    }
};

register_shutdown_function($remettre_a_zero);

// ── Les profils ───────────────────────────────────────────────────────────────
$profils = Installer::presets($racine);
$tous    = array_merge(...array_values(array_map(static fn (array $p): array => $p['module'], $profils)));

if ($o['profil'] !== '')
{
    if (!isset($profils[$o['profil']]))
    {
        nf_refus("profil inconnu : {$o['profil']} — disponibles : ".implode(', ', array_keys($profils)));
    }

    $profils = [$o['profil'] => $profils[$o['profil']]];
}

$bases = [];

register_shutdown_function(static function () use (&$bases, $sql, $o): void {
    if ($o['garder'])
    {
        return;
    }

    foreach ($bases as $nom)
    {
        @$sql->query("DROP DATABASE IF EXISTS `{$nom}`");
    }
});

$journal = $racine.'/logs/php.log';

if (!nf_journal_preparer($journal))
{
    nf_refus("le journal du site n'est pas inscriptible ({$journal}) : le contrôle serait aveugle");
}

// ── Les verdicts ──────────────────────────────────────────────────────────────
$echecs = 0;

function juger(string $titre, bool $ok, string $detail = ''): bool
{
    global $echecs;

    $echecs += $ok ? 0 : 1;
    printf("  %-6s %-62s %s\n", $ok ? 'OK' : 'ÉCHEC', $titre, $detail);

    return $ok;
}

/** Une requête du visiteur, avec ses cookies, sans suivre les redirections. */
function visiter(string $chemin, ?array $post = NULL, bool $ajax = FALSE): array
{
    global $base, $bocal, $o;

    $reponse = nf_http($base.$chemin, ['post' => $post, 'suivre' => 0, 'timeout' => 120, 'bocal' => $bocal, 'ajax' => $ajax]);

    if ($o['trace'])
    {
        printf("         %s %s → %d%s\n", $post === NULL ? 'GET ' : 'POST', $chemin, $reponse['code'],
            isset($reponse['entetes']['location']) ? ' → '.$reponse['entetes']['location'] : '');
    }

    $reponse['texte'] = html_entity_decode($reponse['corps'], ENT_QUOTES | ENT_HTML5, 'UTF-8');

    return $reponse;
}

/** Les messages d'erreur que l'assistant affiche (`<div class="errors">`). */
function erreurs(array $reponse): array
{
    if (!preg_match('#<div class="errors">(.*?)</div>#s', $reponse['corps'], $bloc))
    {
        return [];
    }

    preg_match_all('#<p>(.*?)</p>#s', $bloc[1], $p);

    return array_map(static fn (string $m): string => html_entity_decode(strip_tags($m), ENT_QUOTES | ENT_HTML5, 'UTF-8'), $p[1]);
}

/** Le début d'une réponse en erreur serveur, pour la diagnostiquer d'un coup d'œil ; rien sinon. */
function extrait(array $reponse): string
{
    if ($reponse['code'] !== 0 && $reponse['code'] < 500)
    {
        return '';
    }

    $texte = trim((string) preg_replace('/\s+/u', ' ', strip_tags($reponse['corps'])));

    return ' — '.($texte !== '' ? mb_strimwidth($texte, 0, 160, '…') : '(réponse vide : une erreur fatale avant le journal du site ?)');
}

function jeton(array $reponse): string
{
    return preg_match('/name="csrf" value="([^"]+)"/', $reponse['corps'], $t) ? $t[1] : '';
}

/** Le texte d'une phrase de l'assistant dans une langue, tel que `install/lib/langue.php` le rend. */
function traduire(string $racine, string $langue, string $texte): string
{
    static $catalogues = [];

    if ($langue === 'fr')
    {
        return $texte;
    }

    $catalogues[$langue] ??= (array) (@include $racine.'/install/langs/'.$langue.'.php');

    return (string) ($catalogues[$langue][sprintf('%08x', crc32($texte))] ?? $texte);
}

/** La base d'une installation, comme le site la lit (config/db.php). */
function base_du_site(string $racine): ?mysqli
{
    $cfg = Installer::read_db_config($racine.'/config');

    if ($cfg === NULL)
    {
        return NULL;
    }

    $db = nf_connexion_admin($cfg, 'à la base installée');
    $db->select_db($cfg['database']);

    return $db;
}

// Un paquet de démonstration importe, à la création du compte, ses membres et ses réglages — dont
// le nom du site : l'administrateur créé reste le seul qu'on attend, pas le seul compte.
$demo        = is_file($racine.'/install/demo.sql');
$langues     = ['fr', 'en', 'de', 'es', 'it', 'pt'];
// Un texte que l'écran montre toujours, prérequis remplis ou non : le nom d'une étape.
$temoin      = 'Profil du site';
$langues_vues = FALSE;
$dependance  = FALSE;
$hors_profil = FALSE;
$precedente  = '';
$admin       = ['username' => 'assistant-admin', 'email' => 'assistant@example.test'];

printf("Assistant d'installation joué sur %s (%s)\n", $base, $racine);

foreach ($profils as $cle => $profil)
{
    printf("\n═══ Profil « %s » — %d module(s) en plus du cœur\n", $profil['title'], count($profil['module']));

    $nom_base = 'nfassistant_'.preg_replace('/[^a-z0-9]/', '', strtolower($cle)).'_install_test';
    $bases[]  = $nom_base;
    $bocal    = new NfBocal();
    $octet    = nf_journal_taille($journal);
    $site     = 'Assistant '.$profil['title'];
    $secret   = 'Aa1-'.bin2hex(random_bytes(6));

    // La première base n'existe pas : l'assistant doit la créer. Les suivantes sont vides, comme
    // celles qu'un hébergeur fournit.
    $sql->query("DROP DATABASE IF EXISTS `{$nom_base}`");

    if ($precedente !== '' && !$sql->query("CREATE DATABASE `{$nom_base}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"))
    {
        nf_refus("base jetable `{$nom_base}` impossible : {$sql->error}");
    }

    // 1. Les prérequis
    $r = visiter('/?lang=fr');
    $ko = preg_match_all('#<li class="ko">.*?<span class="t">(.*?)</span>#s', $r['corps'], $manquants) ?: 0;

    juger('L\'assistant prend la main sur un site vierge', $r['code'] === 200 && str_contains($r['corps'], 'class="checks"'), 'HTTP '.$r['code']);
    juger('Tous les prérequis sont remplis', $ko === 0, $ko ? implode(', ', array_map('strip_tags', $manquants[1])) : '');

    $absentes = array_values(array_filter(Installer::PREREQUIS['extensions'], static fn (string $e): bool => !str_contains($r['texte'], 'Extension '.$e)));
    juger('Chaque extension requise est affichée', !$absentes, $absentes ? 'absentes : '.implode(', ', $absentes) : count(Installer::PREREQUIS['extensions']).' extensions');

    if (!$langues_vues)
    {
        $langues_vues = TRUE;
        $muettes      = [];

        foreach ($langues as $langue)
        {
            $l = visiter('/?step=requirements&lang='.$langue);

            if ($l['code'] !== 200 || !str_contains($l['corps'], '<html lang="'.$langue.'"') || !str_contains($l['texte'], traduire($racine, $langue, $temoin)))
            {
                $muettes[] = $langue.' (HTTP '.$l['code'].')';
            }
        }

        visiter('/?step=requirements&lang=fr');
        juger('L\'écran des prérequis parle chacune des six langues', !$muettes, $muettes ? implode(', ', $muettes) : implode(' ', $langues));
    }

    // 2. Les refus, avant tout choix
    $r = visiter('/?step=database');
    juger('« Continuer » mène au choix du profil', str_contains($r['corps'], 'name="preset"'), 'HTTP '.$r['code']);
    $csrf = jeton($r);

    $r = visiter('/?step=modules', ['preset' => $cle]);
    juger('Un envoi sans jeton est refusé', in_array('Session expirée, merci de recommencer l\'étape.', erreurs($r), TRUE), implode(' | ', erreurs($r)));

    $r = visiter('/?step=modules', ['csrf' => $csrf, 'preset' => 'profil-invente']);
    juger('Un profil inventé est refusé', in_array('Profil inconnu — choisissez-en un dans la liste.', erreurs($r), TRUE), implode(' | ', erreurs($r)));

    $r = visiter('/?step=admin');
    juger('L\'écran administrateur ne s\'ouvre pas avant la base', !str_contains($r['corps'], 'name="site_name"'), 'HTTP '.$r['code']);

    // 3. Le profil
    $r = visiter('/?step=modules');
    $csrf = jeton($r);
    preg_match_all('/name="preset" value="([^"]+)"/', $r['corps'], $radios);
    juger('Le profil est proposé', in_array($cle, $radios[1], TRUE), implode(', ', $radios[1]));

    $affiches = [];

    if (preg_match('#<fieldset class="modgroup" id="mods-'.preg_quote($cle, '#').'"[^>]*>(.*?)</fieldset>#s', $r['corps'], $groupe))
    {
        preg_match_all('#name="modules\[\]" value="([^"]+)" checked\s+data-requires="([^"]*)"#', $groupe[1], $cases, PREG_SET_ORDER);

        foreach ($cases as $case)
        {
            $affiches[$case[1]] = array_filter(explode(',', $case[2]));
        }
    }

    $attendus = $profil['module'];
    sort($attendus);
    $vus = array_keys($affiches);
    sort($vus);
    juger('Les modules affichés sont ceux du profil', $vus === $attendus, count($vus).' cochés'.($vus === $attendus ? '' : ' — attendu : '.implode(', ', $attendus)));

    $envoyes = $vus;
    $retire  = '';

    // Une dépendance décochée revient d'office (une fois) : la première trouvée dans le profil.
    if (!$dependance)
    {
        foreach ($affiches as $module => $requis)
        {
            foreach ($requis as $requis_un)
            {
                if ($requis_un !== $module && isset($affiches[$requis_un]))
                {
                    $retire = $requis_un;
                    break 2;
                }
            }
        }

        if ($retire !== '')
        {
            $dependance = TRUE;
            $envoyes    = array_values(array_diff($envoyes, [$retire]));
            printf("         (« %s » décoché : un autre module du profil en dépend)\n", $retire);
        }
    }

    // Un module ajouté au formulaire hors du profil ne doit pas s'installer (une fois).
    $intrus = '';

    if (!$hors_profil && ($candidats = array_values(array_diff(array_unique($tous), $profil['module']))))
    {
        $hors_profil = TRUE;
        $intrus      = $candidats[0];
        $envoyes[]   = $intrus;
        printf("         (« %s » ajouté au formulaire : il n'est pas du profil)\n", $intrus);
    }

    $r = visiter('/?step=modules', ['csrf' => $csrf, 'preset' => $cle, 'modules' => $envoyes]);
    juger('Le profil est retenu', $r['code'] === 302 && str_contains($r['entetes']['location'] ?? '', 'step=database'), 'HTTP '.$r['code'].' → '.($r['entetes']['location'] ?? '—'));

    // 4. La base
    $r = visiter('/?step=database');
    juger('L\'écran de la base s\'ouvre', str_contains($r['corps'], 'name="hostname"'), 'HTTP '.$r['code']);
    $csrf = jeton($r);

    $formulaire = ['csrf' => $csrf, 'hostname' => $acces['hostname'], 'port' => (string) $acces['port'],
        'username' => $acces['username'], 'password' => $acces['password'], 'database' => $nom_base];

    $r = visiter('/?step=database', ['password' => 'faux-'.bin2hex(random_bytes(4))] + $formulaire);
    $e = erreurs($r);
    juger('Un mauvais mot de passe de base est refusé', $e && str_starts_with($e[0], 'Connexion impossible') && !is_file($racine.'/config/db.php'),
        $e ? mb_strimwidth($e[0], 0, 60, '…') : 'aucun message');

    if ($precedente !== '')
    {
        $r = visiter('/?step=database', ['database' => $precedente] + $formulaire);
        $e = erreurs($r);
        juger('Une base qui porte déjà une installation est refusée', $e && str_contains($e[0], 'contient déjà une installation') && !is_file($racine.'/config/db.php'),
            $e ? mb_strimwidth($e[0], 0, 60, '…') : 'aucun message');
    }

    $r = visiter('/?step=database', $formulaire);
    $installe = $r['code'] === 302 && str_contains($r['entetes']['location'] ?? '', 'step=admin');
    juger('La base est installée', $installe, $installe ? ($precedente === '' ? 'base créée par l\'assistant' : 'base vide fournie') : 'HTTP '.$r['code'].' '.implode(' | ', erreurs($r)));

    if (!$installe)
    {
        $remettre_a_zero();
        continue;
    }

    $config = array_filter(['db.php', 'crypt.php', 'password.php', 'email.php', 'url.php'], static fn (string $f): bool => !is_file($racine.'/config/'.$f));
    juger('La configuration est écrite', !$config, $config ? 'manquent : '.implode(', ', $config) : 'db, crypt, password, email, url');

    $adresse = (string) @file_get_contents($racine.'/config/url.php');
    juger('L\'adresse du site est celle de l\'installation', str_contains($adresse, var_export($base, TRUE)), $base);

    // 5. Le compte
    $r = visiter('/?step=admin');
    juger('L\'écran administrateur s\'ouvre', str_contains($r['corps'], 'name="site_name"'), 'HTTP '.$r['code']);
    $csrf = jeton($r);

    $r = visiter('/?step=admin', ['csrf' => $csrf, 'site_name' => '', 'username' => '', 'email' => 'pas-une-adresse',
        'password' => 'court', 'password2' => 'autre', 'webmaster_password' => 'court', 'webmaster_password2' => 'autre']);
    $attendues = ['Le nom du site est obligatoire.', 'Le pseudo administrateur est obligatoire.', 'Adresse email invalide.',
        'Le mot de passe doit faire au moins 8 caractères.', 'Les deux mots de passe ne correspondent pas.',
        'Le mot de passe webmaster doit faire au moins 8 caractères.', 'Les deux mots de passe webmaster ne correspondent pas.'];
    $manquent  = array_values(array_diff($attendues, erreurs($r)));
    juger('Un compte invalide est refusé, champ par champ', !$manquent && !is_file($racine.'/install/db.txt'),
        $manquent ? 'messages absents : '.implode(' | ', $manquent) : count($attendues).' messages');

    $r = visiter('/?step=admin', ['csrf' => $csrf, 'site_name' => $site, 'username' => $admin['username'], 'email' => $admin['email'],
        'password' => $secret, 'password2' => $secret, 'webmaster_password' => $secret.'-wm', 'webmaster_password2' => $secret.'-wm']);
    $fini = $r['code'] === 200 && str_contains($r['texte'], 'NeoFrag est prêt') && !erreurs($r);
    juger('L\'installation se termine', $fini, $fini ? '' : 'HTTP '.$r['code'].' '.implode(' | ', erreurs($r)));
    juger('Le verrou est posé, le mot de passe webmaster écrit', is_file($racine.'/install/db.txt') && is_file($racine.'/config/webmaster.php'));

    if ($retire !== '')
    {
        juger('L\'écran final dit la dépendance ajoutée d\'office', (bool) preg_match('#Ajoutés d\'office[^<]*<strong>[^<]*\b'.preg_quote($retire, '#').'\b#u', $r['texte']), $retire);
    }

    // 6. Ce que la base porte
    $db = base_du_site($racine);

    if ($db === NULL)
    {
        juger('La base installée est joignable', FALSE, 'config/db.php illisible');
        $remettre_a_zero();
        continue;
    }

    $comptes = (int) nf_scalar($db, 'SELECT COUNT(*) FROM nf_user');
    $chef    = (int) nf_scalar($db, "SELECT COUNT(*) FROM nf_user WHERE username = '".$db->real_escape_string($admin['username'])."' AND admin = '1'");

    if ($demo)
    {
        juger('Le compte créé est administrateur, les membres de démo sont là', $chef === 1 && $comptes > 1, $comptes.' compte(s)');
    }
    else
    {
        juger('Un seul compte, administrateur', $comptes === 1 && $chef === 1, $comptes.' compte(s)');
        juger('Le nom du site est appliqué', nf_reglage($db, 'nf_name') === $site, (string) nf_reglage($db, 'nf_name'));
    }

    $type      = nf_type_id($db, 'module');
    $installes = nf_colonne($db, 'SELECT name FROM nf_addon WHERE type_id = '.$type);
    $absents   = array_values(array_diff($profil['module'], $installes));
    juger('Chaque module du profil est installé', !$absents, $absents ? 'absents : '.implode(', ', $absents) : count($profil['module']).' modules');

    if ($intrus !== '')
    {
        juger('Le module ajouté hors profil n\'est pas installé', !in_array($intrus, $installes, TRUE) && !nf_table_existe($db, 'nf_'.$intrus), $intrus);
    }

    $tables_intruses = nf_tables_hors_profil($db, $profil['module'], $tous);
    juger('Aucune table d\'un module absent', !$tables_intruses, implode(', ', $tables_intruses));

    if (in_array('wiki', $profil['module'], TRUE) && is_file($racine.'/install/wiki.sql'))
    {
        $pages = nf_table_existe($db, 'nf_wiki_pages') ? (int) nf_scalar($db, 'SELECT COUNT(*) FROM nf_wiki_pages') : 0;
        juger('La documentation est importée dans le wiki', $pages > 0, $pages.' page(s)');
    }

    // 7. Le site, une fois installé
    $r = visiter('/?step=modules');
    juger('L\'assistant ne revient plus', $r['code'] < 500 && !str_contains($r['corps'], 'data-nf-assistant'), 'HTTP '.$r['code'].extrait($r));

    // Le verrou perdu (un déploiement qui l'écrase, un dossier vidé) : la base dit que le site est
    // installé, l'assistant ne doit pas revenir, et le verrou doit être reposé. Et même s'il revenait,
    // son étape administrateur refuse de créer un second compte.
    @unlink($racine.'/install/db.txt');
    $r = visiter('/?step=admin');
    juger('Le verrou perdu, l\'assistant ne revient pas, et le verrou est reposé',
        $r['code'] < 500 && !str_contains($r['corps'], 'data-nf-assistant') && is_file($racine.'/install/db.txt'), 'HTTP '.$r['code'].extrait($r));

    $r = visiter('/fr');
    $nom = $demo ? (string) nf_reglage($db, 'nf_name') : $site;
    juger('L\'accueil porte le nom du site', $r['code'] === 200 && (bool) preg_match('#<title>[^<]*'.preg_quote($nom, '#').'#u', $r['texte']), 'HTTP '.$r['code'].' — '.$nom.extrait($r));

    echo "  Routes :\n";
    $routes = nf_frapper_profil($base, $profil['module']);
    juger('Chaque route rend ce qu\'elle doit', $routes === 0, $routes ? $routes.' écart(s)' : '');

    // 8. La connexion, par le vrai formulaire
    $avant_connexion = visiter('/fr/admin');
    $formulaire      = nf_formulaire(nf_balisage(visiter('/fr/ajax/user/login', NULL, TRUE)['corps']), 'name="login"');

    if ($formulaire === NULL)
    {
        juger('Le formulaire de connexion est servi', FALSE, '/fr/ajax/user/login');
    }
    else
    {
        $formulaire['login']    = $admin['username'];
        $formulaire['password'] = $secret;
        visiter('/fr/ajax/user/login', $formulaire, TRUE);

        $id        = (int) nf_scalar($db, "SELECT id FROM nf_user WHERE username = '".$db->real_escape_string($admin['username'])."'");
        $session   = (int) nf_scalar($db, 'SELECT COUNT(*) FROM nf_session WHERE user_id = '.$id.' AND last_activity > DATE_SUB(NOW(), INTERVAL 5 MINUTE)') > 0;
        $apres     = visiter('/fr/admin');

        juger('Le mot de passe saisi ouvre une session', $session, $session ? 'compte #'.$id : 'aucune session en base');
        juger('L\'administration s\'ouvre au compte créé', $apres['code'] === 200 && $avant_connexion['code'] !== 200,
            sprintf('visiteur : HTTP %d, connecté : HTTP %d', $avant_connexion['code'], $apres['code']));
    }

    $db->close();

    // 9. Le journal
    $classe  = nf_journal_classer(nf_journal_depuis_octet($journal, $octet));
    $fautifs = count($classe['php']) + count($classe['produit']);

    if (!juger('Le journal PHP du site est resté muet', $fautifs === 0, $fautifs ? $fautifs.' entrée(s)' : ''))
    {
        nf_journal_montrer($classe, $racine);
    }

    $precedente = $nom_base;
    $remettre_a_zero();
}

echo "\n";

if (!$dependance)
{
    nf_avertir('  (aucun profil joué n\'a de dépendance entre ses modules : le rajout d\'office n\'a pas été éprouvé)');
}

if ($echecs)
{
    nf_echec("{$echecs} vérification(s) en échec — l'assistant d'installation ne tient pas sa promesse");
}

nf_ok(sprintf('l\'assistant installe chacun des %d profil(s) de bout en bout, refuse ce qu\'il doit refuser, et laisse un site qui marche', count($profils)));
