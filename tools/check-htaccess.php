<?php
declare(strict_types=1);

/**
 * check-htaccess — les trois configurations livrées (Apache, nginx, Caddy) refusent les mêmes dossiers et fichiers sensibles.
 *
 * Famille : statique
 * Diffusion : publique
 *
 * Pourquoi
 * --------
 * Le produit vise d'abord les hébergements **mutualisés**, qui tournent sous Apache et n'ont que les
 * `.htaccess` du paquet ; il livre aussi un exemple pour **nginx** et un pour **Caddy**. Personne
 * n'exerce ces fichiers au quotidien : ils ne sont vérifiés par aucune requête, sur aucune machine.
 *
 * Ils ont donc divergé, et dans le mauvais sens. Mesuré le 2026-09-21 : le `.htaccess` racine ne
 * refusait ni `.neon` ni `.md`, et cinq dossiers n'avaient aucune garde — `cache`, `install`, `tools`,
 * `tests`, `docs`. Conséquence la plus concrète : sur toute installation Apache, la documentation de
 * travail se lisait en texte. Le 2026-10-04, l'exemple nginx était encore celui d'origine (il laissait
 * `install/`, `tools/`, `tests/` et `docs/` joignables), et le `Caddyfile` une ancienne copie de notre
 * configuration de production.
 *
 * Ce contrôle lit LA liste (`tools/lib/interdits.php`) et vérifie que les trois configurations la refusent toute :
 * les gardes des dossiers et le `.htaccess` racine pour Apache, la ligne `@interdit` du `Caddyfile`,
 * les blocs `deny all` de `nginx.conf` — et leur copie dans le guide de déploiement (`deploy-ftp.md`,
 * pour nginx sur Plesk), qui ne refusait que trois dossiers sur huit.
 *
 * Ce qu'il ne fait pas
 * --------------------
 * Il ne lance ni Apache, ni nginx, ni Caddy : il vérifie que les RÈGLES sont écrites, pas qu'un serveur
 * réel les applique — un `AllowOverride None` chez un hébergeur rend les `.htaccess` inopérants, quoi
 * qu'ils contiennent. C'est `check-serveur-web` qui les éprouve, sur un vrai serveur.
 *
 * Usage
 * -----
 *   php tools/check-htaccess.php
 *   php tools/check-htaccess.php --verbeux
 */

require __DIR__.'/lib/outil.php';
require __DIR__.'/lib/interdits.php';

[$o]     = nf_options(['verbeux' => FALSE]);
$racine  = nf_racine();
$verbeux = $o['verbeux'];

/** Un fichier `.htaccess` refuse-t-il tout accès ? */
function refuse_tout(string $chemin): bool
{
    if (!is_file($chemin))
    {
        return FALSE;
    }

    $contenu = (string) file_get_contents($chemin);

    // Les deux écritures d'Apache, selon la version : `Require all denied` (2.4) et
    // `Deny from all` (2.2). Une seule suffit si l'autre est dans un `<IfModule>` complémentaire.
    return (bool) preg_match('/^\s*Require\s+all\s+denied\s*$/mi', $contenu)
        || (bool) preg_match('/^\s*Deny\s+from\s+all\s*$/mi', $contenu);
}

$manques = [];

$vu = static function (string $fichier, string $quoi) use ($verbeux): void {
    if ($verbeux)
    {
        printf("  ok    %-22s %s\n", $fichier, $quoi);
    }
};

// ── Apache : les gardes des dossiers ──────────────────────────────────────────
foreach (NF_DOSSIERS_INTERDITS as $dossier => $pourquoi)
{
    $chemin = $racine.'/'.$dossier;

    if (!is_dir($chemin))
    {
        // Un dossier absent du dépôt n'est pas un manque : il naît à l'usage.
        continue;
    }

    // `upload` est le seul à devoir rester SERVI : on y interdit l'exécution, pas la lecture.
    if ($dossier === 'upload')
    {
        $contenu = is_file($chemin.'/.htaccess') ? (string) file_get_contents($chemin.'/.htaccess') : '';

        if (!preg_match('/php_flag\s+engine\s+off|RemoveHandler|SetHandler|php_admin_flag\s+engine\s+off|Require\s+all\s+denied/i', $contenu))
        {
            $manques[] = ['upload/.htaccess', "n'empêche pas l'exécution de ce qui y est déposé", $pourquoi];
        }
        else
        {
            $vu('upload/.htaccess', 'exécution neutralisée');
        }

        continue;
    }

    if (!refuse_tout($chemin.'/.htaccess'))
    {
        $manques[] = [$dossier.'/.htaccess', is_file($chemin.'/.htaccess') ? 'ne refuse pas tout accès' : 'absent', $pourquoi];
    }
    else
    {
        $vu($dossier.'/.htaccess', 'refuse tout accès');
    }
}

// ── Apache : le .htaccess racine ──────────────────────────────────────────────
$contenu = (string) @file_get_contents($racine.'/.htaccess');

if ($contenu === '')
{
    $manques[] = ['.htaccess', 'absent à la racine', 'aucune extension sensible refusée'];
}
else
{
    foreach (NF_EXTENSIONS_INTERDITES as $extension)
    {
        // On cherche l'extension dans un `<FilesMatch>` qui refuse. Le motif reste large : il
        // s'agit de repérer un OUBLI, pas de valider une expression régulière d'Apache.
        if (!preg_match('/<FilesMatch[^>]*\b'.preg_quote($extension, '/').'\b/i', $contenu))
        {
            $manques[] = ['.htaccess', 'ne refuse pas les fichiers `.'.$extension.'`', 'refusé par les autres configurations'];
        }
        else
        {
            $vu('.htaccess', '.'.$extension.' refusé');
        }
    }

    foreach (NF_FICHIERS_INTERDITS as $fichier)
    {
        // Un `<Files>` ou un `<FilesMatch>` qui nomme le fichier : là encore, on repère un OUBLI.
        if (!preg_match('/<Files(?:Match)?[^>]*'.preg_quote(str_replace('.', '\\.', $fichier), '/').'/i', $contenu))
        {
            $manques[] = ['.htaccess', 'ne refuse pas `'.$fichier.'`', 'refusé par les autres configurations'];
        }
        else
        {
            $vu('.htaccess', $fichier.' refusé');
        }
    }
}

// ── Caddy : la ligne @interdit ────────────────────────────────────────────────
$caddy    = (string) @file_get_contents($racine.'/Caddyfile');
$interdit = preg_match('/^\s*@interdit\s+path\s+(.+)$/m', $caddy, $m) ? preg_split('/\s+/', trim($m[1])) ?: [] : [];

if (!$interdit)
{
    $manques[] = ['Caddyfile', 'aucune ligne « @interdit path … »', 'rien de sensible refusé sous Caddy'];
}
else
{
    $attendus = array_merge(
        array_map(static fn (string $d): string => '/'.$d.'/*', array_keys(array_diff_key(NF_DOSSIERS_INTERDITS, ['upload' => TRUE]))),
        array_map(static fn (string $e): string => '*.'.$e, NF_EXTENSIONS_INTERDITES),
        array_map(static fn (string $f): string => '/'.$f, NF_FICHIERS_INTERDITS)
    );

    foreach ($attendus as $motif)
    {
        if (!in_array($motif, $interdit, TRUE))
        {
            $manques[] = ['Caddyfile', "@interdit ne refuse pas `{$motif}`", 'refusé par les autres configurations'];
        }
        else
        {
            $vu('Caddyfile', $motif.' refusé');
        }
    }
}

// ── nginx : les blocs deny all, dans l'exemple et dans sa copie du guide de déploiement ──────
/** Les noms qu'un `location ~ ^/(a|b)…` ou `location ~* \.(a|b)$` refuse par `deny all`. */
function nginx_refuse(string $contenu, string $motif): array
{
    preg_match_all($motif, $contenu, $blocs);

    $noms = [];

    foreach ($blocs[1] as $alternatives)
    {
        foreach (explode('|', $alternatives) as $nom)
        {
            $noms[] = str_replace('\\.', '.', $nom);
        }
    }

    return $noms;
}

// Le guide de déploiement recopie ces règles pour les hébergements nginx (Plesk) : jusqu'au
// 2026-10-04, sa copie ne refusait que trois dossiers sur huit.
foreach (['nginx.conf', 'docs/deploy-ftp.md'] as $fichier)
{
    $contenu = (string) @file_get_contents($racine.'/'.$fichier);

    if ($contenu === '')
    {
        $manques[] = [$fichier, 'absent', 'rien de sensible refusé sous nginx'];
        continue;
    }

    $dossiers   = nginx_refuse($contenu, '/location\s+~\s+\^\/\(([^)]+)\)\/\s*\{\s*deny\s+all;/');
    $extensions = nginx_refuse($contenu, '/location\s+~\*\s+\\\\\.\(([^)]+)\)\$\s*\{\s*deny\s+all;/');
    $fichiers   = nginx_refuse($contenu, '/location\s+~\s+\^\/\(([^)]+)\)\$\s*\{\s*deny\s+all;/');

    foreach (array_keys(array_diff_key(NF_DOSSIERS_INTERDITS, ['upload' => TRUE])) as $dossier)
    {
        in_array($dossier, $dossiers, TRUE) ? $vu($fichier, $dossier.'/ refusé') : $manques[] = [$fichier, "ne refuse pas `/{$dossier}/`", NF_DOSSIERS_INTERDITS[$dossier]];
    }

    foreach (NF_EXTENSIONS_INTERDITES as $extension)
    {
        in_array($extension, $extensions, TRUE) ? $vu($fichier, '.'.$extension.' refusé') : $manques[] = [$fichier, "ne refuse pas les fichiers `.{$extension}`", 'refusé par les autres configurations'];
    }

    foreach (NF_FICHIERS_INTERDITS as $interdit)
    {
        in_array($interdit, $fichiers, TRUE) ? $vu($fichier, $interdit.' refusé') : $manques[] = [$fichier, "ne refuse pas `{$interdit}`", 'refusé par les autres configurations'];
    }
}

// ── Le verdict ──────────────────────────────────────────────────────────────
printf("%d dossier(s), %d extension(s) et %d fichier(s) surveillés, dans 3 configurations et le guide de déploiement.\n\n",
    count(NF_DOSSIERS_INTERDITS), count(NF_EXTENSIONS_INTERDITES), count(NF_FICHIERS_INTERDITS));

if (!$manques)
{
    nf_ok('Apache, nginx et Caddy refusent la même liste');
}

echo "CE QU'UNE CONFIGURATION LAISSE PASSER ET QUE LES AUTRES REFUSENT :\n\n";

foreach ($manques as [$fichier, $quoi, $pourquoi])
{
    printf("  %-22s %s\n      → %s\n", $fichier, $quoi, $pourquoi);
}

echo "\nCes fichiers ne sont exercés par personne au quotidien : ils valent pour les hébergements où le\n";
echo "produit s'installe, et c'est précisément là que l'écart ne se voit pas.\n";

nf_echec(count($manques).' règle(s) manquante(s) ou incomplète(s)');
