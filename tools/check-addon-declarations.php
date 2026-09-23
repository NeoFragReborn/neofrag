<?php
declare(strict_types=1);
/**
 * check-addon-declarations — chaque addon déclare `core`, `presets` et `requires`, et le cœur ne dépend jamais d'un optionnel.
 *
 * Famille : statique
 *
 * Chaque addon doit déclarer dans son `__info()` :
 *   'core'     => TRUE|FALSE   livré toujours et non désinstallable, ou non
 *   'presets'  => [...]        profils d'installation qui le pré-cochent ('gaming')
 *   'requires' => [...]        addons dont il a BESOIN (dépendance dure : sans eux, il casse)
 *
 * POURQUOI. Avant, l'appartenance au cœur était un DÉFAUT IMPLICITE : tout ce qui n'était listé
 * nulle part dans tools/addons-manifest.php atterrissait dans le cœur, silencieusement. C'est ainsi
 * qu'`emojis`, `files` et `webhooks` s'y sont retrouvés sans que personne ne l'ait décidé. Ici le
 * défaut est inversé : un addon muet fait ÉCHOUER la CI. Oublier devient une erreur de build, pas
 * une livraison involontaire.
 *
 * La règle 3 est celle dont l'absence a fait capoter le cœur lean en juin 2026 : un module du cœur
 * qui dépend d'un module optionnel rend le paquet indivisible — et produit un 500 sur une
 * installation qui n'a pas ce module.
 *
 * Usage
 * -----
 *   php tools/check-addon-declarations.php
 */

require __DIR__.'/lib/outil.php';

nf_options([]);

$racine = nf_racine();
$erreurs = $avertissements = [];
$addons  = [];

foreach (['module' => 'modules', 'widget' => 'widgets', 'theme' => 'themes'] as $type => $dossier)
{
    foreach (scandir("$racine/$dossier") ?: [] as $nom)
    {
        if ($nom[0] === '.' || !is_file($fichier = "$racine/$dossier/$nom/$nom.php"))
        {
            continue;
        }

        $source = (string) file_get_contents($fichier);

        // Lecture STATIQUE : le contrôle doit tourner en CI sans base de données ni bootstrap.
        $core = preg_match("/'core'\s*=>\s*(TRUE|FALSE)/", $source, $m) ? $m[1] === 'TRUE' : NULL;
        $lit  = function (string $cle) use ($source): ?array {
            if (!preg_match("/'$cle'\s*=>\s*\[([^\]]*)\]/", $source, $m))
            {
                return NULL;
            }
            preg_match_all("/'([^']+)'/", $m[1], $v);
            return $v[1];
        };

        $addons["$type:$nom"] = [
            'type'     => $type,
            'nom'      => $nom,
            'core'     => $core,
            'presets'  => $lit('presets'),
            'requires' => $lit('requires'),
            'icon'     => preg_match("/'icon'\s*=>\s*'([^']*)'/", $source, $m) ? $m[1] : NULL,
        ];
    }
}

// ── Règle 1 : tout addon déclare les trois clés ────────────────────────────────
foreach ($addons as $cle => $a)
{
    foreach (['core', 'presets', 'requires'] as $champ)
    {
        if ($a[$champ] === NULL)
        {
            $erreurs[] = "$cle : la clé « $champ » manque dans __info().";
        }
    }
}

// ── Règle 2 : une dépendance doit désigner un addon qui existe ─────────────────
$noms_modules = [];
foreach ($addons as $a)
{
    if ($a['type'] === 'module')
    {
        $noms_modules[$a['nom']] = $a;
    }
}

foreach ($addons as $cle => $a)
{
    foreach ($a['requires'] ?? [] as $dep)
    {
        if (!isset($noms_modules[$dep]))
        {
            $erreurs[] = "$cle : dépend de « $dep », qui n'est pas un module existant.";
        }
    }
}

// ── Règle 3 : le cœur ne dépend JAMAIS de l'optionnel ─────────────────────────
foreach ($addons as $cle => $a)
{
    if (!$a['core'])
    {
        continue;
    }

    foreach ($a['requires'] ?? [] as $dep)
    {
        if (isset($noms_modules[$dep]) && !$noms_modules[$dep]['core'])
        {
            $erreurs[] = "$cle appartient au cœur mais dépend de « $dep », qui n'en fait pas partie : "
                       . "le paquet ne serait plus divisible (c'est ce défaut qui a produit le 500 de juin 2026).";
        }
    }
}

// ── Règle 4 : les cycles sont signalés, pas bloquants ─────────────────────────
foreach ($noms_modules as $nom => $a)
{
    foreach ($a['requires'] ?? [] as $dep)
    {
        if (in_array($nom, $noms_modules[$dep]['requires'] ?? [], TRUE) && strcmp($nom, $dep) < 0)
        {
            $avertissements[] = "« $nom » et « $dep » dépendent l'un de l'autre : ils ne peuvent être "
                              . "installés séparément. À résoudre en gardant le côté purement affichage.";
        }
    }
}

// ── Règle 5 : un addon non diffusable ne doit pas se retrouver au catalogue ───
//
// `vitrine` est le site neofrag-reborn.xyz lui-même : le publier livrerait notre vitrine comme un
// thème installable. L'exclusion reposait sur une simple omission dans tools/addons-manifest.php ;
// elle est désormais déclarée, et vérifiée ici.
$catalogue = dirname(__DIR__) . '/marketplace/catalog.json';

if (is_file($catalogue))
{
    $data    = json_decode((string) file_get_contents($catalogue), true);
    $publies = [];

    foreach ($data['addons'] ?? [] as $entree)
    {
        $publies[($entree['type'] ?? '') . ':' . ($entree['name'] ?? '')] = TRUE;
    }

    foreach ($addons as $cle => $a)
    {
        $source = file_get_contents(dirname(__DIR__) . '/' . ($a['type'] === 'theme' ? 'themes' : $a['type'] . 's') . "/{$a['nom']}/{$a['nom']}.php");

        if (preg_match("/'distributed'\s*=>\s*FALSE/", (string) $source) && isset($publies[$cle]))
        {
            $erreurs[] = "$cle est déclaré non diffusable mais figure dans marketplace/catalog.json.";
        }
    }
}

// ── Règle 6 : la déclaration et le seed du cœur doivent dire la même chose ────
//
// install/seed.sql enregistre les addons du cœur : c'est ce qui est RÉELLEMENT installé sur une
// base neuve. Si un addon se déclare hors cœur mais y figure quand même (ou l'inverse), on a deux
// vérités — et c'est précisément ce genre de divergence silencieuse qu'on cherche à supprimer.
$seed = dirname(__DIR__) . '/install/seed.sql';

if (is_file($seed))
{
    $dans_seed = [];
    $dedans    = FALSE;

    foreach (file($seed, FILE_IGNORE_NEW_LINES) ?: [] as $ligne)
    {
        if (str_starts_with($ligne, 'INSERT INTO `nf_addon`'))
        {
            $dedans = TRUE;
            continue;
        }

        if ($dedans)
        {
            // type_id : 1 = module, 2 = thème, 3 = widget. Le reste (langues, authentificateurs)
            // n'est pas concerné par la déclaration core/presets/requires.
            if (preg_match("/^\('\d+', '([123])', '([a-z0-9_]+)'/", trim($ligne), $m))
            {
                $dans_seed[[1 => 'module', 2 => 'theme', 3 => 'widget'][(int) $m[1]] . ':' . $m[2]] = TRUE;
            }

            if (str_ends_with(rtrim($ligne), ';'))
            {
                $dedans = FALSE;
            }
        }
    }

    if ($dans_seed)
    {
        foreach ($addons as $cle => $a)
        {
            if ($a['core'] && !isset($dans_seed[$cle]))
            {
                $erreurs[] = "$cle se déclare du cœur mais n'est PAS enregistré par install/seed.sql.";
            }
            else if (!$a['core'] && isset($dans_seed[$cle]))
            {
                $erreurs[] = "$cle se déclare hors cœur mais install/seed.sql l'enregistre quand même : "
                           . "régénérer le seed (tools/dump-schema.php) ou corriger la déclaration.";
            }
        }
    }
}

// ── Règle 7 : une icône déclarée doit EXISTER dans le FontAwesome embarqué ────
//
// Une icône dont le nom n'existe pas ne provoque aucune erreur : elle rend une case vide. Le piège
// est d'autant plus vicieux que FontAwesome a RENOMMÉ des icônes entre la 5 et la 6 (`fa-poll` est
// devenu `fa-square-poll-vertical`, `fa-share-alt` est devenu `fa-share-nodes`) — un nom copié
// d'une documentation d'époque disparaît silencieusement de l'interface.
//
// Le contrôle lit les noms réellement présents dans la feuille de style embarquée, pas une liste
// tenue à la main. Il ne juge PAS le style (`fas` / `far` / `fab`) : la version gratuite n'a qu'un
// jeu *regular* partiel, et cela ne se déduit pas du CSS. La règle de travail, elle, est simple :
// ne jamais inventer un `far`, ne réutiliser que des paires déjà en service.
$fa = dirname(__DIR__) . '/css/icons/fontawesome.min.css';

if (is_file($fa) && preg_match_all('/\.fa-([a-z0-9-]+)(?=[{,:])/', (string) file_get_contents($fa), $m))
{
    $connues = array_flip($m[1]);

    foreach ($addons as $cle => $a)
    {
        if (empty($a['icon']))
        {
            continue;
        }

        if (!preg_match('/^(fas|far|fab|fa-solid|fa-regular|fa-brands)\s+fa-([a-z0-9-]+)$/', $a['icon'], $i))
        {
            $erreurs[] = "« $cle » déclare une icône de forme inattendue : « {$a['icon']} » "
                       . "(attendu : « fas fa-nom », « far fa-nom » ou « fab fa-nom »).";
            continue;
        }

        if (!isset($connues[$i[2]]))
        {
            $erreurs[] = "« $cle » déclare l'icône « {$a['icon']} », absente du FontAwesome embarqué "
                       . "(" . count($connues) . " noms disponibles) — elle rendrait une case vide.";
        }
    }
}

// ── Compte rendu ──────────────────────────────────────────────────────────────
$par_categorie = ['cœur' => 0, 'preset' => 0, 'à la carte' => 0];
foreach ($addons as $a)
{
    if ($a['core'])            { $par_categorie['cœur']++; }
    else if ($a['presets'])    { $par_categorie['preset']++; }
    else                       { $par_categorie['à la carte']++; }
}

printf("%d addons déclarés — cœur : %d · préset : %d · à la carte : %d\n",
    count($addons), $par_categorie['cœur'], $par_categorie['preset'], $par_categorie['à la carte']);

foreach ($avertissements as $a)
{
    echo "  ⚠ $a\n";
}

if ($erreurs)
{
    echo "\n";
    foreach ($erreurs as $e)
    {
        echo "  ✗ $e\n";
    }
    nf_echec(sprintf("%d erreur(s) — déclarations d'addons invalides", count($erreurs)));
}

nf_ok('déclarations d\'addons valides');
