<?php
declare(strict_types=1);
/**
 * check-textes-en-dur — aucun texte d'interface écrit en dur en français : tout passe par les traductions.
 *
 * Famille : statique
 * Diffusion : publique
 *
 * Pourquoi
 * --------
 * NeoFrag s'installe en six langues. Un texte qui passe par `lang()` a sa traduction dans chacune,
 * et `check-langs` le garantit ; un texte écrit EN DUR — `notify('Élément supprimé')`, `<th>Décision</th>`
 * dans une vue, `title = 'Retour en haut'` dans un script — reste en français sur un site anglais,
 * et aucun contrôle ne le voyait : `check-langs` ne regarde que ce qui passe déjà par `lang()`.
 *
 * Le 2026-09-23, le parcours du site en anglais (`check-mise-en-page --langue=en`) en a relevé 340
 * sur les pages qu'il visite. Mais il ne voit que les pages qu'il visite : ni les fenêtres qu'on
 * ouvre, ni les messages d'une action, ni les e-mails. La règle, posée le même jour : « tout le site
 * et tout le CMS (modules, widgets, thèmes, réglages, boutons…) doit être multilingue ». Ce contrôle
 * lit donc les SOURCES : chaque chaîne, chaque texte de gabarit, chaque chaîne de script.
 *
 * Ce qui compte comme un texte français
 * -------------------------------------
 * Une lettre accentuée propre au français, un mot que seul le français emploie (« aucun », « votre »,
 * « veuillez »…), ou deux petits mots français (« le », « des », « pour »…). Les balises, les entités,
 * les `%s` et les adresses sont retirés d'abord. Les commentaires ne sont jamais lus.
 *
 * Ce qui n'est pas un défaut
 * --------------------------
 *   - l'argument d'un appel qui TRADUIT : `lang()` (et `$lang()`, la traduction de la page d'erreur
 *     autonome, qui tourne sans le site), et ce que la bibliothèque traduit elle-même —
 *     `->title()`, `->heading()`, `->tooltip()`, `->label()`, `->modal()`, `->popover()`,
 *     `->placeholder()`, `->info()` (liste tenue avec `check-langs`) — pas `->no_data()`, que le tableau
 *     classique affiche tel quel : on lui passe `$this->lang('…')` ;
 *   - un message pour le DÉVELOPPEUR : `trigger_error()`, `error_log()`, `->debug()` ;
 *   - une expression régulière, une clé de tableau, la valeur d'une clé d'identité (`author`…) ;
 *   - une chaîne SQL : ce qu'elle insère est de la DONNÉE, traitée par les migrations ;
 *   - un nom de langue écrit dans sa langue (« Français », « Deutsch ») : c'est voulu.
 *
 * Un texte qui DOIT rester tel quel se déclare dans `EXEMPTIONS`, avec sa raison.
 *
 * Usage
 * -----
 *   php tools/check-textes-en-dur.php                    le relevé complet, fichier par fichier
 *   php tools/check-textes-en-dur.php modules/forum      un dossier (ou un fichier) seulement
 *   php tools/check-textes-en-dur.php --resume           un compte par fichier, le plus chargé d'abord
 */

require __DIR__.'/lib/outil.php';
require __DIR__.'/lib/depot.php';

[$o, $cibles] = nf_options(['resume' => FALSE, 'detail' => FALSE]);

/** Les appels dont l'argument est traduit, ou n'est pas destiné à l'interface. */
const APPELS_EXEMPTS = [
    // traduits
    'lang', 'title', 'heading', 'tooltip', 'label', 'modal', 'popover', 'placeholder', 'info',
    // pour la machine
    'preg_match', 'preg_match_all', 'preg_replace', 'preg_replace_callback', 'preg_split', 'define',
];

/**
 * Les appels qui s'adressent au DÉVELOPPEUR : tout ce qui est écrit dedans, même à travers un
 * `sprintf()`, part aux journaux ou dans un diagnostic, jamais à l'écran d'un visiteur.
 */
const APPELS_DEVELOPPEUR = ['trigger_error', 'error_log', 'debug', 'nf_refus', 'nf_journaliser_erreur'];

/**
 * Les clés dont la valeur n'est pas un texte à passer par lang() ICI : une identité (`author`…), ou
 * un texte que le produit traduit plus loin, au nom du bon module — le gabarit de notification
 * (`notif_message`, traduit par le trait `Publishable_Content` avec son `%s`), le libellé d'un type
 * de la corbeille (`label` dans `trash_types()`). `check-langs` exige leurs traductions.
 */
const CLES_EXEMPTES = ['author', 'license', 'link', 'email', 'lang', 'locale', 'notif_message'];

/** Les clés dont la valeur est, par nature, un texte d'interface. */
const CLES_LIBELLES = ['title', 'label', 'description', 'placeholder', 'tagline'];

/**
 * Les textes qui restent tels quels, avec leur raison. Clé : `chemin|texte` (le texte nettoyé) ; le
 * chemin accepte les jokers de `fnmatch()` (`themes/*` + `/*.php`).
 *
 * @var array<string, string>
 */
const EXEMPTIONS = [
    // Les noms de mois que chaque addon de langue sait LIRE dans une date saisie : c'est sa langue.
    'addons/language_fr/language_fr.php|février'     => 'lecture des dates saisies en français',
    'addons/language_fr/language_fr.php|août'        => 'lecture des dates saisies en français',
    'addons/language_fr/language_fr.php|décembre'    => 'lecture des dates saisies en français',
    'addons/language_pt/language_pt.php|terça-feira' => 'lecture des dates saisies en portugais',
    'addons/language_pt/language_pt.php|março'       => 'lecture des dates saisies en portugais',
    'addons/language_es/language_es.php|miércoles'   => 'lecture des dates saisies en espagnol',
    // Les noms de zones sont aussi leurs IDENTIFIANTS (dispositions, `Output::region()`) : traduits à
    // l'affichage par neofrag/displayables/zone.php.
    'themes/extend/extend.php|Bannière'              => 'identifiant de zone, traduit à l’affichage',
    'themes/*/*.php|Contenu'                         => 'identifiant de zone, traduit à l’affichage',
    // Le nom de l'administration, identique en français et dans la langue de ses premiers auteurs.
    'themes/admin/admin.php|Administration'          => 'nom propre du thème',
    // La valeur PAR DÉFAUT du titre d'une action : `Action` la traduit à l'affichage.
    'neofrag/actions/create.php|Ajouter'             => 'traduit par Action::__button() et Action::modal()',
    'neofrag/actions/update.php|Éditer'              => 'traduit par Action::__button() et Action::modal()',
    'neofrag/actions/delete.php|Supprimer'           => 'traduit par Action::__button() et Action::modal()',
    // Le diagnostic d'un refus de checker, à l'écran du DÉVELOPPEUR seulement (NEOFRAG_DEBUG_BAR).
    'neofrag/core/output.php|extension d\'URL refusée par le checker (demandée :' => 'diagnostic pour le développeur',
    'neofrag/core/output.php|au lieu d\'un tableau de segments'                => 'diagnostic pour le développeur',
    'neofrag/core/output.php|adresse incomplète : il manque un segment à'       => 'diagnostic pour le développeur',
    'neofrag/core/output.php|Requête refusée par un checker.'                   => 'diagnostic pour le développeur',
    'neofrag/core/output.php|(réponse 400 et non 404 parce que NEOFRAG_DEBUG_BAR est actif ;' => 'diagnostic pour le développeur',
    'neofrag/core/output.php|en production, ce motif ne part qu\'aux journaux)' => 'diagnostic pour le développeur',
    // Les réponses texte de l'adresse appelée par la tâche planifiée : pour l'exploitant.
    'modules/monitoring/controllers/index.php|Reset démo indisponible (NEOFRAG_DEMO non actif).' => 'réponse à la tâche planifiée',
    'modules/monitoring/controllers/index.php|ERREUR demo reset:'                               => 'réponse à la tâche planifiée',
    // Le copyright LIVRÉ, reconnu pour être remplacé par sa traduction (widget copyright).
    'widgets/copyright/controllers/index.php|Copyright , tous droits réservés Propulsé par' => 'le texte livré, comparé pour être traduit',
    // Un mot-clé du format iCalendar (RFC 5545), et un fragment SQL.
    'modules/calendar/controllers/index.php|DESCRIPTION:' => 'mot-clé iCalendar',
    'widgets/clock/models/clock.php|DATE_FORMAT(up.date_of_birth, "%m- ") = DATE_FORMAT(NOW(), "%m- ")' => 'fragment SQL',
];

/**
 * Les fichiers qui ne portent aucun texte d'interface, avec leur raison.
 *
 * @var array<string, string>
 */
const FICHIERS_EXEMPTS = [
    'neofrag/helpers/string.php'     => 'tables de translittération (« é » → « e ») pour fabriquer les adresses',
    'neofrag/helpers/countries.php'  => 'table source des pays, en français ; traduite par ICU (intl), ou par les lang() qui la suivent',
    'neofrag/helpers/user_agent.php' => 'noms de robots et de navigateurs',
    'neofrag/install/alpha.0.2.php'  => 'migration historique : elle écrit une DONNÉE, et ne tourne plus',
];

/** Le texte est-il exempté, pour ce fichier ? */
function exempte(string $relatif, string $texte): bool
{
    foreach (EXEMPTIONS as $cle => $raison)
    {
        [$chemin, $exempt] = explode('|', $cle, 2);

        if ($exempt === $texte && ($chemin === $relatif || fnmatch($chemin, $relatif)))
        {
            return TRUE;
        }
    }

    return FALSE;
}

/** Les noms de langues, écrits dans leur langue : un sélecteur de langue les montre ainsi, exprès. */
const NOMS_DE_LANGUES = ['Français', 'English', 'Deutsch', 'Español', 'Italiano', 'Português'];

const MOTS_FORTS  = 'aucun|aucune|votre|vos|veuillez|cette|avec|pour|dans|sont|être|été|merci|supprimer|modifier|ajouter|enregistrer|annuler|fermer|valider|rechercher|retour|suivant|précédent|connexion|déconnexion|inscription|envoyer|membres|accueil|voir|lire|fichier|dossier|nouveau|nouvelle|catégorie|élément|erreur|oui|mot de passe|réglages|paramètres|télécharger|afficher|masquer|désactiver|activer|ici|déjà|encore|toujours|jamais|mettre|choisir|sélectionner|glisser';
/** Des libellés d'un seul mot, sans accent, que seul le français écrit ainsi — ou qu'une autre langue que l'anglais traduit. */
const MOTS_SEULS  = 'continuer|utilisateur|utilisateurs|pseudo|titre|contenu|publier|brouillon|actif|inactif|non|tous|toutes|partager|imprimer|copier|importer|exporter|trier|filtrer|jour|jours|semaine|mois|heure|heures|auteur|sujet|sujets|commentaire|commentaires|lien|liens|profil|compte|groupe|groupes|nom|adresse|ville|pays|langue|confirmation|administration|terminer|installer|tester|chercher|options|statut|actions|aucune|aide|accueil|forum|message|messages|image|images|description|position|couleur|taille|ordre|type|date|version|valeur|visible|public|site|page|pages|lire|joueurs|joueur|partenaires|offres|offre|candidature|candidatures|votes|vote|sondage|sondages|dons|boutique|objets|panier|paiement|paiements|tickets|ticket|annonces|annonce|lieux|lieu|recettes|recette|citations|citation|pseudo';
const MOTS_FAIBLES = 'les|des|une|est|sur|par|pas|du|au|aux|le|la|et|un|de|en|qui|que|ne|se|il|nous|vous|ou|sa|son|ses|leur|tous|tout|toutes';

/**
 * Le texte, débarrassé de ce qui n'est pas de la langue : balises, entités, jokers, adresses.
 */
function nettoyer(string $texte): string
{
    $texte = preg_replace('#<(script|style)\b.*?</\1>#is', ' ', $texte) ?? $texte;
    $texte = strip_tags($texte);
    $texte = html_entity_decode($texte, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $texte = preg_replace('#\b(?:https?://|www\.)\S+#u', ' ', $texte) ?? $texte;
    $texte = preg_replace('/%(?:\d+\$)?[-+ 0]*\d*(?:\.\d+)?[sdfuxXbc]|\{\d+\}|\{\w+\}/', ' ', $texte) ?? $texte;

    return trim(preg_replace('/\s+/u', ' ', $texte) ?? $texte);
}

/** Le texte nettoyé est-il du français destiné à être lu ? */
function est_francais(string $propre): bool
{
    if (!preg_match('/\p{L}{2}/u', $propre) || in_array($propre, NOMS_DE_LANGUES, TRUE))
    {
        return FALSE;
    }

    // Un commentaire de code porté par une chaîne — le fichier de configuration que l'installeur
    // écrit, un script généré — s'adresse au développeur, pas au visiteur.
    if (preg_match('#^(//|/\*|\*|\#)#', $propre))
    {
        return FALSE;
    }

    // Une chaîne SQL : ce qu'elle porte est de la donnée.
    if (preg_match('/^\s*(SELECT|INSERT|UPDATE|DELETE|REPLACE|ALTER|CREATE|DROP|SHOW|WITH)\s/i', $propre))
    {
        return FALSE;
    }

    // Un mot seul, sans accent : un nom de fichier, de classe, de route — sauf un LIBELLÉ connu, écrit
    // avec sa majuscule (« Continuer », « Utilisateur ») : les identifiants sont en minuscules.
    if (!str_contains($propre, ' ') && !preg_match('/[àâçéèêëîïôûùÿœæ]/iu', $propre))
    {
        return (bool) preg_match('/^\p{Lu}(?<=^.)/u', $propre) && preg_match('/^(?:'.MOTS_FORTS.'|'.MOTS_SEULS.')[.!…:]*$/iu', $propre);
    }

    if (preg_match('/[àâçéèêëîïôûùÿœæ]/iu', $propre))
    {
        return TRUE;
    }

    if (preg_match('/(?<!\p{L})(?:'.MOTS_FORTS.')(?!\p{L})/iu', $propre))
    {
        return TRUE;
    }

    // Un libellé qui COMMENCE par un mot connu, avec sa majuscule : « Adresse IP », « Compte tiers ».
    if (preg_match('/^\p{Lu}/u', $propre) && preg_match('/^(?:'.MOTS_SEULS.')(?!\p{L})/iu', $propre))
    {
        return TRUE;
    }

    preg_match_all('/(?<!\p{L})(?:'.MOTS_FAIBLES.')(?!\p{L})/iu', $propre, $faibles);

    return count(array_unique(array_map('mb_strtolower', $faibles[0]))) >= 2;
}

/**
 * Le nom de l'appel qu'ouvre la parenthèse `$i` : `lang` pour `$this->lang(`, `Exception` pour `new Exception(`.
 */
function nom_appel(array $jetons, int $i): string
{
    for ($j = $i - 1; $j >= 0; $j--)
    {
        if (is_array($jetons[$j]) && $jetons[$j][0] === T_WHITESPACE)
        {
            continue;
        }

        if (is_array($jetons[$j]) && in_array($jetons[$j][0], [T_STRING, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED], TRUE))
        {
            $nom = $jetons[$j][1];

            return strtolower(substr($nom, (int) strrpos('\\'.$nom, '\\')));
        }

        // Une fonction rangée dans une variable : `$lang('…')`, la traduction de la page d'erreur
        // autonome, qui tourne sans le site (neofrag/helpers/erreurs.php). `check-langs` la lit déjà.
        if (is_array($jetons[$j]) && $jetons[$j][0] === T_VARIABLE)
        {
            return strtolower(ltrim($jetons[$j][1], '$'));
        }

        return '';
    }

    return '';
}

/** Le jeton significatif voisin (sans les blancs), vers l'avant ou vers l'arrière. */
function voisin(array $jetons, int $i, int $pas): mixed
{
    for ($j = $i + $pas; $j >= 0 && $j < count($jetons); $j += $pas)
    {
        if (!is_array($jetons[$j]) || !in_array($jetons[$j][0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], TRUE))
        {
            return [$j, $jetons[$j]];
        }
    }

    return [-1, NULL];
}

/**
 * Les textes français d'un fichier PHP (gabarits compris).
 *
 * @return list<array{0: int, 1: string}>  ligne, texte nettoyé
 */
function textes_php(string $source): array
{
    $jetons  = @token_get_all($source) ?: [];
    $pile    = [];
    $trouves = [];
    $script  = FALSE;   // un <script> ouvert dans un morceau de HTML, refermé dans un autre

    // Les lignes des déclarations de la corbeille : leur `'label'` est traduit par la corbeille.
    $corbeille = [];

    if (preg_match_all('/function trash_types\(\)(.*?)\n\t\}/s', $source, $blocs, PREG_OFFSET_CAPTURE))
    {
        foreach ($blocs[0] as [$bloc, $position])
        {
            $debut       = substr_count(substr($source, 0, $position), "\n") + 1;
            $corbeille[] = [$debut, $debut + substr_count($bloc, "\n")];
        }
    }

    foreach ($jetons as $i => $jeton)
    {
        if ($jeton === '(')
        {
            $pile[] = nom_appel($jetons, $i);
            continue;
        }

        if ($jeton === '[')
        {
            $pile[] = '[';
            continue;
        }

        if ($jeton === ')' || $jeton === ']')
        {
            array_pop($pile);
            continue;
        }

        if (!is_array($jeton))
        {
            continue;
        }

        [$id, $brut, $ligne] = $jeton;

        if ($id === T_INLINE_HTML)
        {
            foreach (textes_html($brut, $script) as [$decalage, $texte])
            {
                $trouves[] = [$ligne + $decalage, $texte];
            }

            continue;
        }

        if ($id === T_CONSTANT_ENCAPSED_STRING)
        {
            $valeur = $brut[0] === "'" ? str_replace(["\\'", '\\\\'], ["'", '\\'], substr($brut, 1, -1)) : stripcslashes(substr($brut, 1, -1));
        }
        else if ($id === T_ENCAPSED_AND_WHITESPACE)
        {
            $valeur = $brut;
        }
        else
        {
            continue;
        }

        // L'appel qui contient la chaîne : le plus proche, les tableaux traversés.
        $appel = '';

        for ($p = count($pile) - 1; $p >= 0; $p--)
        {
            if ($pile[$p] !== '[')
            {
                $appel = $pile[$p];
                break;
            }
        }

        if (in_array($appel, APPELS_EXEMPTS, TRUE) || str_ends_with($appel, 'exception') || array_intersect($pile, APPELS_DEVELOPPEUR))
        {
            continue;
        }

        // Une clé de tableau n'est pas affichée ; la valeur d'une clé d'identité non plus.
        [, $suivant] = voisin($jetons, $i, 1);

        if (is_array($suivant) && $suivant[0] === T_DOUBLE_ARROW)
        {
            continue;
        }

        [$k, $precedent] = voisin($jetons, $i, -1);
        $libelle = FALSE;   // la valeur d'une clé qui porte, par nature, un texte d'interface

        if (is_array($precedent) && $precedent[0] === T_DOUBLE_ARROW)
        {
            [, $cle] = voisin($jetons, $k, -1);
            $libelle = is_array($cle) && in_array(trim($cle[1], '\'"'), CLES_LIBELLES, TRUE);

            if (is_array($cle) && $cle[0] === T_CONSTANT_ENCAPSED_STRING && in_array(trim($cle[1], '\'"'), CLES_EXEMPTES, TRUE))
            {
                continue;
            }

            if (is_array($cle) && trim($cle[1], '\'"') === 'label')
            {
                foreach ($corbeille as [$debut, $fin])
                {
                    if ($ligne >= $debut && $ligne <= $fin)
                    {
                        continue 2;
                    }
                }
            }
        }

        $propre = nettoyer($valeur);

        // `'title' => 'Petites annonces'` : ni accent ni mot typique, et pourtant un libellé — la clé
        // le dit. Neuf titres de permissions l'étaient ainsi, en dur (2026-09-23). Un titre technique
        // (`'cURL'`, `'InnoDB'`) n'a pas d'espace.
        if (est_francais($propre) || ($libelle && str_contains($propre, ' ') && preg_match('/^\p{Lu}\p{Ll}/u', $propre)))
        {
            $trouves[] = [$ligne, $propre];
        }
    }

    return $trouves;
}

/**
 * Les textes français d'un morceau de HTML : les nœuds de texte, et les attributs qu'on lit.
 *
 * @return list<array{0: int, 1: string}>  décalage de ligne dans le morceau, texte nettoyé
 */
function textes_html(string $html, bool &$script = FALSE): array
{
    $trouves      = [];
    $sans_scripts = '';
    $reste        = $html;
    $position     = 0;

    // Les scripts en ligne se lisent comme du JavaScript. Un gabarit coupe souvent un script par un
    // `echo` PHP : il s'ouvre dans un morceau de HTML et se ferme dans un autre, d'où l'état `$script`,
    // qui passe d'un morceau au suivant.
    while ($reste !== '')
    {
        if ($script)
        {
            $fin  = stripos($reste, '</script');
            $code = $fin === FALSE ? $reste : substr($reste, 0, $fin);

            foreach (textes_js($code) as [$decalage, $texte])
            {
                $trouves[] = [substr_count(substr($html, 0, $position), "\n") + $decalage, $texte];
            }

            $sans_scripts .= str_repeat("\n", substr_count($code, "\n"));
            $position     += strlen($code);
            $reste         = (string) substr($reste, strlen($code));
            $script        = $fin === FALSE;
            continue;
        }

        if (!preg_match('#<(script|style)\b[^>]*>#i', $reste, $ouvrant, PREG_OFFSET_CAPTURE))
        {
            $sans_scripts .= $reste;
            break;
        }

        $avant         = substr($reste, 0, $ouvrant[0][1] + strlen($ouvrant[0][0]));
        $sans_scripts .= $avant;
        $position     += strlen($avant);
        $reste         = (string) substr($reste, strlen($avant));

        if (strtolower($ouvrant[1][0]) === 'script')
        {
            $script = TRUE;
            continue;
        }

        $fin           = stripos($reste, '</style');
        $style         = $fin === FALSE ? $reste : substr($reste, 0, $fin);
        $sans_scripts .= str_repeat("\n", substr_count($style, "\n"));
        $position     += strlen($style);
        $reste         = (string) substr($reste, strlen($style));
    }

    if (preg_match_all('/\b(?:title|placeholder|alt|aria-label|value|data-bs-original-title|data-confirm)="([^"<]*)"/i', $sans_scripts, $attributs, PREG_OFFSET_CAPTURE))
    {
        foreach ($attributs[1] as [$valeur, $position])
        {
            if (est_francais($propre = nettoyer($valeur)))
            {
                $trouves[] = [substr_count(substr($sans_scripts, 0, $position), "\n"), $propre];
            }
        }
    }

    // Les nœuds de texte, ligne par ligne. Un morceau peut commencer au milieu d'une balise (`">Texte`)
    // ou s'arrêter au milieu d'une autre (`<a title="`) : on retire ces bouts.
    $texte = preg_replace_callback('/^[^<]*?>/s', static fn (array $m): string => str_repeat("\n", substr_count($m[0], "\n")), $sans_scripts, 1) ?? $sans_scripts;
    $texte = preg_replace('/<[^>]*$/s', '', $texte) ?? $texte;
    $texte = preg_replace_callback('/<[^>]*>/s', static fn (array $m): string => str_repeat("\n", substr_count($m[0], "\n")) ?: ' ', $texte) ?? $texte;

    foreach (explode("\n", $texte) as $n => $ligne)
    {
        if (est_francais($propre = nettoyer($ligne)))
        {
            $trouves[] = [$n, $propre];
        }
    }

    return $trouves;
}

/**
 * Les chaînes françaises d'un script. Le PHP interpolé (`<?php echo $this->lang('…') ?>`) est retiré
 * d'abord : c'est justement la bonne écriture.
 *
 * @return list<array{0: int, 1: string}>  ligne (à partir de 0), texte nettoyé
 */
function textes_js(string $code): array
{
    $code    = preg_replace_callback('/<\?(?:php|=).*?\?>/s', static fn (array $m): string => str_repeat("\n", substr_count($m[0], "\n")).'0', $code) ?? $code;
    $trouves = [];
    $n       = strlen($code);
    $ligne   = 0;

    for ($i = 0; $i < $n; $i++)
    {
        $c = $code[$i];

        if ($c === "\n")
        {
            $ligne++;
            continue;
        }

        // Commentaires
        if ($c === '/' && ($code[$i + 1] ?? '') === '/')
        {
            while ($i < $n && $code[$i] !== "\n")
            {
                $i++;
            }

            $i--;
            continue;
        }

        if ($c === '/' && ($code[$i + 1] ?? '') === '*')
        {
            $fin    = strpos($code, '*/', $i + 2);
            $fin    = $fin === FALSE ? $n : $fin + 2;
            $ligne += substr_count(substr($code, $i, $fin - $i), "\n");
            $i      = $fin - 1;
            continue;
        }

        if ($c === "'" || $c === '"' || $c === '`')
        {
            $debut  = $ligne;
            $valeur = '';

            for ($i++; $i < $n && $code[$i] !== $c; $i++)
            {
                if ($code[$i] === '\\' && $i + 1 < $n)
                {
                    $valeur .= $code[++$i] === "\n" ? '' : $code[$i];
                    $ligne  += (int) ($code[$i] === "\n");
                    continue;
                }

                if ($code[$i] === "\n")
                {
                    if ($c !== '`')
                    {
                        break;  // une chaîne ordinaire ne traverse pas la ligne : lecture désynchronisée
                    }

                    $ligne++;
                }

                $valeur .= $code[$i];
            }

            if ($c === '`')
            {
                $valeur = preg_replace('/\$\{[^}]*\}/', ' ', $valeur) ?? $valeur;
            }

            if (est_francais($propre = nettoyer($valeur)))
            {
                $trouves[] = [$debut, $propre];
            }
        }
    }

    return $trouves;
}

// ── Le relevé ──────────────────────────────────────────────────────────────────────────────
// `js/flatpickr/l10n/` : les traductions du sélecteur de dates, livrées par la bibliothèque, une par langue.
$exclus  = array_merge(NF_EXCLUS, ['/langs/', '/js/flatpickr/']);
$sources = nf_fichiers(array_merge(NF_DOSSIERS_PRODUIT, ['js', 'install']), ['php', 'js'], $exclus);

if ($cibles)
{
    $cibles  = array_map(static fn (string $c): string => trim(str_replace('\\', '/', $c), '/'), $cibles);
    $sources = array_filter($sources, static function (string $relatif) use ($cibles): bool {
        foreach ($cibles as $c)
        {
            if ($relatif === $c || str_starts_with($relatif, $c.'/'))
            {
                return TRUE;
            }
        }

        return FALSE;
    }, ARRAY_FILTER_USE_KEY);
}

$releve = [];
$total  = 0;

foreach (array_diff_key($sources, FICHIERS_EXEMPTS) as $relatif => $fichier)
{
    $source = (string) file_get_contents($fichier);

    if (str_ends_with($relatif, '.js'))
    {
        $textes = array_map(static fn (array $t): array => [$t[0] + 1, $t[1]], textes_js($source));
    }
    else
    {
        $textes = textes_php($source);
    }

    foreach ($textes as [$ligne, $texte])
    {
        if (exempte($relatif, $texte))
        {
            continue;
        }

        $releve[$relatif][] = [$ligne, $texte];
        $total++;
    }
}

if (!$releve)
{
    nf_ok(sprintf('aucun texte français écrit en dur dans %d fichier(s) — tout passe par les traductions', count($sources)));
}

uasort($releve, static fn (array $a, array $b): int => count($b) <=> count($a));

foreach ($releve as $relatif => $textes)
{
    if ($o['resume'])
    {
        printf("  %4d  %s\n", count($textes), $relatif);
        continue;
    }

    printf("\n  %s (%d)\n", $relatif, count($textes));

    foreach ($textes as [$ligne, $texte])
    {
        printf("    %5d  %s\n", $ligne, mb_strimwidth($texte, 0, 110, '…'));
    }
}

echo "\n";
nf_echec(sprintf("%d texte(s) français écrit(s) en dur dans %d fichier(s) — les passer par lang() et traduire dans les six langues (php tools/fill-langs.php --dictionnaire=…)", $total, count($releve)));
