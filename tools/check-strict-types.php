<?php
declare(strict_types=1);

/**
 * check-strict-types — le nombre de fichiers en `declare(strict_types=1)` ne baisse jamais (cliquet).
 *
 * Famille : statique
 * Diffusion : publique
 *
 * Pourquoi
 * --------
 * La vague `strict_types` est terminée sur tout le périmètre utile depuis le 2026-09-21 — 1 365
 * fichiers, contre 78 la veille. Elle a mis au jour onze coercitions réelles, dont trois sur des
 * chemins empruntés à chaque page (`strnatcmp()` sur l'ordre des langues à l'initialisation de la
 * session : 500 sur tout le site dès qu'il a deux langues actives ; `crypt::hash()` passant un
 * flottant à `str_split()`, la méthode qui fabrique le jeton CSRF de toute page à formulaire).
 *
 * Ce contrôle garde l'acquis : retirer un `declare` fait baisser le compte, et le compte ne doit
 * jamais baisser. Un fichier neuf le porte (CONTRIBUTING) et monte le cliquet d'autant.
 *
 * Hors du compte, et pourquoi : les gabarits `views/**.tpl.php`. Un `declare` y est possible, mais
 * ces fichiers ne font qu'assembler du HTML ; le monter ferait grimper le cliquet sans rien durcir.
 *
 * Usage
 * -----
 *   php tools/check-strict-types.php
 */

require __DIR__.'/lib/outil.php';
require __DIR__.'/lib/depot.php';

nf_options([]);

/** Le cliquet : à monter à chaque fichier converti, jamais à baisser. */
const MINIMUM = 1365;

$count = 0;

foreach (nf_fichiers(['neofrag', 'modules', 'widgets', 'addons'], ['php'], [], FALSE) as $chemin)
{
    if (str_contains((string) file_get_contents($chemin), 'declare(strict_types=1)'))
    {
        $count++;
    }
}

if ($count < MINIMUM)
{
    nf_echec("régression strict_types : {$count} fichiers < minimum ".MINIMUM." — ne jamais retirer declare(strict_types=1) ; à l'ajout, monter MINIMUM dans tools/check-strict-types.php");
}

nf_ok("{$count} fichiers stricts (minimum requis ".MINIMUM.')');
