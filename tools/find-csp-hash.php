<?php
declare(strict_types=1);

/**
 * find-csp-hash — retrouve le script inline qui correspond à une empreinte CSP.
 *
 * Famille : outil
 * Diffusion : publique
 *
 * Pourquoi
 * --------
 * Quand un navigateur bloque un script inline, il affiche l'empreinte que la politique devrait
 * contenir pour l'autoriser — `sha256-iT2X9V…`. Cette empreinte identifie le script de façon
 * certaine : c'est le SHA-256 de son contenu exact, en base64. On disposait donc depuis le début de
 * la signature du coupable, et on cherchait pourtant son origine « par observation » — piste écrite,
 * puis démentie par relecture du code, deux fois. Cet outil ferme la question : il récupère des
 * pages, extrait chaque `<script>` sans attribut `src`, calcule son empreinte et la compare.
 *
 * Sans empreinte, l'outil liste toutes celles des pages données — utile pour comparer deux
 * chargements, ou pour alimenter une politique par empreintes.
 *
 * Usage
 * -----
 *   php tools/find-csp-hash.php <empreinte> <url> [url…]
 *   php tools/find-csp-hash.php sha256-iT2X9… http://127.0.0.1:8091/fr/admin
 *   php tools/find-csp-hash.php http://127.0.0.1:8091/fr        toutes les empreintes de la page
 */

require __DIR__.'/lib/outil.php';
require __DIR__.'/lib/serveur.php';

[, $arguments] = nf_options([]);

if (!$arguments)
{
    nf_refus('usage : php tools/find-csp-hash.php [empreinte] <url> [url…]');
}

$recherche = '';

if (preg_match('/^sha(?:256|384|512)-/', $arguments[0]))
{
    $recherche = array_shift($arguments);
}

if (!$arguments)
{
    nf_refus('aucune URL à examiner');
}

/** Empreinte CSP d'un contenu de script, telle que le navigateur la calcule. */
function empreinte_csp(string $contenu, string $algorithme = 'sha256'): string
{
    return $algorithme.'-'.base64_encode(hash($algorithme, $contenu, TRUE));
}

/** Tous les scripts INLINE d'un document, dans l'ordre : un script avec `src` est externe. */
function scripts_inline(string $html): array
{
    if (!preg_match_all('#<script\b([^>]*)>(.*?)</script\s*>#is', $html, $trouves, PREG_SET_ORDER))
    {
        return [];
    }

    $scripts = [];

    foreach ($trouves as $t)
    {
        if (!preg_match('/\bsrc\s*=/i', $t[1]))
        {
            $scripts[] = $t[2];
        }
    }

    return $scripts;
}

$total  = 0;
$trouve = FALSE;

foreach ($arguments as $url)
{
    $reponse = nf_http($url, ['suivre' => 0, 'timeout' => 20]);

    if ($reponse['code'] === 0)
    {
        printf("  %-60s INJOIGNABLE\n", $url);
        continue;
    }

    $scripts = scripts_inline($reponse['corps']);
    printf("\n%s — %d script(s) inline\n", $url, count($scripts));

    foreach ($scripts as $i => $contenu)
    {
        $total++;
        $empreinte = empreinte_csp($contenu);
        $extrait   = mb_strimwidth(trim((string) preg_replace('/\s+/', ' ', $contenu)), 0, 70, '…');

        if ($recherche !== '' && $empreinte !== $recherche)
        {
            continue;
        }

        $marque = ($recherche !== '' && $empreinte === $recherche) ? ' <<< CORRESPOND' : '';
        printf("  #%-2d %s%s\n      %s\n", $i + 1, $empreinte, $marque, $extrait);

        if ($marque)
        {
            $trouve = TRUE;
            echo "\n      --- contenu complet ---\n";

            foreach (explode("\n", $contenu) as $ligne)
            {
                echo '      '.$ligne."\n";
            }
        }
    }
}

echo "\n";

if ($recherche === '')
{
    nf_ok("{$total} script(s) inline au total");
}

if (!$trouve)
{
    nf_echec("aucun des {$total} scripts inline ne correspond à {$recherche} — le script bloqué est donc produit ailleurs : par un autre document (une iframe a sa propre politique), par du JavaScript exécuté après le chargement, ou par une extension du navigateur");
}

nf_ok("empreinte {$recherche} retrouvée");
