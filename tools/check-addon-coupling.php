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
 * Le 2026-10-04, il a appris à lire ce qui ne passe pas par le code : la BASE livrée (une mise en
 * page qui pose le widget d'un autre addon, un réglage qui désigne un module, les lignes d'un autre
 * addon dans le seed du cœur) et les traductions faites au nom d'un autre addon. Sa première lecture
 * a trouvé le seed « cœur seul » qui posait le widget `news` dans la colonne latérale de nebula, et
 * portait les réglages, la permission et les gabarits d'e-mail du forum.
 *
 * LES MOYENS DE DÉPENDRE D'UN AUTRE ADDON, PAR CE QUI ARRIVE QUAND IL MANQUE
 *
 * FATALS — la page tombe :
 *
 *   table        Lire une table qui appartient à un autre addon, en PHP ou dans un SQL livré (une
 *                clé étrangère est une dépendance d'ordre d'installation).
 *                → « Table doesn't exist ». C'est le défaut de juin.
 *
 *   classe       Nommer une classe d'un autre addon (\NF\Modules\X\…, \NF\Widgets\X\…).
 *                → « Class not found ».
 *
 *   langue       Faire traduire un texte PAR un autre addon : module('x')->lang('…').
 *                → module('x') rend NULL : « Call to a member function lang() on null ».
 *
 * VISIBLES — rien ne tombe, mais le site montre le manque :
 *
 *   disposition  Une mise en page livrée pose le widget d'un autre addon : l'install() d'un thème
 *                (`'widget' => 'news'`), ou les lignes `nf_widgets` d'un SQL livré.
 *                → la case reste vide, et sa ligne de `nf_widgets` orpheline.
 *
 *   reglage      Un réglage livré DÉSIGNE un addon (cf. REGLAGES_QUI_DESIGNENT).
 *                → l'accueil répond 404, ou le site retombe sur un thème de secours.
 *
 *   donnee       Un SQL livré porte les lignes d'un autre addon : ses réglages (`forum_…`), ses
 *                permissions (`forum.…`), ses gabarits d'e-mail, la mise en page d'un autre thème.
 *                → des lignes orphelines ; les gabarits s'affichent dans l'administration.
 *
 * TOLÉRANTS — rien ne se voit :
 *
 *   service      Demander l'addon au service-locator : module('x'), widget('x'), theme('x'), model2('x').
 *                → rend NULL si l'addon manque. C'est d'ailleurs la BONNE façon de se garder : le voir
 *                  à côté d'un couplage `table` est le signe que le code sait se protéger.
 *
 *   surcharge    Un thème qui remplace un fichier d'un autre addon (`themes/<t>/overrides/modules/<x>/…`).
 *                → la surcharge n'est lue que si l'addon est là ; sans lui, elle dort.
 *
 * COSMÉTIQUE :
 *
 *   route        Une adresse vers un autre addon : url('news/…'), `'url' => 'news'`, un lien du menu
 *                livré, la page qu'une disposition décore (`forum/*`), une route appelée en JS.
 *                → un lien mort (404), pas une erreur serveur.
 *
 * LA RÈGLE
 *
 * Tout couplage FATAL ou VISIBLE vers un addon hors du cœur doit être rendu explicite :
 *
 *   1. déclaré dans `'requires' => [...]` du `__info()` — c'est une dépendance DURE : sans l'autre
 *      addon, la fonctionnalité casse et l'installeur doit l'embarquer (jamais depuis le cœur) ;
 *   2. ou annoté à l'endroit de la référence par un commentaire contenant « couplage: » suivi d'une
 *      raison — c'est une dépendance MOLLE : le code se protège (garde `table_exists`, `try/catch`,
 *      branche inatteignable sans l'addon) et se contente de moins ;
 *   3. ou gardé PAR CONSTRUCTION, ce que l'outil reconnaît seul :
 *        - un appel nul-sûr : module('x')?->lang('…') ;
 *        - tout ce qu'une surcharge de thème fait vers l'addon qu'elle remplace : elle ne s'exécute
 *          qu'avec lui ;
 *        - un widget posé sur les pages de son module homonyme (`forum` sur `forum/*`) : la
 *          disposition ne s'affiche qu'avec le module, qui est livré et retiré avec son widget ;
 *        - un SQL d'install/ hors du socle (SQL_SOCLE) : l'installeur joue toujours le schéma et le
 *          seed, et les autres — contenus, démonstration, documentation — au mieux, sous try/catch ou
 *          si leur table existe.
 *
 * Un couplage FATAL non explicite fait ÉCHOUER le contrôle. Le défaut est donc l'inverse de celui de
 * juin : un couplage nouveau et silencieux est une erreur de build.
 *
 * Un couplage VISIBLE non explicite est listé « À TRANCHER », sans échouer : ceux qu'a trouvés la
 * règle à sa création attendent une décision — le déclarer, ne le livrer que si l'autre addon est
 * installé, ou l'accepter tel quel en l'annotant. Leur nombre, compté par paire d'addons, est tenu
 * par un cliquet (A_TRANCHER_PLAFOND) : une paire NOUVELLE au-delà fait échouer — un couplage visible
 * ne s'ajoute pas plus en silence qu'un fatal. Chaque paire tranchée fait baisser le compte : baisser
 * le plafond avec elle.
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
 * commentaire. `token_get_all()` est le propre analyseur de PHP — il ne voit que du vrai code. Le
 * SQL est lu de même : chaînes et commentaires mis de côté avant d'y chercher une table — une page de
 * documentation qui CITE `nf_news` n'est pas un couplage.
 *
 * CE QU'IL LIT
 *
 *   - le **PHP** de l'addon (code et vues), par le tokenizer ;
 *   - son **SQL** d'installation et de migration — une clé étrangère vers la table d'un autre addon
 *     est une dépendance d'ordre d'installation, tout aussi fatale ;
 *   - son **JavaScript**, hors bibliothèques minifiées : le JS appelle souvent la route d'un autre
 *     module, et ce couplage était totalement invisible ;
 *   - les **SQL livrés** à la racine de `install/` — leurs tables, et les LIGNES qui nomment un addon :
 *     `nf_widgets` et `nf_dispositions`, `nf_settings`, `nf_role_permissions`, `nf_email_templates` ;
 *   - les **traductions** françaises (`langs/fr.php`) de chaque addon et du cœur.
 *
 * Il signale aussi tout littéral en `nf_…` passé à une méthode de base de données et qu'aucun addon
 * ne revendique : soit une table neuve absente de table-map.php, soit un nom construit
 * dynamiquement. Les RÉGLAGES partagent ce préfixe, d'où la restriction au contexte d'appel — sans
 * elle, 61 faux positifs.
 *
 * LES TRADUCTIONS : CE QUI EST UN EMPRUNT, ET CE QUI N'EN EST PAS
 *
 * `$this->lang('…')` cherche son texte au nom de l'addon APPELANT, et nulle part ailleurs : dans ses
 * propres `langs/` (ou leur surcharge, `overrides/<type>s/<addon>/langs/` puis la même sous
 * `themes/<thème actif>/`), puis dans ceux du cœur (`neofrag/langs/`) — cf. Addon::__load(),
 * Language::__invoke(). Jamais dans les `langs/` d'un autre addon, ni dans ceux du thème. Un texte
 * qu'un addon ne trouve que chez un autre n'est donc PAS un couplage : il n'est traduit nulle part,
 * que l'autre soit là ou non — c'est une clé manquante, que `check-langs` refuse déjà. Les VRAIS
 * emprunts sont les textes traduits au nom d'un autre addon :
 *
 *   - `module('x')->lang('…')` — couplage `langue`, fatal sans garde ;
 *   - la vue qu'un thème surcharge s'exécute au nom de l'addon surchargé : ses textes se cherchent
 *     chez LUI. Emprunt gardé par construction — mais un texte qu'il ne trouve ni chez lui, ni chez le
 *     cœur, ni dans les `langs/` de la surcharge, n'est jamais traduit : celui-là fait échouer, car
 *     `check-langs`, qui le range au thème, ne peut pas le voir.
 *
 * CE QU'IL NE VOIT TOUJOURS PAS
 *
 *   - un `service` CHAÎNÉ : `module('x')->model()` sans garde est aussi fatal que lire la table, mais
 *     reste classé tolérant — la garde est souvent une ligne plus haut, ou une boucle qui ne tourne
 *     pas sans l'addon, et l'outil ne sait pas la lire ;
 *   - un nom CALCULÉ : `widget($nom)`, `'widget' => $nom`, un module traduisant tenu dans une
 *     variable (`$m = module('x'); $m->lang(…)`), une table construite par concaténation ;
 *   - un texte qu'un addon CONFIE à un autre pour qu'il le traduise à son nom (un libellé passé à la
 *     bibliothèque d'un autre module) ;
 *   - les réglages, permissions et gabarits qu'un addon lit ou écrit chez un autre DEPUIS SON CODE
 *     (`$this->config->forum_…`) : seuls ceux d'un SQL livré sont relevés ;
 *   - un réglage livré dont le nom ne commence pas par celui de son addon (`images_per_page`, de
 *     gallery) : il est rattaché à son addon par ce préfixe, faute de mieux ;
 *   - le CONTENU des SQL livrés : un lien vers `/forum` écrit dans une page de wiki ou une actualité ;
 *   - les addons de `addons/` (langues, authentificateurs) : ils ne sont pas lus ;
 *   - que l'installeur joue vraiment au mieux les SQL d'install/ hors du socle : c'est pris pour
 *     acquis, comme une annotation ;
 *   - et surtout : **une annotation est une affirmation, pas une preuve**. Si quelqu'un écrit
 *     « couplage: c'est gardé » à tort, cet outil le croit. C'est `check-install-profiles.php` qui
 *     tranche : il installe chaque profil pour de vrai et frappe le site. Les deux sont
 *     complémentaires — l'un dit ce qui est censé être sûr, l'autre le vérifie.
 *
 * Usage
 * -----
 *   php tools/check-addon-coupling.php             code 1 si un couplage fatal n'est pas explicite, ou si
 *                                                  un couplage visible dépasse le plafond des cas à trancher
 *   php tools/check-addon-coupling.php --carte     imprime le graphe complet, sans juger
 *   php tools/check-addon-coupling.php --json      sortie machine
 *   php tools/check-addon-coupling.php --epreuve   ne joue QUE l'épreuve à l'envers
 *
 * L'épreuve à l'envers tourne de toute façon avant chaque analyse : des couplages plantés doivent être
 * vus, des écritures sûres doivent passer — sans quoi le contrôle refuse de conclure.
 */

require __DIR__.'/lib/outil.php';
require __DIR__.'/lib/depot.php';
require __DIR__.'/lib/sql.php';
require __DIR__.'/lib/langues.php';

[$o]    = nf_options(['carte' => FALSE, 'json' => FALSE, 'epreuve' => FALSE]);
$racine = nf_racine();
$carte  = $o['carte'];
$json   = $o['json'];

/** Types de couplage dont l'absence de l'addon cible fait tomber la page. */
const COUPLAGES_FATALS = ['table', 'classe', 'langue'];

/** Types de couplage qui ne cassent rien, mais que le site MONTRE. */
const COUPLAGES_VISIBLES = ['disposition', 'reglage', 'donnee'];

/**
 * Le cliquet des couplages visibles non explicites, compté par paire « addon → addon ». Fixé le
 * 2026-10-04, à la création de la règle : chaque paire listée « À TRANCHER » attend une décision.
 * Une paire tranchée fait baisser le compte — baisser alors ce plafond avec lui ; une paire NOUVELLE
 * au-delà fait échouer.
 */
const A_TRANCHER_PLAFOND = 19;

/**
 * Les réglages dont la VALEUR désigne un addon, et le type de cet addon.
 *
 *   nf_default_page   l'accueil : Output charge le module de son premier segment, sinon retombe sur
 *                     `pages`, qui ne connaît pas la page demandée — l'accueil répond 404 ;
 *   nf_default_theme  le thème : Output retombe sur le thème du produit, et l'écrit au journal.
 */
const REGLAGES_QUI_DESIGNENT = ['nf_default_page' => 'module', 'nf_default_theme' => 'theme'];

/** Les SQL d'install/ que l'installeur joue TOUJOURS, avant tout choix de profil : le socle, donc le cœur. */
const SQL_SOCLE = ['schema.sql', 'seed.sql'];

/** Le nom d'un moyen de couplage dans un message, au singulier puis au pluriel. */
const LIBELLES = [
    'table'       => ['table', 'tables'],
    'classe'      => ['classe', 'classes'],
    'langue'      => ['traduction empruntée', 'traductions empruntées'],
    'disposition' => ['disposition', 'dispositions'],
    'reglage'     => ['réglage', 'réglages'],
    'donnee'      => ['donnée livrée', 'données livrées'],
    'service'     => ['service', 'services'],
    'surcharge'   => ['surcharge', 'surcharges'],
    'route'       => ['route', 'routes'],
];

/** Ce que le site montre quand l'addon cible manque, par type de couplage visible. */
const CONSEQUENCES = [
    'disposition' => 'la case de la mise en page reste vide, sa ligne de nf_widgets orpheline',
    'reglage'     => 'le réglage désigne un absent — accueil en 404, ou thème de secours',
    'donnee'      => 'ses lignes restent orphelines',
];

/** Ce qu'un gabarit d'e-mail orphelin ajoute : la page Emails de l'administration les liste tous, module absent ou non. */
const CONSEQUENCE_GABARIT = ", et ses gabarits d'e-mail restent listés dans l'administration";

/** « 3 tables », « 1 traduction empruntée ». */
function coupling_compte(string $kind, int $n): string
{
    return $n.' '.(LIBELLES[$kind][$n > 1 ? 1 : 0] ?? $kind);
}

// ── 1. Les briques de l'analyse ───────────────────────────────────────────────

/** La valeur d'un littéral PHP tel que le tokenizer le rend (`'a\'b'` → `a'b`). */
function coupling_litteral(string $jeton): string
{
    if (strlen($jeton) >= 2 && $jeton[0] === "'" && substr($jeton, -1) === "'")
    {
        return str_replace(['\\\\', "\\'"], ['\\', "'"], substr($jeton, 1, -1));
    }

    if (strlen($jeton) >= 2 && $jeton[0] === '"' && substr($jeton, -1) === '"')
    {
        return stripcslashes(substr($jeton, 1, -1));
    }

    return $jeton;
}

/** Le module qu'une adresse ou une page de disposition désigne (`news/_news/*` → `news`), ou NULL. */
function coupling_module_de_route(string $chemin, array $addons): ?string
{
    $premier = str_replace('-', '_', strtolower(explode('/', ltrim(trim($chemin), '/'))[0]));

    return $premier !== '' && isset($addons['module'][$premier]) ? $premier : NULL;
}

/**
 * La garde par construction d'un widget posé sur une page : sur les pages de son module homonyme
 * (`forum` sur `forum/*`), la disposition ne s'affiche qu'avec le module, livré avec son widget.
 */
function coupling_garde_de_page(string $widget, ?string $page, array $addons): ?string
{
    if ($page === NULL || coupling_module_de_route($page, $addons) !== $widget)
    {
        return NULL;
    }

    return sprintf('posé sur les pages de « %s », son module homonyme', $widget);
}

/**
 * L'addon à qui appartient une donnée nommée par son préfixe (`forum_topics_per_page` → module forum).
 * Le préfixe le plus long l'emporte ; à longueur égale, le module avant le widget, le widget avant le thème.
 *
 * @return array{0: string, 1: string}|null  [type, nom]
 */
function coupling_proprietaire_du_nom(string $nom, array $addons): ?array
{
    $meilleur = NULL;

    foreach (['module', 'widget', 'theme'] as $type)
    {
        foreach (array_keys($addons[$type] ?? []) as $addon)
        {
            $addon = (string) $addon;

            if (str_starts_with($nom, $addon.'_') && ($meilleur === NULL || strlen($addon) > strlen($meilleur[1])))
            {
                $meilleur = [$type, $addon];
            }
        }
    }

    return $meilleur;
}

/**
 * Un texte SQL découpé, à longueur égale : `sans_commentaires` (les commentaires blanchis, les
 * chaînes intactes — pour lire les lignes livrées), `code` (chaînes ET commentaires blanchis — pour
 * y chercher une table), et les commentaires eux-mêmes, par ligne de début (pour les annotations).
 * Les sauts de ligne restent en place : les numéros de ligne restent justes.
 *
 * @return array{sans_commentaires: string, code: string, commentaires: array<int, string>}
 */
function coupling_sql_decouper(string $sql): array
{
    $n            = strlen($sql);
    $sans         = $sql;
    $code         = $sql;
    $commentaires = [];
    $ligne        = 1;

    for ($i = 0; $i < $n; $i++)
    {
        $c = $sql[$i];

        if ($c === "\n")
        {
            $ligne++;
            continue;
        }

        // Une chaîne : son contenu disparaît du code. `''` et `\'` restent dedans.
        if ($c === "'" || $c === '"')
        {
            for ($i++; $i < $n && $sql[$i] !== $c; $i++)
            {
                if ($sql[$i] === '\\' && $i + 1 < $n)
                {
                    $code[$i] = ' ';
                    $i++;
                }

                if ($sql[$i] === "\n")
                {
                    $ligne++;
                    continue;
                }

                $code[$i] = ' ';
            }

            continue;
        }

        $bloc = $c === '/' && ($sql[$i + 1] ?? '') === '*';

        if ($c === '#' || ($c === '-' && ($sql[$i + 1] ?? '') === '-') || $bloc)
        {
            $fin = $bloc ? strpos($sql, '*/', $i + 2) : strpos($sql, "\n", $i);
            $fin = $fin === FALSE ? $n : ($bloc ? $fin + 2 : $fin);

            $commentaires[$ligne] = ($commentaires[$ligne] ?? '').substr($sql, $i, $fin - $i);

            for ($j = $i; $j < $fin; $j++)
            {
                if ($sql[$j] !== "\n")
                {
                    $sans[$j] = ' ';
                    $code[$j] = ' ';
                }
            }

            $ligne += substr_count($sql, "\n", $i, $fin - $i);
            $i      = $fin - 1;
        }
    }

    return ['sans_commentaires' => $sans, 'code' => $code, 'commentaires' => $commentaires];
}

/** Une analyse vide : la forme commune de ce que rendent les trois analyseurs. */
function coupling_vide(): array
{
    return ['couplages' => [], 'annotees' => [], 'portees' => [], 'inconnues' => [], 'introuvables' => []];
}

// ── 2. Analyse d'un fichier PHP ───────────────────────────────────────────────

/**
 * Relève dans un source PHP tous les couplages vers un autre addon, et les lignes portant une
 * annotation « couplage: ». Commentaires et HTML inline sont ignorés par construction :
 * token_get_all ne les classe pas comme du code.
 *
 * `$ctx` porte le produit (cf. coupling_produit()) et, pour un fichier de surcharge, l'addon
 * surchargé (`surcharge`), au nom de qui ses textes se traduisent.
 */
function coupling_analyse(string $source, array $ctx): array
{
    $r      = coupling_vide();
    $jetons = @token_get_all($source) ?: [];
    $addons = $ctx['addons'];

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
                        $r['portees'][strtolower($cible_annotee)] = TRUE;
                    }
                }

                if (stripos($texte, 'couplage:') !== FALSE)
                {
                    $fin = $ligne + substr_count($texte, "\n");

                    for ($l = $ligne; $l <= $fin; $l++)
                    {
                        $r['annotees'][$l] = TRUE;
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
    $locateurs  = ['module' => 'module', 'widget' => 'widget', 'theme' => 'theme', 'model2' => 'module'];
    $fleches    = [T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR];
    $pages      = [];   // les ->set('<page>', …) ouverts : la page de la disposition en cours
    $profondeur = 0;
    $surcharge  = $ctx['surcharge'] ?? NULL;

    foreach ($utiles as $i => $t)
    {
        $avant1 = $utiles[$i - 1] ?? NULL;
        $avant2 = $utiles[$i - 2] ?? NULL;

        // ── la page de disposition en cours : $dispositions->set('forum/*', 'Zone', …)
        if ($t['type'] === NULL && $t['texte'] === '(')
        {
            $profondeur++;

            if ($avant1 && $avant1['type'] === T_STRING && strtolower($avant1['texte']) === 'set'
                && $avant2 && in_array($avant2['type'], $fleches, TRUE))
            {
                $argument = $utiles[$i + 1] ?? NULL;
                $page     = $argument && $argument['type'] === T_CONSTANT_ENCAPSED_STRING && ($utiles[$i + 2]['texte'] ?? '') === ','
                    ? coupling_litteral($argument['texte'])
                    : NULL;

                $pages[] = ['page' => $page, 'profondeur' => $profondeur];

                // La page décorée est celle d'un autre module : un lien vers lui, sans plus.
                if ($page !== NULL && ($module = coupling_module_de_route($page, $addons)) !== NULL)
                {
                    $r['couplages'][] = ['kind' => 'route', 'cible' => $module, 'cible_type' => 'module',
                                         'detail' => sprintf('disposition de la page « %s »', $page), 'ligne' => $argument['ligne']];
                }
            }

            continue;
        }

        if ($t['type'] === NULL && $t['texte'] === ')')
        {
            while ($pages && end($pages)['profondeur'] >= $profondeur)
            {
                array_pop($pages);
            }

            $profondeur--;
            continue;
        }

        // ── classe : \NF\Modules\Forum\… ou \NF\Widgets\News\…
        if ($t['type'] !== NULL
            && (defined('T_NAME_FULLY_QUALIFIED') && $t['type'] === T_NAME_FULLY_QUALIFIED
                || defined('T_NAME_QUALIFIED') && $t['type'] === T_NAME_QUALIFIED))
        {
            if (preg_match('#NF\\\\(Modules|Widgets|Themes)\\\\([A-Za-z0-9_]+)#', $t['texte'], $m))
            {
                $type_cible = ['Modules' => 'module', 'Widgets' => 'widget', 'Themes' => 'theme'][$m[1]];
                $nom        = strtolower($m[2]);

                if (isset($addons[$type_cible][$nom]))
                {
                    $r['couplages'][] = ['kind' => 'classe', 'cible' => $nom, 'cible_type' => $type_cible,
                                         'detail' => $t['texte'], 'ligne' => $t['ligne']];
                }
            }
        }

        // ── langue : module('forum')->lang('…') — traduit AU NOM de forum, fatal sans lui
        if ($t['type'] === T_STRING && strtolower($t['texte']) === 'lang' && ($utiles[$i + 1]['texte'] ?? '') === '('
            && $avant1 && in_array($avant1['type'], $fleches, TRUE)
            && ($utiles[$i - 2]['texte'] ?? '') === ')'
            && ($utiles[$i - 3]['type'] ?? NULL) === T_CONSTANT_ENCAPSED_STRING
            && ($utiles[$i - 4]['texte'] ?? '') === '('
            && ($utiles[$i - 5]['type'] ?? NULL) === T_STRING
            && in_array($type_cible = strtolower($utiles[$i - 5]['texte']), ['module', 'widget', 'theme'], TRUE))
        {
            $nom   = strtolower(coupling_litteral($utiles[$i - 3]['texte']));
            $texte = ($utiles[$i + 2]['type'] ?? NULL) === T_CONSTANT_ENCAPSED_STRING ? coupling_litteral($utiles[$i + 2]['texte']) : NULL;

            if (isset($addons[$type_cible][$nom]))
            {
                $detail = sprintf("%s('%s')%slang(%s)", $type_cible, $nom, $avant1['texte'],
                    $texte === NULL ? '…' : "'".mb_strimwidth($texte, 0, 40, '…')."'");

                if ($texte !== NULL && !isset($ctx['langs'][$type_cible.':'.$nom][nf_langue_cle($texte)]) && !isset($ctx['coeur'][nf_langue_cle($texte)]))
                {
                    $detail .= ' — et ce texte manque à ses traductions';
                }

                $r['couplages'][] = ['kind' => 'langue', 'cible' => $nom, 'cible_type' => $type_cible, 'detail' => $detail, 'ligne' => $t['ligne'],
                                     'garde' => $avant1['type'] === T_NULLSAFE_OBJECT_OPERATOR ? 'appel nul-sûr (?->)' : NULL];
            }
        }

        if ($t['type'] !== T_CONSTANT_ENCAPSED_STRING && $t['type'] !== T_ENCAPSED_AND_WHITESPACE)
        {
            continue;
        }

        $litteral = trim($t['texte'], "'\"");

        // ── table : 'nf_x' seul ou suivi d'un alias ('nf_forum_topics t')
        $table = preg_split('/\s+/', trim($litteral))[0] ?? '';

        if (isset($ctx['proprietaire'][$table]))
        {
            if ($ctx['proprietaire'][$table] !== '')
            {
                $r['couplages'][] = ['kind' => 'table', 'cible' => $ctx['proprietaire'][$table], 'cible_type' => 'module',
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
            if ($avant2 && $avant2['type'] === T_STRING
                && in_array(strtolower($avant2['texte']),
                            ['from', 'join', 'insert', 'update', 'delete', 'replace', 'table_exists', 'truncate'], TRUE)
                && ($avant1['texte'] ?? '') === '(')
            {
                $r['inconnues'][] = ['table' => $table, 'ligne' => $t['ligne']];
            }
        }

        if ($t['type'] !== T_CONSTANT_ENCAPSED_STRING)
        {
            continue;
        }

        $valeur = coupling_litteral($t['texte']);

        // ── clé => valeur : 'widget' => 'news' pose un widget ; 'url' => 'news' fait un lien
        if ($avant1 && $avant1['type'] === T_DOUBLE_ARROW && $avant2 && $avant2['type'] === T_CONSTANT_ENCAPSED_STRING)
        {
            $cle = strtolower(coupling_litteral($avant2['texte']));
            $nom = strtolower($valeur);

            if ($cle === 'widget' && isset($addons['widget'][$nom]))
            {
                $page = $pages ? end($pages)['page'] : NULL;

                $r['couplages'][] = ['kind' => 'disposition', 'cible' => $nom, 'cible_type' => 'widget',
                                     'detail' => sprintf('widget « %s »%s', $nom, $page !== NULL ? sprintf(' (page « %s »)', $page) : ''),
                                     'ligne' => $t['ligne'], 'garde' => coupling_garde_de_page($nom, $page, $addons)];
            }
            elseif ($cle === 'url' && ($module = coupling_module_de_route($valeur, $addons)) !== NULL)
            {
                $r['couplages'][] = ['kind' => 'route', 'cible' => $module, 'cible_type' => 'module',
                                     'detail' => sprintf("'url' => '%s'", $valeur), 'ligne' => $t['ligne']];
            }
        }

        // ── reglage : $this->config('nf_default_page', 'news')
        if ($avant1 && $avant1['texte'] === ',' && $avant2 && $avant2['type'] === T_CONSTANT_ENCAPSED_STRING
            && isset(REGLAGES_QUI_DESIGNENT[$reglage = coupling_litteral($avant2['texte'])])
            && ($utiles[$i - 3]['texte'] ?? '') === '('
            && ($utiles[$i - 4]['type'] ?? NULL) === T_STRING && strtolower($utiles[$i - 4]['texte']) === 'config')
        {
            $type_cible = REGLAGES_QUI_DESIGNENT[$reglage];
            $nom        = strtolower(explode('/', ltrim($valeur, '/'))[0]);

            if (isset($addons[$type_cible][$nom]))
            {
                $r['couplages'][] = ['kind' => 'reglage', 'cible' => $nom, 'cible_type' => $type_cible,
                                     'detail' => sprintf('%s = « %s »', $reglage, $valeur), 'ligne' => $t['ligne']];
            }
        }

        // Motif d'appel : <nom> ( '<litteral>' — on regarde les deux jetons précédents.
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

            if (isset($addons[$type_cible][$nom]))
            {
                $r['couplages'][] = ['kind' => 'service', 'cible' => $nom, 'cible_type' => $type_cible,
                                     'detail' => $appel . "('" . $nom . "')", 'ligne' => $t['ligne']];
            }
        }

        // ── route : url('news/...') vers un autre module
        if ($appel === 'url')
        {
            $premier = strtolower(explode('/', ltrim($litteral, '/'))[0] ?? '');

            if ($premier !== '' && isset($addons['module'][$premier]))
            {
                $r['couplages'][] = ['kind' => 'route', 'cible' => $premier, 'cible_type' => 'module',
                                     'detail' => "url('" . $litteral . "')", 'ligne' => $t['ligne']];
            }
        }

        // ── le texte d'une surcharge se traduit au nom de l'addon surchargé
        if ($appel === 'lang' && $surcharge !== NULL)
        {
            $cle = nf_langue_cle($valeur);

            if (isset($ctx['coeur'][$cle]) || isset($surcharge['atteignables'][$cle]))
            {
                continue;   // le cœur, ou la surcharge elle-même, le traduit
            }

            if (isset($ctx['langs'][$surcharge['type'].':'.$surcharge['nom']][$cle]))
            {
                $r['couplages'][] = ['kind' => 'langue', 'cible' => $surcharge['nom'], 'cible_type' => $surcharge['type'],
                                     'detail' => sprintf("lang('%s'), traduit par %s", mb_strimwidth($valeur, 0, 40, '…'), $surcharge['nom']),
                                     'ligne' => $t['ligne']];
            }
            else
            {
                $r['introuvables'][] = ['texte' => $valeur, 'ligne' => $t['ligne']];
            }
        }
    }

    return $r;
}

// ── 3. Analyse d'un fichier SQL ───────────────────────────────────────────────

/**
 * Analyse d'un fichier .sql : les tables d'un AUTRE addon qu'il nomme dans son CODE, et les lignes
 * qu'il livre et qui nomment un addon — widgets posés, dispositions, réglages, permissions, gabarits
 * d'e-mail.
 *
 * Chaînes et commentaires sont mis de côté avant de chercher une table, pour la même raison que le
 * tokenizer côté PHP : une table citée en commentaire, ou dans le TEXTE d'une page de documentation,
 * n'est pas un couplage.
 */
function coupling_analyse_sql(string $source, array $ctx): array
{
    $r      = coupling_vide();
    $addons = $ctx['addons'];

    ['sans_commentaires' => $sql, 'code' => $code, 'commentaires' => $commentaires] = coupling_sql_decouper($source);

    foreach ($commentaires as $ligne => $texte)
    {
        if (preg_match_all('/couplage\(([a-z0-9_]+)\)\s*:/i', $texte, $m))
        {
            foreach ($m[1] as $cible)
            {
                $r['portees'][strtolower($cible)] = TRUE;
            }
        }

        if (stripos($texte, 'couplage:') !== FALSE)
        {
            for ($l = $ligne; $l <= $ligne + substr_count($texte, "\n"); $l++)
            {
                $r['annotees'][$l] = TRUE;
            }
        }
    }

    // ── les tables, dans le code seulement
    foreach (explode("\n", $code) as $i => $ligne)
    {
        if (!preg_match_all('/(?<![a-z0-9_])(nf_[a-z0-9_]+)/i', $ligne, $m))
        {
            continue;
        }

        foreach (array_unique($m[1]) as $table)
        {
            if (!empty($ctx['proprietaire'][$table]))
            {
                $r['couplages'][] = ['kind' => 'table', 'cible' => $ctx['proprietaire'][$table], 'cible_type' => 'module',
                                     'detail' => $table . ' (SQL)', 'ligne' => $i + 1];
            }
        }
    }

    // ── les lignes livrées
    $ligne_de = static fn (int $position): int => substr_count($sql, "\n", 0, $position) + 1;
    $lire     = static function (string $table) use ($sql): array {
        $lu    = nf_sql_tuples($sql, $table);
        $index = array_flip($lu['colonnes']);

        return array_map(static function (array $tuple) use ($index): array {
            $valeurs = ['debut' => $tuple['debut']];

            foreach ($index as $colonne => $k)
            {
                $valeurs[$colonne] = $tuple['valeurs'][$k] ?? NULL;
            }

            return $valeurs;
        }, $lu['tuples']);
    };

    // Les widgets livrés, par identifiant : une disposition les désigne par lui.
    $widgets = [];

    foreach ($lire('nf_widgets') as $k => $w)
    {
        $widgets[$w['widget_id'] ?? 'sans-id-'.$k] = ['nom' => strtolower((string) ($w['widget'] ?? '')), 'ligne' => $ligne_de($w['debut']), 'pose' => FALSE];

        // Les liens que portent ses réglages : un menu livré qui mène à un autre module.
        $reglages = json_decode((string) ($w['settings'] ?? ''), TRUE);

        if (is_array($reglages))
        {
            array_walk_recursive($reglages, static function ($valeur, $cle) use (&$r, $addons, $w, $ligne_de): void {
                if ($cle === 'url' && is_string($valeur) && ($module = coupling_module_de_route($valeur, $addons)) !== NULL)
                {
                    $r['couplages'][] = ['kind' => 'route', 'cible' => $module, 'cible_type' => 'module',
                                         'detail' => sprintf('lien « %s » (widget %s)', $valeur, $w['widget'] ?? '?'), 'ligne' => $ligne_de($w['debut'])];
                }
            });
        }
    }

    foreach ($lire('nf_dispositions') as $d)
    {
        $theme = strtolower((string) ($d['theme'] ?? ''));
        $page  = (string) ($d['page'] ?? '');
        $ligne = $ligne_de($d['debut']);

        if (isset($addons['theme'][$theme]))
        {
            $r['couplages'][] = ['kind' => 'donnee', 'cible' => $theme, 'cible_type' => 'theme',
                                 'detail' => sprintf('mise en page du thème « %s »', $theme), 'ligne' => $ligne];
        }

        if (($module = coupling_module_de_route($page, $addons)) !== NULL)
        {
            $r['couplages'][] = ['kind' => 'route', 'cible' => $module, 'cible_type' => 'module',
                                 'detail' => sprintf('disposition de la page « %s »', $page), 'ligne' => $ligne];
        }

        // Les widgets qu'elle range : `"id":133` en JSON, `_widget";i:133` à l'ancien format sérialisé.
        preg_match_all('/"id"\s*:\s*(\d+)|_widget";i:(\d+)/', (string) ($d['disposition'] ?? ''), $ids, PREG_SET_ORDER);

        foreach ($ids as $id)
        {
            $id = ($id[2] ?? '') !== '' ? $id[2] : $id[1];

            if (!isset($widgets[$id]))
            {
                continue;
            }

            $widgets[$id]['pose'] = TRUE;
            $nom                  = $widgets[$id]['nom'];

            if (isset($addons['widget'][$nom]))
            {
                $r['couplages'][] = ['kind' => 'disposition', 'cible' => $nom, 'cible_type' => 'widget',
                                     'detail' => sprintf('widget « %s » (thème « %s », page « %s »)', $nom, $theme, $page),
                                     'ligne' => $widgets[$id]['ligne'], 'garde' => coupling_garde_de_page($nom, $page, $addons)];
            }
        }
    }

    foreach ($widgets as $w)
    {
        if (!$w['pose'] && isset($addons['widget'][$w['nom']]))
        {
            $r['couplages'][] = ['kind' => 'disposition', 'cible' => $w['nom'], 'cible_type' => 'widget',
                                 'detail' => sprintf('widget « %s » livré', $w['nom']), 'ligne' => $w['ligne']];
        }
    }

    // Les réglages : écrits par INSERT, ou par UPDATE … SET value = '…' WHERE name = '…'.
    $reglages = [];

    foreach ($lire('nf_settings') as $s)
    {
        $reglages[] = [(string) ($s['name'] ?? ''), (string) ($s['value'] ?? ''), $ligne_de($s['debut'])];
    }

    if (preg_match_all("/UPDATE\s+`?nf_settings`?\s+SET\s+`?value`?\s*=\s*'((?:[^'\\\\]|\\\\.)*)'\s+WHERE\s+`?name`?\s*=\s*'([^']+)'/i", $sql, $m, PREG_SET_ORDER | PREG_OFFSET_CAPTURE))
    {
        foreach ($m as $u)
        {
            $reglages[] = [$u[2][0], stripslashes($u[1][0]), $ligne_de($u[0][1])];
        }
    }

    foreach ($reglages as [$nom, $valeur, $ligne])
    {
        if (isset(REGLAGES_QUI_DESIGNENT[$nom]) && $valeur !== '')
        {
            $type_cible = REGLAGES_QUI_DESIGNENT[$nom];
            $cible      = strtolower(explode('/', ltrim($valeur, '/'))[0]);

            if (isset($addons[$type_cible][$cible]))
            {
                $r['couplages'][] = ['kind' => 'reglage', 'cible' => $cible, 'cible_type' => $type_cible,
                                     'detail' => sprintf('%s = « %s »', $nom, $valeur), 'ligne' => $ligne];
            }
        }

        if ($proprietaire = coupling_proprietaire_du_nom($nom, $addons))
        {
            $r['couplages'][] = ['kind' => 'donnee', 'cible' => $proprietaire[1], 'cible_type' => $proprietaire[0],
                                 'detail' => sprintf('réglage « %s »', $nom), 'ligne' => $ligne];
        }
    }

    foreach ($lire('nf_role_permissions') as $p)
    {
        $permission = (string) ($p['permission'] ?? '');
        $module     = strtolower(explode('.', $permission)[0]);

        if (isset($addons['module'][$module]))
        {
            $r['couplages'][] = ['kind' => 'donnee', 'cible' => $module, 'cible_type' => 'module',
                                 'detail' => sprintf('permission « %s »', $permission), 'ligne' => $ligne_de($p['debut'])];
        }
    }

    foreach ($lire('nf_email_templates') as $g)
    {
        $module = strtolower((string) ($g['module'] ?? ''));

        if (isset($addons['module'][$module]))
        {
            $r['couplages'][] = ['kind' => 'donnee', 'cible' => $module, 'cible_type' => 'module',
                                 'detail' => sprintf("gabarit d'e-mail « %s »", $g['key'] ?? $module), 'ligne' => $ligne_de($g['debut'])];
        }
    }

    return $r;
}

// ── 4. Analyse d'un fichier JavaScript ────────────────────────────────────────

/**
 * Analyse d'un fichier .js : les chaines qui nomment la table ou la route d'un AUTRE addon.
 *
 * Le JS d'un addon appelle souvent une route d'un autre module ('/news/ajax/...'), et parfois cite
 * une table dans un payload. Ce n'est jamais fatal cote serveur — au pire un appel qui repond 404 —
 * mais c'est un couplage reel, et il etait totalement invisible jusqu'ici.
 */
function coupling_analyse_js(string $source, array $ctx): array
{
    $r = coupling_vide();

    foreach (explode("\n", $source) as $i => $ligne)
    {
        $numero = $i + 1;

        if (stripos($ligne, 'couplage:') !== FALSE)
        {
            $r['annotees'][$numero] = TRUE;
        }

        if (preg_match_all('/couplage\(([a-z0-9_]+)\)\s*:/i', $ligne, $m))
        {
            foreach ($m[1] as $cible)
            {
                $r['portees'][strtolower($cible)] = TRUE;
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
            if (preg_match('/\b(nf_[a-z0-9_]+)\b/i', $litteral, $t) && !empty($ctx['proprietaire'][$t[1]]))
            {
                $r['couplages'][] = ['kind' => 'table', 'cible' => $ctx['proprietaire'][$t[1]], 'cible_type' => 'module',
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

            if ($premier !== '' && isset($ctx['addons']['module'][$premier]))
            {
                $r['couplages'][] = ['kind' => 'route', 'cible' => $premier, 'cible_type' => 'module',
                                     'detail' => "'" . $litteral . "' (JS)", 'ligne' => $numero];
            }
        }
    }

    return $r;
}

/**
 * Ce qu'une surcharge de thème ajoute à l'analyse de son fichier : le couplage `surcharge` lui-même,
 * et la garde de tout ce qu'elle fait vers l'addon qu'elle remplace — elle ne s'exécute qu'avec lui.
 *
 * @param array{type: string, nom: string} $surcharge
 */
function coupling_surcharger(array $analyse, array $surcharge, string $remplace): array
{
    foreach ($analyse['couplages'] as $k => $cpl)
    {
        if ($cpl['cible'] === $surcharge['nom'] && $cpl['cible_type'] === $surcharge['type'])
        {
            $analyse['couplages'][$k]['garde'] ??= "surcharge : elle ne s'exécute qu'avec l'addon qu'elle remplace";
        }
    }

    $analyse['couplages'][] = ['kind' => 'surcharge', 'cible' => $surcharge['nom'], 'cible_type' => $surcharge['type'],
                               'detail' => 'remplace '.$remplace, 'ligne' => 1];

    return $analyse;
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

// ── 5. Le jugement ────────────────────────────────────────────────────────────

/**
 * Confronte les couplages relevés aux déclarations.
 *
 * @param  array<string, array<string, array<string, list<array>>>> $references  "type:addon" => "type:cible" => kind => sites
 * @return array{0: list<string>, 1: list<string>, 2: list<array>}  [erreurs, à trancher, résumé par paire]
 */
function coupling_juger(array $references, array $declarations): array
{
    $erreurs = $a_trancher = $resume = [];

    $explicite = static fn (array $s): bool => $s['annote'] || ($s['garde'] ?? NULL) !== NULL;
    $exemples  = static function (array $sites): string {
        $lignes = implode("\n          ", array_map(
            static fn (array $s): string => sprintf('%s:%d  %s', $s['fichier'], $s['ligne'], $s['detail']),
            array_slice($sites, 0, 3)
        ));

        return $lignes.(count($sites) > 3 ? sprintf("\n          … et %d autre(s)", count($sites) - 3) : '');
    };

    foreach ($references as $source => $par_cible)
    {
        $decl     = $declarations[$source] ?? ['core' => FALSE, 'requires' => []];
        $nom_src  = explode(':', $source, 2)[1] ?? $source;
        $engendre = str_starts_with($source, 'install:');

        foreach ($par_cible as $cible => $par_kind)
        {
            $nom_cible = explode(':', $cible, 2)[1];
            $declare   = in_array($nom_cible, $decl['requires'] ?? [], TRUE);
            $fatals    = $visibles = [];

            foreach (COUPLAGES_FATALS as $k)
            {
                $fatals = array_merge($fatals, $par_kind[$k] ?? []);
            }

            foreach (COUPLAGES_VISIBLES as $k)
            {
                $visibles = array_merge($visibles, $par_kind[$k] ?? []);
            }

            $nus         = array_values(array_filter($fatals, static fn (array $s): bool => !$explicite($s)));
            $nus_visibles = array_values(array_filter($visibles, static fn (array $s): bool => !$explicite($s)));

            $resume[] = [
                'addon'      => $source,
                'cible'      => $cible,
                'moyens'     => array_map('count', $par_kind),
                'fatals'     => count($fatals),
                'nus'        => count($nus),
                'visibles'   => count($visibles),
                'a_trancher' => count($nus_visibles),
                'declare'    => $declare,
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

            // Le code appelle déjà le service de la cible : c'est peut-être la garde, qu'il reste à annoter.
            $garde = !empty($par_kind['service'])
                ? sprintf("\n        (le code y appelle déjà %s — si c'est la garde, annotez-la)", $par_kind['service'][0]['detail'])
                : '';

            if ($nus)
            {
                $par_moyen = [];

                foreach (COUPLAGES_FATALS as $k)
                {
                    if (!empty($par_kind[$k]))
                    {
                        $par_moyen[] = coupling_compte($k, count($par_kind[$k]));
                    }
                }

                $remede = !empty($decl['core'])
                    ? sprintf("%s appartient au cœur et ne peut pas le requérir : garder le code (table_exists(), module('%s'), ?->)\n"
                        . "        et annoter la ligne d'un commentaire « couplage: <raison> ».", $nom_src, $nom_cible)
                    : sprintf("Soit c'est une dépendance dure → ajouter '%s' à 'requires' dans son __info() ;\n"
                        . "        soit le code s'en passe → annoter la ligne d'un commentaire « couplage: <raison> ».", $nom_cible);

                $erreurs[] = sprintf(
                    "%s dépend de « %s » (%s) sans que ce couplage soit explicite.%s\n        %s\n          %s",
                    $source,
                    $cible,
                    implode(', ', $par_moyen),
                    $garde,
                    $remede,
                    $exemples($nus)
                );
            }

            if ($nus_visibles)
            {
                $par_moyen = $consequences = [];

                foreach (COUPLAGES_VISIBLES as $k)
                {
                    if (!empty($par_kind[$k]))
                    {
                        $par_moyen[]    = coupling_compte($k, count($par_kind[$k]));
                        $consequences[] = CONSEQUENCES[$k];
                    }
                }

                foreach ($par_kind['donnee'] ?? [] as $s)
                {
                    if (str_starts_with($s['detail'], "gabarit d'e-mail"))
                    {
                        $consequences[array_search(CONSEQUENCES['donnee'], $consequences, TRUE)] .= CONSEQUENCE_GABARIT;
                        break;
                    }
                }

                $remede = !empty($decl['core'])
                    ? sprintf("%s appartient au cœur et ne peut pas le requérir : ne le livrer que si « %s » est installé,\n"
                        . "        ou l'accepter tel quel — et l'annoter « couplage(%s): <raison> ».", $nom_src, $nom_cible, $nom_cible)
                    : sprintf("Le déclarer dans 'requires' s'il en a vraiment besoin, ne le livrer que si « %s » est installé,\n"
                        . "        ou l'accepter tel quel — et l'annoter « couplage(%s): <raison> ».", $nom_cible, $nom_cible);

                if ($engendre)
                {
                    $remede .= "\n        Ce fichier est engendré depuis une base : la décision se prend à sa source, puis il se régénère.";
                }

                $a_trancher[] = sprintf(
                    "%s → %s (%s)%s\n        Sans « %s » : %s.\n        %s\n          %s",
                    $source,
                    $cible,
                    implode(', ', $par_moyen),
                    $garde,
                    $nom_cible,
                    implode(' ; ', $consequences),
                    $remede,
                    $exemples($nus_visibles)
                );
            }
        }
    }

    sort($a_trancher);

    return [$erreurs, $a_trancher, $resume];
}

// ── 6. L'épreuve à l'envers ───────────────────────────────────────────────────

/**
 * Des couplages plantés, qui doivent être vus et NON gardés ; des écritures sûres, qui doivent être
 * reconnues gardées — ou ne pas être prises pour un couplage du tout. Puis le jugement lui-même :
 * un fatal refusé, un visible mis à trancher, un déclaré et un annoté laissés en paix.
 *
 * @return list<string> les échecs de l'épreuve
 */
function coupling_epreuve(): array
{
    $produit = [
        'proprietaire' => ['nf_news' => 'news', 'nf_forum_topics' => 'forum', 'nf_teams' => 'teams', 'nf_settings' => '', 'nf_widgets' => ''],
        'addons'       => [
            'module' => ['forum' => TRUE, 'news' => TRUE, 'teams' => TRUE],
            'widget' => ['forum' => TRUE, 'news' => TRUE, 'module' => TRUE],
            'theme'  => ['ailleurs' => TRUE, 'clair' => TRUE],
        ],
        'langs'        => [
            'module:forum' => [nf_langue_cle('Sujets') => 0],
            'module:news'  => [nf_langue_cle('Lire la suite') => 0],
            'theme:clair'  => [nf_langue_cle('Texte du thème') => 0],
        ],
        'coeur'        => [nf_langue_cle('Visiteur') => 0],
        'surcharge'    => NULL,
    ];

    // Une vue de `news` que le thème `clair` surcharge : elle s'exécute au nom de news.
    $surcharge              = $produit;
    $surcharge['surcharge'] = ['type' => 'module', 'nom' => 'news', 'atteignables' => []];

    // Une mise en page livrée, comme celle du seed : JSON échappé, un commentaire entre deux tuples.
    $mise_en_page = "INSERT INTO `nf_widgets` (`widget_id`, `widget`, `type`, `title`, `settings`) VALUES\n"
        ."('1', 'news', 'categories', NULL, NULL),\n"
        ."-- le widget du forum, sur ses propres pages\n"
        ."('2', 'forum', 'activity', NULL, NULL);\n\n"
        ."INSERT INTO `nf_dispositions` (`disposition_id`, `theme`, `page`, `zone`, `disposition`) VALUES\n"
        ."('1', 'clair', '*', '2', '[{\\\"style\\\":null,\\\"cols\\\":[{\\\"size\\\":null,\\\"widgets\\\":[{\\\"id\\\":1,\\\"style\\\":null,\\\"size\\\":null}]}]}]'),\n"
        ."('2', 'clair', 'forum/*', '3', '[{\\\"style\\\":null,\\\"cols\\\":[{\\\"size\\\":null,\\\"widgets\\\":[{\\\"id\\\":2,\\\"style\\\":null,\\\"size\\\":null}]}]}]');\n";

    // L'ancien format sérialisé : la garde n'est reconnue que si la disposition est bien lue.
    $ancien_format = "INSERT INTO `nf_widgets` (`widget_id`, `widget`, `type`, `title`, `settings`) VALUES (7, 'forum', 'index', NULL, NULL);\n"
        ."INSERT INTO `nf_dispositions` (`theme`, `page`, `zone`, `disposition`) VALUES ('clair', 'forum/*', 2, 'O:30:\\\"NF\\\\\\\\NeoFrag\\\\\\\\Displayables\\\\\\\\Widget\\\":1:{s:10:\\\"\\0*\\0_widget\\\";i:7;}');\n";

    $reglages = "INSERT INTO `nf_settings` (`name`, `site`, `lang`, `value`, `type`) VALUES\n"
        ."('nf_name', '', '', 'Site', 'string'),\n"
        ."-- une note glissée entre deux réglages\n"
        ."('nf_default_page', '', '', 'news', 'string'),\n"
        ."('forum_topics_per_page', '', '', '20', 'int');\n"
        ."UPDATE `nf_settings` SET `value` = 'ailleurs' WHERE `name` = 'nf_default_theme';\n";

    // [nom, langage, source, contexte, kind, cible, gardé : TRUE, FALSE — ou NULL : ce n'est pas un couplage]
    $cas = [
        ['widget optionnel posé sur toutes les pages', 'php', "<?php \$d->set('*', 'Contenu', \$this->array([\$this->widget(\$this->db->insert('nf_widgets', ['widget' => 'news', 'type' => 'categories']))]));", $produit, 'disposition', 'news', FALSE],
        ['traduction faite au nom d\'un autre module', 'php', "<?php echo \$this->module('forum')->lang('Sujets');", $produit, 'langue', 'forum', FALSE],
        ['widget optionnel dans une mise en page livrée', 'sql', $mise_en_page, $produit, 'disposition', 'news', FALSE],
        ['widget du module dans une disposition à l\'ancien format sérialisé', 'sql', $ancien_format, $produit, 'disposition', 'forum', TRUE],
        ['accueil livré sur un module optionnel, après un commentaire', 'sql', $reglages, $produit, 'reglage', 'news', FALSE],
        ['thème désigné par un UPDATE', 'sql', $reglages, $produit, 'reglage', 'ailleurs', FALSE],
        ['réglage d\'un autre module livré', 'sql', $reglages, $produit, 'donnee', 'forum', FALSE],
        ['mise en page d\'un autre thème livrée', 'sql', $mise_en_page, $produit, 'donnee', 'clair', FALSE],
        ['clé étrangère vers la table d\'un autre module', 'sql', "CREATE TABLE `nf_x` (`team_id` int, FOREIGN KEY (`team_id`) REFERENCES `nf_teams` (`team_id`));\n", $produit, 'table', 'teams', FALSE],
        ['accueil désigné depuis le code', 'php', "<?php \$this->config('nf_default_page', 'news');", $produit, 'reglage', 'news', FALSE],
        ['table lue en PHP', 'php', "<?php \$this->db->from('nf_forum_topics')->get();", $produit, 'table', 'forum', FALSE],
        ['lien d\'un menu posé par un thème', 'php', "<?php \$s = ['links' => [['title' => 'Actualités', 'url' => 'news']]];", $produit, 'route', 'news', FALSE],
        ['lien d\'un menu livré en SQL', 'sql', "INSERT INTO `nf_widgets` (`widget_id`, `widget`, `type`, `title`, `settings`) VALUES ('3', 'module', 'index', NULL, '{\\\"links\\\":[{\\\"title\\\":\\\"Forum\\\",\\\"url\\\":\\\"forum\\\"}]}');\n", $produit, 'route', 'forum', FALSE],
        ['page d\'un autre module décorée par une disposition', 'sql', $mise_en_page, $produit, 'route', 'forum', FALSE],

        ['widget posé sur les pages de son module', 'php', "<?php \$d->set('forum/*', 'Post-contenu', \$this->array([\$this->widget(\$this->db->insert('nf_widgets', ['widget' => 'forum', 'type' => 'activity']))]));", $produit, 'disposition', 'forum', TRUE],
        ['widget sur les pages de son module, dans un SQL livré', 'sql', $mise_en_page, $produit, 'disposition', 'forum', TRUE],
        ['traduction empruntée par un appel nul-sûr', 'php', "<?php echo \$this->module('forum')?->lang('Sujets');", $produit, 'langue', 'forum', TRUE],
        ['texte d\'une surcharge traduit par l\'addon surchargé', 'php', "<?php echo \$this->lang('Lire la suite');", $surcharge, 'langue', 'news', FALSE],

        ['table citée dans le TEXTE d\'une page livrée', 'sql', "INSERT INTO `nf_wiki` (`id`, `content`) VALUES ('1', 'la table nf_news garde les actualités');\n", $produit, 'table', 'news', NULL],
        ['table citée en commentaire SQL', 'sql', "-- autrefois : REFERENCES nf_news\nSELECT 1;\n", $produit, 'table', 'news', NULL],
        ['table citée en commentaire PHP', 'php', "<?php\n// \$this->db->from('nf_news')\n", $produit, 'table', 'news', NULL],
        ['texte traduit chez soi, même connu d\'un autre addon', 'php', "<?php echo \$this->lang('Sujets');", $produit, 'langue', 'forum', NULL],
        ['texte d\'une surcharge que le cœur traduit', 'php', "<?php echo \$this->lang('Visiteur');", $surcharge, 'langue', 'news', NULL],
    ];

    $echecs = [];

    foreach ($cas as [$nom, $langage, $source, $contexte, $kind, $cible, $garde])
    {
        $r   = $langage === 'sql' ? coupling_analyse_sql($source, $contexte) : coupling_analyse($source, $contexte);
        $vus = array_filter($r['couplages'], static fn (array $c): bool => $c['kind'] === $kind && $c['cible'] === $cible);

        if ($garde === NULL)
        {
            if ($vus)
            {
                $echecs[] = "faux positif : $nom";
            }

            continue;
        }

        if (!$vus)
        {
            $echecs[] = "couplage NON vu : $nom";
            continue;
        }

        $gardes = array_filter($vus, static fn (array $c): bool => ($c['garde'] ?? NULL) !== NULL);

        if ($garde && count($gardes) !== count($vus))
        {
            $echecs[] = "garde NON reconnue : $nom";
        }

        if (!$garde && $gardes)
        {
            $echecs[] = "garde imaginée : $nom";
        }
    }

    // Tout ce que la surcharge fait vers l'addon qu'elle remplace est gardé, et la surcharge est relevée.
    $surchargee = coupling_surcharger(coupling_analyse("<?php echo \$this->lang('Lire la suite'), url('news/1');", $surcharge), $surcharge['surcharge'], 'modules/news/views/index.tpl.php');
    $vers_news  = array_filter($surchargee['couplages'], static fn (array $c): bool => $c['cible'] === 'news');

    if (count(array_filter($vers_news, static fn (array $c): bool => $c['kind'] === 'surcharge')) !== 1
        || array_filter($vers_news, static fn (array $c): bool => ($c['garde'] ?? NULL) === NULL && $c['kind'] !== 'surcharge'))
    {
        $echecs[] = 'surcharge : non relevée, ou ce qu\'elle fait vers l\'addon remplacé non gardé';
    }

    // Le texte d'une surcharge que seul le thème connaît : jamais traduit, il doit être refusé.
    if (!coupling_analyse("<?php echo \$this->lang('Texte du thème');", $surcharge)['introuvables'])
    {
        $echecs[] = 'texte de surcharge introuvable NON vu';
    }

    // L'annotation qui couvre le fichier.
    if (empty(coupling_analyse("<?php\n// couplage(news): la case reste vide sans news, accepté\n\$x = ['widget' => 'news'];", $produit)['portees']['news']))
    {
        $echecs[] = 'annotation « couplage(news): » NON lue';
    }

    // Le jugement : un fatal refusé, un visible à trancher, un déclaré et un annoté en paix.
    $site         = static fn (bool $annote): array => ['fichier' => 'x.php', 'ligne' => 1, 'detail' => 'x', 'annote' => $annote, 'garde' => NULL];
    $declarations = [
        'theme:clair'  => ['core' => TRUE, 'requires' => []],
        'module:m'     => ['core' => FALSE, 'requires' => ['teams']],
        'module:teams' => ['core' => FALSE, 'requires' => []],
    ];

    [$erreurs, $a_trancher] = coupling_juger([
        'theme:clair' => ['widget:news' => ['disposition' => [$site(FALSE)]]],
        'module:m'    => [
            'module:forum' => ['table' => [$site(FALSE)]],
            'module:teams' => ['table' => [$site(FALSE)], 'donnee' => [$site(FALSE)]],
            'module:news'  => ['disposition' => [$site(TRUE)], 'langue' => [$site(TRUE)]],
        ],
    ], $declarations);

    if (count($erreurs) !== 1 || !str_contains($erreurs[0], 'module:forum'))
    {
        $echecs[] = 'jugement : le seul fatal non explicite n\'est pas le seul refusé';
    }

    if (count($a_trancher) !== 1 || !str_contains($a_trancher[0], 'widget:news') || !str_contains($a_trancher[0], 'ne peut pas le requérir'))
    {
        $echecs[] = 'jugement : le seul visible non explicite n\'est pas le seul à trancher, ou le cœur s\'y voit proposer requires';
    }

    return $echecs;
}

$echecs = coupling_epreuve();

if ($o['epreuve'])
{
    foreach ($echecs as $echec)
    {
        echo "  $echec\n";
    }

    $echecs ? nf_echec('épreuve à l\'envers : le contrôle est aveugle ou trop bavard') : nf_ok('épreuve à l\'envers : les couplages plantés sont vus, les écritures sûres passent');
}

if ($echecs)
{
    // Un contrôle qui ne voit plus ce qu'il vise ne doit pas se dire vert.
    nf_refus('épreuve à l\'envers ratée — '.implode(' ; ', $echecs));
}

// ── 7. Le produit : tables, addons, traductions ───────────────────────────────

// Table → addon propriétaire.
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

// Addons présents et leurs déclarations.
require_once $racine . '/neofrag/installer.php';
$declarations = \NF\NeoFrag\Installer::addon_declarations($racine);

/** nom d'addon → type, pour reconnaître module('x') / widget('x') / theme('x'). */
$addons_par_type = ['module' => [], 'widget' => [], 'theme' => []];
$langs           = [];

foreach ($declarations as $cle => $d)
{
    $addons_par_type[$d['type']][$d['name']] = TRUE;
    $langs[$cle] = nf_langue_cles($racine.'/'.$d['type'].'s/'.$d['name'].'/langs/fr.php');
}

$produit = [
    'proprietaire' => $proprietaire,
    'addons'       => $addons_par_type,
    'langs'        => $langs,
    'coeur'        => nf_langue_cles($racine.'/neofrag/langs/fr.php'),
    'surcharge'    => NULL,
];

// ── 8. Parcours : les addons, puis les SQL livrés ─────────────────────────────
$references   = [];   // "type:addon" => "type:cible" => kind => liste de sites
$inconnues    = [];   // littéral nf_* dont aucun addon ne se déclare propriétaire => où il est cité
$introuvables = [];   // textes de surcharge que rien ne traduit
$examines     = 0;

/** Range les couplages d'un fichier analysé dans $references, hors soi-même et hors cœur. */
$retenir = static function (string $source, string $addon, string $relatif, array $analyse, ?string $garde_du_fichier = NULL)
    use (&$references, &$inconnues, &$introuvables, $declarations): void {
    foreach ($analyse['inconnues'] as $inc)
    {
        $inconnues[$inc['table']][] = $relatif . ':' . $inc['ligne'];
    }

    foreach ($analyse['introuvables'] as $txt)
    {
        $introuvables[] = ['fichier' => $relatif, 'ligne' => $txt['ligne'], 'texte' => $txt['texte']];
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
            'garde'   => $cpl['garde'] ?? $garde_du_fichier,
        ];
    }
};

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

            // Un fichier sous themes/<t>/overrides/<type>s/<x>/ remplace celui de l'addon <x> : il ne
            // s'exécute qu'avec lui, et à son nom — ses textes se traduisent chez <x>.
            $surcharge = NULL;

            if (preg_match('#^themes/([^/]+)/overrides/(modules|widgets|themes)/([^/]+)/(.+)$#', $relatif, $s)
                && isset($addons_par_type[rtrim($s[2], 's')][$s[3]]))
            {
                $surcharge = [
                    'type'         => rtrim($s[2], 's'),
                    'nom'          => $s[3],
                    'atteignables' => nf_langue_cles("{$racine}/themes/{$s[1]}/overrides/{$s[2]}/{$s[3]}/langs/fr.php")
                                    + nf_langue_cles("{$racine}/themes/{$s[1]}/overrides/neofrag/langs/fr.php"),
                ];
            }

            $contenu = (string) @file_get_contents($chemin);

            // Le SQL d'installation d'un addon peut porter une cle etrangere vers la table d'un
            // autre : c'est une dependance d'ORDRE D'INSTALLATION, fatale si l'autre n'est pas la.
            $analyse = match ($extension) {
                'sql'   => coupling_analyse_sql($contenu, $produit),
                'js'    => coupling_analyse_js($contenu, $produit),
                default => coupling_analyse($contenu, ['surcharge' => $surcharge] + $produit),
            };

            $retenir($source, $addon, $relatif, $surcharge === NULL ? $analyse : coupling_surcharger($analyse, $surcharge, $s[2].'/'.$s[3].'/'.$s[4]));
        }
    }
}

// Les SQL livrés à la racine d'install/ : le socle se juge comme le cœur, les autres sont joués au mieux.
foreach (glob($racine . '/install/*.sql') ?: [] as $chemin)
{
    $fichier = basename($chemin);
    $source  = 'install:' . $fichier;
    $socle   = in_array($fichier, SQL_SOCLE, TRUE);

    $declarations[$source] = ['type' => 'install', 'name' => $fichier, 'core' => $socle, 'requires' => []];
    $examines++;

    $retenir($source, $fichier, 'install/' . $fichier, coupling_analyse_sql((string) @file_get_contents($chemin), $produit),
        $socle ? NULL : "joué au mieux par l'installeur (hors du socle)");
}

// ── 9. Confrontation aux déclarations ─────────────────────────────────────────
[$erreurs, $a_trancher, $resume] = coupling_juger($references, $declarations);
$avertissements = [];

foreach ($introuvables as $txt)
{
    $erreurs[] = sprintf(
        "%s:%d — lang('%s') : cette vue surcharge un autre addon et se traduit à SON nom ;\n"
        . "        le texte n'est ni chez lui, ni dans le cœur, ni dans les langs/ de la surcharge : il restera en français.",
        $txt['fichier'],
        $txt['ligne'],
        mb_strimwidth($txt['texte'], 0, 60, '…')
    );
}

// ── 10. Cycles du graphe RÉEL (couplages fatals seulement) ────────────────────
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
                "« %s » et « %s » dépendent DUREMENT l'un de l'autre : aucun des deux ne peut être\n"
                . "        installé sans l'autre. Garder le côté purement affichage — celui qui ne fait\n"
                . "        qu'AFFICHER l'autre — et l'annoter « couplage: », pour qu'un ordre existe.",
                $a,
                $b
            );

            continue;
        }

        $avertissements[] = sprintf(
            "« %s » et « %s » se lisent mutuellement, mais un sens est gardé : un ordre\n"
            . "        d'installation existe. Signalé pour mémoire, rien à corriger.",
            $a,
            $b
        );
    }
}

// Le cliquet des couplages visibles : une paire NOUVELLE au-delà du plafond fait échouer.
$depasse = count($a_trancher) > A_TRANCHER_PLAFOND;

// ── 11. Compte rendu ──────────────────────────────────────────────────────────
usort($resume, static fn (array $a, array $b): int => [$a['addon'], $a['cible']] <=> [$b['addon'], $b['cible']]);

if ($json)
{
    echo json_encode([
        'fichiers_examines' => $examines,
        'tables_classees'   => count($proprietaire),
        'couplages'         => $resume,
        'erreurs'           => $erreurs,
        'a_trancher'        => $a_trancher,
        'a_trancher_plafond'=> A_TRANCHER_PLAFOND,
        'avertissements'    => $avertissements,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), "\n";

    exit($erreurs || $depasse ? NF_ECHEC : NF_OK);
}

if ($carte)
{
    printf("Couplage réel entre addons — %d fichiers analysés (php, sql, js), %d tables classées.\n\n", $examines, count($proprietaire));
    // Alignées en caractères, pas en octets : « données livrées » compte ses accents une fois.
    $colonne = static fn (string $texte, int $largeur): string => $texte.str_repeat(' ', max(1, $largeur - mb_strlen($texte)));

    echo '  '.$colonne('ADDON', 25).$colonne('DÉPEND DE', 21).$colonne('PAR QUEL MOYEN', 54)."ÉTAT\n";
    printf("  %s\n", str_repeat('-', 124));

    foreach ($resume as $r)
    {
        $moyens = [];

        foreach ($r['moyens'] as $k => $n)
        {
            $moyens[] = coupling_compte($k, $n);
        }

        $etat = match (TRUE) {
            $r['declare']        => 'dépendance DURE déclarée',
            $r['nus'] > 0        => sprintf('%d fatal(s) NON explicite(s)', $r['nus']),
            $r['a_trancher'] > 0 => sprintf('%d à trancher', $r['a_trancher']),
            $r['fatals'] + $r['visibles'] > 0 => 'couplage mou (annoté ou gardé)',
            default              => 'tolérant',
        };

        echo '  '.$colonne($r['addon'], 25).$colonne($r['cible'], 21).$colonne(implode(', ', $moyens), 54).$etat."\n";
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

if ($a_trancher)
{
    printf("\nÀ TRANCHER — %d couplage(s) VISIBLE(S) non explicite(s), plafond %d : rien ne casse, mais le site montre le manque.\n\n",
        count($a_trancher), A_TRANCHER_PLAFOND);

    foreach ($a_trancher as $t)
    {
        echo "  ◆ {$t}\n\n";
    }
}

if ($erreurs || $depasse)
{
    echo "\n";

    foreach ($erreurs as $e)
    {
        echo "  ✗ {$e}\n\n";
    }

    nf_echec(implode(' ; ', array_filter([
        $erreurs ? sprintf('%d couplage(s) fatal(s) non explicite(s) ou défaut(s) de traduction — voir la règle en tête de ce fichier', count($erreurs)) : '',
        $depasse ? sprintf('%d couplage(s) visible(s) à trancher pour un plafond de %d : un couplage visible NOUVEAU s\'est ajouté sans être rendu explicite', count($a_trancher), A_TRANCHER_PLAFOND) : '',
    ])));
}

nf_ok(sprintf('tous les couplages fatals entre addons sont explicites ; %d couplage(s) visible(s) à trancher (plafond %d)',
    count($a_trancher), A_TRANCHER_PLAFOND));
