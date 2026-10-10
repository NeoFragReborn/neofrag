<?php
declare(strict_types=1);

/**
 * check-langs — tout ce qui concerne lang() : clés manquantes, formats de date, arguments du pluriel.
 *
 * Famille : statique
 * Diffusion : publique
 * Batterie : --toutes
 *
 * Pourquoi
 * --------
 * Chaque texte passé à `lang()` est cherché dans le fichier de langue de l'addon appelant, sous
 * une clé qui est l'empreinte CRC32 du texte source. Quand la clé manque, NeoFrag affiche bien le
 * texte d'origine — rien ne casse à l'écran — mais journalise un avertissement PHP : 3 846 sur une
 * seule journée de production, de quoi noyer une vraie erreur. Silencieux à l'écran, bruyant dans
 * les journaux — exactement ce qu'on ne découvre qu'en allant lire les journaux.
 *
 * Quatre vérifications, toutes sur `lang()` :
 *
 *   1. les CLÉS MANQUANTES d'une langue, appels `lang('…')` et libellés que la bibliothèque traduit
 *      elle-même (`->title('…')`, `->heading('…')`…) — le titre du champ de connexion n'avait de
 *      traduction dans AUCUNE langue et le contrôle disait « six langues complètes » (2026-09-17) ;
 *   2. les FORMATS DE DATE : chaque addon de langue expose `date()`, et `Date::short_time()` lit la
 *      clé sans filet — une coquille en allemand (`time_short`) devenait « Undefined array key » sur
 *      toute page allemande qui affiche une heure ;
 *   3. les ARGUMENTS DU PLURIEL : `lang()` RETIRE l'argument qui a servi à choisir la forme, ce qui
 *      reste part à `sprintf`. `lang('%d part|%d parts', $n)` ne lui laisse rien : ArgumentCountError,
 *      et une page 404 qui s'affiche PARFAITEMENT — trois pages en étaient là le 2026-09-20. La
 *      bonne écriture passe le compteur deux fois : `lang('%d part|%d parts', $n, $n)`.
 *
 *   4. lang() écrit dans du JSON (`json_encode`, `->json`) sans être converti en texte : il rend un
 *      objet, que le JSON écrit `{}` — la mention RGPD de l'export des membres (2026-10-03) ;
 *   5. les INTITULÉS DE MENU qu'un thème pose à son installation : le widget de navigation les traduit à
 *      l'affichage, sous SA clé — « À la une » de Granite et « Matchs » de Forge restaient en français sur
 *      un site anglais (2026-10-06).
 *
 * Et deux vérifications sur les TRADUCTIONS elles-mêmes : une traduction qui perd une forme du
 * pluriel, et une « traduction » restée identique au français dans un texte qui a l'air français
 * (« Propulsé par NeoFrag Reborn » dans les fichiers anglais du thème extend, 2026-09-23) — la clé
 * existe, rien ne manque, et le site anglais affiche du français.
 *
 * Usage
 * -----
 *   php tools/check-langs.php              le français : clés manquantes, formats, pluriels
 *   php tools/check-langs.php --toutes     les six langues d'un coup (ce que joue la batterie)
 *   php tools/check-langs.php --lang=en    une autre langue
 *   php tools/check-langs.php --fix        écrit les clés manquantes dans la langue SOURCE (fr) — jamais
 *                                          dans les autres, où ce serait faire passer du français pour
 *                                          une traduction
 */

require __DIR__.'/lib/outil.php';
require __DIR__.'/lib/depot.php';
require __DIR__.'/lib/langues.php';

[$o] = nf_options(['fix' => FALSE, 'lang' => 'fr', 'toutes' => FALSE, 'detail' => FALSE]);

if ($o['fix'] && $o['lang'] !== 'fr')
{
    nf_refus("--fix n'est permis que sur la langue source (fr)");
}

const LANGUES = NF_LANGUES;

$racine  = nf_racine();
$erreurs = [];

// ══ 1. Les formats de date : les six langues déclarent les mêmes clés ═══════════════════════════
$formats = [];

foreach (glob($racine.'/addons/language_*/language_*.php') ?: [] as $fichier)
{
    $code = substr(basename(dirname($fichier)), strlen('language_'));

    if (preg_match('/function date\(\)(.*?)\n\t\}/s', (string) file_get_contents($fichier), $bloc)
        && preg_match_all("/'([a-z_]+)'\s*=>/", $bloc[1], $cles))
    {
        sort($cles[1]);
        $formats[$code] = $cles[1];
    }
}

if (isset($formats['fr']))
{
    foreach ($formats as $code => $cles)
    {
        if ($cles !== $formats['fr'])
        {
            $erreurs[] = sprintf('formats de date : %s ne déclare pas les mêmes clés que le français (%s) — corriger addons/language_%s/language_%s.php, Date::short_time() et consorts lisent ces clés sans filet',
                $code, implode(', ', array_merge(
                    array_map(static fn ($c) => "manque $c", array_diff($formats['fr'], $cles)),
                    array_map(static fn ($c) => "en trop $c", array_diff($cles, $formats['fr']))
                )), $code, $code);
        }
    }
}

// ══ 2. Les arguments du pluriel, au tokeniseur ══════════════════════════════════════════════════

/** Le nombre de conversions `sprintf` d'un format — `%%` n'en est pas une. */
function conversions(string $format): int
{
    return preg_match_all('/%[-+ 0#\']*[0-9]*(?:\.[0-9]+)?[bcdeEfFgGosuxX]/', str_replace('%%', '', $format));
}

/**
 * Les appels `lang(...)` d'un fichier, avec leur littéral et leur nombre d'arguments.
 *
 * Par `token_get_all()` : un `lang(` écrit dans un commentaire ou dans une chaîne ne doit pas
 * être pris pour un appel. Le compteur est relevé s'il est un entier écrit en dur : on sait alors
 * LAQUELLE des formes sortira, et donc de combien d'arguments elle a besoin.
 *
 * @return list<array{int, string, int, int|null}> ligne, littéral, nombre d'arguments, compteur en dur
 */
function appels_lang(string $chemin): array
{
    $tokens = token_get_all((string) file_get_contents($chemin));
    $appels = [];

    foreach ($tokens as $i => $token)
    {
        if (!is_array($token) || $token[0] !== T_STRING || $token[1] !== 'lang')
        {
            continue;
        }

        $j = $i + 1;

        while (isset($tokens[$j]) && is_array($tokens[$j]) && $tokens[$j][0] === T_WHITESPACE)
        {
            $j++;
        }

        if (!isset($tokens[$j]) || $tokens[$j] !== '(')
        {
            continue;
        }

        $k = $j + 1;

        while (isset($tokens[$k]) && is_array($tokens[$k]) && $tokens[$k][0] === T_WHITESPACE)
        {
            $k++;
        }

        // Le premier argument doit être une chaîne écrite en dur, seule, entre apostrophes.
        if (!isset($tokens[$k]) || !is_array($tokens[$k]) || $tokens[$k][0] !== T_CONSTANT_ENCAPSED_STRING || $tokens[$k][1][0] !== "'")
        {
            continue;
        }

        $texte      = str_replace(['\\\\', "\\'"], ['\\', "'"], substr($tokens[$k][1], 1, -1));
        $ligne      = $tokens[$k][2];
        $profondeur = 0;
        $arguments  = 1;
        $fini       = FALSE;
        $compteur   = NULL;

        for ($m = $j, $n_tokens = count($tokens); $m < $n_tokens; $m++)
        {
            $t = $tokens[$m];

            if ($t === '(' || $t === '[' || $t === '{')
            {
                $profondeur++;
            }
            elseif ($t === ')' || $t === ']' || $t === '}')
            {
                if (--$profondeur === 0)
                {
                    $fini = TRUE;
                    break;
                }
            }
            elseif ($t === ',' && $profondeur === 1)
            {
                if ($arguments === 1)
                {
                    $n = $m + 1;

                    while (isset($tokens[$n]) && is_array($tokens[$n]) && $tokens[$n][0] === T_WHITESPACE)
                    {
                        $n++;
                    }

                    if (isset($tokens[$n]) && is_array($tokens[$n]) && $tokens[$n][0] === T_LNUMBER)
                    {
                        $suivant = $n + 1;

                        while (isset($tokens[$suivant]) && is_array($tokens[$suivant]) && $tokens[$suivant][0] === T_WHITESPACE)
                        {
                            $suivant++;
                        }

                        if (isset($tokens[$suivant]) && ($tokens[$suivant] === ',' || $tokens[$suivant] === ')'))
                        {
                            $compteur = (int) $tokens[$n][1];
                        }
                    }
                }

                $arguments++;
            }
        }

        // `lang('Bienvenue <a href="'.url('user').'">…')` : le texte est COMPOSÉ avant d'arriver à lang(),
        // sa clé change avec l'adresse et la langue, et aucune traduction ne le trouve jamais — il
        // restait en français partout (2026-09-23). Les morceaux variables passent en `%s`.
        $apres = $k + 1;

        while (isset($tokens[$apres]) && is_array($tokens[$apres]) && $tokens[$apres][0] === T_WHITESPACE)
        {
            $apres++;
        }

        if (($tokens[$apres] ?? NULL) === '.')
        {
            $GLOBALS['composes'][] = [$chemin, $ligne, $texte];
        }

        if ($fini)
        {
            $appels[] = [$ligne, $texte, $arguments, $compteur];
        }
    }

    return $appels;
}

$pluriels_examines = 0;
$composes          = [];

foreach (nf_fichiers(array_merge(NF_DOSSIERS_PRODUIT, ['install']), ['php']) as $rel => $fichier)
{
    foreach (appels_lang($fichier) as [$ligne, $texte, $arguments, $compteur])
    {
        if (!str_contains($texte, '|'))
        {
            continue;   // pas un pluriel : `sprintf` reçoit tout, rien à vérifier ici
        }

        $pluriels_examines++;
        $formes = explode('|', $texte);

        if ($compteur !== NULL)
        {
            // `lang()` numérote la première forme [0,1] et les suivantes 2, 3… la dernière allant jusqu'à l'infini.
            $besoin = conversions($formes[$compteur <= 1 ? 0 : min($compteur - 1, count($formes) - 1)]);
        }
        else
        {
            $besoin = max(array_map('conversions', $formes));
        }

        // Le format (1), le compteur consommé par lang() (1), puis un argument par conversion.
        $requis = 2 + $besoin;

        if ($besoin > 0 && $arguments < $requis)
        {
            $erreurs[] = sprintf("pluriel : %s:%d — lang('%s', …) : %d argument(s), %d requis — passer le compteur DEUX fois, une pour choisir la forme, une pour `%%d`",
                $rel, $ligne, mb_strimwidth($texte, 0, 70, '…'), $arguments, $requis);
        }
    }
}

// ══ 3. Les clés manquantes, langue par langue ═══════════════════════════════════════════════════

/**
 * Les dossiers qui portent un jeu de fichiers de langue. Un texte écrit dans un module est
 * cherché dans le fichier de CE module, puis dans celui du cœur.
 */
$domaines = ['neofrag' => $racine.'/neofrag'];

// L'assistant d'installation a sa propre fonction `lang()` et ses propres fichiers (`install/langs/`) :
// il tourne avant le CMS, et n'a PAS le cœur pour recours (cf. install/lib/langue.php).
$domaines['install'] = $racine.'/install';
const SANS_RECOURS_AU_COEUR = ['install'];

// Les addons de `addons/` (langues, authentificateurs) n'ont PAS de fichiers de langue à eux : leur
// chemin de recherche (`Addon::__load()`) vise `languages/<nom>/langs/`, `authenticators/<nom>/langs/`,
// qui n'existent pas, puis le cœur. Leurs textes appartiennent donc au domaine du cœur — des
// traductions rangées dans `addons/*/langs/` n'auraient jamais été lues (relevé le 2026-09-23).
foreach (['module', 'widget', 'theme'] as $type)
{
    foreach (nf_addons($type) as $nom => $dossier)
    {
        $domaines[nf_relatif($dossier)] = $dossier;
    }
}

/** Extrait les textes littéraux passés à lang() dans un fichier, commentaires exclus. */
function textes_traduits(string $source): array
{
    $textes = [];
    $source = nf_sans_commentaires($source);

    foreach (['/\blang\(\s*\'((?:[^\'\\\\]|\\\\.)*)\'/', '/\blang\(\s*"((?:[^"\\\\]|\\\\.)*)"/'] as $motif)
    {
        if (preg_match_all($motif, $source, $trouves))
        {
            foreach ($trouves[1] as $brut)
            {
                $textes[] = stripcslashes($brut);
            }
        }
    }

    // Les textes que la bibliothèque de libellés traduit ELLE-MÊME, sans lang() explicite : le titre
    // d'un champ (`form_text('login')->title('Pseudo ou adresse email')`), l'en-tête d'un panneau, une
    // infobulle, un libellé, le titre d'une modale, l'indication d'un champ. Seul un littéral qui forme
    // TOUT l'argument compte. `->modal()` manquait : « Inviter des membres », titre d'une modale des
    // événements, n'avait sa traduction que dans le module talks, et le site anglais le journalisait
    // à chaque affichage (2026-09-23). Liste tenue avec `check-textes-en-dur`.
    foreach (['/->(?:title|heading|tooltip|label|modal|popover|placeholder|info)\(\s*\'((?:[^\'\\\\]|\\\\.)*)\'\s*[,)]/', '/(?<![\w>])label\(\s*\'((?:[^\'\\\\]|\\\\.)*)\'\s*[,)]/'] as $motif)
    {
        if (preg_match_all($motif, $source, $trouves))
        {
            foreach ($trouves[1] as $brut)
            {
                // Une chaîne vide, du HTML, un nom d'icône ou une classe CSS ne sont pas des textes à traduire.
                if ($brut === '' || $brut[0] === '<' || preg_match('/^(fa[srb]? |fa-|btn-|text-|col-|badge)/', $brut) || !preg_match('/[a-zA-ZÀ-ÿ]{2}/', $brut))
                {
                    continue;
                }

                $textes[] = stripcslashes($brut);
            }
        }
    }

    // Le gabarit de notification d'un contenu publiable (`'notif_message' => 'Nouvelle actualité : %s'`) :
    // le trait `Publishable_Content` le traduit, avec son titre, au nom du module.
    if (preg_match_all('/\'notif_message\'\s*=>\s*\'((?:[^\'\\\\]|\\\\.)*)\'/', $source, $trouves))
    {
        foreach ($trouves[1] as $brut)
        {
            $textes[] = stripcslashes($brut);
        }
    }

    // Les libellés DÉCLARÉS en donnée et traduits à l'affichage : les types de contenu que chaque module
    // confie à la corbeille (`trash_types()`, `'label' => 'Galerie'`). La déclaration reste sans lang()
    // pour être testable hors HTTP ; la corbeille la traduit au nom du module déclarant. Sans ce relevé,
    // « Galerie » n'avait de traduction que par hasard, dans un autre module (2026-09-23).
    if (preg_match_all('/function trash_types\(\)(.*?)\n\t\}/s', $source, $blocs))
    {
        foreach ($blocs[1] as $bloc)
        {
            if (preg_match_all('/\'label\'\s*=>\s*\'((?:[^\'\\\\]|\\\\.)*)\'/', $bloc, $trouves))
            {
                foreach ($trouves[1] as $brut)
                {
                    $textes[] = stripcslashes($brut);
                }
            }
        }
    }

    return array_values(array_unique($textes));
}

/** Les textes employés dans chaque domaine, relevés une fois pour toutes les langues. */
$emplois = [];
$total   = 0;

foreach ($domaines as $nom => $dossier)
{
    $emplois[$nom] = [];

    // Les scripts de `js/` à la racine sont ceux du cœur : ils s'interprètent à son nom, et leurs
    // `lang()` se cherchent dans `neofrag/langs/`. Aucun domaine ne les lisait — « Parcourir », dans
    // `js/file.js`, restait en français sur le site anglais sans que ce contrôle le voie.
    $lus = [nf_relatif($dossier)];

    if ($nom === 'neofrag')
    {
        $lus[] = 'js';
        $lus[] = 'addons';
    }

    // La bibliothèque d'installation vit dans le cœur (`neofrag/installer.php`), mais ses textes
    // passent par la `lang()` de l'assistant et se traduisent dans `install/langs/` : elle appartient
    // au domaine `install`, pas au cœur.
    $fichiers = nf_fichiers($lus, ['php', 'js'], array_merge(NF_EXCLUS, ['/langs/'], $nom === 'neofrag' ? ['/neofrag/installer.php'] : []));

    if ($nom === 'install')
    {
        $fichiers['neofrag/installer.php'] = $racine.'/neofrag/installer.php';
    }

    foreach ($fichiers as $fichier)
    {
        foreach (textes_traduits((string) file_get_contents($fichier)) as $texte)
        {
            $total++;
            $emplois[$nom][sprintf('%08x', crc32($texte))] = $texte;
        }
    }
}

$langues    = $o['toutes'] ? LANGUES : [$o['lang']];
$manquantes = [];   // langue => domaine => clé => texte

foreach ($langues as $langue)
{
    $coeur = nf_langue_cles($racine.'/neofrag/langs/'.$langue.'.php');

    foreach ($emplois as $nom => $textes)
    {
        $connues = nf_langue_cles($domaines[$nom].'/langs/'.$langue.'.php');

        foreach ($textes as $cle => $texte)
        {
            // Le cœur sert de recours : un texte qu'il connaît est traduit partout — sauf là où il
            // n'est pas chargé.
            if (!isset($connues[$cle]) && (!isset($coeur[$cle]) || in_array($nom, SANS_RECOURS_AU_COEUR, TRUE)))
            {
                $manquantes[$langue][$nom][$cle] = $texte;
            }
        }
    }
}

/*
 * ── Le PLURIEL perdu en traduction ──────────────────────────────────────────────
 * `lang('%d sujet|%d sujets', $n, $n)` choisit sa forme selon $n, parmi celles que porte la
 * traduction. Traduit « %d topic », le texte n'en a plus qu'une : un site en anglais affichait
 * « 3 topic ». Le 2026-09-23, 47 traductions avaient perdu ainsi la forme plurielle de leur source,
 * dans cinq langues. Une traduction doit porter autant de formes que le français.
 */
$pluriels_perdus = 0;

foreach ($domaines as $nom => $dossier)
{
    $source = array_filter(nf_langue_valeurs($dossier.'/langs/fr.php'), static fn (string $v): bool => str_contains($v, '|'));

    if (!$source)
    {
        continue;
    }

    foreach (array_diff(LANGUES, ['fr']) as $langue)
    {
        $traduit = nf_langue_valeurs($dossier.'/langs/'.$langue.'.php');

        foreach ($source as $cle => $francais)
        {
            if (isset($traduit[$cle]) && substr_count($traduit[$cle], '|') !== substr_count($francais, '|'))
            {
                $erreurs[] = sprintf('pluriel perdu en traduction : %s/langs/%s.php, %s — « %s » traduit « %s », qui n\'a pas le même nombre de formes',
                    nf_relatif($dossier), $langue, $cle, $francais, $traduit[$cle]);
                $pluriels_perdus++;
            }
        }
    }
}

/*
 * ── La FORME d'une traduction ───────────────────────────────────────────────────
 * Une traduction garde ce qui n'est pas du texte : les balises de son modèle français (`<b>`, `<br />`), ses
 * marques de remplacement (`%s`, `%d`), et un séparateur de pluriel collé à ses formes. Le 2026-10-09, l'anglais
 * portait vingt pluriels écrits « forme| forme » (une espace en tête de la seconde forme) et un `< br / >` qui
 * s'affichait en toutes lettres — relevés sur une capture de la démo, où le forum disait « There are 1 user ».
 */
$forme = static function (string $t): array {
    preg_match_all('#</?[a-z][a-z0-9]*(?:\s[^<>]*)?/?>#i', $t, $b);
    preg_match_all('/%(?:\d+\$)?[sd]/', $t, $r);
    $balises = array_map(static fn (string $x): string => strtolower((string) preg_replace('#^<(/?)([a-z0-9]+).*$#is', '$1$2', $x)), $b[0]);
    sort($balises);

    return ['balises' => $balises, 'remplacements' => count($r[0])];
};

foreach ($domaines as $nom => $dossier)
{
    $source = nf_langue_valeurs($dossier.'/langs/fr.php');

    foreach (LANGUES as $langue)
    {
        foreach (nf_langue_valeurs($dossier.'/langs/'.$langue.'.php') as $cle => $traduit)
        {
            $ou = sprintf('%s/langs/%s.php, %s — « %s »', nf_relatif($dossier), $langue, $cle, mb_strimwidth($traduit, 0, 80, '…'));

            if (preg_match('/\s\||\|\s/', $traduit))
            {
                $erreurs[] = 'pluriel mal séparé (une espace contre « | ») : '.$ou;
            }

            if (preg_match('#<\s+/?[a-z]+[^<>]*>|<[a-z]+\s*/\s+>#i', $traduit))
            {
                $erreurs[] = 'balise mal écrite (elle s\'afficherait en toutes lettres) : '.$ou;
            }

            if ($langue !== 'fr' && isset($source[$cle]) && ($a = $forme($source[$cle])) !== ($t = $forme($traduit)))
            {
                $erreurs[] = sprintf('traduction qui ne garde pas la forme du français (%s) : %s',
                    $a['balises'] !== $t['balises'] ? 'balises '.implode(' ', $a['balises']).' → '.(implode(' ', $t['balises']) ?: 'aucune') : sprintf('%d marque(s) %%s/%%d → %d', $a['remplacements'], $t['remplacements']),
                    $ou);
            }
        }
    }
}

/*
 * ── La traduction RECOPIÉE du français ──────────────────────────────────────────
 * Un texte qui a l'air français (accent, petit mot) et dont la « traduction » est le texte même.
 * Quelques mots s'écrivent pareil dans une autre langue : ils sont déclarés ici.
 */
const IDENTIQUES_PERMIS = [
    'it:palmarès', 'it:%d palmarès|%d palmarès',
    'pt:Série', 'pt:Séries',
];

foreach ($domaines as $nom => $dossier)
{
    $source = nf_langue_valeurs($dossier.'/langs/fr.php');

    foreach (array_diff(LANGUES, ['fr']) as $langue)
    {
        foreach (nf_langue_valeurs($dossier.'/langs/'.$langue.'.php') as $cle => $traduit)
        {
            if (($source[$cle] ?? NULL) === $traduit && !in_array($langue.':'.$traduit, IDENTIQUES_PERMIS, TRUE)
                && preg_match('/[éèêàçùûôœ]|(?<!\p{L})(le|la|les|des|une|pour|avec|votre|vous|aucun|aucune)(?!\p{L})/iu', $traduit))
            {
                $erreurs[] = sprintf('traduction recopiée du français : %s/langs/%s.php, %s — « %s »', nf_relatif($dossier), $langue, $cle, mb_strimwidth($traduit, 0, 70, '…'));
            }
        }
    }
}

/*
 * ── lang() écrit dans du JSON sans être converti ────────────────────────────────
 * lang() rend un OBJET, qui ne devient texte qu'à l'affichage. `json_encode()` n'affiche pas : il
 * écrit `{}` — ou, débogage allumé, l'intérieur de l'objet. L'export RGPD des membres portait ainsi
 * une mention vide, et le titre défilant de la vitrine des mesures de mémoire (2026-10-03). Le texte
 * se convertit au passage : `(string) $this->lang(…)`. Seul l'appel DIRECT se voit ici ; un texte
 * rangé d'abord dans une variable se convertit de même, sans contrôle pour le rappeler.
 */
foreach (nf_fichiers(['neofrag', 'modules', 'widgets', 'themes', 'addons'], ['php']) + ['index.php' => nf_racine().'/index.php'] as $relatif => $chemin)
{
    $code = nf_sans_commentaires((string) file_get_contents($chemin));

    foreach (['json_encode(', '->json('] as $appel)
    {
        for ($debut = strpos($code, $appel); $debut !== FALSE; $debut = strpos($code, $appel, $debut + 1))
        {
            // L'argument, jusqu'à la parenthèse qui ferme l'appel.
            for ($fin = $debut + strlen($appel), $profondeur = 1; $fin < strlen($code) && $profondeur > 0; $fin++)
            {
                $profondeur += $code[$fin] === '(' ? 1 : ($code[$fin] === ')' ? -1 : 0);
            }

            $argument = substr($code, $debut, $fin - $debut);

            preg_match_all('/(?:\$this->|NeoFrag\(\)->|\$[a-z_]+->)?\blang\(/i', $argument, $appels, PREG_OFFSET_CAPTURE);

            foreach ($appels[0] as [, $position])
            {
                if (!preg_match('/(\(string\)|strval\()\s*$/', substr($argument, 0, $position)))
                {
                    $erreurs[] = sprintf('lang() écrit dans du JSON sans être converti : %s:%d — `(string) $this->lang(…)`, sans quoi le JSON porte `{}`',
                        $relatif, substr_count(substr($code, 0, $debut + $position), "\n") + 1);
                }
            }
        }
    }
}

foreach ($composes as [$chemin, $ligne, $texte])
{
    $erreurs[] = sprintf("texte composé passé à lang() : %s:%d — lang('%s'.…) n'a pas de clé : écrire la partie variable en %%s, passée en argument",
        nf_relatif($chemin), $ligne, mb_strimwidth($texte, 0, 60, '…'));
}

printf("%d appel(s) à lang() examinés dans %d domaines, %d pluriel(s), %d langue(s).\n", $total, count($domaines), $pluriels_examines, count($langues));

foreach ($langues as $langue)
{
    $nombre = array_sum(array_map('count', $manquantes[$langue] ?? []));
    printf("  %s : %s\n", $langue, $nombre ? "{$nombre} clé(s) manquante(s)" : 'aucune clé manquante');
}

// ══ 5. Les intitulés de menu qu'un thème pose : le widget de navigation sait les traduire ══════════
// Un thème pose son menu à l'installation (`install()`), intitulés en français ; le widget les traduit à
// l'AFFICHAGE, `$this->lang($link['title'])`, donc sous SA clé (widgets/navigation/langs). Un intitulé que ses
// fichiers ne connaissent pas reste en français sur un site anglais, sans un mot au journal qu'on lise : « À la
// une » et « Agenda » de Granite, « Matchs » et « Nous rejoindre » de Forge (2026-10-06).
$navigation = [];

foreach ($langues as $langue)
{
    $fichier = $racine.'/widgets/navigation/langs/'.$langue.'.php';
    $navigation[$langue] = is_file($fichier) ? (array) include $fichier : [];
}

foreach (glob($racine.'/themes/*/*.php') ?: [] as $fichier_theme)
{
    // Le fichier principal du thème seulement (themes/<nom>/<nom>.php), où vit install().
    if (basename($fichier_theme, '.php') !== basename(dirname($fichier_theme)))
    {
        continue;
    }

    $source = (string) file_get_contents($fichier_theme);
    $titres = [];

    // Les deux écritures en usage : `'title' => utf8_htmlentities($this->lang('…'))` et la liste
    // `[$this->lang('…'), 'adresse', …]` que les thèmes refondus parcourent.
    foreach (['/\'title\'\s*=>\s*utf8_htmlentities\(\$this->lang\(\'((?:[^\'\\\\]|\\\\.)*)\'\)\)/',
              '/\[\s*\$this->lang\(\'((?:[^\'\\\\]|\\\\.)*)\'\)\s*,\s*\'[^\']*\'/'] as $motif)
    {
        if (preg_match_all($motif, $source, $trouves))
        {
            foreach ($trouves[1] as $titre)
            {
                $titres[] = stripslashes($titre);
            }
        }
    }

    foreach (array_unique($titres) as $titre)
    {
        $cle = hash('crc32b', $titre);

        foreach ($langues as $langue)
        {
            if (!array_key_exists($cle, $navigation[$langue]))
            {
                $erreurs[] = sprintf("intitulé de menu sans traduction : « %s » (%s) posé par %s, absent de widgets/navigation/langs/%s.php — le widget le traduit à l'affichage, sous sa clé",
                    $titre, $cle, nf_relatif($fichier_theme), $langue);
            }
        }
    }
}

// ── Écriture (--fix, français seulement) ─────────────────────────────────────
// Les nouvelles lignes sont INSÉRÉES avant le crochet fermant, plutôt que le fichier réécrit à
// partir d'un tableau : certaines valeurs du cœur sont calculées, et une reconstruction les figerait.
if ($o['fix'] && !empty($manquantes['fr']))
{
    echo "\n";
    $ecrits = 0;

    foreach ($manquantes['fr'] as $nom => $cles)
    {
        // Insertion en fin de tableau, fichier créé au besoin avec son en-tête (tools/lib/langues.php).
        if (!nf_langue_ajouter($domaines[$nom].'/langs/fr.php', $cles))
        {
            printf("  ! %s : fichier de langue illisible (pas de crochet fermant) ou dossier impossible à créer\n", $nom);
            continue;
        }

        printf("  %-28s %d ajoutée(s)\n", $nom, count($cles));
        $ecrits += count($cles);
    }

    nf_ok("{$ecrits} clé(s) écrite(s) dans les fichiers français");
}

// ── Le rapport ────────────────────────────────────────────────────────────────
foreach ($manquantes as $langue => $domaines_manquants)
{
    printf("\nClés manquantes en « %s » :\n\n", $langue);

    foreach ($domaines_manquants as $nom => $cles)
    {
        printf("  %s (%d)\n", $nom, count($cles));

        foreach ($cles as $cle => $texte)
        {
            printf("      %s  %s\n", $cle, mb_strimwidth(str_replace("\n", ' ', $texte), 0, 90, '…'));
        }
    }
}

if ($erreurs)
{
    echo "\n";

    foreach ($erreurs as $erreur)
    {
        echo "  ✗ {$erreur}\n";
    }
}

$total_manquantes = array_sum(array_map(static fn (array $d): int => array_sum(array_map('count', $d)), $manquantes));

if ($total_manquantes || $erreurs)
{
    nf_echec(sprintf('%d clé(s) manquante(s), %d autre(s) défaut(s)%s', $total_manquantes, count($erreurs),
        !empty($manquantes['fr']) ? ' — ajouter les clés françaises avec --fix' : ''));
}

nf_ok(sprintf('%s complète(s), formats de date alignés, %d pluriel(s) avec leurs arguments',
    $o['toutes'] ? 'les six langues' : 'langue « '.$o['lang'].' »', $pluriels_examines));
