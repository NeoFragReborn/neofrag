<?php
declare(strict_types=1);

/**
 * fill-langs — écrit les traductions qui manquent : celles que le produit possède déjà, puis celles d'un dictionnaire.
 *
 * Famille : outil
 * Diffusion : publique
 *
 * Pourquoi
 * --------
 * La clé d'une traduction est l'empreinte CRC32 du TEXTE SOURCE FRANÇAIS. Deux addons qui écrivent
 * « Aucune catégorie. » portent donc la même clé, et la traduction allemande de l'un vaut mot pour
 * mot pour l'autre. Un addon neuf arrive pourtant avec ses six fichiers vides : `check-langs --fix`
 * remplit le français, et les cinq autres langues restent à écrire — dont les trois quarts existent
 * déjà, à l'identique, ailleurs dans le dépôt.
 *
 * 1. REPRISE. Pour chaque clé présente dans le fichier FRANÇAIS d'un addon et absente d'une autre
 *    langue, il cherche cette clé dans tous les autres fichiers de cette langue. S'il la trouve, et
 *    que le texte source français y est bien le même, il la recopie.
 *
 * 2. DICTIONNAIRE (`--dictionnaire=fichier.json`). Ce que le produit ne sait pas encore dire se
 *    traduit une fois, dans un fichier `{"texte français": {"en": "…", "de": "…", "es": "…", "it": "…",
 *    "pt": "…"}}`, et l'outil l'écrit dans CHAQUE addon dont le français porte ce texte. Une
 *    traduction qui perd une forme du pluriel (`a|b`), un `%s` ou une balise est REFUSÉE, et dite :
 *    elle casserait `sprintf` ou l'affichage. Un texte de DONNÉE, traduit à l'affichage et donc
 *    absent de tout fichier français, déclare où il vit : `"_addons": ["modules/access"]`.
 *
 * Ce qui reste manquant est listé : c'est précisément la liste courte à traduire. Rien n'est
 * réécrit : les lignes neuves sont insérées en fin de fichier (cf. `tools/lib/langues.php`).
 *
 * La marche complète pour un texte écrit en dur (`check-textes-en-dur`) :
 *   1. l'écrire `$this->lang('…')` dans le code ;
 *   2. `php tools/check-langs.php --fix` ajoute la clé française ;
 *   3. `php tools/fill-langs.php --tous --dictionnaire=…` écrit les cinq autres langues ;
 *   4. `php tools/check-langs.php --toutes` confirme.
 *
 * Usage
 * -----
 *   php tools/fill-langs.php modules/quotes                          remplit un addon
 *   php tools/fill-langs.php --tous                                  tous les addons du dépôt
 *   php tools/fill-langs.php --tous --dictionnaire=traductions.json  et ce que dit le dictionnaire
 *   php tools/fill-langs.php --tous --pretend                        montre ce qu'il ferait, sans écrire
 */

require __DIR__.'/lib/outil.php';
require __DIR__.'/lib/depot.php';
require __DIR__.'/lib/langues.php';

[$o, $cibles] = nf_options(['pretend' => FALSE, 'tous' => FALSE, 'dictionnaire' => [], 'detail' => FALSE]);

/** Les langues traduites ; le français est la SOURCE, il n'est jamais écrit par cet outil. */
const LANGUES = ['en', 'de', 'es', 'it', 'pt'];

// ── Le dictionnaire, vérifié entrée par entrée ────────────────────────────────
/** @var array<string, array<string, string>> $dictionnaire  clé => langue => traduction */
$dictionnaire = [];
$refus        = [];
$du_dico      = [];   // clé => texte français, pour dire ce qui n'a servi nulle part

foreach ($o['dictionnaire'] as $chemin)
{
    $entrees = is_file($chemin) ? json_decode((string) file_get_contents($chemin), TRUE) : NULL;

    if (!is_array($entrees))
    {
        nf_refus("dictionnaire illisible : {$chemin} — un objet JSON {\"texte français\": {\"en\": \"…\", …}} est attendu");
    }

    foreach ($entrees as $francais => $traductions)
    {
        $francais = (string) $francais;
        $cle      = nf_langue_cle($francais);

        // Un texte de DONNÉE traduit à l'affichage (`$this->lang($role['title'])`) n'est dans aucun
        // fichier français : `check-langs --fix` ne voit que les littéraux. L'entrée dit alors où il
        // vit — `"_addons": ["modules/access"]` — et sa clé française y est d'abord écrite.
        foreach ((array) ($traductions['_addons'] ?? []) as $addon)
        {
            $francais_addon = nf_racine().'/'.trim((string) $addon, '/').'/langs/fr.php';

            if (!isset(nf_langue_cles($francais_addon)[$cle]) && !$o['pretend'] && !nf_langue_ajouter($francais_addon, [$cle => $francais]))
            {
                nf_echec("écriture impossible : {$addon}/langs/fr.php");
            }
        }

        foreach (LANGUES as $langue)
        {
            $traduction = is_array($traductions) ? ($traductions[$langue] ?? NULL) : NULL;

            if (!is_string($traduction) || trim($traduction) === '')
            {
                $refus[] = sprintf('« %s » : pas de traduction « %s »', mb_strimwidth($francais, 0, 60, '…'), $langue);
                continue;
            }

            if (substr_count($traduction, '|') !== substr_count($francais, '|'))
            {
                $refus[] = sprintf('« %s » → %s « %s » : pas le même nombre de formes du pluriel', mb_strimwidth($francais, 0, 60, '…'), $langue, $traduction);
                continue;
            }

            if (nf_langue_jokers($traduction) !== nf_langue_jokers($francais))
            {
                $refus[] = sprintf('« %s » → %s « %s » : les %%s, {0} ou balises ne sont pas les mêmes', mb_strimwidth($francais, 0, 60, '…'), $langue, $traduction);
                continue;
            }

            $dictionnaire[$cle][$langue] = $traduction;
        }

        $du_dico[$cle] = $francais;
    }
}

// ── Le répertoire de ce que le produit sait déjà dire ────────────────────────
$dossiers = [];

foreach (['neofrag/langs', 'modules/*/langs', 'widgets/*/langs', 'themes/*/langs', 'addons/*/langs', 'install/langs'] as $motif)
{
    foreach (glob(nf_racine().'/'.$motif, GLOB_ONLYDIR) ?: [] as $dossier)
    {
        $dossiers[] = $dossier;
    }
}

sort($dossiers);

/** @var array<string, array<string, string>> $connu  langue => clé => valeur */
$connu = array_fill_keys(LANGUES, []);
/** @var array<string, string> $sources  clé => texte français */
$sources = [];

foreach ($dossiers as $dossier)
{
    foreach (nf_langue_valeurs($dossier.'/fr.php') as $cle => $valeur)
    {
        // Une clé est une empreinte du texte source : deux textes différents ne peuvent pas la
        // partager. Si cela arrivait, c'est qu'un fichier est faux — on garde le premier vu.
        $sources[$cle] ??= $valeur;
    }

    foreach (LANGUES as $langue)
    {
        foreach (nf_langue_valeurs($dossier.'/'.$langue.'.php') as $cle => $valeur)
        {
            $connu[$langue][$cle] ??= $valeur;
        }
    }
}

// ── Les addons à remplir ──────────────────────────────────────────────────────
$cibles = array_map(static fn (string $c): string => rtrim(str_replace('\\', '/', $c), '/'), $cibles);

if ($o['tous'])
{
    foreach ($dossiers as $dossier)
    {
        $cibles[] = nf_relatif(dirname($dossier));
    }
}

if (!$cibles)
{
    nf_refus("dire quel addon remplir : php tools/fill-langs.php <chemin/de/l/addon>, ou --tous");
}

$repris    = 0;
$traduits  = 0;
$manquants = [];
$traites   = 0;
$servis    = [];

foreach (array_unique($cibles) as $cible)
{
    $dossier = nf_racine().'/'.$cible.'/langs';

    if (!is_dir($dossier))
    {
        printf("  ? %s : pas de dossier langs/\n", $cible);
        continue;
    }

    $source = nf_langue_valeurs($dossier.'/fr.php');

    if (!$source)
    {
        continue;
    }

    $traites++;

    foreach (LANGUES as $langue)
    {
        $presentes = nf_langue_cles($dossier.'/'.$langue.'.php');
        $ajouts    = [];
        $du_depot  = 0;

        foreach ($source as $cle => $francais)
        {
            if (isset($presentes[$cle]))
            {
                continue;
            }

            // La reprise n'a de sens que si le texte source est bien le même : la clé le garantit,
            // on le vérifie tout de même, car une clé fausse dans un fichier ancien contaminerait.
            if (isset($connu[$langue][$cle]) && ($sources[$cle] ?? NULL) === $francais)
            {
                $ajouts[$cle] = $connu[$langue][$cle];
                $du_depot++;
            }
            else if (isset($dictionnaire[$cle][$langue]))
            {
                $ajouts[$cle]  = $dictionnaire[$cle][$langue];
                $servis[$cle]  = TRUE;
            }
            else
            {
                $manquants[$cible.' / '.$langue][] = $francais;
            }
        }

        if (!$ajouts)
        {
            continue;
        }

        if (!$o['pretend'] && !nf_langue_ajouter($dossier.'/'.$langue.'.php', $ajouts))
        {
            nf_echec("écriture impossible : {$cible}/langs/{$langue}.php (pas de crochet fermant ?)");
        }

        $repris   += $du_depot;
        $traduits += count($ajouts) - $du_depot;

        if ($o['detail'] || $o['pretend'])
        {
            printf("  %s %-34s %s : %d reprise(s), %d du dictionnaire\n", $o['pretend'] ? '·' : '✓', $cible, $langue, $du_depot, count($ajouts) - $du_depot);
        }
    }
}

echo "\n";

if ($manquants)
{
    echo "À TRADUIRE — ni le produit ni le dictionnaire ne connaissent ces textes :\n\n";

    foreach ($manquants as $ou => $textes)
    {
        printf("  %s (%d)\n", $ou, count($textes));

        foreach (array_unique($textes) as $texte)
        {
            printf("      %s\n", mb_strimwidth($texte, 0, 90, '…'));
        }

        echo "\n";
    }
}

if ($inutiles = array_diff_key($du_dico, $sources))
{
    echo "Entrées du dictionnaire qu'AUCUN fichier français ne porte — le texte a-t-il été ajouté par `check-langs --fix` ?\n\n";

    foreach ($inutiles as $texte)
    {
        printf("      %s\n", mb_strimwidth($texte, 0, 90, '…'));
    }

    echo "\n";
}

if ($refus)
{
    echo "Traductions REFUSÉES :\n\n";

    foreach ($refus as $r)
    {
        echo "  ✗ {$r}\n";
    }

    echo "\n";
    nf_echec(sprintf('%d traduction(s) refusée(s) — %d reprise(s), %d écrite(s) du dictionnaire', count($refus), $repris, $traduits));
}

nf_ok(sprintf('%d traduction(s) reprise(s), %d écrite(s) du dictionnaire, dans %d addon(s)%s', $repris, $traduits, $traites, $o['pretend'] ? ' (rien écrit : --pretend)' : ''));
