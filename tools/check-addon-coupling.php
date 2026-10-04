<?php
declare(strict_types=1);
/**
 * check-addon-coupling — le couplage réel entre addons, lu au tokeniseur : tout couplage fatal est déclaré ou annoté.
 *
 * Famille : statique
 * Diffusion : publique
 *
 * POURQUOI CET OUTIL EXISTE
 *
 * tools/table-map.php dit depuis longtemps quelle table appartient à quel module. Ce qui manquait,
 * c'est l'autre moitié : QUI SE SERT DE QUOI CHEZ LES AUTRES. Cette moitié-là n'était écrite nulle
 * part — au mieux en commentaire dans tools/addons-manifest.php (« about → tables teams »), au pire
 * pas du tout. Résultat, en juin 2026 une installation allégée renvoyait un 500 parce que des
 * modules du cœur interrogeaient des tables optionnelles sans que rien ne le signale.
 *
 * Refait à la main le 2026-09-15 dans un script jetable, cet inventaire a trouvé deux bombes non
 * gardées (l'export RGPD de `user`, le sitemap de `settings`), une garde posée APRÈS la requête
 * qu'elle devait protéger (`teams`), et six copies de la même connaissance. Un script jetable ne
 * protège de rien : cet outil-ci le rend permanent et exécutable en CI.
 *
 * LES QUATRE MOYENS DE DÉPENDRE D'UN AUTRE ADDON
 *
 *   table    Lire une table qui appartient à un autre addon.
 *            → FATAL si l'addon est absent : « Table doesn't exist ». C'est le défaut de juin.
 *
 *   classe   Nommer une classe d'un autre addon (\NF\Modules\X\…, \NF\Widgets\X\…).
 *            → FATAL si l'addon est absent : « Class not found ».
 *
 *   service  Demander l'addon au service-locator : module('x'), widget('x'), theme('x'), model2('x').
 *            → TOLÉRANT : rend NULL si l'addon manque. C'est d'ailleurs la BONNE façon de se garder,
 *              et c'est pour ça que ce type est signalé sans jamais faire échouer : le voir apparaître
 *              à côté d'un couplage `table` est le signe que le code sait se protéger.
 *
 *   route    Construire une URL vers un autre addon : url('news/...').
 *            → COSMÉTIQUE : produit un lien mort (404), pas une erreur serveur.
 *
 * LA RÈGLE
 *
 * Tout couplage FATAL (`table` ou `classe`) vers un addon non-cœur doit être rendu explicite :
 *
 *   1. déclaré dans `'requires' => [...]` du `__info()` — c'est une dépendance DURE : sans l'autre
 *      addon, la fonctionnalité casse et l'installeur doit l'embarquer ;
 *   2. ou annoté à l'endroit de la référence par un commentaire contenant « couplage: » suivi d'une
 *      raison — c'est une dépendance MOLLE : le code se protège (garde `table_exists`, `try/catch`,
 *      branche inatteignable sans l'addon) et se contente de moins.
 *
 * Tout couplage fatal qui n'est ni l'un ni l'autre fait ÉCHOUER le contrôle. Le défaut est donc
 * l'inverse de celui de juin : un couplage nouveau et silencieux est une erreur de build.
 *
 * DEUX RÈGLES DURES EN PLUS
 *
 *   - un addon du CŒUR ne peut pas avoir de dépendance DURE déclarée vers un addon optionnel : le
 *     paquet ne serait plus divisible (exactement le défaut qui a produit le 500 de juin) ;
 *   - les cycles du graphe RÉEL sont signalés — check-addon-declarations.php ne voit que les cycles
 *     DÉCLARÉS, celui-ci voit aussi ceux que personne n'a déclarés.
 *
 * POURQUOI LE TOKENIZER ET PAS UNE REGEX
 *
 * Une regex compte les mentions en commentaire. C'est ce qui, le 2026-09-15, a fait accuser à tort
 * le module `monitoring` d'interroger `nf_news` : la seule occurrence était une ligne de
 * commentaire. `token_get_all()` est le propre analyseur de PHP — il ne voit que du vrai code.
 *
 * CE QU'IL LIT
 *
 *   - le **PHP** de l'addon (code et vues), par le tokenizer ;
 *   - son **SQL** d'installation et de migration — une clé étrangère vers la table d'un autre addon
 *     est une dépendance d'ordre d'installation, tout aussi fatale ;
 *   - son **JavaScript**, hors bibliothèques minifiées : le JS appelle souvent la route d'un autre
 *     module, et ce couplage était totalement invisible.
 *
 * Il signale aussi tout littéral en `nf_…` passé à une méthode de base de données et qu'aucun addon
 * ne revendique : soit une table neuve absente de table-map.php, soit un nom construit
 * dynamiquement. Les RÉGLAGES partagent ce préfixe, d'où la restriction au contexte d'appel — sans
 * elle, 61 faux positifs.
 *
 * CE QU'IL NE VOIT TOUJOURS PAS
 *
 *   - les couplages qui passent par la BASE de données : une disposition de thème qui référence un
 *     widget, un réglage qui nomme un module. Ils n'existent pas dans le code source ;
 *   - et surtout : **une annotation est une affirmation, pas une preuve**. Si quelqu'un écrit
 *     « couplage: c'est gardé » à tort, cet outil le croit. C'est `check-install-profiles.php` qui
 *     tranche : il installe chaque profil pour de vrai et frappe le site. Les deux sont
 *     complémentaires — l'un dit ce qui est censé être sûr, l'autre le vérifie.
 *
 * Usage
 * -----
 *   php tools/check-addon-coupling.php             code 1 si un couplage fatal n'est pas explicite
 *   php tools/check-addon-coupling.php --carte     imprime le graphe complet, sans juger
 *   php tools/check-addon-coupling.php --json      sortie machine
 */

require __DIR__.'/lib/outil.php';
require __DIR__.'/lib/depot.php';

[$o]    = nf_options(['carte' => FALSE, 'json' => FALSE]);
$racine = nf_racine();
$carte  = $o['carte'];
$json   = $o['json'];

/** Types de couplage dont l'absence de l'addon cible provoque une erreur fatale. */
const COUPLAGES_FATALS = ['table', 'classe'];

// ── 1. Table → addon propriétaire ─────────────────────────────────────────────
$map          = require __DIR__.'/lib/table-map.php';
$proprietaire = [];

foreach ($map['modules'] ?? [] as $module => $tables)
{
    foreach ((array) $tables as $table)
    {
        $proprietaire[$table] = $module;
    }
}

// Tout le reste de table-map (core, widgets, themes) appartient au cœur : jamais un couplage.
array_walk_recursive($map, static function ($valeur) use (&$proprietaire): void {
    if (is_string($valeur) && str_starts_with($valeur, 'nf_'))
    {
        $proprietaire[$valeur] ??= '';
    }
});

// ── 2. Addons présents et leurs déclarations ──────────────────────────────────
require_once $racine . '/neofrag/installer.php';
$declarations = \NF\NeoFrag\Installer::addon_declarations($racine);

/** nom d'addon → type, pour reconnaître module('x') / widget('x') / theme('x'). */
$addons_par_type = ['module' => [], 'widget' => [], 'theme' => []];

foreach ($declarations as $cle => $d)
{
    $addons_par_type[$d['type']][$d['name']] = TRUE;
}

// ── 3. Analyse d'un fichier PHP : couplages + lignes annotées ─────────────────

/**
 * Relève dans un fichier PHP tous les couplages vers un autre addon, et les lignes
 * portant une annotation « couplage: ». Commentaires et HTML inline sont ignorés par
 * construction : token_get_all ne les classe pas comme du code.
 *
 * @return array{couplages:array<int,array{kind:string,cible:string,detail:string,ligne:int}>,annotees:array<int,bool>}
 */
function coupling_analyse(string $fichier, array $proprietaire, array $addons_par_type): array
{
    $source = @file_get_contents($fichier);

    if ($source === FALSE)
    {
        return ['couplages' => [], 'annotees' => [], 'portees' => [], 'inconnues' => []];
    }

    $jetons    = @token_get_all($source) ?: [];
    $couplages = [];
    $annotees  = [];
    $portees   = [];   // addon cible => annoté pour tout le fichier
    $inconnues = [];   // littéraux nf_* dont aucun addon ne se déclare propriétaire

    // Jetons signifiants (hors espaces) pour reconnaître les motifs d'appel.
    $utiles = [];

    foreach ($jetons as $j)
    {
        if (is_array($j))
        {
            [$type, $texte, $ligne] = $j;

            if ($type === T_COMMENT || $type === T_DOC_COMMENT)
            {
                // « couplage(forum): raison » couvre TOUTES les références à `forum` dans le
                // fichier — pratique quand elles y sont étalées. « couplage: raison » sans cible
                // ne couvre que les lignes voisines, pour une référence isolée.
                if (preg_match_all('/couplage\(([a-z0-9_]+)\)\s*:/i', $texte, $m))
                {
                    foreach ($m[1] as $cible_annotee)
                    {
                        $portees[strtolower($cible_annotee)] = TRUE;
                    }
                }

                if (stripos($texte, 'couplage:') !== FALSE)
                {
                    $fin = $ligne + substr_count($texte, "\n");

                    for ($l = $ligne; $l <= $fin; $l++)
                    {
                        $annotees[$l] = TRUE;
                    }
                }

                continue;
            }

            if ($type === T_WHITESPACE)
            {
                continue;
            }

            $utiles[] = ['type' => $type, 'texte' => $texte, 'ligne' => $ligne];
            continue;
        }

        $utiles[] = ['type' => NULL, 'texte' => $j, 'ligne' => 0];
    }

    // Les appels du service-locator à reconnaître : nom => type d'addon visé.
    $locateurs = ['module' => 'module', 'widget' => 'widget', 'theme' => 'theme', 'model2' => 'module'];

    foreach ($utiles as $i => $t)
    {
        // ── classe : \NF\Modules\Forum\… ou \NF\Widgets\News\…
        if ($t['type'] !== NULL
            && (defined('T_NAME_FULLY_QUALIFIED') && $t['type'] === T_NAME_FULLY_QUALIFIED
                || defined('T_NAME_QUALIFIED') && $t['type'] === T_NAME_QUALIFIED))
        {
            if (preg_match('#NF\\\\(Modules|Widgets|Themes)\\\\([A-Za-z0-9_]+)#', $t['texte'], $m))
            {
                $type_cible = ['Modules' => 'module', 'Widgets' => 'widget', 'Themes' => 'theme'][$m[1]];
                $nom        = strtolower($m[2]);

                if (isset($addons_par_type[$type_cible][$nom]))
                {
                    $couplages[] = ['kind' => 'classe', 'cible' => $nom, 'cible_type' => $type_cible,
                                    'detail' => $t['texte'], 'ligne' => $t['ligne']];
                }
            }
        }

        if ($t['type'] !== T_CONSTANT_ENCAPSED_STRING && $t['type'] !== T_ENCAPSED_AND_WHITESPACE)
        {
            continue;
        }

        $litteral = trim($t['texte'], "'\"");

        // ── table : 'nf_x' seul ou suivi d'un alias ('nf_forum_topics t')
        $table = preg_split('/\s+/', trim($litteral))[0] ?? '';

        if (isset($proprietaire[$table]))
        {
            if ($proprietaire[$table] !== '')
            {
                $couplages[] = ['kind' => 'table', 'cible' => $proprietaire[$table], 'cible_type' => 'module',
                                'detail' => $table, 'ligne' => $t['ligne']];
            }
        }
        elseif (str_starts_with($table, 'nf_') && strlen($table) > 3)
        {
            // Un litteral en nf_* qu'aucun proprietaire ne reclame : soit une table NEUVE absente de
            // tools/lib/table-map.php, soit un nom construit dynamiquement — dans les deux cas le
            // couplage qu'il porte est invisible.
            //
            // MAIS les REGLAGES partagent ce prefixe ('nf_default_theme', 'nf_moderation_enabled').
            // On ne retient donc le litteral que s'il est passe a une methode de base de donnees :
            // un reglage n'apparait jamais la, il se lit en propriete ($this->config->nf_x).
            $appelant = ($utiles[$i - 2] ?? NULL);

            if ($appelant && $appelant['type'] === T_STRING
                && in_array(strtolower($appelant['texte']),
                            ['from', 'join', 'insert', 'update', 'delete', 'replace', 'table_exists', 'truncate'], TRUE)
                && ($utiles[$i - 1]['texte'] ?? '') === '(')
            {
                $inconnues[] = ['table' => $table, 'ligne' => $t['ligne']];
            }
        }

        // Motif d'appel : <nom> ( '<litteral>' — on regarde les deux jetons précédents.
        $avant2 = $utiles[$i - 2] ?? NULL;
        $avant1 = $utiles[$i - 1] ?? NULL;

        if (!$avant1 || $avant1['texte'] !== '(' || !$avant2 || $avant2['type'] !== T_STRING)
        {
            continue;
        }

        $appel = strtolower($avant2['texte']);

        // ── service : module('x'), widget('x'), theme('x'), model2('x')
        if (isset($locateurs[$appel]))
        {
            $type_cible = $locateurs[$appel];
            $nom        = strtolower($litteral);

            if (isset($addons_par_type[$type_cible][$nom]))
            {
                $couplages[] = ['kind' => 'service', 'cible' => $nom, 'cible_type' => $type_cible,
                                'detail' => $appel . "('" . $nom . "')", 'ligne' => $t['ligne']];
            }
        }

        // ── route : url('news/...') vers un autre module
        if ($appel === 'url')
        {
            $premier = strtolower(explode('/', ltrim($litteral, '/'))[0] ?? '');

            if ($premier !== '' && isset($addons_par_type['module'][$premier]))
            {
                $couplages[] = ['kind' => 'route', 'cible' => $premier, 'cible_type' => 'module',
                                'detail' => "url('" . $litteral . "')", 'ligne' => $t['ligne']];
            }
        }
    }

    return ['couplages' => $couplages, 'annotees' => $annotees, 'portees' => $portees, 'inconnues' => $inconnues];
}

/**
 * Analyse d'un fichier .sql : les tables qu'il nomme et qui appartiennent a un AUTRE addon.
 *
 * Les commentaires SQL (-- et #) sont retires avant l'analyse, pour la meme raison que le
 * tokenizer cote PHP : une table citee en commentaire n'est pas un couplage.
 */
function coupling_analyse_sql(string $fichier, array $proprietaire): array
{
    $source = @file_get_contents($fichier);

    if ($source === FALSE)
    {
        return ['couplages' => [], 'annotees' => [], 'portees' => [], 'inconnues' => []];
    }

    $couplages = [];
    $annotees  = [];
    $portees   = [];

    foreach (explode("
", $source) as $i => $ligne)
    {
        $numero = $i + 1;

        if (stripos($ligne, 'couplage:') !== FALSE)
        {
            $annotees[$numero] = TRUE;
        }

        if (preg_match_all('/couplage\(([a-z0-9_]+)\)\s*:/i', $ligne, $m))
        {
            foreach ($m[1] as $cible)
            {
                $portees[strtolower($cible)] = TRUE;
            }
        }

        $code = preg_replace('/(--|#).*$/', '', $ligne) ?? '';

        if (!preg_match_all('/`?(nf_[a-z0-9_]+)`?/i', $code, $m))
        {
            continue;
        }

        foreach (array_unique($m[1]) as $table)
        {
            if (!empty($proprietaire[$table]))
            {
                $couplages[] = ['kind' => 'table', 'cible' => $proprietaire[$table], 'cible_type' => 'module',
                                'detail' => $table . ' (SQL)', 'ligne' => $numero];
            }
        }
    }

    return ['couplages' => $couplages, 'annotees' => $annotees, 'portees' => $portees, 'inconnues' => []];
}

/**
 * Analyse d'un fichier .js : les chaines qui nomment la table ou la route d'un AUTRE addon.
 *
 * Le JS d'un addon appelle souvent une route d'un autre module ('/news/ajax/...'), et parfois cite
 * une table dans un payload. Ce n'est jamais fatal cote serveur — au pire un appel qui repond 404 —
 * mais c'est un couplage reel, et il etait totalement invisible jusqu'ici.
 */
function coupling_analyse_js(string $fichier, array $proprietaire, array $addons_par_type): array
{
    $source = @file_get_contents($fichier);

    if ($source === FALSE)
    {
        return ['couplages' => [], 'annotees' => [], 'portees' => [], 'inconnues' => []];
    }

    $couplages = [];
    $annotees  = [];
    $portees   = [];

    foreach (explode("
", $source) as $i => $ligne)
    {
        $numero = $i + 1;

        if (stripos($ligne, 'couplage:') !== FALSE)
        {
            $annotees[$numero] = TRUE;
        }

        if (preg_match_all('/couplage\(([a-z0-9_]+)\)\s*:/i', $ligne, $m))
        {
            foreach ($m[1] as $cible)
            {
                $portees[strtolower($cible)] = TRUE;
            }
        }

        // Chaînes entre quotes simples, doubles, ou gabarits.
        if (!preg_match_all('/[\'"`]([^\'"`\n]{2,120})[\'"`]/', $ligne, $m))
        {
            continue;
        }

        foreach ($m[1] as $litteral)
        {
            // Une table nommee dans le JS.
            if (preg_match('/\b(nf_[a-z0-9_]+)\b/i', $litteral, $t) && !empty($proprietaire[$t[1]]))
            {
                $couplages[] = ['kind' => 'table', 'cible' => $proprietaire[$t[1]], 'cible_type' => 'module',
                                'detail' => $t[1] . ' (JS)', 'ligne' => $numero];
                continue;
            }

            // Une route vers un autre module : '/news/...', 'news/ajax/...'
            $chemin = ltrim(trim($litteral), '/');

            if (!str_contains($chemin, '/'))
            {
                continue;
            }

            $premier = strtolower(explode('/', $chemin)[0]);

            // Prefixe de langue eventuel : /fr/news/...
            if (strlen($premier) === 2 && preg_match('/^[a-z]{2}$/', $premier))
            {
                $premier = strtolower(explode('/', $chemin)[1] ?? '');
            }

            if ($premier !== '' && isset($addons_par_type['module'][$premier]))
            {
                $couplages[] = ['kind' => 'route', 'cible' => $premier, 'cible_type' => 'module',
                                'detail' => "'" . $litteral . "' (JS)", 'ligne' => $numero];
            }
        }
    }

    return ['couplages' => $couplages, 'annotees' => $annotees, 'portees' => $portees, 'inconnues' => []];
}

/** Une annotation « couplage: » protège-t-elle cette ligne ? (même ligne, ou l'une des 4 au-dessus) */
function coupling_annote(array $annotees, int $ligne): bool
{
    for ($l = $ligne; $l >= $ligne - 4; $l--)
    {
        if (isset($annotees[$l]))
        {
            return TRUE;
        }
    }

    return FALSE;
}

// ── 4. Parcours de tous les addons ────────────────────────────────────────────
$references = [];   // "type:addon" => "type:cible" => kind => liste de sites
$inconnues  = [];   // littéral nf_* dont aucun addon ne se déclare propriétaire => où il est cité
$examines   = 0;

foreach (['module' => 'modules', 'widget' => 'widgets', 'theme' => 'themes'] as $type => $dossier)
{
    foreach (glob($racine . '/' . $dossier . '/*', GLOB_ONLYDIR) ?: [] as $dir)
    {
        $addon  = basename($dir);
        $source = $type . ':' . $addon;

        // Sans exclusion ni filtre des minifiés ici : le SQL et le PHP d'un addon se lisent tous,
        // et le JS est filtré juste après, selon sa propre règle.
        foreach (nf_fichiers([nf_relatif($dir)], ['php', 'sql', 'js'], [], FALSE) as $relatif => $chemin)
        {
            $extension = pathinfo($chemin, PATHINFO_EXTENSION);

            // Les bibliotheques tierces minifiees ne sont pas notre code : les analyser noierait
            // le rapport sous des faux positifs (noms de trois lettres, chemins arbitraires).
            if ($extension === 'js' && preg_match('/\.min\.js$|\/(vendor|lib|libs)\//i', '/' . $relatif))
            {
                continue;
            }

            $examines++;

            // Le SQL d'installation d'un addon peut porter une cle etrangere vers la table d'un
            // autre : c'est une dependance d'ORDRE D'INSTALLATION, fatale si l'autre n'est pas la.
            $analyse = match ($extension) {
                'sql'   => coupling_analyse_sql($chemin, $proprietaire),
                'js'    => coupling_analyse_js($chemin, $proprietaire, $addons_par_type),
                default => coupling_analyse($chemin, $proprietaire, $addons_par_type),
            };

            foreach ($analyse['inconnues'] ?? [] as $inc)
            {
                $inconnues[$inc['table']][] = $relatif . ':' . $inc['ligne'];
            }

            foreach ($analyse['couplages'] as $cpl)
            {
                $cible = $cpl['cible_type'] . ':' . $cpl['cible'];

                // Un addon qui se référence lui-même n'est pas un couplage. Un module et son widget
                // homonyme non plus : ils sont livrés et retirés ensemble par construction.
                if ($cpl['cible'] === $addon)
                {
                    continue;
                }

                // Le cœur n'est jamais un couplage : il est toujours là.
                if (!empty($declarations[$cible]['core']))
                {
                    continue;
                }

                $references[$source][$cible][$cpl['kind']][] = [
                    'fichier' => $relatif,
                    'ligne'   => $cpl['ligne'],
                    'detail'  => $cpl['detail'],
                    'annote'  => isset($analyse['portees'][$cpl['cible']])
                                 || coupling_annote($analyse['annotees'], $cpl['ligne']),
                ];
            }
        }
    }
}

// ── 5. Confrontation aux déclarations ─────────────────────────────────────────
$erreurs = $avertissements = [];
$resume  = [];

foreach ($references as $source => $par_cible)
{
    $decl = $declarations[$source] ?? ['core' => FALSE, 'requires' => []];

    foreach ($par_cible as $cible => $par_kind)
    {
        $nom_cible = explode(':', $cible, 2)[1];
        $declare   = in_array($nom_cible, $decl['requires'] ?? [], TRUE);
        $fatals    = [];

        foreach (COUPLAGES_FATALS as $k)
        {
            $fatals = array_merge($fatals, $par_kind[$k] ?? []);
        }

        $nus = array_values(array_filter($fatals, static fn (array $s): bool => !$s['annote']));

        $resume[] = [
            'addon'   => $source,
            'cible'   => $cible,
            'moyens'  => array_map('count', $par_kind),
            'fatals'  => count($fatals),
            'nus'     => count($nus),
            'declare' => $declare,
        ];

        if ($declare)
        {
            if (!empty($decl['core']) && empty($declarations[$cible]['core']))
            {
                $erreurs[] = sprintf(
                    "%s appartient au cœur et dépend DUREMENT de « %s », qui n'en fait pas partie.\n"
                    . "        Le paquet ne serait plus divisible — c'est le défaut qui a produit le 500 de juin 2026.",
                    $source,
                    $cible
                );
            }

            continue;
        }

        if (!$nus)
        {
            continue;
        }

        $exemples = array_slice($nus, 0, 3);
        $lignes   = implode("\n          ", array_map(
            static fn (array $s): string => sprintf('%s:%d  %s', $s['fichier'], $s['ligne'], $s['detail']),
            $exemples
        ));

        $par_moyen = [];

        foreach (COUPLAGES_FATALS as $k)
        {
            if (!empty($par_kind[$k]))
            {
                $par_moyen[] = count($par_kind[$k]) . ' ' . $k . (count($par_kind[$k]) > 1 ? 's' : '');
            }
        }

        $garde = !empty($par_kind['service'])
            ? sprintf("\n        (le code y appelle déjà %s — si c'est la garde, annotez-la)", $par_kind['service'][0]['detail'])
            : '';

        $erreurs[] = sprintf(
            "%s dépend de « %s » (%s) sans que ce couplage soit explicite.%s\n"
            . "        Soit c'est une dépendance dure → ajouter '%s' à 'requires' dans son __info() ;\n"
            . "        soit le code s'en passe → annoter la ligne d'un commentaire « couplage: <raison> ».\n"
            . "          %s%s",
            $source,
            $cible,
            implode(', ', $par_moyen),
            $garde,
            $nom_cible,
            $lignes,
            count($nus) > 3 ? sprintf("\n          … et %d autre(s)", count($nus) - 3) : ''
        );
    }
}

// ── 6. Cycles du graphe RÉEL (couplages fatals seulement) ─────────────────────
$arcs = [];

foreach ($resume as $r)
{
    if ($r['fatals'] > 0)
    {
        $arcs[$r['addon']][$r['cible']] = TRUE;
    }
}

/** Une dépendance est-elle DURE (déclarée dans requires) dans ce sens ? */
$dur = static function (string $de, string $vers) use ($declarations): bool {
    $nom = explode(':', $vers, 2)[1] ?? '';

    return in_array($nom, $declarations[$de]['requires'] ?? [], TRUE);
};

foreach ($arcs as $a => $cibles)
{
    foreach (array_keys($cibles) as $b)
    {
        if (!isset($arcs[$b][$a]) || strcmp($a, $b) >= 0)
        {
            continue;
        }

        // Un cycle DUR des deux côtés est bloquant : aucun des deux addons ne peut être installé
        // sans l'autre, donc ce ne sont plus deux addons mais un seul mal découpé. Si UN des deux
        // sens est mou (gardé et annoté), le cycle est résolu : un ordre d'installation existe.
        if ($dur($a, $b) && $dur($b, $a))
        {
            $erreurs[] = sprintf(
                "« %s » et « %s » dépendent DUREMENT l'un de l'autre : aucun des deux ne peut être
"
                . "        installé sans l'autre. Garder le côté purement affichage — celui qui ne fait
"
                . "        qu'AFFICHER l'autre — et l'annoter « couplage: », pour qu'un ordre existe.",
                $a,
                $b
            );

            continue;
        }

        $avertissements[] = sprintf(
            "« %s » et « %s » se lisent mutuellement, mais un sens est gardé : un ordre
"
            . "        d'installation existe. Signalé pour mémoire, rien à corriger.",
            $a,
            $b
        );
    }
}

// ── 7. Compte rendu ───────────────────────────────────────────────────────────
usort($resume, static fn (array $a, array $b): int => [$a['addon'], $a['cible']] <=> [$b['addon'], $b['cible']]);

if ($json)
{
    echo json_encode([
        'fichiers_examines' => $examines,
        'tables_classees'   => count($proprietaire),
        'couplages'         => $resume,
        'erreurs'           => $erreurs,
        'avertissements'    => $avertissements,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), "\n";

    exit($erreurs ? NF_ECHEC : NF_OK);
}

if ($carte)
{
    printf("Couplage réel entre addons — %d fichiers PHP analysés, %d tables classées.\n\n", $examines, count($proprietaire));
    printf("  %-24s %-18s %-34s %s\n", 'ADDON', 'DÉPEND DE', 'PAR QUEL MOYEN', 'ÉTAT');
    printf("  %s\n", str_repeat('-', 104));

    foreach ($resume as $r)
    {
        $moyens = [];

        foreach ($r['moyens'] as $k => $n)
        {
            $moyens[] = $n . ' ' . $k . ($n > 1 ? 's' : '');
        }

        $etat = $r['declare']
            ? 'dépendance DURE déclarée'
            : ($r['nus'] ? sprintf('%d NON explicite(s)', $r['nus']) : ($r['fatals'] ? 'couplage mou annoté' : 'tolérant'));

        printf("  %-24s %-18s %-34s %s\n", $r['addon'], $r['cible'], implode(', ', $moyens), $etat);
    }

    nf_ok(sprintf('%d couplage(s) entre addons (carte, sans jugement)', count($resume)));
}

printf("%d fichiers analysés (php, sql, js) · %d tables classées · %d couplage(s) entre addons.\n",
    $examines, count($proprietaire), count($resume));

if ($inconnues)
{
    printf("\n  %d littéral(aux) « nf_… » dont aucun addon ne se déclare propriétaire.\n", count($inconnues));
    echo "  Soit une table neuve absente de tools/lib/table-map.php, soit un nom construit dynamiquement :\n";
    echo "  dans les deux cas le couplage qu'elle porte est invisible. Classez-la.\n";

    foreach (array_slice($inconnues, 0, 10, TRUE) as $table => $ou)
    {
        printf("    %-30s %s%s\n", $table, $ou[0], count($ou) > 1 ? sprintf(' (+%d)', count($ou) - 1) : '');
    }

    if (count($inconnues) > 10)
    {
        printf("    … et %d autre(s)\n", count($inconnues) - 10);
    }

    echo "\n";
}

foreach ($avertissements as $a)
{
    echo "  ⚠ {$a}\n";
}

if ($erreurs)
{
    echo "\n";

    foreach ($erreurs as $e)
    {
        echo "  ✗ {$e}\n\n";
    }

    nf_echec(sprintf('%d couplage(s) fatal(s) non explicite(s) — voir la règle en tête de ce fichier', count($erreurs)));
}

nf_ok('tous les couplages fatals entre addons sont explicites');
