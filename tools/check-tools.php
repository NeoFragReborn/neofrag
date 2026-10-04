<?php
declare(strict_types=1);

/**
 * check-tools — les outils de `tools/` respectent leurs propres conventions.
 *
 * Famille : statique
 * Diffusion : publique
 *
 * Pourquoi
 * --------
 * Le 2026-09-21, `tools/` comptait soixante fichiers écrits sur quatre mois, chacun à sa manière :
 * deux styles d'indentation, trois formes de garde HTTP (dont trois absentes), quatorze copies du
 * lancement d'un serveur, deux formats de verdict, des ports par défaut en collision. Rien ne
 * l'empêchait, donc chaque passe de rangement était à refaire à la suivante. Ce contrôle est ce qui
 * empêche : les conventions sont écrites dans `tools/README.md`, et il les fait respecter.
 *
 * Ce qu'il vérifie
 * ----------------
 *   1. l'EN-TÊTE : `declare(strict_types=1)`, un bloc de documentation qui commence par
 *      `<nom> — …`, porte `Usage` et `Famille : statique|navigateur|cible|outil`, puis
 *      `require __DIR__.'/lib/outil.php'` comme première instruction — c'est elle qui garde ;
 *      un fichier de la bibliothèque se présente aussi par `<nom> — …` ;
 *   2. l'INDENTATION : quatre espaces, jamais de tabulation en tête de ligne (cf. `.editorconfig`) ;
 *   3. la PLOMBERIE : aucun outil n'ouvre lui-même une base, un serveur, un navigateur ni une
 *      session — cela vit dans `tools/lib/`, une fois ;
 *   4. le VERDICT : un contrôle `check-*` conclut par `nf_ok()`, et refuse par `nf_echec()` ou
 *      `nf_refus()` ;
 *   5. les PORTS : tout outil qui sert le site a son port réservé dans `NF_PORTS` ;
 *   6. le CATALOGUE et la BIBLIOTHÈQUE : `tools/README.md` liste chaque outil et chaque fichier de
 *      `tools/lib/`, et rien d'autre — ses deux tables sont produites depuis les en-têtes
 *      (`--catalogue`), et doivent être à jour (`--ecrire` les met à jour). La table de la
 *      bibliothèque était écrite à la main : le 2026-10-04, trois fichiers sur dix-huit y manquaient ;
 *   7. la SORTIE : jamais d'`exit("message")` ni de `die("message")` — une chaîne passée à `exit`
 *      s'affiche et rend le code ZÉRO ; la CI a enchaîné sur un refus ainsi masqué. On refuse par
 *      `nf_refus()` ou `nf_echec()` ;
 *   8. la CI : tout contrôle de la famille `statique` est joué par `.github/workflows/ci.yml`, sauf
 *      exception nommée avec sa raison (`NON_JOUES_EN_CI`). Cinq contrôles créés le 2026-10-02
 *      n'étaient joués par personne : la CI restait verte sans les avoir lus ;
 *   9. la DIFFUSION : tout fichier de `tools/` déclare s'il part dans le dépôt public —
 *      `Diffusion : publique` — ou s'il reste chez nous — `Diffusion : interne — <raison>` ;
 *  10. la CLOISON : un fichier public ne cite jamais un fichier interne, ni dans son code ni dans
 *      ses commentaires, et la prose de `tools/README.md` non plus : la copie publique n'aura pas
 *      ce fichier, le renvoi y serait mort. On nomme le besoin, pas l'outil ;
 *  11. le catalogue range les fichiers INTERNES à part, avec leur raison : la copie publique le
 *      réécrit sans eux.
 *
 * Usage
 * -----
 *   php tools/check-tools.php                toutes les règles, code 1 s'il y a un écart
 *   php tools/check-tools.php --catalogue    imprime les tables engendrées du README (catalogue, bibliothèque)
 *   php tools/check-tools.php --ecrire       réécrit ces tables dans tools/README.md
 */

require __DIR__.'/lib/outil.php';
require __DIR__.'/lib/depot.php';
require __DIR__.'/lib/entetes.php';

[$o] = nf_options(['catalogue' => FALSE, 'ecrire' => FALSE]);

/** Ce qui n'a pas à exister hors de `tools/lib/` (motifs), et le nom de ce qui le remplace. */
const PLOMBERIE = [
    '/new mysqli\b/'                       => 'nf_connexion() ou nf_connexion_admin()',
    '/\bmysqli_connect\(/'                 => 'nf_connexion()',
    '/-S 127\.0\.0\.1/'                    => 'nf_serveur()',
    '/--headless/'                         => 'nf_chrome_dom() / nf_chrome_capture()',
    '/INSERT INTO nf_session/'             => 'nf_session_admin()',
    '/(require|include)[^;\n]*config\/db\.php/' => 'nf_config_db()',
    '/RecursiveDirectoryIterator/'         => 'nf_fichiers() / nf_parcourir()',
    '/\bPHP_SAPI\b/'                       => "require __DIR__.'/lib/outil.php' (la garde y est)",
];

/** Les outils : tout `tools/*.php` sauf le routeur du serveur intégré, qui n'est pas un outil. */
$outils = [];

foreach (glob(nf_racine().'/tools/*.php') ?: [] as $chemin)
{
    if (basename($chemin) !== 'router-builtin.php')
    {
        $outils[basename($chemin, '.php')] = $chemin;
    }
}

ksort($outils);

/** Tout fichier de `tools/` et de `tools/lib/`, ce README excepté : chacun déclare sa diffusion. */
$fichiers = [];

foreach (array_merge(glob(nf_racine().'/tools/*') ?: [], glob(nf_racine().'/tools/lib/*') ?: []) as $chemin)
{
    if (is_file($chemin) && basename($chemin) !== 'README.md')
    {
        $fichiers[nf_relatif($chemin)] = $chemin;
    }
}

ksort($fichiers);

$entetes   = [];
$anomalies = [];

foreach ($outils as $nom => $chemin)
{
    $entetes[$nom] = $e = nf_entete_outil($nom, $chemin);

    foreach ($e['erreurs'] as $erreur)
    {
        $anomalies[] = [$nom, $erreur];
    }
}

// ── Règle 9 : chaque fichier dit s'il part dans le dépôt public ──────────────────────────────
$diffusions = [];

foreach ($fichiers as $relatif => $chemin)
{
    $diffusions[$relatif] = $d = nf_diffusion($chemin);

    if ($d['valeur'] === NULL)
    {
        $anomalies[] = [$relatif, 'aucune ligne `Diffusion : publique` ou `Diffusion : interne — <raison>` dans ses premières lignes'];
    }
    elseif ($d['valeur'] === 'interne' && $d['raison'] === '')
    {
        $anomalies[] = [$relatif, 'diffusion interne sans sa raison : `Diffusion : interne — <ce qui le retient chez nous>`'];
    }
}

$internes = array_filter($diffusions, static fn (array $d): bool => $d['valeur'] === 'interne');

// ── Règle 1, pour la bibliothèque : chaque fichier se présente par `<nom> — <résumé>` ──────────
$bibliotheque = [];

foreach ($fichiers as $relatif => $chemin)
{
    if (!str_starts_with($relatif, 'tools/lib/'))
    {
        continue;
    }

    $resume = nf_resume($chemin);

    if ($resume === NULL || $resume['nom'] !== basename($relatif, '.php'))
    {
        $anomalies[] = [$relatif, sprintf('sa documentation doit commencer par `%s — <ce qu\'il donne>`', basename($relatif, '.php'))];
    }
    elseif (!isset($internes[$relatif]))
    {
        $bibliotheque[$relatif] = $resume['resume'];
    }
}

// ── Règle 8 : un contrôle statique se joue en CI ─────────────────────────────────────────────
// La famille « statique » promet le job `statique` (tools/README.md, « Les familles »). Une exception se
// nomme, avec ce qui empêche la CI de la jouer.
const NON_JOUES_EN_CI = [
    'check-marketplace' => "les archives des addons ne sont pas versionnées : il se joue sur une installation, après package-addons",
];

$ci = (string) @file_get_contents(nf_racine().'/.github/workflows/ci.yml');

foreach ($entetes as $nom => $e)
{
    if ($e['famille'] === 'statique' && str_starts_with($nom, 'check-') && !isset(NON_JOUES_EN_CI[$nom])
        && !str_contains($ci, 'tools/'.$nom.'.php'))
    {
        $anomalies[] = [$nom, "contrôle statique que la CI ne joue pas : l'ajouter au job `statique` de .github/workflows/ci.yml (ou à NON_JOUES_EN_CI, avec sa raison)"];
    }
}

// ── Règles 2 et 3 : indentation et plomberie, sur les outils ET la bibliothèque ──────────────
foreach (nf_fichiers(['tools'], ['php']) as $relatif => $chemin)
{
    $source = (string) file_get_contents($chemin);

    if (preg_match('/^\t/m', $source))
    {
        $anomalies[] = [$relatif, 'indentation par tabulation — les outils s\'écrivent à quatre espaces (.editorconfig)'];
    }

    // La bibliothèque porte la plomberie par définition, le routeur a sa garde propre, et ce
    // contrôle-ci NOMME les motifs qu'il refuse.
    if (str_starts_with($relatif, 'tools/lib/') || in_array($relatif, ['tools/router-builtin.php', 'tools/check-tools.php'], TRUE))
    {
        continue;
    }

    $code = nf_sans_commentaires($source);

    foreach (PLOMBERIE as $motif => $remplacant)
    {
        if (preg_match($motif, $code, $m))
        {
            $anomalies[] = [$relatif, sprintf('emploie `%s` — c\'est le rôle de %s, dans tools/lib/', trim($m[0]), $remplacant)];
        }
    }

    // Règle 4 : le verdict d'un contrôle passe par le socle.
    $nom = basename($relatif, '.php');

    if (str_starts_with($nom, 'check-') && $nom !== 'check-all')
    {
        if (!str_contains($code, 'nf_ok('))
        {
            $anomalies[] = [$relatif, 'un contrôle conclut par nf_ok()'];
        }

        if (!str_contains($code, 'nf_echec(') && !str_contains($code, 'nf_refus('))
        {
            $anomalies[] = [$relatif, 'un contrôle refuse par nf_echec() ou nf_refus()'];
        }
    }

    // Règle 7 : un refus ne se masque pas derrière un exit à message (code de sortie zéro).
    if (preg_match('/\b(?:exit|die)\s*\(\s*[\x27"]/', nf_sans_commentaires($code)))
    {
        $anomalies[] = [$relatif, 'termine par exit("message") — le code de sortie serait zéro ; refuser par nf_refus() ou nf_echec()'];
    }

    // Règle 5 : un port réservé pour qui sert le site.
    if (str_contains($code, 'nf_serveur(') && !array_key_exists($nom, NF_PORTS))
    {
        $anomalies[] = [$relatif, 'sert le site sans port réservé dans NF_PORTS (tools/lib/outil.php)'];
    }
}

// ── Règles 6 et 11 : les tables engendrées du README ─────────────────────────────────────────
function catalogue(array $entetes, array $internes): string
{
    $titres = [
        'statique'   => ['Contrôles statiques', 'Joués par défaut par `check-all`. Ils lisent les sources, sans base ni serveur.'],
        'navigateur' => ['Contrôles en navigateur', 'Ajoutés par `check-all --navigateur`. Ils servent le site, et la plupart ouvrent un Chrome sans interface.'],
        'cible'      => ['Contrôles à cible explicite', 'Jamais lancés d\'office : chacun exige un argument, une base jetable, ou abîme le site pour l\'éprouver.'],
        'outil'      => ['Les autres outils', 'Ils agissent — construire, publier, régénérer, installer — plutôt qu\'ils ne vérifient.'],
    ];

    $sortie = '';

    foreach ($titres as $famille => [$titre, $sous_titre])
    {
        $lignes = array_filter($entetes, static fn (array $e): bool => $e['famille'] === $famille && !isset($internes['tools/'.$e['nom'].'.php']));

        if (!$lignes)
        {
            continue;
        }

        $sortie .= "### {$titre}\n\n{$sous_titre}\n\n| Outil | Ce qu'il fait | Usage |\n|---|---|---|\n";

        foreach ($lignes as $e)
        {
            $usage = $e['usage'][0] ?? '';
            $sortie .= sprintf("| [`%s`](%s.php) | %s | `%s` |\n", $e['nom'], $e['nom'], $e['resume'], $usage);
        }

        $sortie .= "\n";
    }

    // Règle 11 : les internes à part. La copie publique n'en a aucun, cette section y disparaît.
    if ($internes)
    {
        $sortie .= "### Les fichiers internes\n\n"
            ."Ils ne partent jamais dans le dépôt public : ils servent notre serveur, notre publication ou notre\n"
            ."site officiel. La copie publique ne les porte pas, et son catalogue s'écrit sans eux.\n\n"
            ."| Fichier | Ce qu'il fait | Pourquoi il reste chez nous |\n|---|---|---|\n";

        foreach ($internes as $relatif => $d)
        {
            $lien   = substr($relatif, strlen('tools/'));
            $resume = nf_resume(nf_racine().'/'.$relatif)['resume'] ?? '';
            $sortie .= sprintf("| [`%s`](%s) | %s | %s |\n", $lien, $lien, $resume, $d['raison']);
        }
    }

    return rtrim($sortie)."\n";
}

/** Les fonctions et constantes qu'un fichier de la bibliothèque définit, dans l'ordre du fichier. */
function definitions(string $chemin): string
{
    preg_match_all('/^(?:function (\w+)\(|const (\w+)\b)/m', (string) file_get_contents($chemin), $m, PREG_SET_ORDER);

    $noms = array_map(static fn (array $d): string => ($d[2] ?? '') !== '' ? '`'.$d[2].'`' : '`'.$d[1].'()`', $m);

    return $noms ? implode(', ', $noms) : '—';
}

function bibliotheque(array $bibliotheque): string
{
    $sortie = "| Fichier | Ce qu'il donne | Ce qu'il définit |\n|---|---|---|\n";

    foreach ($bibliotheque as $relatif => $resume)
    {
        $nom     = basename($relatif);
        $sortie .= sprintf("| [`%s`](lib/%s) | %s | %s |\n", $nom, $nom, $resume, definitions(nf_racine().'/'.$relatif));
    }

    return $sortie;
}

$sections = ['catalogue' => catalogue($entetes, $internes), 'bibliotheque' => bibliotheque($bibliotheque)];

if ($o['catalogue'])
{
    echo implode("\n", $sections);
    exit(NF_OK);
}

$readme  = nf_racine().'/tools/README.md';
$texte   = (string) @file_get_contents($readme);
$nouveau = $texte;

foreach ($sections as $cle => $attendu)
{
    $debut = "<!-- {$cle}:début -->";
    $fin   = "<!-- {$cle}:fin -->";
    $a     = strpos($nouveau, $debut);
    $b     = strpos($nouveau, $fin);

    if ($a === FALSE || $b === FALSE || $b < $a)
    {
        $anomalies[] = ['tools/README.md', "les marqueurs `{$debut}` et `{$fin}` manquent"];
        continue;
    }

    if (trim(substr($nouveau, $a + strlen($debut), $b - $a - strlen($debut))) === trim($attendu))
    {
        continue;
    }

    if ($o['ecrire'])
    {
        $nouveau = substr($nouveau, 0, $a + strlen($debut))."\n".$attendu.substr($nouveau, $b);
    }
    else
    {
        $anomalies[] = ['tools/README.md', "la table « {$cle} » ne correspond plus aux en-têtes — php tools/check-tools.php --ecrire"];
    }
}

if ($nouveau !== $texte)
{
    file_put_contents($readme, $nouveau);
    echo "tools/README.md : tables engendrées réécrites.\n";
}

// ── Règle 10 : un fichier public ne cite jamais un fichier interne ───────────────────────────
// Un nom à trait d'union est assez singulier pour être cherché nu ; un nom commun ne se cherche
// qu'avec son extension. La prose du README compte, ses tables engendrées non : la copie publique
// les réécrit.
$motifs = [];

foreach (array_keys($internes) as $relatif)
{
    $tige    = pathinfo($relatif, PATHINFO_FILENAME);
    $cherche = str_contains($tige, '-') ? $tige : basename($relatif);

    $motifs['/(?<![\w-])'.preg_quote($cherche, '/').'(?![\w-])/u'] = $relatif;
}

$textes = ['tools/README.md' => (string) preg_replace_callback(
    '/<!-- ([a-z]+):début -->.*?<!-- \1:fin -->/su',
    static fn (array $m): string => str_repeat("\n", substr_count($m[0], "\n")),
    $texte
)];

foreach ($diffusions as $relatif => $d)
{
    if ($d['valeur'] === 'publique')
    {
        $textes[$relatif] = (string) file_get_contents($fichiers[$relatif]);
    }
}

foreach ($textes as $relatif => $source)
{
    foreach ($motifs as $motif => $interne)
    {
        preg_match_all($motif, $source, $trouves, PREG_OFFSET_CAPTURE);

        foreach ($trouves[0] as [$trouve, $position])
        {
            $anomalies[] = [$relatif.':'.(substr_count($source, "\n", 0, $position) + 1),
                sprintf('cite `%s`, qui reste chez nous (%s) : la copie publique ne l\'aura pas — nommer le besoin, pas l\'outil', $trouve, $interne)];
        }
    }
}

printf("%d outil(s), %d fichier(s) dans la bibliothèque, %d fichier(s) interne(s).\n",
    count($outils), count(glob(nf_racine().'/tools/lib/*.php') ?: []), count($internes));

if (!$anomalies)
{
    nf_ok(sprintf('les %d outils respectent les conventions de tools/README.md', count($outils)));
}

echo "\n";

foreach ($anomalies as [$ou, $quoi])
{
    printf("  ✗ %-28s %s\n", $ou, $quoi);
}

nf_echec(sprintf('%d écart(s) aux conventions', count($anomalies)));
