<?php
declare(strict_types=1);

/**
 * check-js-sources — les sources JavaScript : syntaxe (`node --check`) et vocabulaire (aucun jQuery).
 *
 * Famille : statique
 * Diffusion : publique
 *
 * Pourquoi
 * --------
 * Le projet a soixante-quinze fichiers JS et RIEN ne vérifiait leur syntaxe : une parenthèse
 * manquante ne se découvrait qu'en ouvrant la page concernée. Ce qui avait sans doute découragé
 * un contrôle : une partie de ces fichiers contient du PHP interpolé — `url('admin/ajax/...')`,
 * `$this->lang('…')` — que `node --check` refuse en bloc. La parade : remplacer chaque bloc
 * `<?php … ?>` par un IDENTIFIANT NU avant de vérifier (pas une chaîne : le PHP apparaît tantôt
 * comme valeur, tantôt DÉJÀ entre guillemets JS, et un identifiant reste valide dans les deux cas).
 *
 * Puis le vocabulaire. jQuery n'est plus chargé depuis juin 2026 ; trois fichiers l'appelaient
 * encore le 2026-09-17 et levaient « $ is not defined » au chargement, avant d'attacher le moindre
 * écouteur — le tri des tables de toute l'administration était mort depuis trois mois. `$(…)` est
 * syntaxiquement valide : il fallait une règle de VOCABULAIRE. Dans un projet sans jQuery, `$(`,
 * `$.` et `jQuery` sont des erreurs.
 *
 * Périmètre : nos fichiers, jamais les bibliothèques minifiées d'autrui (cf. NF_EXCLUS), moins une
 * liste blanche de bibliothèques tierces livrées non minifiées qui se GARDENT elles-mêmes.
 *
 * Usage
 * -----
 *   php tools/check-js-sources.php
 *   NF_NODE=/chemin/node php tools/check-js-sources.php
 */

require __DIR__.'/lib/outil.php';
require __DIR__.'/lib/depot.php';

nf_options([]);

/** Bibliothèques tierces livrées non minifiées : elles mentionnent jQuery pour s'y brancher SI présent. */
const TIERS = ['modules/gallery/js/dropzone.js'];

/** node : NF_NODE, puis le PATH. */
function trouver_node(): ?string
{
    if (($env = getenv('NF_NODE')) && is_file($env))
    {
        return $env;
    }

    $sonde = stripos(PHP_OS, 'WIN') === 0 ? 'where node 2>NUL' : 'command -v node 2>/dev/null';

    foreach (explode("\n", (string) @shell_exec($sonde)) as $ligne)
    {
        if (($ligne = trim($ligne)) !== '' && is_file($ligne))
        {
            return $ligne;
        }
    }

    return NULL;
}

$node = trouver_node() ?? nf_refus('node introuvable — renseigner NF_NODE, ou installer Node.js');
$salle = nf_temp('salle-'.bin2hex(random_bytes(4)));
@mkdir($salle, 0775, TRUE);
register_shutdown_function(static function () use ($salle): void { exec('rm -rf '.escapeshellarg($salle)); });

$fichiers = nf_fichiers(NF_DOSSIERS_JS, ['js']);
$syntaxe  = [];
$jquery   = [];
$avec_php = 0;

foreach ($fichiers as $rel => $chemin)
{
    $source = (string) file_get_contents($chemin);

    // ── 1. Syntaxe : chaque bloc PHP devient un identifiant nu, puis `node --check` ──────────
    $nettoye = preg_replace('/<\?php.*?\?>|<\?=.*?\?>|<\?.*?\?>/s', 'NF_PHP', $source, -1, $n);

    if ($n > 0)
    {
        $avec_php++;
    }

    $tmp = $salle.'/'.str_replace('/', '__', $rel);
    file_put_contents($tmp, $nettoye);

    exec(escapeshellarg($node).' --check '.escapeshellarg($tmp).' 2>&1', $sortie, $code);

    if ($code !== 0)
    {
        $syntaxe[$rel] = str_replace($tmp, $rel, implode("\n", array_slice($sortie, 0, 6)));
    }

    $sortie = [];

    // ── 2. Vocabulaire : on ne juge que le CODE, blocs PHP et commentaires retirés ───────────
    if (in_array($rel, TIERS, TRUE))
    {
        continue;
    }

    $code = preg_replace_callback('/<\?php.*?\?>|<\?=.*?\?>|\/\*.*?\*\//s', static fn (array $m): string => str_repeat("\n", substr_count($m[0], "\n")), $source);
    $code = (string) preg_replace('~^\s*//.*$~m', '', (string) $code);

    foreach (explode("\n", $code) as $i => $ligne)
    {
        // `$(`, `$.x` (hors `${…}` des gabarits) et `jQuery` suivi d'un appel ou d'un accès.
        if (preg_match('/(?<![\w$])\$\s*\(|(?<![\w$])\$\.\w|\bjQuery\s*[.(]/', $ligne))
        {
            $jquery[] = sprintf('%s:%d: %s', $rel, $i + 1, trim($ligne));
        }
    }
}

printf("%d fichiers JS vérifiés (%d contiennent du PHP interpolé).\n", count($fichiers), $avec_php);

if ($syntaxe)
{
    echo "\nERREURS DE SYNTAXE :\n\n";

    foreach ($syntaxe as $rel => $msg)
    {
        echo "  ✗ {$rel}\n";

        foreach (explode("\n", $msg) as $l)
        {
            if (trim($l) !== '')
            {
                echo '      '.$l."\n";
            }
        }
    }
}

if ($jquery)
{
    echo "\nAPPELS jQUERY — la bibliothèque n'est plus chargée, ces fichiers lèvent « \$ is not defined » au chargement :\n\n";

    foreach ($jquery as $t)
    {
        echo '   '.$t."\n";
    }

    echo "\nRéécrire en vanilla sur les primitives du cœur (NF.ready, NF.ajax, NF.post, NF.data, NF.setHtml).\n";
    echo "Une bibliothèque tierce non minifiée qui se garde elle-même s'ajoute à TIERS, en tête de cet outil.\n";
}

if ($syntaxe || $jquery)
{
    nf_echec(sprintf('%d fichier(s) avec une erreur de syntaxe, %d appel(s) jQuery', count($syntaxe), count($jquery)));
}

nf_ok('aucune erreur de syntaxe, aucun appel jQuery');
