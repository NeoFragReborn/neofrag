<?php
declare(strict_types=1);
require_once __DIR__.'/outil.php';

/**
 * entetes — ce que l'en-tête d'un outil déclare : sa famille, son usage, sa batterie, sa diffusion.
 *
 * Diffusion : publique
 *
 * L'en-tête d'un outil est sa fiche : `check-tools` le fait respecter, `check-all` y lit la famille et
 * la batterie, et la fabrique du dépôt public y lit la **diffusion**. Chacun le relisait à sa manière ;
 * la lecture vit ici, une fois.
 *
 * La diffusion (plan des dépôts publics, 2026-10-04) : chaque fichier de `tools/` dit s'il part dans le
 * dépôt public — `Diffusion : publique` — ou s'il reste chez nous — `Diffusion : interne — <raison>`. Un
 * outil propre à notre serveur, à notre publication ou à la vitrine n'a rien à faire dans le dépôt
 * public, et une déclaration écrite vaut mieux qu'une liste d'exclusions tenue à part : elle voyage avec
 * l'outil, et un outil nouveau ne peut pas l'oublier (`check-tools` la refuse absente).
 */

/** Les familles d'un outil (`check-all` les joue selon leur coût). */
const NF_FAMILLES = ['statique', 'navigateur', 'cible', 'outil'];

/** Les deux diffusions possibles. */
const NF_DIFFUSIONS = ['publique', 'interne'];

/**
 * La diffusion déclarée par un fichier de `tools/` (PHP ou Python), lue dans ses premières lignes.
 *
 * @return array{valeur: ?string, raison: string} `valeur` NULL si la ligne manque ou porte une valeur inconnue
 */
function nf_diffusion(string $chemin): array
{
    $tete = implode("\n", array_slice(explode("\n", (string) @file_get_contents($chemin)), 0, 80));

    if (!preg_match('/^\s*(?:\*|#)\s*Diffusion : ([a-z]+)(?:\s+—\s+(.+?))?\s*$/m', $tete, $m) || !in_array($m[1], NF_DIFFUSIONS, TRUE))
    {
        return ['valeur' => NULL, 'raison' => ''];
    }

    return ['valeur' => $m[1], 'raison' => trim($m[2] ?? '')];
}

/**
 * Ce qu'un fichier de `tools/` dit de lui-même à la première ligne de sa documentation :
 * `<nom> — <résumé>`, dans le premier bloc de commentaire en PHP (ouvert par `/**` ou `/*`), dans la
 * docstring en Python. Le catalogue et la table de la bibliothèque de `tools/README.md` en sont tirés.
 *
 * @return array{nom: string, resume: string}|null NULL si cette première ligne ne suit pas la forme
 */
function nf_resume(string $chemin): ?array
{
    $tete = implode("\n", array_slice(explode("\n", (string) @file_get_contents($chemin)), 0, 80));

    if (!preg_match('#^(?:/\*\*?|""")$#m', $tete, $ouverture, PREG_OFFSET_CAPTURE))
    {
        return NULL;
    }

    $ligne = explode("\n", substr($tete, $ouverture[0][1] + strlen($ouverture[0][0]) + 1), 2)[0];

    if (!preg_match('/^(?: \* )?([a-z0-9-]+) — (.+)$/', $ligne, $m))
    {
        return NULL;
    }

    return ['nom' => $m[1], 'resume' => trim($m[2])];
}

/**
 * L'en-tête d'un outil (`tools/<nom>.php`), lu dans son bloc de documentation, avec les écarts à la
 * convention de `tools/README.md`.
 *
 * @return array{nom: string, resume: string, famille: string, usage: list<string>, batterie: string, diffusion: ?string, raison: string, erreurs: list<string>}
 */
function nf_entete_outil(string $nom, string $chemin): array
{
    $source    = (string) file_get_contents($chemin);
    $lignes    = explode("\n", $source);
    $erreurs   = [];
    $diffusion = nf_diffusion($chemin);
    $entete    = ['nom' => $nom, 'resume' => '', 'famille' => '', 'usage' => [], 'batterie' => '',
                  'diffusion' => $diffusion['valeur'], 'raison' => $diffusion['raison'], 'erreurs' => []];

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

    if (preg_match('/^ \* Famille : ([a-z]+)\s*$/m', $corps, $f) && in_array($f[1], NF_FAMILLES, TRUE))
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
    // à espaces de noms l'écrit dans son premier bloc `namespace { … }`.
    $code = nf_sans_commentaires($source);
    $code = (string) preg_replace('/^<\?php\s+declare\(strict_types=1\);/', '', $code);

    if (!preg_match("/^\s*(?:namespace\s*\{\s*)?require(?:_once)? __DIR__\.'\/lib\/outil\.php';/", $code))
    {
        $erreurs[] = "la première instruction doit être `require __DIR__.'/lib/outil.php';`";
    }

    $entete['erreurs'] = $erreurs;

    return $entete;
}
