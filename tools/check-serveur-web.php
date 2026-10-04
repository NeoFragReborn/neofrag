<?php
declare(strict_types=1);

/**
 * check-serveur-web — un vrai serveur web (Apache, nginx, Caddy) refuse ce qu'il doit refuser, et sert le site comme il faut.
 *
 * Famille : cible
 * Diffusion : publique
 *
 * Pourquoi
 * --------
 * Le produit livre trois configurations : les `.htaccess` pour Apache, `nginx.conf` et `Caddyfile`.
 * `check-htaccess` vérifie que leurs RÈGLES nomment la même liste (`tools/lib/interdits.php`) ; il ne
 * prouve pas qu'un serveur les applique. Une règle bien écrite peut ne rien faire (un `AllowOverride
 * None`), ou faire plus que prévu : sous Apache, `RewriteRule index\.php - [L]` laissait passer TOUT
 * chemin contenant « index.php », et `/modules/news/controllers/index.php` s'exécutait directement
 * (relevé le 2026-10-04, en écrivant ce contrôle).
 *
 * Celui-ci interroge le site servi, en posant des SONDES dans son arbre — un fichier marqué d'un
 * jeton tiré au hasard —, puis en les demandant :
 *   1. chaque dossier interdit refuse sa sonde (`config/`, `logs/`, `install/`…) ;
 *   2. `upload/` sert un fichier texte, mais n'exécute jamais un script ;
 *   3. chaque extension interdite (`.sql`, `.md`…) et chaque fichier interdit par son nom
 *      (`package.json`, `Caddyfile`…) est refusé ;
 *   4. aucun script PHP autre que `index.php` à la racine ne s'exécute — ni ailleurs dans l'arbre, ni
 *      un `index.php` de sous-dossier ;
 *   5. le site se sert : l'accueil, une page, `robots.txt` et le plan du site réécrits vers le produit,
 *      une feuille de style et un script passés par le site, une image et TinyMCE servis tels quels ;
 *   6. les en-têtes de sécurité sont posés, la politique de contenu comprise.
 * Les sondes sont retirées à la fin, quoi qu'il arrive.
 *
 * Il lui faut un site INSTALLÉ (le routage se juge sur des pages qui existent), l'adresse où il est
 * servi, et le dossier qu'il sert — ou le dépôt lui-même par défaut.
 *
 * Usage
 * -----
 *   php tools/check-serveur-web.php --url=http://localhost:8080
 *   php tools/check-serveur-web.php --url=http://localhost:8080 --racine=/var/www/neofrag
 */

require __DIR__.'/lib/outil.php';
require __DIR__.'/lib/serveur.php';
require __DIR__.'/lib/interdits.php';

[$o] = nf_options(['url' => '', 'racine' => '']);

$base   = rtrim($o['url'], '/');
$racine = rtrim(str_replace('\\', '/', (string) realpath($o['racine'] !== '' ? $o['racine'] : nf_racine())), '/');

if ($base === '' || !preg_match('#^https?://#', $base))
{
    nf_refus('--url=<adresse du site servi> : http://localhost:8080 par exemple');
}

if ($racine === '' || !is_file($racine.'/index.php'))
{
    nf_refus('--racine ne désigne pas un site NeoFrag Reborn : '.$o['racine']);
}

// La configuration suffit : le verrou `install/db.txt`, le site le pose lui-même à sa première visite
// (une installation par `ci-install` ne l'écrit pas).
if (!is_file($racine.'/config/db.php'))
{
    nf_refus("le site servi n'est pas installé ({$racine}) : le routage se juge sur des pages qui existent — php tools/ci-install.php");
}

// ── Les sondes ────────────────────────────────────────────────────────────────
$jeton  = bin2hex(random_bytes(6));
$poses  = [];
$crees  = [];

register_shutdown_function(static function () use (&$poses, &$crees): void {
    foreach ($poses as $fichier)
    {
        @unlink($fichier);
    }

    foreach (array_reverse($crees) as $dossier)
    {
        @rmdir($dossier);
    }
});

/** Pose un fichier dans l'arbre servi (et ses dossiers s'il le faut) ; rend son adresse. */
function sonder(string $relatif, string $contenu): string
{
    global $racine, $poses, $crees;

    $chemin  = $racine.'/'.$relatif;
    $dossier = dirname($chemin);
    $manque  = [];

    while (!is_dir($dossier))
    {
        $manque[] = $dossier;
        $dossier  = dirname($dossier);
    }

    foreach (array_reverse($manque) as $d)
    {
        if (@mkdir($d, 0775))
        {
            $crees[] = $d;
        }
    }

    if (is_file($chemin))
    {
        nf_refus("la sonde écraserait un fichier existant : {$relatif}");
    }

    if (@file_put_contents($chemin, $contenu) === FALSE)
    {
        nf_refus("impossible de poser la sonde {$relatif} — l'outil doit pouvoir écrire dans le dossier servi");
    }

    @chmod($chemin, 0644);
    $poses[] = $chemin;

    return '/'.$relatif;
}

$echecs = 0;

function juger(string $titre, bool $ok, string $detail = ''): void
{
    global $echecs;

    $echecs += $ok ? 0 : 1;
    printf("  %-6s %-60s %s\n", $ok ? 'OK' : 'ÉCHEC', $titre, $detail);
}

function demander(string $chemin): array
{
    global $base;

    return nf_http($base.$chemin, ['suivre' => 0, 'timeout' => 30]);
}

/** Ce que la réponse contient vraiment — son titre et son début —, pour diagnostiquer un échec d'un coup d'œil. */
function apercu(array $r): string
{
    $titre = preg_match('#<title>([^<]*)</title>#i', $r['corps'], $t) ? ' « '.trim(html_entity_decode($t[1])).' »' : '';
    $texte = trim((string) preg_replace('/\s+/u', ' ', strip_tags($r['corps'])));

    return $titre.' : '.($texte !== '' ? mb_strimwidth($texte, 0, 140, '…') : '(vide)');
}

$texte  = 'nf-sonde '.$jeton;
// Le script ÉCRIT un jeton qu'il ne contient pas tel quel : le lire dans la réponse prouve qu'il a été
// EXÉCUTÉ, pas seulement servi en texte.
$script = "<?php echo 'NF-EXEC-'.'{$jeton}';\n";
$execute = 'NF-EXEC-'.$jeton;

printf("Serveur web éprouvé : %s (dossier servi : %s)\n", $base, $racine);

// 1. Les dossiers interdits
echo "\nDossiers interdits :\n";

foreach (NF_DOSSIERS_INTERDITS as $dossier => $pourquoi)
{
    if ($dossier === 'upload')
    {
        continue;
    }

    $r = demander(sonder($dossier.'/nf-sonde-'.$jeton.'.txt', $texte));
    juger("/{$dossier}/ est refusé", !str_contains($r['corps'], $texte) && $r['code'] !== 200, 'HTTP '.$r['code']);
}

// 2. upload/ : servi, jamais exécuté
echo "\nupload/ :\n";
$r = demander(sonder('upload/nf-sonde-'.$jeton.'.txt', $texte));
juger('un fichier déposé se sert', $r['code'] === 200 && str_contains($r['corps'], $texte), 'HTTP '.$r['code']);
$r = demander(sonder('upload/nf-sonde-'.$jeton.'.php', $script));
juger('un script déposé ne s\'exécute pas', !str_contains($r['corps'], $execute), 'HTTP '.$r['code']);

// 3. Les extensions et les fichiers interdits
echo "\nExtensions et fichiers interdits :\n";
$fuites = [];

foreach (NF_EXTENSIONS_INTERDITES as $extension)
{
    $r = demander(sonder('nf-sonde-'.$jeton.'.'.$extension, $texte));

    if (str_contains($r['corps'], $texte))
    {
        $fuites[] = '.'.$extension.' (HTTP '.$r['code'].')';
    }
}

juger('chaque extension interdite est refusée', !$fuites, $fuites ? 'servies : '.implode(', ', $fuites) : implode(' ', NF_EXTENSIONS_INTERDITES));

$fuites = [];

foreach (NF_FICHIERS_INTERDITS as $fichier)
{
    // Un fichier présent est demandé tel quel : on cherche sa première ligne dans la réponse. Absent,
    // une sonde prend sa place le temps de la demande.
    if (is_file($racine.'/'.$fichier))
    {
        $signature = trim((string) strtok((string) file_get_contents($racine.'/'.$fichier), "\n"));
        $r         = demander('/'.$fichier);
        $fuite     = $signature !== '' && str_contains($r['corps'], $signature);
    }
    else
    {
        $r     = demander(sonder($fichier, $texte));
        $fuite = str_contains($r['corps'], $texte);
    }

    if ($fuite)
    {
        $fuites[] = $fichier.' (HTTP '.$r['code'].')';
    }
}

juger('chaque fichier interdit par son nom est refusé', !$fuites, $fuites ? 'servis : '.implode(', ', $fuites) : count(NF_FICHIERS_INTERDITS).' fichiers');

// 4. Aucun autre script ne s'exécute
echo "\nScripts PHP :\n";

foreach (['nf-sonde-'.$jeton.'.php' => 'à la racine', 'modules/nf-sonde-'.$jeton.'.php' => 'dans modules/',
    'modules/nf-sonde-'.$jeton.'/index.php' => 'un index.php de sous-dossier', 'vendor/nf-sonde-'.$jeton.'/index.php' => 'un index.php dans vendor/'] as $sonde => $ou)
{
    $r = demander(sonder($sonde, $script));
    juger("aucun script ne s'exécute ({$ou})", !str_contains($r['corps'], $execute), 'HTTP '.$r['code']);
}

// 5. Le site se sert
echo "\nLe site :\n";
$r = demander('/');
juger('l\'accueil répond', in_array($r['code'], [200, 301, 302], TRUE), 'HTTP '.$r['code'].(isset($r['entetes']['location']) ? ' → '.$r['entetes']['location'] : ''));
// Un site installé qui ne se reconnaît pas comme tel sert l'assistant d'installation à chaque adresse :
// tout répond 200, et rien de ce qui suit ne voudrait plus rien dire.
juger('le site se sert lui-même, et non l\'assistant d\'installation', !str_contains($r['corps'], 'data-nf-assistant'),
    str_contains($r['corps'], 'data-nf-assistant') ? 'l\'assistant s\'affiche : le site ne se reconnaît pas installé (voir son journal, « [install] »)' : '');
$page = demander('/fr');
juger('une page du site se rend (réécriture vers index.php)', $page['code'] === 200 && stripos($page['corps'], '</html>') !== FALSE, 'HTTP '.$page['code'].apercu($page));
$r = demander('/fr/contact');
juger('une page de module se rend', $r['code'] === 200, 'HTTP '.$r['code']);
$r = demander('/robots.txt');
$ok = $r['code'] === 200 && stripos($r['corps'], 'User-agent') !== FALSE;
juger('robots.txt est rendu par le produit', $ok, 'HTTP '.$r['code'].($ok ? '' : apercu($r)));
$r = demander('/fr/sitemap.xml');
$ok = $r['code'] === 200 && (str_contains($r['corps'], '<urlset') || str_contains($r['corps'], '<sitemapindex'));
juger('le plan du site est rendu par le produit', $ok, 'HTTP '.$r['code'].($ok ? '' : apercu($r)));

/** La première adresse de la page, servie par CE site, qui finit par l'extension donnée. */
function adresse_locale(string $html, string $attribut, string $extension): string
{
    global $base;

    preg_match_all('#'.$attribut.'="([^"]+\.'.$extension.'(?:\?[^"]*)?)"#', $html, $liens);

    foreach ($liens[1] as $lien)
    {
        $lien = html_entity_decode($lien, ENT_QUOTES, 'UTF-8');

        if (str_starts_with($lien, $base.'/') || (str_starts_with($lien, '/') && !str_starts_with($lien, '//')))
        {
            return (string) preg_replace('#^https?://[^/]+#', '', $lien);
        }
    }

    return '';
}

// Une feuille et un script que la page elle-même demande : ils passent par le site (?v=…).
foreach (['la feuille de style' => [adresse_locale($page['corps'], 'href', 'css'), 'text/css'],
    'le script' => [adresse_locale($page['corps'], 'src', 'js'), 'javascript']] as $quoi => [$chemin, $type])
{
    $r = $chemin !== '' ? demander($chemin) : ['code' => 0, 'entetes' => []];
    juger("{$quoi} de la page se sert", $r['code'] === 200 && str_contains($r['entetes']['content-type'] ?? '', $type),
        $chemin !== '' ? 'HTTP '.$r['code'].' '.($r['entetes']['content-type'] ?? '—') : 'aucune dans la page');
}

$images = glob($racine.'/images/*.{png,jpg,svg,webp}', GLOB_BRACE) ?: [];

if ($images)
{
    $r = demander('/images/'.rawurlencode(basename($images[0])));
    juger('une image se sert telle quelle', $r['code'] === 200 && str_starts_with($r['entetes']['content-type'] ?? '', 'image/'), 'HTTP '.$r['code'].' '.basename($images[0]));
}

if (is_file($racine.'/js/tinymce/tinymce.min.js'))
{
    $r = demander('/js/tinymce/tinymce.min.js');
    juger('TinyMCE se sert tel quel, sans passer par le site', $r['code'] === 200 && strlen($r['corps']) === filesize($racine.'/js/tinymce/tinymce.min.js'), 'HTTP '.$r['code']);
}

// 6. Les en-têtes de sécurité
echo "\nEn-têtes de sécurité (sur /fr) :\n";
$attendus = [
    'x-content-type-options'  => 'nosniff',
    'x-frame-options'         => 'SAMEORIGIN',
    'referrer-policy'         => 'strict-origin-when-cross-origin',
    'permissions-policy'      => 'geolocation=()',
    'content-security-policy' => "nonce-",
];
$manquants = [];

foreach ($attendus as $entete => $valeur)
{
    if (!str_contains($page['entetes'][$entete] ?? '', $valeur))
    {
        $manquants[] = $entete;
    }
}

juger('chaque en-tête de sécurité est posé', !$manquants, $manquants ? 'absents : '.implode(', ', $manquants) : count($attendus).' en-têtes');

echo "\n";

if ($echecs)
{
    nf_echec("{$echecs} vérification(s) en échec — la configuration de ce serveur ne tient pas ce que le produit promet");
}

nf_ok('le serveur refuse ce qu\'il doit refuser, et sert le site comme il faut');
