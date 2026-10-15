<?php
declare(strict_types=1);
/**
 * check-versions-tierces — les bibliothèques embarquées sont confrontées à l'amont, et pas dans six mois.
 *
 * Famille : statique
 * Diffusion : publique
 *
 * Pourquoi cet outil existe
 * -------------------------
 * Le 2026-10-15, le relevé des huit bibliothèques livrées a trouvé TinyMCE deux versions majeures
 * en arrière — la ligne 7 perdait son support le 14 novembre, et la 8.9.3 corrigeait une faille
 * XSS du greffon « media » — et Font Awesome une majeure derrière. Personne ne l'avait vu : rien
 * ne regardait jamais l'amont. Une dépendance ne devient pas dangereuse d'un coup ; elle dérive
 * doucement, et c'est la dérive qu'il faut rendre visible.
 *
 * Ce que l'outil fait, et ce qu'il ne fait pas
 * -------------------------------------------
 * Il NE crie PAS à chaque publication de l'amont : un contrôle qui rougit le jour où Bootstrap
 * sort un correctif finit ignoré. Il tient un CLIQUET DE DATE : la date de la dernière
 * confrontation est écrite ici même, et le contrôle refuse quand elle date de plus de
 * `JOURS_AVANT_DE_REGARDER` jours. Il dit alors quoi lancer.
 *
 * Avec `--en-ligne`, il interroge le registre npm, compare version par version et dit qui est en
 * retard, de combien (correctif, mineure, MAJEURE). Avec `--ecrire` en plus, il inscrit la date du
 * jour — mais seulement si rien n'est en retard : on ne tamponne pas une dérive.
 *
 * La version LIVRÉE n'est écrite nulle part ici : elle est lue dans le fichier servi, qui est la
 * seule source de vérité (`tools/check-notice.php` la vérifie de son côté contre la NOTICE).
 *
 * Usage
 * -----
 *   php tools/check-versions-tierces.php              le cliquet de date (hors ligne, pour la CI)
 *   php tools/check-versions-tierces.php --en-ligne   confronte au registre npm
 *   php tools/check-versions-tierces.php --en-ligne --ecrire    … et inscrit la date du jour
 */

require __DIR__.'/lib/outil.php';

[$o] = nf_options(['en-ligne' => FALSE, 'ecrire' => FALSE]);
$racine = nf_racine();

/** La dernière confrontation à l'amont. Écrite par `--en-ligne --ecrire`. */
const DERNIERE_CONFRONTATION = '2026-10-14';

/** Au-delà, le contrôle refuse : une dépendance qu'on n'a pas regardée depuis un trimestre dérive. */
const JOURS_AVANT_DE_REGARDER = 90;

/**
 * Les bibliothèques livrées dont l'amont se lit sur npm.
 *
 * `fichier` + `motif` : où lire la version SERVIE (le fichier est la source de vérité).
 * `gele` : une ligne majeure qu'on ne suit pas, et pourquoi. Elle est dite, jamais refusée.
 */
const SUIVIES = [
    'Bootstrap' => [
        'npm' => 'bootstrap', 'fichier' => 'js/bootstrap.bundle.min.js', 'motif' => '/Bootstrap v([0-9.]+)/'],
    'Chart.js' => [
        'npm' => 'chart.js', 'fichier' => 'js/chart.umd.min.js', 'motif' => '/Chart\.js v([0-9.]+)/'],
    'flatpickr' => [
        'npm' => 'flatpickr', 'fichier' => 'js/flatpickr.min.js', 'motif' => '/flatpickr v([0-9.]+)/'],
    'SortableJS' => [
        'npm' => 'sortablejs', 'fichier' => 'js/sortable.lib.min.js', 'motif' => '/Sortable ([0-9.]+)/'],
    'Tom Select' => [
        'npm' => 'tom-select', 'fichier' => 'js/tom-select.complete.min.js', 'motif' => '/Tom Select v([0-9.]+)/'],
    'TinyMCE' => [
        'npm' => 'tinymce', 'fichier' => 'js/tinymce/tinymce.min.js', 'motif' => '/TinyMCE version ([0-9.]+)/'],
    'Font Awesome' => [
        'npm' => '@fortawesome/fontawesome-free', 'fichier' => 'css/icons/fontawesome.min.css',
        'motif' => '/Font Awesome Free ([0-9.]+)/'],
    'CodeMirror' => [
        'npm' => 'codemirror', 'fichier' => 'js/codemirror', 'motif' => '/^([0-9]+\.[0-9]+\.[0-9]+)$/',
        'gele' => 'la ligne 6 est une réécriture complète (autre API, un empaqueteur obligatoire) : c’est une décision de produit, pas une mise à jour'],
];

/** La version servie, lue dans le fichier (ou dans le nom du dossier, pour CodeMirror). */
function version_servie(string $racine, array $suivie): ?string
{
    $chemin = $racine.'/'.$suivie['fichier'];

    if (is_dir($chemin))
    {
        foreach (scandir($chemin) ?: [] as $entree)
        {
            if (preg_match($suivie['motif'], $entree, $m))
            {
                return $m[1];
            }
        }

        return NULL;
    }

    if (!is_file($chemin))
    {
        return NULL;
    }

    // L'en-tête suffit : la version est toujours dans les premières lignes.
    return preg_match($suivie['motif'], (string) file_get_contents($chemin, FALSE, NULL, 0, 4096), $m) ? $m[1] : NULL;
}

/** La dernière version publiée sur npm, ou NULL si le registre n'a pas répondu. */
function version_amont(string $paquet): ?string
{
    $contexte = stream_context_create(['http' => [
        'timeout' => 20,
        'header'  => "Accept: application/vnd.npm.install-v1+json\r\nUser-Agent: NeoFrag-Reborn-check-versions\r\n",
    ]]);

    $json = @file_get_contents('https://registry.npmjs.org/'.str_replace('/', '%2f', $paquet), FALSE, $contexte);

    if ($json === FALSE)
    {
        return NULL;
    }

    $lu = json_decode($json, TRUE);

    return is_array($lu) && isset($lu['dist-tags']['latest']) ? (string) $lu['dist-tags']['latest'] : NULL;
}

/** De combien est-on en arrière : '', 'correctif', 'mineure' ou 'MAJEURE'. */
function retard(string $servie, string $amont): string
{
    if (version_compare($servie, $amont, '>='))
    {
        return '';
    }

    $a = array_map('intval', array_pad(explode('.', $servie), 3, 0));
    $b = array_map('intval', array_pad(explode('.', $amont), 3, 0));

    if ($a[0] !== $b[0]) { return 'MAJEURE'; }
    if ($a[1] !== $b[1]) { return 'mineure'; }

    return 'correctif';
}

// ── Hors ligne : le cliquet de date ─────────────────────────────────────────
if (!$o['en-ligne'])
{
    $jours = (int) floor((time() - (int) strtotime(DERNIERE_CONFRONTATION)) / 86400);

    printf("%d bibliothèque(s) suivie(s) ; dernière confrontation à l'amont : %s (il y a %d jour(s)).\n\n",
        count(SUIVIES), DERNIERE_CONFRONTATION, $jours);

    if ($jours > JOURS_AVANT_DE_REGARDER)
    {
        nf_echec(sprintf(
            'les versions livrées n’ont pas été confrontées à l’amont depuis %d jours (plafond %d) : lancer '
            .'`php tools/check-versions-tierces.php --en-ligne`, mettre à jour ce qui est en retard, puis '
            .'`--en-ligne --ecrire`',
            $jours, JOURS_AVANT_DE_REGARDER));
    }

    nf_ok(sprintf('les versions livrées ont été confrontées à l’amont il y a %d jour(s)', $jours));
}

// ── En ligne : la confrontation ─────────────────────────────────────────────
$retards = $muets = 0;
$geles   = [];

printf("%-14s %-12s %-12s %s\n", 'bibliothèque', 'livrée', 'amont', 'écart');
printf("%s\n", str_repeat('─', 58));

foreach (SUIVIES as $nom => $suivie)
{
    $servie = version_servie($racine, $suivie);

    if ($servie === NULL)
    {
        printf("%-14s %-12s %-12s %s\n", $nom, '?', '—', 'version servie illisible dans '.$suivie['fichier']);
        $retards++;

        continue;
    }

    $amont = version_amont($suivie['npm']);

    if ($amont === NULL)
    {
        printf("%-14s %-12s %-12s %s\n", $nom, $servie, '?', 'le registre npm n’a pas répondu');
        $muets++;

        continue;
    }

    $ecart = retard($servie, $amont);

    if ($ecart === '')
    {
        printf("%-14s %-12s %-12s %s\n", $nom, $servie, $amont, 'à jour');

        continue;
    }

    if (isset($suivie['gele']))
    {
        printf("%-14s %-12s %-12s %s\n", $nom, $servie, $amont, 'GELÉ : '.$suivie['gele']);
        $geles[] = $nom;

        continue;
    }

    printf("%-14s %-12s %-12s %s\n", $nom, $servie, $amont, $ecart.' de retard');
    $retards++;
}

echo "\n";

if ($muets)
{
    nf_refus(sprintf('%d paquet(s) sans réponse du registre npm : rien n’est conclu, relancer plus tard', $muets));
}

if ($retards)
{
    nf_echec(sprintf('%d bibliothèque(s) en retard sur l’amont%s', $retards,
        $geles ? sprintf(' (%d gelée(s) à part, dite(s) ci-dessus)', count($geles)) : ''));
}

if ($o['ecrire'])
{
    $fichier = __FILE__;
    $source  = (string) file_get_contents($fichier);
    $neuf    = preg_replace(
        "/const DERNIERE_CONFRONTATION = '[0-9-]+';/",
        "const DERNIERE_CONFRONTATION = '".date('Y-m-d')."';",
        $source,
        1
    );

    if ($neuf !== NULL && $neuf !== $source)
    {
        file_put_contents($fichier, $neuf);
        printf("Date de confrontation inscrite : %s\n", date('Y-m-d'));
    }
}

nf_ok(sprintf('les %d bibliothèques suivies sont à jour%s', count(SUIVIES) - count($geles),
    $geles ? ' ('.implode(', ', $geles).' gelée(s), avec leur raison)' : ''));
