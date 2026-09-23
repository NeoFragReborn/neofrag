<?php
declare(strict_types=1);

/**
 * langues — lire et écrire les fichiers de langue (`langs/<code>.php`) sans les exécuter.
 *
 * Pourquoi
 * --------
 * Deux outils écrivent dans ces fichiers : `check-langs --fix` y ajoute les clés françaises,
 * `fill-langs` les traductions. Chacun avait sa façon de faire, et celle de `fill-langs` RÉÉCRIVAIT
 * le fichier entier à partir des seules valeurs littérales : l'en-tête `declare(strict_types=1)`
 * disparaissait, et une valeur calculée du cœur (`'Copyright © '.date('Y').…`) aurait été tronquée à
 * son premier morceau. La seule écriture sûre est celle-ci : INSÉRER les lignes neuves avant le
 * crochet fermant, sans toucher à ce qui existe.
 *
 * Usage
 * -----
 *   $cle = nf_langue_cle('Aucun résultat');                        // « 7ff1f0e6 »
 *   $fr  = nf_langue_valeurs('modules/forum/langs/fr.php');        // clé => texte
 *   nf_langue_ajouter('modules/forum/langs/en.php', [$cle => 'No results']);
 */

/** Les six langues du produit ; le français est la langue SOURCE, celle des clés. */
const NF_LANGUES = ['fr', 'en', 'de', 'es', 'it', 'pt'];

/** La clé d'un texte : l'empreinte CRC32 du texte français, comme la calcule `lang()`. */
function nf_langue_cle(string $texte): string
{
    return sprintf('%08x', crc32($texte));
}

/**
 * Les valeurs LITTÉRALES d'un fichier de langue, par clé, lues au texte. Une valeur calculée
 * (`'…'.date('Y')`) n'y figure pas : on ne sait pas la lire sans exécuter le fichier.
 *
 * @return array<string, string>  clé => texte, échappement PHP retiré
 */
function nf_langue_valeurs(string $fichier): array
{
    if (!is_file($fichier))
    {
        return [];
    }

    // Une clé faite de chiffres seuls s'écrivait parfois sans guillemets (`93714304 =>`) : PHP la lit
    // comme la même clé, et l'ignorer ici faisait écrire un doublon (156 le 2026-09-23).
    preg_match_all("/(?<![\w'])'?([0-9a-f]{8})'?\s*=>\s*'((?:[^'\\\\]|\\\\.)*)'\s*(?=,|\n|\])/", (string) file_get_contents($fichier), $trouves, PREG_SET_ORDER);

    $valeurs = [];

    foreach ($trouves as [, $cle, $valeur])
    {
        $valeurs[$cle] ??= str_replace(["\\'", '\\\\'], ["'", '\\'], $valeur);
    }

    return $valeurs;
}

/** Toutes les CLÉS d'un fichier de langue, valeurs calculées comprises. */
function nf_langue_cles(string $fichier): array
{
    if (!is_file($fichier))
    {
        return [];
    }

    preg_match_all("/(?<![\w'])'?([0-9a-f]{8})'?\s*=>/", (string) file_get_contents($fichier), $trouves);

    return array_flip($trouves[1]);
}

/** Un texte écrit entre apostrophes PHP. */
function nf_langue_echapper(string $texte): string
{
    return str_replace(['\\', "'"], ['\\\\', "\\'"], $texte);
}

/**
 * Ajoute des entrées à un fichier de langue, en fin de tableau, sans rien réécrire de l'existant.
 * Le fichier absent est créé avec l'en-tête de son voisin français, langue substituée.
 *
 * @param array<string, string> $entrees  clé => texte (non échappé)
 */
function nf_langue_ajouter(string $fichier, array $entrees): bool
{
    if (!$entrees)
    {
        return TRUE;
    }

    $lignes = [];

    foreach ($entrees as $cle => $texte)
    {
        $lignes[] = "\t'".$cle."' => '".nf_langue_echapper($texte)."',";
    }

    if (is_file($fichier))
    {
        $contenu  = (string) file_get_contents($fichier);
        $position = strrpos($contenu, '];');

        if ($position === FALSE)
        {
            return FALSE;
        }

        $avant = substr($contenu, 0, $position);
        $apres = substr($contenu, $position);

        // La dernière entrée n'a pas toujours de virgule finale : sans l'ajouter, deux entrées
        // collées sans séparateur rendaient le fichier illisible.
        $fin = rtrim($avant);

        if ($fin !== '' && substr($fin, -1) !== ',' && substr($fin, -1) !== '[')
        {
            $avant = $fin.",\n";
        }
        else
        {
            $avant = $fin."\n";
        }

        return file_put_contents($fichier, $avant.implode("\n", $lignes)."\n".$apres) !== FALSE;
    }

    $dossier = dirname($fichier);

    if (!is_dir($dossier) && !mkdir($dossier, 0775, TRUE))
    {
        return FALSE;
    }

    $code   = basename($fichier, '.php');
    $entete = "<?php\ndeclare(strict_types=1);\n/**\n * https://translate.neofr.ag\n */\n\n";

    if (is_file($francais = $dossier.'/fr.php') && preg_match('/^(.*?)return \[/s', (string) file_get_contents($francais), $m))
    {
        $entete = str_replace('(fr)', '('.$code.')', $m[1]);
    }

    return file_put_contents($fichier, $entete."return [\n".implode("\n", $lignes)."\n];\n") !== FALSE;
}

/**
 * Les « jokers » d'un texte : ce qu'une traduction doit conserver tel quel — `%s`, `%1$d`, `{0}`,
 * les balises HTML. Rendus triés, pour comparer une traduction à sa source.
 *
 * @return list<string>
 */
function nf_langue_jokers(string $texte): array
{
    preg_match_all('/%(?:\d+\$)?[-+ 0]*\d*(?:\.\d+)?[sdfuxXbc]|\{\d+\}|<\/?[a-z][a-z0-9]*/i', $texte, $trouves);

    $jokers = array_map('strtolower', $trouves[0]);
    sort($jokers);

    return $jokers;
}
