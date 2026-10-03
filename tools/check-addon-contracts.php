<?php
declare(strict_types=1);
/**
 * check-addon-contracts — les contrats des carrefours : la méthode qu'un carrefour appelle existe, publique, avec la bonne signature.
 *
 * Famille : statique
 *
 * Pourquoi cet outil existe
 * -------------------------
 * Le CMS repose sur des conventions tacites. Un module se branche sur un « carrefour » (§9 de
 * docs/architecture.md) en posant un fichier `controllers/<carrefour>.php` : le carrefour scanne
 * les modules installés et appelle ceux qui répondent. Rien ne vérifiait que ce fichier respecte
 * bien le contrat attendu.
 *
 * Or un contrat tacite se casse en silence. Le carrefour appelle `$controller->statistics()` ; si
 * la méthode s'appelle autrement, ou n'est pas publique, PHP lève une erreur fatale — en
 * production, à l'ouverture de la page des statistiques, jamais pendant le développement du
 * module. Le même piège vaut pour `search()`, dont le carrefour exige AUSSI une méthode
 * `suggest()` sans que rien ne le dise au module.
 *
 * Ce contrôle relit donc les contributions et vérifie ce que les carrefours vont réellement
 * appeler. Il attrape la classe entière de défauts, y compris ceux des modules pas encore écrits.
 *
 * Lecture STATIQUE, par jetons PHP : le contrôle doit tourner en intégration continue sans base de
 * données ni démarrage du CMS, comme les autres `check-*`. Il ne dit donc pas si la méthode REND
 * la bonne chose — il dit qu'elle existe, qu'elle est appelable, et avec la bonne signature.
 *
 * Usage
 * -----
 *   php tools/check-addon-contracts.php
 */

require __DIR__.'/lib/outil.php';

nf_options([]);

$racine = nf_racine();

/**
 * Les carrefours et ce que chacun appelle réellement, relevé dans le code du carrefour lui-même
 * — jamais supposé. La référence figure en commentaire pour que la prochaine relecture puisse
 * vérifier sans relire tout le projet.
 */
$carrefours = [
    // modules/statistics/models/statistics.php : $controller->statistics()
    'statistics' => ['methodes' => ['statistics' => 0], 'appelant' => 'modules/statistics/models/statistics.php'],

    // modules/user/controllers/index.php : $controller->activity($user_id, 10)
    'activity'   => ['methodes' => ['activity' => 2], 'appelant' => 'modules/user/controllers/index.php'],

    // modules/admin/controllers/admin.php : $controller->dashboard()
    'dashboard'  => ['methodes' => ['dashboard' => 0], 'appelant' => 'modules/admin/controllers/admin.php'],

    // modules/pages/models/pages.php : $controller->block()
    'block'      => ['methodes' => ['block' => 0], 'appelant' => 'modules/pages/models/pages.php'],

    // modules/search/controllers/ajax.php exige search() ET suggest() sur le MÊME contrôleur :
    //   if (!($sc = @$module->controller('search')) || !method_exists($sc, 'suggest') || !($columns = $sc->search()))
    // Un module qui ne fournirait que search() est ignoré en silence par la recherche instantanée.
    'search'     => ['methodes' => ['search' => 0, 'suggest' => null], 'appelant' => 'modules/search/controllers/ajax.php'],

    // neofrag/helpers/seo.php, nf_seo_plan() : $controleur->sitemap(), dans la langue du plan.
    // Chaque module y donne ses adresses publiques ; sans lui, il est absent du plan du site.
    'sitemap'    => ['methodes' => ['sitemap' => 0], 'appelant' => 'neofrag/helpers/seo.php'],

    // modules/monitoring/controllers/index.php : $controller->cron(), sur les WIDGETS.
    // Un widget qui dépend d'un service extérieur y rafraîchit son cache hors du rendu d'une page.
    'cron'       => ['methodes' => ['cron' => 0], 'appelant' => 'modules/monitoring/controllers/index.php', 'famille' => 'widgets'],
];

/**
 * Méthodes publiques déclarées dans un fichier PHP, avec leur nombre de paramètres OBLIGATOIRES.
 *
 * L'analyse passe par `token_get_all()` plutôt que par une expression régulière : un commentaire
 * ou une chaîne contenant « function statistics » tromperait un motif textuel, pas l'analyseur.
 *
 * @return array<string, array{visibilite: string, requis: int, total: int}>
 */
function methodes_publiques(string $chemin): array
{
    $jetons   = token_get_all((string) file_get_contents($chemin));
    $methodes = [];
    $portee   = 'public';   // sans modificateur explicite, PHP considère la méthode publique
    $abstrait = FALSE;

    for ($i = 0, $n = count($jetons); $i < $n; $i++)
    {
        $jeton = $jetons[$i];

        if (!is_array($jeton))
        {
            continue;
        }

        if (in_array($jeton[0], [T_PUBLIC, T_PROTECTED, T_PRIVATE], TRUE))
        {
            $portee = strtolower($jeton[1]);
            continue;
        }

        if ($jeton[0] === T_ABSTRACT)
        {
            $abstrait = TRUE;
            continue;
        }

        if ($jeton[0] !== T_FUNCTION)
        {
            continue;
        }

        // Nom de la méthode : premier T_STRING après `function`.
        $nom = NULL;
        $j   = $i + 1;

        while ($j < $n)
        {
            if (is_array($jetons[$j]) && $jetons[$j][0] === T_STRING)
            {
                $nom = $jetons[$j][1];
                break;
            }

            // `function (` : fermeture anonyme, pas une méthode.
            if ($jetons[$j] === '(')
            {
                break;
            }

            $j++;
        }

        if ($nom !== NULL)
        {
            $methodes[$nom] = [
                'visibilite' => $abstrait ? 'abstract '.$portee : $portee,
                'requis'     => parametres_requis($jetons, $j),
            ];
        }

        $portee   = 'public';
        $abstrait = FALSE;
    }

    return $methodes;
}

/** Nombre de paramètres SANS valeur par défaut, à partir du nom de la méthode. */
function parametres_requis(array $jetons, int $depart): int
{
    $n = count($jetons);
    $i = $depart;

    while ($i < $n && $jetons[$i] !== '(')
    {
        $i++;
    }

    if ($i >= $n)
    {
        return 0;
    }

    $profondeur = 0;
    $requis     = 0;
    $courant    = FALSE;   // un paramètre est-il commencé ?
    $defaut     = FALSE;   // a-t-il une valeur par défaut ?

    for (; $i < $n; $i++)
    {
        $jeton = $jetons[$i];

        if ($jeton === '(')
        {
            $profondeur++;
            continue;
        }

        if ($jeton === ')')
        {
            $profondeur--;

            if ($profondeur === 0)
            {
                if ($courant && !$defaut)
                {
                    $requis++;
                }

                return $requis;
            }

            continue;
        }

        if ($profondeur !== 1)
        {
            continue;
        }

        if ($jeton === ',')
        {
            if ($courant && !$defaut)
            {
                $requis++;
            }

            $courant = $defaut = FALSE;
            continue;
        }

        if ($jeton === '=')
        {
            $defaut = TRUE;
            continue;
        }

        if (is_array($jeton) && $jeton[0] === T_VARIABLE)
        {
            $courant = TRUE;
        }
    }

    return $requis;
}

// ── 1. Les contributions aux carrefours ──────────────────────────────────────
$anomalies = [];
$controles = 0;

foreach ($carrefours as $carrefour => $contrat)
{
    // La plupart des carrefours sont servis par des modules ; `cron` l'est par des widgets. Sans
    // cette famille, la boucle chercherait dans `modules/` un fichier qui n'y est jamais et le
    // contrôle se dirait vert sans avoir rien regardé.
    $famille = $contrat['famille'] ?? 'modules';

    foreach (glob($racine.'/'.$famille.'/*/controllers/'.$carrefour.'.php') ?: [] as $fichier)
    {
        $addon  = basename(dirname(dirname($fichier)));
        $rel    = $famille.'/'.$addon.'/controllers/'.$carrefour.'.php';
        $trouve = methodes_publiques($fichier);

        foreach ($contrat['methodes'] as $methode => $requis)
        {
            $controles++;

            if (!isset($trouve[$methode]))
            {
                $anomalies[] = [
                    'fichier' => $rel,
                    'defaut'  => sprintf('la méthode `%s()` est absente', $methode),
                    'effet'   => sprintf('%s l\'appelle sans vérifier : erreur fatale à l\'exécution.', $contrat['appelant']),
                ];
                continue;
            }

            if ($trouve[$methode]['visibilite'] !== 'public')
            {
                $anomalies[] = [
                    'fichier' => $rel,
                    'defaut'  => sprintf('`%s()` est %s, donc inappelable de l\'extérieur', $methode, $trouve[$methode]['visibilite']),
                    'effet'   => sprintf('%s l\'appelle depuis un autre objet : erreur fatale.', $contrat['appelant']),
                ];
                continue;
            }

            // `null` = le carrefour se contente de l'existence (method_exists), la signature est libre.
            if ($requis !== null && $trouve[$methode]['requis'] > $requis)
            {
                $anomalies[] = [
                    'fichier' => $rel,
                    'defaut'  => sprintf('`%s()` exige %d paramètre(s) obligatoire(s)', $methode, $trouve[$methode]['requis']),
                    'effet'   => sprintf('%s n\'en passe que %d : ArgumentCountError.', $contrat['appelant'], $requis),
                ];
            }
        }
    }
}

// ── 2. Les thèmes de site public déclarent leurs régions ─────────────────────
// Les régions nommées sont la fondation du page-builder : un thème qui n'en déclare aucune ne
// peut recevoir ni disposition ni bloc de page. Le thème d'administration en est exempt — il ne
// sert jamais de site public, et n'a donc pas de région à offrir.
foreach (glob($racine.'/themes/*', GLOB_ONLYDIR) ?: [] as $dossier)
{
    $theme = basename($dossier);

    if ($theme === 'admin' || !is_file($declaration = $dossier.'/'.$theme.'.php'))
    {
        continue;
    }

    $controles++;

    if (!preg_match("/'regions'\s*=>/", (string) file_get_contents($declaration)))
    {
        $anomalies[] = [
            'fichier' => 'themes/'.$theme.'/'.$theme.'.php',
            'defaut'  => 'aucune clé `regions` dans `__info()`',
            'effet'   => 'le thème ne peut recevoir ni disposition nommée ni bloc de page.',
        ];
    }
}

printf("%d contrat(s) vérifié(s) sur %d carrefour(s) et les thèmes publics.\n",
    $controles, count($carrefours));

if (!$anomalies)
{
    nf_ok("tous les contrats d'addons sont respectés");
}

foreach ($anomalies as $a)
{
    printf("  ✗ %s\n    %s\n    %s\n\n", $a['fichier'], $a['defaut'], $a['effet']);
}

echo "Un contrat de carrefour se casse en SILENCE : le module se charge, et l'erreur\n";
echo "n'apparaît qu'à l'ouverture de la page qui agrège — en production.\n";

nf_echec(sprintf('%d contrat(s) rompu(s)', count($anomalies)));
