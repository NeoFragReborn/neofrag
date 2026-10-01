<?php
declare(strict_types=1);

/**
 * check-all — lance toute la batterie de contrôles d'un coup, et dit ce qu'elle n'a pas joué.
 *
 * Famille : outil
 *
 * Pourquoi
 * --------
 * Les contrôles n'avaient aucun lanceur : la CI les appelle un par un, et en local chaque séance
 * refaisait une boucle shell jetable — jamais la même, jamais complète. Deux conséquences vues le
 * 2026-09-20 : `composer audit` n'existait QUE dans la CI, qui ne tournait pas sur la branche de
 * travail — onze avis de sécurité sont restés ouverts sans que rien ne le dise ; et les contrôles
 * lents, en navigateur, étaient oubliés des boucles à la main.
 *
 * Ce qu'il fait
 * -------------
 * Il DÉCOUVRE les contrôles présents (`tools/check-*.php`), lit dans l'en-tête de chacun sa
 * famille — il n'en tient aucune liste, qui périmerait au prochain contrôle ajouté — lance ceux
 * qui sont demandés avec une durée bornée, et rend un tableau lisible, code non nul dès qu'un
 * contrôle échoue. Trois familles, parce qu'elles n'ont pas le même coût :
 *
 *   statique    lit les sources, quelques secondes, aucune dépendance — joué par défaut ;
 *   navigateur  sert le site et souvent ouvre Chrome, quelques minutes — ajouté par --navigateur ;
 *   cible       exige un argument ou une base jetable, ou abîme le site — jamais automatique,
 *               l'outil rappelle sa commande à la fin.
 *
 * Un contrôle qui dit `Batterie : <arguments>` dans son en-tête les reçoit (`check-langs --toutes`).
 *
 * Usage
 * -----
 *   php tools/check-all.php                 composer audit + les contrôles statiques
 *   php tools/check-all.php --navigateur    + ceux qui servent le site (long)
 *   php tools/check-all.php --seul=langs,css-variables
 *   php tools/check-all.php --sauf=liens
 *   php tools/check-all.php --liste         ce qui existe, classé, sans rien lancer
 *   php tools/check-all.php --minutes=20    plafond par contrôle (défaut : 10)
 */

require __DIR__.'/lib/outil.php';
require __DIR__.'/lib/navigateur.php';

[$o] = nf_options(['navigateur' => FALSE, 'tout' => FALSE, 'liste' => FALSE, 'seul' => '', 'sauf' => '', 'minutes' => 10]);

$o['navigateur'] = $o['navigateur'] || $o['tout'];
$seul = array_filter(array_map('trim', explode(',', $o['seul'])));
$sauf = array_filter(array_map('trim', explode(',', $o['sauf'])));

/*
 * GARDE-FOU : de la place pour écrire, sinon aucun verdict.
 *
 * Presque tous les contrôles écrivent dans le dossier temporaire. Quand il est plein, ils
 * n'échouent pas franchement : ils rendent une sortie VIDE, qui se lit comme un succès. Le
 * 2026-09-21, `/tmp` était plein à 100 %, PHPStan rendait zéro ligne, et ce silence a été lu comme
 * « aucune erreur » alors qu'il restait dix coercitions de type. Un outil qui ne peut pas écrire
 * doit le DIRE.
 */
$temporaire = sys_get_temp_dir();
$libre      = @disk_free_space($temporaire);
$minimum    = 300 * 1024 * 1024;

if ($libre !== FALSE && $libre < $minimum)
{
    nf_refus(sprintf("il ne reste que %.0f Mo dans %s, et il en faut au moins %d.\n"
        ."  Sans place, les contrôles rendent une sortie vide, qui se lit comme un succès.\n"
        ."  rm -rf %s/.com.google.Chrome.* %s/nf-* %s/phpstan",
        $libre / 1048576, $temporaire, $minimum / 1048576, $temporaire, $temporaire, $temporaire));
}

// ── Découverte des contrôles, famille lue dans l'en-tête ──────────────────────────────────────
$controles = [];

foreach (glob(nf_racine().'/tools/check-*.php') ?: [] as $fichier)
{
    $nom = substr(basename($fichier, '.php'), strlen('check-'));

    if ($nom === 'all')
    {
        continue;
    }

    $entete   = (string) file_get_contents($fichier, FALSE, NULL, 0, 6000);
    $famille  = preg_match('/^ \* Famille : (statique|navigateur|cible)\s*$/m', $entete, $m) ? $m[1] : 'statique';
    $batterie = preg_match('/^ \* Batterie : (.+?)\s*$/m', $entete, $m) ? trim($m[1]) : '';
    $usage    = preg_match('/^ \*\s{2,}(php tools\/check-'.preg_quote($nom, '/').'\.php.*?)(?:\s{2,}.*)?$/m', $entete, $m) ? trim($m[1]) : '';

    $controles[$nom] = ['fichier' => $fichier, 'famille' => $famille, 'batterie' => $batterie, 'usage' => $usage];
}

ksort($controles);

if (!$controles)
{
    nf_refus('aucun contrôle trouvé dans tools/ — le dépôt est-il complet ?');
}

$titres = [
    'statique'   => 'Statiques (joués par défaut)',
    'navigateur' => 'Navigateur (option --navigateur)',
    'cible'      => 'À cible explicite (jamais automatiques)',
];

if ($o['liste'])
{
    echo 'Contrôles présents : '.count($controles)."\n\n";

    foreach ($titres as $famille => $titre)
    {
        $liste = array_filter($controles, static fn (array $c): bool => $c['famille'] === $famille);
        echo $titre.' — '.count($liste)."\n";

        foreach ($liste as $nom => $c)
        {
            echo '  '.$nom.($c['batterie'] !== '' ? ' '.$c['batterie'] : '')."\n";
        }

        echo "\n";
    }

    exit(NF_OK);
}

// ── Sélection ─────────────────────────────────────────────────────────────────────────────────
if ($seul && ($inconnus = array_diff($seul, array_keys($controles))))
{
    nf_refus('contrôle inconnu : '.implode(', ', $inconnus));
}

$a_jouer = [];

foreach ($controles as $nom => $c)
{
    if ($seul)
    {
        if (in_array($nom, $seul, TRUE))
        {
            $a_jouer[$nom] = $c;
        }

        continue;
    }

    if (in_array($nom, $sauf, TRUE) || $c['famille'] === 'cible')
    {
        continue;
    }

    if ($c['famille'] === 'statique' || $o['navigateur'])
    {
        $a_jouer[$nom] = $c;
    }
}

// ── Exécution ─────────────────────────────────────────────────────────────────────────────────

/** Lance une commande, borne sa durée, et rend [code de sortie, sortie mêlée, secondes]. */
/**
 * La commande qui lance Composer, ou NULL s'il est introuvable.
 *
 * Sous Windows, `composer` installé pour Git Bash n'est qu'un script shell posé à côté de
 * `composer.phar` : l'invite de commandes — celle de `proc_open()` — ne sait pas le lancer, et
 * l'audit échouait en un dixième de seconde en annonçant « 0 avis de sécurité » (2026-10-01). On
 * lance alors `composer.phar` avec le PHP courant.
 */
function commande_composer(): ?string
{
    if (stripos(PHP_OS, 'WIN') !== 0)
    {
        return 'composer';
    }

    if (trim((string) @shell_exec('where composer.bat composer.cmd composer.exe 2>NUL')) !== '')
    {
        return 'composer';
    }

    foreach (explode(PATH_SEPARATOR, (string) getenv('PATH')) as $dossier)
    {
        if ($dossier !== '' && is_file($phar = rtrim($dossier, '\\/').DIRECTORY_SEPARATOR.'composer.phar'))
        {
            return escapeshellarg(PHP_BINARY).' '.escapeshellarg($phar);
        }
    }

    return NULL;
}

function lancer(string $commande, int $secondes_max): array
{
    $debut = microtime(TRUE);
    $tubes = [];
    $proc  = proc_open($commande, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $tubes, nf_racine());

    if (!is_resource($proc))
    {
        return [127, "Impossible de lancer : $commande", 0.0];
    }

    stream_set_blocking($tubes[1], FALSE);
    stream_set_blocking($tubes[2], FALSE);

    $sortie = '';
    $expire = FALSE;

    while (TRUE)
    {
        $etat    = proc_get_status($proc);
        $sortie .= (string) stream_get_contents($tubes[1]);
        $sortie .= (string) stream_get_contents($tubes[2]);

        if (!$etat['running'])
        {
            break;
        }

        if (microtime(TRUE) - $debut > $secondes_max)
        {
            $expire = TRUE;
            proc_terminate($proc, 9);
            break;
        }

        usleep(100000);
    }

    $sortie .= (string) stream_get_contents($tubes[1]);
    $sortie .= (string) stream_get_contents($tubes[2]);
    fclose($tubes[1]);
    fclose($tubes[2]);

    $code = $expire ? 124 : (proc_get_status($proc)['exitcode'] ?? proc_close($proc));

    if ($expire)
    {
        proc_close($proc);
    }

    return [$code, $sortie, microtime(TRUE) - $debut];
}

/** La dernière ligne non vide : c'est là que les contrôles mettent leur verdict. */
function verdict(string $sortie): string
{
    $lignes = array_values(array_filter(array_map('trim', explode("\n", $sortie)), 'strlen'));

    return $lignes ? end($lignes) : '(aucune sortie)';
}

$resultats    = [];
$debut_global = microtime(TRUE);

echo 'Batterie NeoFrag Reborn — '.count($a_jouer).' contrôle(s)';
echo $o['navigateur'] ? ", navigateur compris\n" : " (ajouter --navigateur pour les épreuves en navigateur)\n";
echo str_repeat('─', 96)."\n";

// composer audit d'abord : le contrôle qui n'avait aucun lanceur local, et il coûte deux secondes.
if (!$seul && !in_array('audit', $sauf, TRUE) && is_file(nf_racine().'/composer.lock'))
{
    $composer = commande_composer();
    [$code, $sortie, $duree] = $composer !== NULL ? lancer($composer.' audit --locked --no-interaction 2>&1', 120) : [127, '', 0.0];
    $avis = substr_count($sortie, 'Package: ');
    $resultats['composer audit'] = [
        'ok'      => $code === 0,
        'duree'   => $duree,
        'verdict' => match (TRUE) {
            $code === 0     => 'aucun avis de sécurité sur les dépendances verrouillées',
            $avis > 0       => $avis.' avis de sécurité — détail : composer audit --locked',
            $composer === NULL => 'Composer introuvable — l\'installer, ou lancer composer audit --locked à la main',
            default         => 'composer audit n\'a pas abouti : '.mb_substr(trim(strtok(trim($sortie), "\n") ?: ''), 0, 60),
        },
        'sortie'  => $sortie,
    ];
    printf("%-1s %-26s %6.1fs  %s\n", $code === 0 ? '✓' : '✗', 'composer audit', $duree, $resultats['composer audit']['verdict']);
}

foreach ($a_jouer as $nom => $c)
{
    $args = $c['batterie'] !== '' ? ' '.$c['batterie'] : '';
    [$code, $sortie, $duree] = lancer(escapeshellarg(PHP_BINARY).' '.escapeshellarg($c['fichier']).$args.' 2>&1', $o['minutes'] * 60);

    $resultats[$nom] = [
        'ok'      => $code === 0,
        'duree'   => $duree,
        'verdict' => $code === 124 ? 'INTERROMPU après '.$o['minutes'].' min (--minutes= pour allonger)' : verdict($sortie),
        'sortie'  => $sortie,
    ];

    printf("%-1s %-26s %6.1fs  %s\n", $code === 0 ? '✓' : '✗', $nom, $duree, mb_substr($resultats[$nom]['verdict'], 0, 58));
}

// ── Bilan ─────────────────────────────────────────────────────────────────────────────────────
$echecs = array_keys(array_filter($resultats, static fn (array $r): bool => !$r['ok']));

echo str_repeat('─', 96)."\n";
printf("%d vert(s), %d rouge(s), en %s\n", count($resultats) - count($echecs), count($echecs),
    gmdate('i\m\i\n s\s', (int) (microtime(TRUE) - $debut_global)));

foreach ($echecs as $nom)
{
    echo "\n── ".$nom.' '.str_repeat('─', max(0, 90 - mb_strlen($nom)))."\n";
    echo rtrim(implode("\n", array_slice(explode("\n", trim($resultats[$nom]['sortie'])), -25)))."\n";
}

// Ce que cette exécution n'a PAS couvert : le dire, sinon « tout est vert » trompe.
$non_joues = array_diff(array_keys($controles), array_keys($resultats));

if ($non_joues)
{
    echo "\nNon joué ici :\n";

    foreach ($non_joues as $nom)
    {
        $c = $controles[$nom];

        // La VRAIE raison : annoncer « exclu par --sauf » quand c'est --seul qui a restreint ferait
        // chercher une option qu'on n'a pas passée.
        if ($c['famille'] === 'cible')
        {
            $pourquoi = $c['usage'] !== '' ? $c['usage'] : 'à cible explicite';
        }
        elseif (in_array($nom, $sauf, TRUE))
        {
            $pourquoi = 'exclu par --sauf';
        }
        elseif ($seul)
        {
            $pourquoi = 'hors sélection --seul';
        }
        elseif ($c['famille'] === 'navigateur')
        {
            $pourquoi = 'épreuve en navigateur, ajouter --navigateur';
        }
        else
        {
            $pourquoi = 'non joué';
        }

        echo '  '.$nom.' — '.$pourquoi."\n";
    }
}

if (!$o['navigateur'] && !$seul)
{
    echo "\nLa suite de tests et l'analyse statique ne sont pas ici : composer test && composer stan\n";
}

// Chrome sème, la batterie balaie — seulement après des contrôles en navigateur.
if ($o['navigateur'] && ($menage = nf_chrome_menage()) > 0)
{
    printf("\nMénage : %d dossier(s) de travail de Chrome supprimé(s) dans %s.\n", $menage, sys_get_temp_dir());
}

exit($echecs ? NF_ECHEC : NF_OK);
