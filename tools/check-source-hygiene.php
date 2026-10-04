<?php
declare(strict_types=1);

/**
 * check-source-hygiene — aucun caractère invisible dans les sources : contrôle, BOM, espace insécable, `?>` dans un commentaire.
 *
 * Famille : statique
 * Diffusion : publique
 *
 * Pourquoi
 * --------
 * Un défaut de cette famille a réellement coûté du temps le 2026-09-15 : une expression régulière
 * écrite `/\b\d+\.\d+/` s'était retrouvée avec un vrai caractère BACKSPACE (0x08) à la place de
 * `\b`, à cause d'un échappement mangé par un outil d'édition. Le fichier passait `php -l`, le motif
 * ne correspondait simplement JAMAIS, et le symptôme — « aucun navigateur trouvé » alors qu'il
 * était installé — envoyait chercher le problème à l'opposé de sa cause.
 *
 * Quatre familles, toutes silencieuses à la relecture :
 *   - les caractères de CONTRÔLE hors tabulation, retour chariot et fin de ligne ;
 *   - un BOM UTF-8 en tête d'un `.php` : émis AVANT le `<?php`, donc avant tout en-tête HTTP ;
 *   - les ESPACES INVISIBLES (insécable, largeur nulle) dans du code : une erreur de syntaxe qu'on
 *     relit dix fois sans la voir ;
 *   - une FERMETURE DE BALISE PHP dans un commentaire de ligne d'un fichier tout en PHP : elle coupe
 *     le fichier en deux, et l'erreur tombe des dizaines de lignes plus bas, sur du code irréprochable.
 *
 * Usage
 * -----
 *   php tools/check-source-hygiene.php
 */

require __DIR__.'/lib/outil.php';
require __DIR__.'/lib/depot.php';

nf_options([]);

const DOSSIERS   = ['neofrag', 'modules', 'widgets', 'themes', 'addons', 'install', 'tools', 'tests', 'js', 'css', 'docs', 'migrations'];
const EXTENSIONS = ['php', 'js', 'css', 'scss', 'sql', 'md', 'yml', 'yaml', 'json', 'html', 'tpl'];

/** Ce qui est permis : tabulation (09), fin de ligne (0A), retour chariot (0D). */
const CONTROLE_OK = [0x09, 0x0A, 0x0D];

/** Espaces trompeurs : insécable, insécable étroite, largeur nulle, joiners, BOM en cours de texte. */
const INVISIBLES = [
    "\xC2\xA0"     => 'espace insécable (U+00A0)',
    "\xE2\x80\xAF" => 'espace insécable étroite (U+202F)',
    "\xE2\x80\x8B" => 'espace de largeur nulle (U+200B)',
    "\xE2\x80\x8C" => 'antiliant sans chasse (U+200C)',
    "\xE2\x80\x8D" => 'liant sans chasse (U+200D)',
    "\xEF\xBB\xBF" => 'BOM UTF-8 en cours de fichier (U+FEFF)',
];

$fichiers = 0;
$defauts  = [];

// Les minifiés sont écartés par nf_fichiers() : ce n'est pas notre code, et un minifieur y met
// légitimement des espaces insécables (constaté dans fullcalendar.min.js et bootstrap.min.css).
foreach (nf_fichiers(DOSSIERS, EXTENSIONS) as $rel => $chemin)
{
    $fichiers++;

    $extension = strtolower(pathinfo($rel, PATHINFO_EXTENSION));
    $contenu   = (string) file_get_contents($chemin);

    // ── BOM en tête d'un .php : il sort AVANT le <?php.
    if ($extension === 'php' && strncmp($contenu, "\xEF\xBB\xBF", 3) === 0)
    {
        $defauts[] = [$rel, 1, 'BOM UTF-8 en tête de fichier PHP (émis avant tout en-tête HTTP)'];
    }

    // ── Caractères de contrôle.
    $ligne = 1;

    for ($i = 0, $len = strlen($contenu); $i < $len; $i++)
    {
        $o = ord($contenu[$i]);

        if ($o === 0x0A)
        {
            $ligne++;
            continue;
        }

        if (($o < 0x20 || $o === 0x7F) && !in_array($o, CONTROLE_OK, TRUE))
        {
            $defauts[] = [$rel, $ligne, sprintf('caractère de contrôle 0x%02X — presque toujours un échappement mangé', $o)];
        }
    }

    /* ── Fermeture de balise PHP dans un commentaire de LIGNE.
          `?>` dans un `//` ou un `#` FERME la balise PHP : tout ce qui suit devient du HTML. On ne
          signale que le cas où AUCUN fragment HTML n'a encore été rencontré : le fichier est du PHP
          pur (convention PSR-12), et la fermeture y coupe tout ce qui suit. Dans un gabarit, la
          fermeture est le fonctionnement normal (`<?php if (...): // pourquoi ?>`). */
    if ($extension === 'php')
    {
        $html_vu   = FALSE;
        $precedent = NULL;

        foreach (token_get_all($contenu) as $t)
        {
            if (is_array($t) && $t[0] === T_INLINE_HTML && trim($t[1]) !== '')
            {
                $html_vu = TRUE;
            }

            if (!$html_vu && is_array($t) && $t[0] === T_CLOSE_TAG
                && is_array($precedent) && $precedent[0] === T_COMMENT
                && !str_contains($precedent[1], "\n")
                && (str_starts_with(ltrim($precedent[1]), '//') || str_starts_with(ltrim($precedent[1]), '#')))
            {
                $defauts[] = [$rel, $t[2], 'fermeture de balise PHP en fin de commentaire de ligne — elle coupe le fichier en deux'];
            }

            $precedent = $t;
        }
    }

    // ── Espaces invisibles, hors Markdown et JSON (où ils peuvent être du contenu légitime).
    if (!in_array($extension, ['md', 'json'], TRUE))
    {
        foreach (INVISIBLES as $octets => $quoi)
        {
            $pos = 0;

            while (($pos = strpos($contenu, $octets, $pos)) !== FALSE)
            {
                $defauts[] = [$rel, substr_count($contenu, "\n", 0, $pos) + 1, $quoi];
                $pos += strlen($octets);
            }
        }
    }
}

if (!$defauts)
{
    nf_ok("{$fichiers} fichiers, aucun caractère invisible");
}

foreach (array_slice($defauts, 0, 60) as [$rel, $ligne, $quoi])
{
    printf("  %s:%d — %s\n", $rel, $ligne, $quoi);
}

if (count($defauts) > 60)
{
    printf("  … et %d autre(s).\n", count($defauts) - 60);
}

echo "\nCes caractères passent php -l et la relecture, mais changent le comportement.\n";

nf_echec(count($defauts).' caractère(s) invisible(s) dans les sources');
