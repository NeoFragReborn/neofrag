<?php
declare(strict_types=1);

/**
 * depot — parcourir les fichiers du dépôt, toujours avec les mêmes exclusions.
 *
 * Diffusion : publique
 *
 * Pourquoi
 * --------
 * Vingt-et-un outils instanciaient leur propre `RecursiveDirectoryIterator`, avec chacun sa liste
 * d'exclusions — et elles divergeaient : l'un écartait `js/tinymce`, l'autre pas ; l'un sautait les
 * minifiés, l'autre les lisait. Un `grep` fait à la main a raté un fichier sur quatre le 2026-09-17
 * parce qu'il ne descendait pas dans un sous-dossier. Ici la liste est écrite UNE fois.
 *
 * Usage
 * -----
 *   foreach (nf_fichiers(['modules', 'widgets'], ['php']) as $relatif => $absolu) { … }
 *   foreach (nf_addons('module') as $nom => $dossier) { … }
 */

require_once __DIR__.'/outil.php';

/** Ce qui n'est jamais notre code : le code d'autrui et les artefacts. */
const NF_EXCLUS = [
    '/vendor/', '/node_modules/', '/.git/', '/cache/', '/logs/', '/dist/', '/backups/',
    '/js/tinymce/', '/js/codemirror/', '/js/altcha/', '/.phpunit.cache/', '/graphify-out/',
];

/** Les dossiers où vit du code de produit. */
const NF_DOSSIERS_PRODUIT = ['neofrag', 'modules', 'widgets', 'themes', 'addons'];

/** Les dossiers qui portent du JavaScript à nous. */
const NF_DOSSIERS_JS = ['js', 'neofrag', 'modules', 'widgets', 'themes', 'addons', 'install', 'tests'];

/**
 * Les fichiers des dossiers donnés, par extension, hors exclusions et hors minifiés.
 *
 * @param  list<string> $dossiers    relatifs à la racine
 * @param  list<string> $extensions  sans le point, en minuscules ; `['*']` pour tous les fichiers
 * @param  string|null  $racine      une autre racine que le dépôt — un faux produit d'auto-épreuve
 * @return array<string, string>     chemin relatif (séparateur `/`) => chemin absolu, trié
 */
function nf_fichiers(array $dossiers, array $extensions, array $exclus = NF_EXCLUS, bool $sans_minifies = TRUE, ?string $racine = NULL): array
{
    $racine  = $racine === NULL ? nf_racine() : rtrim(str_replace('\\', '/', $racine), '/');
    $trouves = [];
    $toutes  = $extensions === ['*'];

    foreach ($dossiers as $dossier)
    {
        if (!is_dir($chemin = $racine.'/'.$dossier))
        {
            continue;
        }

        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($chemin, FilesystemIterator::SKIP_DOTS));

        foreach ($it as $fichier)
        {
            if (!$fichier->isFile() || (!$toutes && !in_array(strtolower($fichier->getExtension()), $extensions, TRUE)))
            {
                continue;
            }

            $relatif = substr(str_replace('\\', '/', $fichier->getPathname()), strlen($racine) + 1);

            if ($sans_minifies && str_contains($relatif, '.min.'))
            {
                continue;
            }

            foreach ($exclus as $x)
            {
                if (str_contains('/'.$relatif, $x))
                {
                    continue 2;
                }
            }

            // Séparateur `/` partout, même sous Windows : les contrôles comparent des chemins
            // (`/docs/guide/`) et PHP ouvre indifféremment l'une ou l'autre forme.
            $trouves[$relatif] = str_replace('\\', '/', $fichier->getPathname());
        }
    }

    ksort($trouves);

    return $trouves;
}

/**
 * Tous les FICHIERS d'une arborescence, sans filtre d'extension, en écartant les dossiers nommés.
 *
 * Pour les outils qui empaquettent ou comparent une arborescence entière (paquets de release,
 * instantané public, dérive d'une installation). Rien de plus : les dossiers vides n'existent pas
 * pour git, et ne sont donc jamais livrés.
 *
 * @param  list<string> $dossiers_ignores  noms de dossiers (à toute profondeur) à ne pas descendre
 * @return Generator<string, SplFileInfo>  chemin relatif à `$base` (séparateur `/`) => fichier
 */
function nf_parcourir(string $base, array $dossiers_ignores = []): Generator
{
    $base      = rtrim(str_replace('\\', '/', $base), '/');
    $iterateur = new RecursiveIteratorIterator(
        new RecursiveCallbackFilterIterator(
            new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS),
            static fn (SplFileInfo $f): bool => !($f->isDir() && in_array($f->getFilename(), $dossiers_ignores, TRUE))
        )
    );

    foreach ($iterateur as $fichier)
    {
        if ($fichier->isFile())
        {
            yield substr(str_replace('\\', '/', $fichier->getPathname()), strlen($base) + 1) => $fichier;
        }
    }
}

/**
 * Supprime un dossier et tout ce qu'il contient — sans suivre un lien symbolique, et en levant la
 * lecture seule que git pose sur ses objets (sous Windows, `unlink()` les refuse sinon). Écrite une fois :
 * cinq outils en tenaient chacun leur copie le 2026-10-04.
 *
 * `$obligatoire` à FALSE : un dossier de travail qui résiste (l'antivirus tient un fichier) est signalé et
 * laissé, au lieu d'arrêter l'outil — la publication de la 1.2.26 s'est arrêtée ainsi, release en brouillon,
 * pour un dossier temporaire déjà vérifié (2026-10-05).
 */
function nf_supprimer(string $dossier, bool $obligatoire = TRUE): void
{
    if (!is_dir($dossier) || is_link($dossier))
    {
        return;
    }

    // Sous Windows, un fichier qu'on vient d'écrire peut être tenu un instant (l'antivirus le relit) :
    // le dossier se vide alors en plusieurs passes, et l'on réessaie avant de renoncer.
    for ($essai = 1; ; $essai++)
    {
        foreach (array_diff(scandir($dossier) ?: [], ['.', '..']) as $entree)
        {
            $chemin = $dossier.'/'.$entree;

            if (is_dir($chemin) && !is_link($chemin))
            {
                nf_supprimer($chemin);
            }
            else
            {
                @chmod($chemin, 0666);
                @unlink($chemin);
            }
        }

        if (@rmdir($dossier) || !is_dir($dossier))
        {
            return;
        }

        if ($essai >= 10)
        {
            if (!$obligatoire)
            {
                nf_avertir("  ⚠ {$dossier} n'a pas pu être supprimé (un fichier y reste tenu) : à effacer plus tard");

                return;
            }

            nf_refus("impossible de supprimer {$dossier} : un fichier y reste tenu");
        }

        usleep(300000);
    }
}

/** Le chemin relatif à la racine, séparateur `/` quel que soit le système. */
function nf_relatif(string $chemin): string
{
    $plat   = str_replace('\\', '/', $chemin);
    $racine = str_replace('\\', '/', nf_racine()).'/';

    return str_starts_with($plat, $racine) ? substr($plat, strlen($racine)) : $plat;
}

/**
 * Les addons d'un type, selon la règle du chargeur : un dossier `x/` qui contient `x.php`.
 *
 * @param  'module'|'widget'|'theme'|'addon' $type
 * @return array<string, string>  nom => dossier absolu, trié
 */
function nf_addons(string $type): array
{
    $dossier = ['module' => 'modules', 'widget' => 'widgets', 'theme' => 'themes', 'addon' => 'addons'][$type]
        ?? nf_refus("type d'addon inconnu : $type");

    $addons = [];

    foreach (glob(nf_racine().'/'.$dossier.'/*', GLOB_ONLYDIR) ?: [] as $chemin)
    {
        $nom = basename($chemin);

        if (is_file($chemin.'/'.$nom.'.php'))
        {
            $addons[$nom] = $chemin;
        }
    }

    ksort($addons);

    return $addons;
}

/** Les thèmes publics du disque — jamais `admin`, qui n'est pas servi au visiteur. */
function nf_themes_publics(): array
{
    $themes = array_keys(nf_addons('theme'));

    return array_values(array_diff($themes, ['admin']));
}

/**
 * Les addons à la carte que le catalogue livré annonce et que l'arbre n'a pas : `module ads`, …
 *
 * Le dépôt `neofrag` ne porte que le cœur et les addons d'identité ; les addons à la carte vivent dans
 * le dépôt `extensions`, et `tools/assembler.php` les pose dans l'arbre. Les documents, eux, décrivent
 * le produit entier — le paquet d'installation réunit les deux —, si bien qu'un contrôle qui compte les
 * addons du disque ne peut juger que l'arbre assemblé. Vide dans un arbre complet.
 *
 * @return list<string>
 */
function nf_extensions_absentes(): array
{
    $catalogue = json_decode((string) @file_get_contents(nf_racine().'/marketplace/catalog.json'), TRUE);
    $absentes  = [];

    foreach (is_array($catalogue) ? ($catalogue['addons'] ?? []) : [] as $addon)
    {
        $dossier = ['module' => 'modules', 'widget' => 'widgets', 'theme' => 'themes'][$addon['type'] ?? ''] ?? NULL;

        if ($dossier !== NULL && ($addon['tier'] ?? 0) === 2 && !is_file(nf_racine()."/{$dossier}/{$addon['name']}/{$addon['name']}.php"))
        {
            $absentes[] = $addon['type'].' '.$addon['name'];
        }
    }

    return $absentes;
}

/** Le refus d'un contrôle qui ne peut juger que l'arbre assemblé, si les addons à la carte manquent. */
function nf_exiger_assemblage(string $ce_qui_se_juge): void
{
    if ($absentes = nf_extensions_absentes())
    {
        nf_refus(sprintf("%d addon(s) à la carte absent(s) de l'arbre (%s…) : %s se juge sur le produit entier.\n"
            ."  Poser d'abord le dépôt extensions : php tools/assembler.php --extensions=../extensions",
            count($absentes), implode(', ', array_slice($absentes, 0, 3)), $ce_qui_se_juge));
    }
}
