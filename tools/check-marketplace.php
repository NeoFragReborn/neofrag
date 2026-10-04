<?php
declare(strict_types=1);

/**
 * check-marketplace — le catalogue publié dit-il la vérité sur les archives qu'il propose ?
 *
 * Famille : cible
 * Diffusion : publique
 *
 * Il juge des archives FABRIQUÉES : un clone du dépôt n'en a pas (elles ne sont pas versionnées), et
 * il se joue après `php tools/package-addons.php`, ou contre une installation qui les sert. Classé
 * « statique » jusqu'au 2026-10-04, il rendait `check-all` rouge sur tout clone neuf — 63 « écarts »
 * qui n'étaient que l'absence des archives (trouvé par check-nouveau-venu).
 *
 * Pourquoi
 * --------
 * `package-addons` zippe les addons et écrit `marketplace/catalog.json`. Entre cette génération et
 * ce qui est SERVI, tout peut diverger : une archive régénérée sans le catalogue, un catalogue
 * copié sans les archives, un addon dont la version a changé dans le code sans être repackagé, un
 * aperçu annoncé mais absent. Rien ne casse bruyamment — le visiteur télécharge simplement une
 * archive périmée, ou clique sur un lien mort.
 *
 * Ce contrôle compare TROIS sources qui doivent s'accorder :
 *
 *   1. le CODE sur le disque   — la version que l'addon déclare dans son `__info()` ;
 *   2. les ARCHIVES            — leur présence, leur taille, leur empreinte SHA-256, leur structure ;
 *   3. le CATALOGUE            — ce qu'il annonce de chacune.
 *
 * Il vérifie en plus ce dont dépend l'INSTALLATION par ZIP : l'archive doit contenir un unique
 * dossier racine portant le nom de l'addon, et dedans son fichier principal `<nom>.php`. C'est la
 * structure qu'attend « Admin → Thèmes & Addons → Ajouter » ; une archive plate s'installe dans un
 * dossier au mauvais nom et l'addon reste introuvable.
 *
 * Et ce dont dépend la MISE À JOUR : une version lisible et comparable, et l'empreinte qui permet
 * de vérifier ce qu'on télécharge.
 *
 * L'archive est-elle À JOUR ?
 * --------------------------
 * Comparer les VERSIONS ne suffit pas : une correction qui ne change pas le numéro laisse une
 * archive périmée que rien ne distingue. Le contrôle compare donc, fichier par fichier, l'empreinte
 * CRC-32 que l'archive porte déjà dans son index — sans rien extraire — à celle du fichier sur le
 * disque. C'est ainsi qu'on voit qu'un addon corrigé n'a pas été repackagé.
 *
 * Les deux conditions de l'installation par dépôt d'archive
 * ---------------------------------------------------------
 * L'installeur de « Ajouter » lit le fichier principal de l'addon et exige DEUX choses pour le
 * reconnaître : un `use NF\NeoFrag\Addons\(Module|Widget|Theme);`, qui lui donne le type, et un
 * `depends['neofrag']` non vide dans `__info()`, faute de quoi il passe son chemin — SANS RIEN DIRE,
 * ni notification ni journal. Le widget `awards` était dans ce cas le 2026-09-22 : seul des 61
 * addons distribuables à ne pas déclarer sa dépendance au cœur, et donc seul à ne pas s'installer
 * par archive. Rien ne l'aurait signalé.
 *
 * Usage
 * -----
 *   php tools/check-marketplace.php                      le catalogue du dépôt
 *   php tools/check-marketplace.php /var/www/neofrag     celui d'une installation servie
 */

require __DIR__.'/lib/outil.php';
require __DIR__.'/lib/depot.php';

[$o, $reste] = nf_options(['verbeux' => FALSE]);

$racine = isset($reste[0]) ? rtrim($reste[0], '/') : nf_racine();
$dossier = $racine.'/marketplace';
$json    = $dossier.'/catalog.json';

if (!is_file($json))
{
    nf_refus("catalogue introuvable : $json — le générer avec php tools/package-addons.php");
}

$catalogue = json_decode((string) file_get_contents($json), TRUE);

if (!is_array($catalogue) || !isset($catalogue['addons']) || !is_array($catalogue['addons']))
{
    nf_refus("catalogue illisible : $json");
}

$entrees = $catalogue['addons'];

printf("%s\n%d entrée(s), schéma %s, base %s, généré le %s.\n\n",
    $json,
    count($entrees),
    (string) ($catalogue['schema'] ?? '?'),
    (string) ($catalogue['base_version'] ?? '?'),
    (string) ($catalogue['generated_at'] ?? '?'));

/** Le dossier source d'un type d'addon. */
const DOSSIERS = ['module' => 'modules', 'widget' => 'widgets', 'theme' => 'themes', 'authenticator' => 'addons'];

/**
 * La version déclarée par l'addon dans son `__info()`, ou NULL.
 *
 * On lit le BLOC `__info()` et lui seul : ailleurs dans le fichier, `'version' => '…'` peut désigner
 * tout autre chose. C'est la même prudence que dans `package-addons`, et pour la même raison.
 */
function version_declaree(string $fichier): ?string
{
    if (!is_file($fichier))
    {
        return NULL;
    }

    $source = (string) file_get_contents($fichier);
    $debut  = strpos($source, 'function __info');

    if ($debut === FALSE)
    {
        return NULL;
    }

    $bloc = substr($source, $debut);

    if (preg_match('/\n\t+(?:public|protected|private|static|final|abstract)[^\n]*function\s/', $bloc, $m, PREG_OFFSET_CAPTURE))
    {
        $bloc = substr($bloc, 0, (int) $m[0][1]);
    }

    return preg_match('/[\'"]version[\'"]\s*=>\s*(?:\$this->lang\(\s*)?[\'"]([^\'"]+)[\'"]/', $bloc, $t) ? $t[1] : NULL;
}

/**
 * Ce que l'installeur de « Ajouter » cherche dans le fichier principal d'un addon.
 *
 * @return array{use: bool, depends: bool}
 */
function conditions_installation(string $fichier): array
{
    if (!is_file($fichier))
    {
        return ['use' => FALSE, 'depends' => FALSE];
    }

    $source = (string) file_get_contents($fichier);
    $debut  = strpos($source, 'function __info');
    $bloc   = $debut === FALSE ? '' : substr($source, $debut);

    if ($bloc !== '' && preg_match('/\n\t+(?:public|protected|private|static|final|abstract)[^\n]*function\s/', $bloc, $m, PREG_OFFSET_CAPTURE))
    {
        $bloc = substr($bloc, 0, (int) $m[0][1]);
    }

    return [
        'use'     => (bool) preg_match('/use NF\\\\NeoFrag\\\\Addons\\\\(Module|Widget|Theme);/', $source),
        'depends' => (bool) preg_match('/[\'"]neofrag[\'"]\s*=>\s*[\'"][^\'"]+[\'"]/', $bloc),
    ];
}

/**
 * Ce que contient l'archive, et si elle correspond au dossier source.
 *
 * La comparaison se fait sur le CRC-32 que l'archive porte deja dans son index : rien n'est extrait,
 * et un fichier corrige mais non repackage se voit immediatement.
 *
 * @return array{racine: string, principal: bool, vignette: bool, fichiers: int, perimes: list<string>, absents: list<string>}|NULL
 */
function contenu_zip(string $chemin, string $nom, string $source): ?array
{
    $zip = new ZipArchive();

    if ($zip->open($chemin) !== TRUE)
    {
        return NULL;
    }

    $racines   = [];
    $principal = FALSE;
    $vignette  = FALSE;
    $perimes   = [];
    $vus       = [];

    for ($i = 0; $i < $zip->numFiles; $i++)
    {
        $stat   = $zip->statIndex($i);
        $entree = (string) ($stat['name'] ?? '');
        $parts  = explode('/', $entree, 2);
        $racines[$parts[0]] = TRUE;

        if ($entree === $nom.'/'.$nom.'.php')
        {
            $principal = TRUE;
        }

        if ($entree === $nom.'/images/thumbnail.jpg')
        {
            $vignette = TRUE;
        }

        // Le chemin DANS l'archive, prive de sa racine, donne le chemin sur le disque.
        $relatif = $parts[1] ?? '';

        if ($relatif === '' || str_ends_with($entree, '/'))
        {
            continue;
        }

        $vus[$relatif] = TRUE;
        $sur_disque    = $source.'/'.$relatif;

        if (!is_file($sur_disque))
        {
            $perimes[] = $relatif.' (dans l’archive, absent du dépôt)';
            continue;
        }

        if (crc32((string) file_get_contents($sur_disque)) !== (int) ($stat['crc'] ?? -1))
        {
            $perimes[] = $relatif;
        }
    }

    $nombre = $zip->numFiles;
    $zip->close();

    // Et l'inverse : un fichier ajoute au depot mais absent de l'archive.
    $absents = [];

    foreach (nf_parcourir($source) as $relatif => $fichier)
    {
        // Les cartes de source sont regenerees a l'execution et ne sont ni versionnees ni
        // packagees : leur absence de l'archive est normale, leur presence serait le defaut.
        if (strtolower($fichier->getExtension()) === 'map')
        {
            continue;
        }

        if (!isset($vus[$relatif]))
        {
            $absents[] = $relatif;
        }
    }

    return [
        'racine'    => count($racines) === 1 ? (string) array_key_first($racines) : '(' .count($racines).' racines)',
        'principal' => $principal,
        'vignette'  => $vignette,
        'fichiers'  => $nombre,
        'perimes'   => $perimes,
        'absents'   => $absents,
    ];
}

$defauts   = [];
$attendus  = [];
$avec_vignette = 0;

foreach ($entrees as $e)
{
    $type = (string) ($e['type'] ?? '');
    $nom  = (string) ($e['name'] ?? '');
    $cle  = $type.':'.$nom;

    $zip = $dossier.'/'.((string) ($e['file'] ?? ''));
    $attendus[] = realpath($zip) ?: $zip;

    // ── 1. L'archive existe et n'est pas vide ────────────────────────────────
    if (!is_file($zip))
    {
        $defauts[] = [$cle, 'archive absente : '.((string) ($e['file'] ?? '(aucun chemin)'))];
        continue;
    }

    $taille = (int) filesize($zip);

    if ($taille === 0)
    {
        $defauts[] = [$cle, 'archive vide'];
        continue;
    }

    // ── 2. Taille et empreinte annoncées ─────────────────────────────────────
    if ((int) ($e['size'] ?? 0) !== $taille)
    {
        $defauts[] = [$cle, sprintf('taille annoncée %d, réelle %d — catalogue et archive ne viennent pas du même passage',
            (int) ($e['size'] ?? 0), $taille)];
    }

    $empreinte = (string) hash_file('sha256', $zip);

    if (($e['sha256'] ?? '') !== $empreinte)
    {
        $defauts[] = [$cle, 'empreinte SHA-256 différente de celle annoncée — l’archive a changé depuis la génération'];
    }

    // ── 3. Structure attendue par l'installation ZIP ─────────────────────────
    $source_dir = $racine.'/'.(DOSSIERS[$type] ?? 'modules').'/'.$nom;
    $contenu    = contenu_zip($zip, $nom, $source_dir);

    if ($contenu === NULL)
    {
        $defauts[] = [$cle, 'archive illisible'];
        continue;
    }

    if ($contenu['racine'] !== $nom)
    {
        $defauts[] = [$cle, 'racine de l’archive « '.$contenu['racine'].' » au lieu de « '.$nom.' » : l’installation par ZIP créerait un dossier au mauvais nom'];
    }

    if (!$contenu['principal'])
    {
        $defauts[] = [$cle, 'l’archive ne contient pas '.$nom.'/'.$nom.'.php : l’addon serait introuvable après installation'];
    }

    // ── 3 bis. L'archive est-elle A JOUR ? ───────────────────────────────────
    if ($contenu['perimes'])
    {
        $defauts[] = [$cle, sprintf('archive PÉRIMÉE : %d fichier(s) diffèrent du dépôt (%s%s) — repackager',
            count($contenu['perimes']),
            implode(', ', array_slice($contenu['perimes'], 0, 3)),
            count($contenu['perimes']) > 3 ? ', …' : '')];
    }

    if ($contenu['absents'])
    {
        $defauts[] = [$cle, sprintf('%d fichier(s) du dépôt manquent à l’archive (%s%s) — repackager',
            count($contenu['absents']),
            implode(', ', array_slice($contenu['absents'], 0, 3)),
            count($contenu['absents']) > 3 ? ', …' : '')];
    }

    // ── 4. La version du CODE et celle du catalogue ──────────────────────────
    $source  = $racine.'/'.(DOSSIERS[$type] ?? 'modules').'/'.$nom.'/'.$nom.'.php';
    $version = version_declaree($source);

    if ($version === NULL)
    {
        $defauts[] = [$cle, 'version introuvable dans '.$nom.'.php'];
    }
    elseif ($version !== (string) ($e['version'] ?? ''))
    {
        $defauts[] = [$cle, sprintf('le code déclare %s, le catalogue annonce %s — repackager', $version, (string) ($e['version'] ?? '?'))];
    }

    // ── 5. Les deux conditions de l'installation par dépôt d'archive ─────────
    $conditions = conditions_installation($source);

    if (!$conditions['use'])
    {
        $defauts[] = [$cle, 'pas de « use NF\\NeoFrag\\Addons\\… » dans '.$nom.'.php : l’installeur ne reconnaîtrait pas son type'];
    }

    if (!$conditions['depends'])
    {
        $defauts[] = [$cle, 'pas de depends[neofrag] dans __info() : l’installation par archive passerait son chemin, EN SILENCE'];
    }

    // ── 6. L'aperçu annoncé existe ───────────────────────────────────────────
    $apercu = (string) ($e['preview'] ?? '');

    if ($apercu !== '')
    {
        $fichier = $dossier.'/'.$apercu;

        if (!is_file($fichier))
        {
            $defauts[] = [$cle, 'aperçu annoncé mais absent : '.$apercu];
        }
        elseif (@getimagesize($fichier) === FALSE)
        {
            $defauts[] = [$cle, 'aperçu illisible : '.$apercu];
        }
        else
        {
            $avec_vignette++;
            $attendus[] = realpath($fichier) ?: $fichier;
        }
    }
}

// ── 7. Les fichiers EN TROP : une archive que le catalogue ne propose pas ────
// Elle serait téléchargeable par adresse directe, sans jamais avoir été annoncée ni vérifiée.
$orphelins = [];

foreach (glob($dossier.'/*/*') ?: [] as $fichier)
{
    if (!in_array(realpath($fichier) ?: $fichier, $attendus, TRUE))
    {
        $orphelins[] = substr($fichier, strlen($dossier) + 1);
    }
}

printf("%d archive(s) vérifiée(s), %d aperçu(s) présent(s).\n", count($entrees), $avec_vignette);

if ($orphelins)
{
    printf("\n%d fichier(s) dans marketplace/ que le catalogue n'annonce pas :\n", count($orphelins));

    foreach (array_slice($orphelins, 0, 20) as $f)
    {
        echo '  ', $f, "\n";
    }

    echo "\nIls restent téléchargeables par adresse directe sans avoir été annoncés. Régénérer avec\n";
    echo "php tools/package-addons.php, ou les retirer.\n";
}

if ($defauts)
{
    echo "\nÉCARTS :\n\n";

    foreach ($defauts as [$cle, $raison])
    {
        printf("  %-22s %s\n", $cle, $raison);
    }

    echo "\n";
    nf_echec(sprintf('%d écart(s) entre le catalogue, les archives et le code', count($defauts)));
}

if ($orphelins)
{
    nf_echec(count($orphelins).' fichier(s) publiés sans être annoncés');
}

nf_ok(sprintf('les %d archives correspondent au catalogue et au code, et s’installent par ZIP', count($entrees)));
