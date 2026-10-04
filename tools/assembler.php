<?php
declare(strict_types=1);

/**
 * assembler — pose les addons à la carte du dépôt extensions dans cet arbre, pour éprouver le produit entier.
 *
 * Famille : outil
 * Diffusion : publique
 *
 * Pourquoi
 * --------
 * Le projet vit dans deux dépôts : `neofrag` porte le cœur et les addons qu'installent les profils,
 * `extensions` les addons à la carte. Le paquet d'installation les réunit, et la documentation décrit
 * ce produit entier (« 62 modules »), comme la NOTICE dit qui a écrit chacun de ses addons. Les
 * contrôles qui en jugent (`check-docs`, `check-notice`) et la suite de tests ne jugent donc que
 * l'arbre ASSEMBLÉ : sans lui, ils refusent de se prononcer plutôt que de se tromper.
 *
 * L'outil copie chaque addon d'`extensions` à son chemin (`modules/<nom>`, `widgets/<nom>`,
 * `themes/<nom>`). Dans un clone git, il inscrit les dossiers posés dans `.git/info/exclude` : ils
 * n'apparaissent pas dans `git status`, et l'inscription sert de registre — un nouvel assemblage
 * remplace ce qu'il avait posé, `--retirer` l'enlève, et rien d'autre n'est jamais écrasé. On modifie
 * un addon à la carte dans le clone d'`extensions`, puis on assemble de nouveau.
 *
 * Usage
 * -----
 *   php tools/assembler.php --extensions=../extensions             pose (ou met à jour) les addons
 *   php tools/assembler.php --extensions=../extensions --retirer   les retire
 *
 * Codes retour : 0 fait · 1 un dossier existe déjà sans venir d'un assemblage · 2 mauvais usage.
 */

require __DIR__.'/lib/outil.php';
require __DIR__.'/lib/depot.php';

[$o] = nf_options(['extensions' => '', 'retirer' => FALSE]);

$source = $o['extensions'] !== '' ? realpath($o['extensions']) : FALSE;

if ($source === FALSE || !is_dir($source))
{
    nf_refus('usage : php tools/assembler.php --extensions=<clone du dépôt extensions> [--retirer]');
}

$racine = nf_racine();

if ($source === $racine)
{
    nf_refus('--extensions désigne cet arbre lui-même');
}

/** Les addons du dépôt extensions : un dossier `x/` qui contient `x.php`. */
$addons = [];

foreach (['modules', 'widgets', 'themes'] as $dossier)
{
    foreach (glob($source.'/'.$dossier.'/*', GLOB_ONLYDIR) ?: [] as $chemin)
    {
        if (is_file($chemin.'/'.basename($chemin).'.php'))
        {
            $addons[] = $dossier.'/'.basename($chemin);
        }
    }
}

if (!$addons)
{
    nf_refus("{$source} ne porte aucun addon (modules/, widgets/, themes/) : est-ce bien le dépôt extensions ?");
}

// ── Le registre : le bloc de `.git/info/exclude` qui nomme les dossiers posés ─────────────────────
const DEBUT = '# assembler : début (php tools/assembler.php --retirer)';
const FIN   = '# assembler : fin';

$exclude = is_dir($racine.'/.git') ? $racine.'/.git/info/exclude' : NULL;
$texte   = $exclude !== NULL ? (string) @file_get_contents($exclude) : '';
$poses   = [];

if (preg_match('/^'.preg_quote(DEBUT, '/').'\n(.*?)^'.preg_quote(FIN, '/').'\n?/ms', $texte, $m))
{
    foreach (array_filter(explode("\n", $m[1])) as $ligne)
    {
        $poses[] = trim($ligne, '/');
    }

    $texte = str_replace($m[0], '', $texte);
}

/** Copie un dossier et son contenu. */
function copier(string $de, string $vers): int
{
    $n = 0;

    foreach (nf_parcourir($de) as $relatif => $fichier)
    {
        @mkdir(dirname($vers.'/'.$relatif), 0775, TRUE);
        copy($fichier->getPathname(), $vers.'/'.$relatif);
        $n++;
    }

    return $n;
}

// Avant de toucher à quoi que ce soit : un dossier qui existe sans venir d'un assemblage arrête tout.
$occupes = $o['retirer'] ? [] : array_filter($addons, static fn (string $a): bool => file_exists($racine.'/'.$a) && !in_array($a, $poses, TRUE));

if ($occupes)
{
    nf_echec(sprintf("%d dossier(s) existent déjà sans venir d'un assemblage : %s.\n"
        ."  L'arbre porte-t-il déjà ces addons ? Rien n'a été écrasé.", count($occupes), implode(', ', $occupes)));
}

// ── Retirer ce qu'un assemblage a posé ──────────────────────────────────────────────────────────
foreach ($poses as $pose)
{
    if (is_dir($racine.'/'.$pose))
    {
        nf_supprimer($racine.'/'.$pose);
    }
}

if ($o['retirer'])
{
    if ($exclude !== NULL)
    {
        file_put_contents($exclude, $texte);
    }

    nf_ok(count($poses).' addon(s) retiré(s) de l’arbre');
}

// ── Poser ───────────────────────────────────────────────────────────────────────────────────────
$fichiers = 0;

foreach ($addons as $addon)
{
    $fichiers += copier($source.'/'.$addon, $racine.'/'.$addon);
}

if ($exclude !== NULL)
{
    $bloc = DEBUT."\n".implode("\n", array_map(static fn (string $a): string => '/'.$a.'/', $addons))."\n".FIN."\n";
    file_put_contents($exclude, rtrim($texte, "\n").($texte !== '' ? "\n" : '').$bloc);
}

$absentes = nf_extensions_absentes();

if ($absentes)
{
    nf_echec(sprintf("%d addon(s) posé(s), mais le catalogue en attend encore %d : %s.\n"
        ."  Le clone d'extensions et celui-ci sont-ils de la même version ?", count($addons), count($absentes), implode(', ', $absentes)));
}

nf_ok(sprintf('%d addon(s) à la carte posé(s) (%d fichiers)%s', count($addons), $fichiers,
    $exclude !== NULL ? ', inscrits dans .git/info/exclude' : ''));
