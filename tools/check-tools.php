<?php
declare(strict_types=1);

/**
 * check-tools — les outils de `tools/` respectent leurs propres conventions.
 *
 * Famille : statique
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
 *   2. l'INDENTATION : quatre espaces, jamais de tabulation en tête de ligne (cf. `.editorconfig`) ;
 *   3. la PLOMBERIE : aucun outil n'ouvre lui-même une base, un serveur, un navigateur ni une
 *      session — cela vit dans `tools/lib/`, une fois ;
 *   4. le VERDICT : un contrôle `check-*` conclut par `nf_ok()`, et refuse par `nf_echec()` ou
 *      `nf_refus()` ;
 *   5. les PORTS : tout outil qui sert le site a son port réservé dans `NF_PORTS` ;
 *   6. le CATALOGUE : `tools/README.md` liste chaque outil, et rien d'autre — la table est
 *      produite depuis les en-têtes (`--catalogue`), et doit être à jour (`--ecrire` la met à jour) ;
 *   7. la SORTIE : jamais d'`exit("message")` ni de `die("message")` — une chaîne passée à `exit`
 *      s'affiche et rend le code ZÉRO ; la CI a enchaîné sur un refus ainsi masqué. On refuse par
 *      `nf_refus()` ou `nf_echec()` ;
 *   8. la CI : tout contrôle de la famille `statique` est joué par `.github/workflows/ci.yml`, sauf
 *      exception nommée avec sa raison (`NON_JOUES_EN_CI`). Cinq contrôles créés le 2026-10-02
 *      n'étaient joués par personne : la CI restait verte sans les avoir lus.
 *
 * Usage
 * -----
 *   php tools/check-tools.php                toutes les règles, code 1 s'il y a un écart
 *   php tools/check-tools.php --catalogue    imprime le catalogue tel qu'il doit figurer dans le README
 *   php tools/check-tools.php --ecrire       réécrit le catalogue dans tools/README.md
 */

require __DIR__.'/lib/outil.php';
require __DIR__.'/lib/depot.php';

[$o] = nf_options(['catalogue' => FALSE, 'ecrire' => FALSE]);

const FAMILLES = ['statique', 'navigateur', 'cible', 'outil'];

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

/**
 * L'en-tête d'un outil, lu dans son bloc de documentation.
 *
 * @return array{nom: string, resume: string, famille: string, usage: list<string>, batterie: string, erreurs: list<string>}
 */
function entete(string $nom, string $chemin): array
{
    $source  = (string) file_get_contents($chemin);
    $lignes  = explode("\n", $source);
    $erreurs = [];
    $entete  = ['nom' => $nom, 'resume' => '', 'famille' => '', 'usage' => [], 'batterie' => '', 'erreurs' => []];

    if (($lignes[0] ?? '') !== '<?php' || ($lignes[1] ?? '') !== 'declare(strict_types=1);')
    {
        $erreurs[] = 'les deux premières lignes doivent être `<?php` puis `declare(strict_types=1);`';
    }

    if (!preg_match('#/\*\*\n \* ([a-z0-9-]+) — (.+?)\n(.*?)\*/#s', $source, $doc))
    {
        $erreurs[] = 'aucun bloc de documentation `/** * <nom> — <résumé> … */` en tête';
        $entete['erreurs'] = $erreurs;

        return $entete;
    }

    if ($doc[1] !== $nom)
    {
        $erreurs[] = sprintf('le bloc de documentation nomme « %s », le fichier s\'appelle « %s »', $doc[1], $nom);
    }

    $entete['resume'] = trim($doc[2]);
    $corps            = $doc[3];

    if (preg_match('/^ \* Famille : ([a-z]+)\s*$/m', $corps, $f) && in_array($f[1], FAMILLES, TRUE))
    {
        $entete['famille'] = $f[1];
    }
    else
    {
        $erreurs[] = 'aucune ligne ` * Famille : statique|navigateur|cible|outil` dans le bloc';
    }

    if (preg_match('/^ \* Batterie : (.+?)\s*$/m', $corps, $b))
    {
        $entete['batterie'] = trim($b[1]);
    }

    if (preg_match('/^ \* Usage\n \* -----\n((?: \*.*\n)+)/m', $corps, $u))
    {
        foreach (explode("\n", $u[1]) as $ligne)
        {
            if (preg_match('/^ \*\s{2,}(php tools\/\S+.*?)(?:\s{2,}.*)?$/', $ligne, $c))
            {
                $entete['usage'][] = trim($c[1]);
            }
        }
    }

    if (!$entete['usage'])
    {
        $erreurs[] = 'aucune section `Usage` avec au moins une ligne `php tools/…`';
    }

    // La première INSTRUCTION doit charger le socle : c'est lui qui porte la garde HTTP. Un fichier
    // à espaces de noms (un outil interne) l'écrit dans son premier bloc `namespace { … }`.
    $code = nf_sans_commentaires($source);
    $code = (string) preg_replace('/^<\?php\s+declare\(strict_types=1\);/', '', $code);

    if (!preg_match("/^\s*(?:namespace\s*\{\s*)?require(?:_once)? __DIR__\.'\/lib\/outil\.php';/", $code))
    {
        $erreurs[] = "la première instruction doit être `require __DIR__.'/lib/outil.php';`";
    }

    $entete['erreurs'] = $erreurs;

    return $entete;
}

$entetes  = [];
$anomalies = [];

foreach ($outils as $nom => $chemin)
{
    $entetes[$nom] = $e = entete($nom, $chemin);

    foreach ($e['erreurs'] as $erreur)
    {
        $anomalies[] = [$nom, $erreur];
    }
}

// ── Règle 8 : un contrôle statique se joue en CI ─────────────────────────────────────────────
// La famille « statique » promet le job `statique` (tools/README.md, « Les familles »). Une exception se
// nomme, avec ce qui empêche la CI de la jouer.
const NON_JOUES_EN_CI = [
    'check-marketplace' => "les archives des addons ne sont pas versionnées : il se joue sur l'atelier, après package-addons",
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

// ── Règle 6 : le catalogue du README ──────────────────────────────────────────────────────────
function catalogue(array $entetes): string
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
        $lignes = array_filter($entetes, static fn (array $e): bool => $e['famille'] === $famille);

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

    return rtrim($sortie)."\n";
}

$catalogue = catalogue($entetes);

if ($o['catalogue'])
{
    echo $catalogue;
    exit(NF_OK);
}

$readme  = nf_racine().'/tools/README.md';
$texte   = (string) @file_get_contents($readme);
$debut   = '<!-- catalogue:début -->';
$fin     = '<!-- catalogue:fin -->';
$a       = strpos($texte, $debut);
$b       = strpos($texte, $fin);

if ($a === FALSE || $b === FALSE || $b < $a)
{
    $anomalies[] = ['tools/README.md', "les marqueurs `{$debut}` et `{$fin}` manquent"];
}
else
{
    $actuel = trim(substr($texte, $a + strlen($debut), $b - $a - strlen($debut)));

    if ($actuel !== trim($catalogue))
    {
        if ($o['ecrire'])
        {
            file_put_contents($readme, substr($texte, 0, $a + strlen($debut))."\n".$catalogue.substr($texte, $b));
            echo "tools/README.md : catalogue réécrit.\n";
        }
        else
        {
            $anomalies[] = ['tools/README.md', 'le catalogue ne correspond plus aux en-têtes — php tools/check-tools.php --ecrire'];
        }
    }
}

printf("%d outil(s), %d dans la bibliothèque.\n", count($outils), count(glob(nf_racine().'/tools/lib/*.php') ?: []));

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
