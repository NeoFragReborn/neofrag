<?php
declare(strict_types=1);
/**
 * check-classes-bs4 — aucun legs de Bootstrap 3 ou 4 : classes disparues, attributs `data-*` sans `bs`, classes fabriquées par concaténation.
 *
 * Famille : statique
 * Diffusion : publique
 *
 * Pourquoi cet outil existe
 * -------------------------
 * Bootstrap 5 a supprimé des classes que Bootstrap 3 et 4 fournissaient, et renommé ses
 * utilitaires directionnels. Une vue qui emploie encore l'ancien nom ne casse pas : elle s'affiche
 * simplement **de travers**, sans la moindre erreur. C'est le pire mode de défaillance — invisible
 * aux tests, invisible aux journaux, visible uniquement à l'œil, écran par écran.
 *
 * Trois cas trouvés par signalement visuel avant que ce contrôle n'existe :
 *
 *   - `.card-columns` : la galerie empilait ses albums sur une seule colonne ;
 *   - `.btn-block`    : 15 boutons dans 12 fichiers restaient serrés côte à côte ;
 *   - `.form-inline`  : la barre de recherche s'empilait verticalement.
 *
 * Ce que la première version ne voyait pas
 * ----------------------------------------
 * Elle ne cherchait les noms que dans des attributs `class="…"` littéraux. Or les bibliothèques
 * du cœur ne posent pas la classe, elles la **fabriquent** : `'float-'.$align`. Aucune recherche
 * sur `float-right` ne pouvait les trouver. Le 2026-09-20, cet angle mort cachait quatre
 * émetteurs, qui touchaient tous les sept thèmes :
 *
 *   - `neofrag/libraries/button.php`    — le pied de CHAQUE panneau et de CHAQUE modale ;
 *   - `neofrag/libraries/table.php`     — l'alignement des cellules, ×2 ;
 *   - `neofrag/libraries/table_col.php` — celui des colonnes d'actions, ×2.
 *
 * Elle ignorait aussi deux autres familles, trouvées le même jour :
 *
 *   - les attributs `data-*` restés sans le préfixe `bs` (Bootstrap 5 les ignore en silence) :
 *     l'accordéon de la FAQ ne s'ouvrait pas, et TOUS les popovers d'aide du cœur étaient vides ;
 *   - les classes livrées dans les **données** : `install/seed.sql` posait le copyright par défaut
 *     dans un `<div class="float-right">`, donc sur toute installation neuve.
 *
 * Et l'angle mort qui restait, le même d'un cran plus loin
 * --------------------------------------------------------
 * Le contrôle cherchait les attributs sous leur forme ÉCRITE, `data-dismiss=`. Or les bibliothèques
 * du cœur ne les écrivent pas davantage qu'elles n'écrivent leurs classes : elles appellent
 * `->data('dismiss', 'modal')`, et c'est `Button::__toString()` qui préfixe `data-`. Aucune
 * recherche sur `data-dismiss=` ne pouvait les trouver.
 *
 * Trois émetteurs se cachaient là, et chacun touchait TOUT le produit :
 *
 *   - `Modal::dismiss()`         — le bouton « Fermer » / « Annuler » du pied de CHAQUE modale ;
 *   - `Button::modal()`          — chaque bouton qui ouvre une modale déclarative ;
 *   - `Buttons\\Dropdown`        — chaque bouton qui déroule un menu.
 *
 * Le premier a été signalé à l'œil le 2026-09-22 : la croix de l'en-tête fermait la
 * modale, le bouton « Fermer » juste en dessous ne faisait rien.
 *
 * Et une troisième écriture, trouvée dans le journal de la démonstration
 * ---------------------------------------------------------------------
 * Ni écrit `data-toggle="…"`, ni fabriqué par `->data('toggle', …)` : le nom COMPLET passé en
 * argument, `->attr('data-toggle', 'collapse')` ou `$attrs['data-toggle'] = 'tooltip'`. Le
 * 2026-09-22, cette forme cachait trois émetteurs :
 *
 *   - `Forms\Labelable::_label()` — la bulle d'aide (i) de CHAQUE champ des formulaires `form2()`,
 *     muette au survol dans toute l'administration ;
 *   - `Label`                     — ses `->tooltip()` et `->popover()` ;
 *   - le widget `navigation`      — ses sous-menus, que son propre script cherche en
 *     `data-bs-toggle` et qui ne s'ouvraient donc jamais.
 *
 * Plus de tolérance, depuis le 2026-09-23
 * ---------------------------------------
 * Le contrôle acceptait jusque-là une classe héritée dès qu'une feuille lui redonnait un style : le
 * « pont » (`css/nf-bs5-bridge.css`) rétablissait `.form-group`, `.media`, `.close`, `.btn-block`,
 * `.card-columns`… et le verdict était vert. Il a été demandé que tout soit « bien migré et/ou
 * adapté pour BS5 » : un nom de Bootstrap 3 ou 4 dans le balisage est désormais refusé, qu'il soit
 * redéfini ou non. Une classe qui sert de crochet au produit se RENOMME (préfixe `nf-`) et se style
 * sous son propre nom — elle ne prétend plus venir de Bootstrap.
 *
 * Le même jour, il a appris quatre formes qu'il ne voyait pas :
 *
 *   - les SÉLECTEURS hérités dans les feuilles du produit (`.badge-danger { … }` dans les sept
 *     thèmes) : ils maintenaient en vie des noms que le balisage aurait dû perdre ;
 *   - les classes de GRILLE sur une cellule de tableau (`<td class="col-12 col-lg-3">`) : en
 *     Bootstrap 3 elles empilaient les cellules, en 5 elles imposent 100 % de large à chacune — les
 *     listes du forum débordaient d'un téléphone ;
 *   - une LISTE DÉROULANTE en `form-control` : Bootstrap 5 lui retire sa flèche (`form-select`) ;
 *   - la pastille FABRIQUÉE `'badge badge-'.$couleur`, invisible à une recherche de nom.
 *
 * Et le BALISAGE, qu'aucun nom ne trahit (2026-09-23, le soir)
 * -----------------------------------------------------------
 * Un téléphone a montré les indicateurs du carrousel sous la forme « 1. 2. 3. » : le carrousel
 * était écrit `<ol><li>` comme en Bootstrap 4, avec des noms de classes parfaitement valides. Le
 * contrôle lit désormais les STRUCTURES (`STRUCTURES`) : carrousel en liste ou en liens, bouton de
 * fermeture qui garde son « × », accordéon « carte + bouton-lien », `data-bs-parent` posé sur le
 * déclencheur, `role="tabcard"`, jQuery. Et les noms qu'aucun Bootstrap n'a jamais eus, nés d'un
 * renommage aveugle (`card-heading`), la grille de Bootstrap 3 (`col-sm-offset-3`), la flèche des
 * bulles de Bootstrap 4 (`.arrow`, `[x-placement]`) dans les feuilles.
 *
 * Usage
 * -----
 *   php tools/check-classes-bs4.php            code 1 s'il reste quoi que ce soit
 *   php tools/check-classes-bs4.php --epreuve  ne joue QUE l'auto-épreuve (voir epreuve())
 *
 * L'auto-épreuve tourne de toute façon avant chaque analyse : le contrôle refuse de rendre un
 * verdict s'il ne sait plus voir. `--sans-epreuve` la saute, pour déboguer le contrôle lui-même.
 */

require __DIR__.'/lib/outil.php';
require __DIR__.'/lib/depot.php';

[$o]    = nf_options(['epreuve' => FALSE, 'sans-epreuve' => FALSE]);
$racine = nf_racine();

/*
 * Classes supprimées ou renommées entre Bootstrap 3/4 et Bootstrap 5.
 *
 * La valeur donne le remplaçant, pour que le message dise quoi faire. `NULL` = pas d'équivalent
 * direct, il faut réécrire la vue.
 */
const CLASSES_MORTES = [
    // ── Bootstrap 3, supprimées dès Bootstrap 4 ──────────────────────────────
    'pull-left'            => 'float-start',
    'pull-right'           => 'float-end',
    'center-block'         => 'mx-auto d-block',
    'img-responsive'       => 'img-fluid',
    'img-circle'           => 'rounded-circle',
    'img-rounded'          => 'rounded',
    'panel'                => 'card',
    'panel-heading'        => 'card-header',
    'panel-body'           => 'card-body',
    'panel-footer'         => 'card-footer',
    'panel-title'          => 'card-title',
    'well'                 => 'p-3 border rounded bg-body-tertiary',
    'page-header'          => NULL,
    'btn-default'          => 'btn-secondary',
    'btn-xs'               => 'btn-sm',
    'label-default'        => 'badge text-bg-secondary',
    'label-primary'        => 'badge text-bg-primary',
    'label-success'        => 'badge text-bg-success',
    'label-info'           => 'badge text-bg-info',
    'label-warning'        => 'badge text-bg-warning',
    'label-danger'         => 'badge text-bg-danger',
    'progress-bar-success' => 'bg-success',
    'progress-bar-info'    => 'bg-info',
    'progress-bar-warning' => 'bg-warning',
    'progress-bar-danger'  => 'bg-danger',
    'control-label'        => 'col-form-label',
    'help-block'           => 'form-text',
    'has-error'            => 'nf-field-invalid',
    'has-success'          => NULL,
    'has-warning'          => NULL,
    'input-group-addon'    => 'input-group-text',
    'form-horizontal'      => NULL,
    'dl-horizontal'        => 'row',
    'table-condensed'      => 'table-sm',
    'alert-dismissable'    => 'alert-dismissible',
    'navbar-default'       => NULL,
    'navbar-inverse'       => NULL,
    'navbar-fixed-top'     => 'fixed-top',
    'navbar-toggle'        => 'navbar-toggler',
    'nav-justified'        => 'nav-fill',
    'caret'                => NULL,
    'glyphicon'            => NULL,
    'text-hide'            => NULL,
    'hidden-xs'            => 'd-none d-sm-block',
    'hidden-sm'            => 'd-none d-md-block',
    'hidden-md'            => 'd-none d-lg-block',
    'hidden-lg'            => 'd-none d-xl-block',
    'visible-xs'           => 'd-block d-sm-none',
    'visible-sm'           => 'd-none d-sm-block d-md-none',
    'visible-md'           => 'd-none d-md-block d-lg-none',
    'visible-lg'           => 'd-none d-lg-block d-xl-none',
    'col-xs-1'             => 'col-1',
    'col-xs-2'             => 'col-2',
    'col-xs-3'             => 'col-3',
    'col-xs-4'             => 'col-4',
    'col-xs-6'             => 'col-6',
    'col-xs-12'            => 'col-12',

    // ── Bootstrap 4, supprimées ou renommées en Bootstrap 5 ──────────────────
    'has-danger'           => 'nf-field-invalid',
    'no-gutters'           => 'g-0',
    'float-left'           => 'float-start',
    'float-right'          => 'float-end',
    'text-left'            => 'text-start',
    'text-right'           => 'text-end',
    'font-weight-bold'     => 'fw-bold',
    'font-weight-normal'   => 'fw-normal',
    'font-weight-light'    => 'fw-light',
    'font-weight-bolder'   => 'fw-bolder',
    'font-weight-lighter'  => 'fw-lighter',
    'font-italic'          => 'fst-italic',
    'text-monospace'       => 'font-monospace',
    'badge-primary'        => 'text-bg-primary',
    'badge-secondary'      => 'text-bg-secondary',
    'badge-success'        => 'text-bg-success',
    'badge-danger'         => 'text-bg-danger',
    'badge-warning'        => 'text-bg-warning',
    'badge-info'           => 'text-bg-info',
    'badge-light'          => 'text-bg-light',
    'badge-dark'           => 'text-bg-dark',
    'badge-pill'           => 'rounded-pill',
    'custom-select'        => 'form-select',
    'custom-select-sm'     => 'form-select-sm',
    'custom-select-lg'     => 'form-select-lg',
    'custom-control'       => 'form-check',
    'custom-checkbox'      => 'form-check',
    'custom-switch'        => 'form-switch',
    'custom-radio'         => 'form-check',
    'custom-file'          => 'form-control',
    'custom-range'         => 'form-range',
    'custom-control-input' => 'form-check-input',
    'custom-control-label' => 'form-check-label',
    'dropdown-menu-right'  => 'dropdown-menu-end',
    'dropdown-menu-left'   => 'dropdown-menu-start',
    'rounded-sm'           => 'rounded-1',
    'rounded-lg'           => 'rounded-3',
    'sr-only'              => 'visually-hidden',
    'sr-only-focusable'    => 'visually-hidden-focusable',
    'jumbotron'            => NULL,
    'card-deck'            => NULL,
    'card-columns'         => NULL,
    'btn-block'            => 'd-grid',
    'form-row'             => 'row g-2',
    'form-inline'          => NULL,
    'media'                => 'd-flex',
    'media-body'           => 'flex-grow-1',
    'close'                => 'btn-close',
    'input-group-append'   => 'retirer l’enveloppe : les addons sont enfants directs du groupe',
    'input-group-prepend'  => 'retirer l’enveloppe : les addons sont enfants directs du groupe',
    'thumbnail'            => 'img-thumbnail',
    'form-group'           => 'nf-field (le champ du produit) ou mb-3',
    'form-control-file'    => 'form-control',
    'form-control-range'   => 'form-range',
    'media-left'           => 'flex-shrink-0',
    'media-right'          => 'flex-shrink-0',
    'media-object'         => NULL,
    'media-heading'        => NULL,
    'embed-responsive'     => 'ratio',
    'embed-responsive-item'  => NULL,
    'embed-responsive-16by9' => 'ratio-16x9',
    'embed-responsive-4by3'  => 'ratio-4x3',

    // ── Bootstrap 3, que la première liste avait oubliées ────────────────────
    'checkbox'             => 'form-check',
    'radio'                => 'form-check',
    'checkbox-inline'      => 'form-check-inline',
    'radio-inline'         => 'form-check-inline',
    'input-group-btn'      => 'retirer l’enveloppe : le bouton est enfant direct du groupe',
    'form-control-feedback' => NULL,
    'has-feedback'         => NULL,
    'btn-outline-default'  => 'btn-outline-secondary',

    // ── AdminLTE, le thème d'administration de l'époque Bootstrap 3 ──────────
    // Inconnues de Bootstrap 5 : la boîte de statut d'une candidature n'avait AUCUN fond, et son
    // texte blanc disparaissait (2026-09-23).
    'bg-teal'              => 'text-bg-info',
    'bg-aqua'              => 'text-bg-info',
    'bg-green'             => 'text-bg-success',
    'bg-red'               => 'text-bg-danger',
    'bg-yellow'            => 'text-bg-warning',
    'bg-orange'            => 'text-bg-warning',
    'bg-blue'              => 'text-bg-primary',
    'bg-gray'              => 'text-bg-secondary',
    'bg-purple'            => NULL,
    'bg-maroon'            => NULL,
    'bg-navy'              => NULL,

    // Le carrousel de Bootstrap 3 : `.carousel-control` pour une flèche, `.item` pour une diapositive.
    // `item` est un mot trop courant pour être surveillé seul : la lecture des feuilles le cherche à
    // côté de « carousel » (voir selecteurs_herites()).
    'carousel-control'     => 'carousel-control-prev / carousel-control-next',

    // ── Nés d'un renommage AVEUGLE de « panel » en « card » (Bootstrap 3 → 4) ─────
    // `panel-heading` est devenu `card-heading`, `panel-collapse` `card-collapse` : des noms
    // qu'aucune version de Bootstrap n'a jamais définis (relevés le 2026-09-23).
    'card-heading'         => 'card-header, ou accordion-header dans un accordéon',
    'card-collapse'        => 'accordion-collapse collapse',

    // ── Les bulles de Bootstrap 4 : leur flèche s'appelait `.arrow` et se plaçait par `[x-placement]`.
    // Bootstrap 5 : `.popover-arrow`, `.tooltip-arrow`, et des variables (`--bs-popover-bg`).
    'arrow'                => 'popover-arrow / tooltip-arrow, ou les variables --bs-popover-* / --bs-tooltip-*',

    // ── jQuery UI, retiré du produit en juin 2026 ────────────────────────────
    'ui-sortable-handle'   => NULL,
    'ui-sortable'          => NULL,
    'ui-state-highlight'   => NULL,
];

/**
 * Ce qu'il faut écrire à la place d'un sélecteur hérité qui n'est pas un simple nom de classe.
 */
const SELECTEURS_REMEDES = [
    '[x-placement]'    => 'Popper 2 écrit data-popper-placement ; pour une couleur de flèche, les variables --bs-popover-* / --bs-tooltip-*',
    'item (carrousel)' => '.carousel-item',
];

/**
 * Les sélecteurs hérités d'une feuille : `[nom, ligne]` pour chaque nom surveillé.
 *
 * Les commentaires sont retirés — une feuille a le droit d'expliquer ce qu'elle a remplacé — en
 * gardant leurs sauts de ligne, pour que les numéros restent justes.
 *
 * @param array<string, string|null> $surveillees
 * @return list<array{0: string, 1: int}>
 */
function selecteurs_herites(string $texte, array $surveillees): array
{
    $texte   = preg_replace_callback('#/\*.*?\*/#s', static fn (array $c): string => str_repeat("\n", substr_count($c[0], "\n")), $texte) ?? $texte;
    $trouves = [];

    foreach (explode("\n", $texte) as $i => $ligne)
    {
        $selecteur = explode('{', $ligne)[0];

        // Le placement des bulles de Bootstrap 4 : Popper 2 écrit `data-popper-placement`.
        if (str_contains($selecteur, '[x-placement'))
        {
            $trouves[] = ['[x-placement]', $i + 1];
        }

        // La diapositive de Bootstrap 3, `.item`, à côté d'un carrousel : `.carousel-item` depuis la 4.
        if (str_contains($selecteur, 'carousel') && preg_match('/\.item\b(?!-)/', $selecteur))
        {
            $trouves[] = ['item (carrousel)', $i + 1];
        }

        if (!preg_match_all('/\.([a-zA-Z][\w-]*)/', $selecteur, $m))
        {
            continue;
        }

        foreach (array_unique($m[1]) as $classe)
        {
            if (isset($surveillees[$classe]))
            {
                $trouves[] = [$classe, $i + 1];
            }
        }
    }

    return $trouves;
}

/**
 * La grille de Bootstrap 3 : `col-sm-offset-3`, `col-md-push-2`… Bootstrap 4 a renommé les
 * décalages (`offset-sm-3`) et remplacé push/pull par `order-*` ; Bootstrap 5 a gardé ces noms.
 * Un décalage écrit à l'ancienne ne décale rien, sans une erreur.
 */
function classes_grille_bs3(): array
{
    $sortie = [];

    foreach (['xs', 'sm', 'md', 'lg'] as $rupture)
    {
        $neuf = $rupture === 'xs' ? '' : $rupture.'-';

        for ($n = 0; $n <= 12; $n++)
        {
            $sortie['col-'.$rupture.'-offset-'.$n] = 'offset-'.$neuf.$n;
            $sortie['col-'.$rupture.'-push-'.$n]   = 'order-'.$neuf.'* (push/pull n\'existent plus)';
            $sortie['col-'.$rupture.'-pull-'.$n]   = 'order-'.$neuf.'* (push/pull n\'existent plus)';
        }
    }

    return $sortie;
}

/*
 * Les STRUCTURES de composants que Bootstrap 5 a changées — le nom des classes n'y suffit pas.
 *
 * Ce contrôle ne cherchait que des noms de classes et d'attributs. Le 2026-09-23, un téléphone a
 * montré les indicateurs du carrousel afficher « 1. 2. 3. » à côté de leurs traits : le
 * carrousel était écrit `<ol class="carousel-indicators"><li …>`, le balisage de Bootstrap 4, que
 * Bootstrap 5 remplace par des `<button>`. Aucune classe morte : les noms sont les mêmes, c'est la
 * STRUCTURE qui a changé. La même passe a trouvé huit boutons de fermeture qui gardaient leur « × »
 * de Bootstrap 4 en plus de l'icône de Bootstrap 5, un accordéon « carte + bouton-lien », des
 * `role="tabcard"` nés d'un renommage aveugle, et du jQuery dans un gabarit.
 *
 * motif (sur le fichier entier, commentaires PHP retirés) => ce qu'il faut écrire à la place.
 */
const STRUCTURES = [
    '/<ol\b[^>]*\bcarousel-indicators\b/'
        => 'indicateurs de carrousel en <ol> (Bootstrap 4) : <div class="carousel-indicators"> et un <button type="button" data-bs-target data-bs-slide-to aria-label> par diapositive',
    '/<li\b[^>]*\bdata-bs-slide-to=/'
        => 'indicateur de carrousel en <li> (Bootstrap 4) : un <button type="button">',
    '/<a\b[^>]*\bcarousel-control-(?:prev|next)\b/'
        => 'flèche de carrousel en lien (Bootstrap 4) : <button type="button" class="carousel-control-…" data-bs-target="#…">',
    '/class=\\\\?["\']btn-close\b[^"\']*\\\\?["\'](?:[^>]|(?<=-)>)*>\s*(?:<span|&times;|×)/'
        => 'bouton de fermeture NON VIDE : le « × » de Bootstrap 4 s\'ajoute à l\'icône de Bootstrap 5 — un <button class="btn-close"> est vide',
    '/\brole=\\\\?["\']tabcard\b/'
        => 'role="tabcard" n\'existe pas (renommage aveugle de « tabpanel ») : role="tabpanel"',
    '/<[^>]*\bbtn-link\b[^>]*\bdata-bs-toggle=\\\\?["\']collapse|<[^>]*\bdata-bs-toggle=\\\\?["\']collapse\\\\?["\'][^>]*\bbtn-link\b/'
        => 'accordéon de Bootstrap 4 (carte + bouton-lien) : .accordion > .accordion-item > .accordion-header > .accordion-button',
    '/<[^>]*\bdata-bs-toggle=\\\\?["\']collapse\\\\?["\'][^>]*\bdata-bs-parent=|<[^>]*\bdata-bs-parent=[^>]*\bdata-bs-toggle=\\\\?["\']collapse/'
        => 'data-bs-parent sur le DÉCLENCHEUR : il n\'y fait rien — il se pose sur l\'élément .collapse',
    '/\$\((?:this|document|window|[\'"][^\'"]*[\'"])\)\s*\./'
        => 'jQuery : le produit ne le charge plus (juin 2026) — JavaScript natif, ou l\'API de Bootstrap 5 (bootstrap.Modal.getOrCreateInstance…)',
];

/**
 * Les utilitaires directionnels de Bootstrap 4, à tous les points de rupture : `ml-3`, `mr-auto`,
 * `pl-md-0`, `text-lg-left`, `float-sm-right`… Le codemod de la migration les connaissait avec
 * leurs points de rupture ; ce contrôle ne les connaissait que nus, et `ml-md-2` lui échappait.
 */
function classes_directionnelles(): array
{
    $sortie = [];

    foreach (['', 'sm-', 'md-', 'lg-', 'xl-', 'xxl-'] as $rupture)
    {
        foreach (['m', 'p'] as $propriete)
        {
            foreach (['l' => 's', 'r' => 'e'] as $ancien => $neuf)
            {
                foreach (['0', '1', '2', '3', '4', '5', 'auto'] as $taille)
                {
                    $sortie[$propriete.$ancien.'-'.$rupture.$taille] = $propriete.$neuf.'-'.$rupture.$taille;
                }
            }
        }

        foreach (['left' => 'start', 'right' => 'end'] as $ancien => $neuf)
        {
            $sortie['text-'.$rupture.$ancien]  = 'text-'.$rupture.$neuf;
            $sortie['float-'.$rupture.$ancien] = 'float-'.$rupture.$neuf;
        }
    }

    return $sortie;
}

/*
 * Les attributs de données que Bootstrap 5 a tous préfixés de `bs-`.
 *
 * Un attribut inconnu ne déclenche AUCUNE erreur : le composant reste muet, ou ignore le réglage.
 * C'est pour cela que l'accordéon de la FAQ, resté en `data-target`, ne s'ouvrait plus depuis la
 * migration sans que rien ne le signale.
 */
/*
 * Les attributs FABRIQUÉS qui portent un nom de Bootstrap 4 sans en être un.
 *
 * `data-parent` était l'attribut de l'accordéon de Bootstrap 4. C'est AUSSI, chez nous, le
 * sélecteur du conteneur que `js/sortable.js` lit (`btn.dataset.parent`) pour savoir quoi rendre
 * triable. Le préfixer casserait le tri par glissement de l'administration.
 *
 * L'exemption porte sur la PAIRE fichier + attribut, et jamais sur l'attribut seul : partout
 * ailleurs, un `data-parent` fabriqué reste un legs à corriger.
 */
const FABRIQUES_TOLERES = [
    'neofrag/libraries/buttons/sort.php' => ['id', 'update', 'parent', 'items'],
];

const ATTRIBUTS_BS = [
    'toggle', 'target', 'dismiss', 'ride', 'parent', 'slide', 'slide-to', 'spy', 'offset',
    'backdrop', 'keyboard', 'placement', 'content', 'trigger', 'interval', 'touch', 'wrap',
    'delay', 'html', 'container', 'boundary', 'animation', 'autohide',
];

/*
 * Les concaténations qui fabriquent une classe directionnelle.
 *
 * `'float-'.$align` ne contient AUCUN nom de classe mort — le nom n'existe qu'à l'exécution.
 * On refuse donc le motif lui-même : l'alignement passe par `nf_bs_align()`, qui est le seul
 * endroit du produit qui sait traduire un alignement en classe Bootstrap 5.
 */
const CONCATENATIONS = "/'(?:float|text|pull|ml|mr|pl|pr|input-group)-'\\s*\\./";

/*
 * La pastille FABRIQUÉE : `'badge badge-'.$couleur` en PHP, `'badge badge-' + couleur` en JavaScript.
 * Le nom mort n'existe qu'à l'exécution. La couleur d'une pastille passe par badge_class().
 */
const PASTILLES_FABRIQUEES = "/badge-['\"]\\s*(?:\\.|\\+)|badge-<\\?(?:php|=)/";

/*
 * La colonne de Bootstrap 3 FABRIQUÉE : `col-xs-<?php echo … ?>` dans une vue, `'col-xs-'.$n` en PHP. Le nombre n'existe
 * qu'à l'exécution, et la liste des classes mortes ne voyait rien : la fiche d'un match a gardé une colonne `col-xs-`,
 * qui n'existe plus en Bootstrap 5 — l'adversaire passait sous l'équipe (2026-10-10).
 */
const GRILLE_BS3_FABRIQUEE = "/\\bcol-xs-(?:<\\?(?:php|=)|['\"]\\s*(?:\\.|\\+))/";

/** Les fichiers où une classe peut être employée — sous la racine donnée, qui peut être un faux produit. */
function fichiers_produit(string $racine, array $extensions, array $dossiers): array
{
    return array_values(nf_fichiers($dossiers, $extensions, ['/vendor/', '/node_modules/'], TRUE, $racine));
}

/*
 * Toutes les feuilles SERVIES, `css/bootstrap.min.css` compris — c'est lui la référence.
 *
 * « Servies » est le mot important. Une feuille présente sur le disque mais qu'aucun thème ne
 * charge fait mentir ce contrôle : il la lit, y trouve la classe, et la déclare couverte alors
 * qu'elle n'atteint jamais un navigateur. C'est arrivé à `.has-danger`, dont la seule déclaration
 * vivait dans une feuille d'administration héritée — supprimée depuis pour cette raison.
 *
 * Si une telle feuille réapparaît, c'est ici qu'il faudra l'écarter.
 */
function feuilles(string $racine): array
{
    $trouves = fichiers_produit($racine, ['css'], ['css', 'themes', 'modules', 'widgets']);

    // `.min.` est écarté plus haut pour ne pas lire les bibliothèques d'autrui, mais Bootstrap
    // lui-même est la référence : sans lui, tout ce que Bootstrap 5 définit passerait pour nu.
    if (is_file($bs = $racine.'/css/bootstrap.min.css'))
    {
        $trouves[] = $bs;
    }

    return $trouves;
}


/** Les jetons de chaque attribut `class="…"`, fichier par fichier et ligne par ligne. */
function jetons_de_classe(string $contenu): array
{
    $sortie = [];
    $lignes = explode("\n", $contenu);

    foreach ($lignes as $i => $ligne)
    {
        if (!preg_match_all('/class\s*=\s*(["\'])(.*?)\1/', $ligne, $m))
        {
            continue;
        }

        foreach ($m[2] as $valeur)
        {
            /* Une valeur peut contenir un bloc PHP — `class="btn <?php echo $x ?>"`. On le
               neutralise, sinon on fabriquerait des jetons qui n'existent dans aucune page.
               (Ce commentaire est en bloc et non en ligne : une balise fermante dans un
               commentaire `//` ferme le mode PHP, et coupe le fichier en deux.) */
            $valeur = preg_replace('/<\?(?:php|=).*?\?>/s', ' ', $valeur) ?? $valeur;

            foreach (preg_split('/\s+/', trim($valeur)) ?: [] as $jeton)
            {
                if ($jeton !== '')
                {
                    $sortie[$jeton][] = $i + 1;
                }
            }
        }
    }

    return $sortie;
}

/**
 * L'analyse complete d'une racine : c'est elle que `--epreuve` rejoue sur un faux produit.
 *
 * @return array{orphelines: array, feuilles_h: array, cellules: array, listes: array, attributs: array, fabriques: array, nommes: array, concats: array, donnees: array, feuilles: int}
 */
function analyser(string $racine): array
{
    $surveillees = CLASSES_MORTES + classes_directionnelles() + classes_grille_bs3();

    $structures  = [];   // [fichier, ligne, ce qu'il faut écrire]
    $feuilles_h  = [];   // classe => [ [fichier, ligne], … ] — sélecteurs hérités dans les feuilles
    $cellules    = [];   // [fichier, ligne, extrait] — grille sur une cellule de tableau
    $listes      = [];   // [fichier, ligne, extrait] — <select class="form-control">
    $fabriques   = [];   // attribut => [ [fichier, ligne], … ]
    $nommes      = [];   // attribut => [ [fichier, ligne], … ]
    $orphelines  = [];   // classe => [ [fichier, ligne], … ]
    $attributs   = [];   // attribut => [ [fichier, ligne], … ]
    $concats     = [];   // [fichier, ligne, extrait]
    $donnees     = [];   // [fichier, ligne, classe]

    // ── 1. Les classes, dans les attributs `class="…"` ───────────────────────────
    foreach (fichiers_produit($racine, ['php', 'js', 'html'], ['modules', 'widgets', 'themes', 'neofrag', 'install', 'js']) as $fichier)
    {
        $contenu = (string) file_get_contents($fichier);
        $relatif = substr($fichier, strlen($racine) + 1);

        foreach (jetons_de_classe($contenu) as $jeton => $lignes)
        {
            if (!isset($surveillees[$jeton]))
            {
                continue;
            }

            foreach ($lignes as $ligne)
            {
                $orphelines[$jeton][] = [$relatif, $ligne];
            }
        }

        // ── 2. Les classes passées en argument PHP : attr('class', 'has-danger') ──
        if (preg_match_all('/([\'"])class\1\s*,\s*([\'"])([^\'"]+)\2/', $contenu, $m, PREG_OFFSET_CAPTURE))
        {
            foreach ($m[3] as $trouve)
            {
                foreach (preg_split('/\s+/', trim($trouve[0])) ?: [] as $jeton)
                {
                    if (isset($surveillees[$jeton]))
                    {
                        $ligne = substr_count(substr($contenu, 0, $trouve[1]), "\n") + 1;
                        $orphelines[$jeton][] = [$relatif, $ligne];
                    }
                }
            }
        }

        // ── 3. Les concaténations qui fabriquent une classe directionnelle ────────
        // Sans les commentaires : la première version signalait la phrase de `neofrag/helpers/bootstrap.php`
        // qui EXPLIQUE le motif `'float-'.$align`. Les chaînes restent : c'est dedans que vivent les classes.
        if (str_ends_with($fichier, '.php') && preg_match_all(CONCATENATIONS, nf_sans_commentaires($contenu), $m, PREG_OFFSET_CAPTURE))
        {
            foreach ($m[0] as $trouve)
            {
                $ligne   = substr_count(substr($contenu, 0, $trouve[1]), "\n") + 1;
                $lignes  = explode("\n", $contenu);
                $concats[] = [$relatif, $ligne, trim($lignes[$ligne - 1] ?? '')];
            }
        }

        // ── 3 bis. La pastille et la colonne de Bootstrap 3 fabriquées ───────────
        foreach ([PASTILLES_FABRIQUEES, GRILLE_BS3_FABRIQUEE] as $fabrique)
        {
            if (preg_match_all($fabrique, str_ends_with($fichier, '.php') ? nf_sans_commentaires($contenu) : $contenu, $m, PREG_OFFSET_CAPTURE))
            {
                foreach ($m[0] as $trouve)
                {
                    $ligne     = substr_count(substr($contenu, 0, $trouve[1]), "\n") + 1;
                    $lignes    = explode("\n", $contenu);
                    $concats[] = [$relatif, $ligne, trim($lignes[$ligne - 1] ?? '')];
                }
            }
        }

        // ── 3 ter. La grille sur une cellule de tableau, la liste en form-control ────
        foreach (explode("\n", $contenu) as $i => $texte)
        {
            if (preg_match('/<t[dh]\b[^>]*\bclass=\\\\?["\'][^"\']*\bcol(?:-(?:sm|md|lg|xl|xxl))?-(?:\d|auto|<)/', $texte))
            {
                $cellules[] = [$relatif, $i + 1, trim($texte)];
            }

            if (preg_match('/<select\b[^>]*\bclass=\\\\?["\'][^"\']*\bform-control(?:-sm|-lg)?\b/', $texte))
            {
                $listes[] = [$relatif, $i + 1, trim($texte)];
            }
        }

        // ── 3 quater. Les STRUCTURES de composants de Bootstrap 4 ────────────────
        $lu = str_ends_with($fichier, '.php') ? nf_sans_commentaires($contenu) : $contenu;

        // Un petit bloc PHP DANS une balise (un `echo $id` au milieu d'un data-bs-target) porte un `>`
        // qui arrêterait la lecture de la balise : on le remplace par un jeton neutre, sur une ligne.
        $lu = preg_replace('/<\?(?:php|=)[^\n]*?\?>/', 'X', $lu) ?? $lu;

        foreach (STRUCTURES as $motif => $remede)
        {
            if (preg_match_all($motif, $lu, $m, PREG_OFFSET_CAPTURE))
            {
                foreach ($m[0] as $trouve)
                {
                    $structures[] = [$relatif, substr_count(substr($lu, 0, (int) $trouve[1]), "\n") + 1, $remede];
                }
            }
        }

        // ── 3 quinquies. Les feuilles écrites DANS une vue ──────────────────────
        // Un bloc `<style>` est une feuille comme une autre. Le widget des partenaires y gardait
        // `.item > .row` et `.carousel-control`, les noms de Bootstrap 3 : deux règles qui ne
        // s'appliquaient plus à rien, et que la lecture des seuls fichiers `.css` ne voyait pas
        // (2026-09-23).
        if (preg_match_all('#<style\b[^>]*>(.*?)</style>#si', $contenu, $m, PREG_OFFSET_CAPTURE))
        {
            foreach ($m[1] as [$bloc, $position])
            {
                $avant = substr_count(substr($contenu, 0, (int) $position), "\n");

                foreach (selecteurs_herites((string) $bloc, $surveillees) as [$classe, $ligne])
                {
                    $feuilles_h[$classe][] = [$relatif, $avant + $ligne];
                }
            }
        }

        // ── 4. Les attributs `data-*` restés sans le préfixe `bs` ─────────────────
        foreach (ATTRIBUTS_BS as $attribut)
        {
            if (!preg_match_all('/(?<![-\w])data-'.preg_quote($attribut, '/').'=/', $contenu, $m, PREG_OFFSET_CAPTURE))
            {
                continue;
            }

            foreach ($m[0] as $trouve)
            {
                $ligne = substr_count(substr($contenu, 0, $trouve[1]), "\n") + 1;
                $attributs['data-'.$attribut][] = [$relatif, $ligne];
            }
        }

        // ── 4 bis. Les attributs `data-*` FABRIQUÉS par le cœur ────────────────
        // `->data('dismiss', 'modal')` et `->data(['toggle' => 'modal'])` produisent exactement
        // `data-dismiss=` et `data-toggle=`, que la recherche littérale ci-dessus ne voit pas.
        // La clé correcte porte le préfixe : `->data('bs-dismiss', …)`.
        preg_match_all('/->data\(\s*\[[^\[\]]*\]/s', $contenu, $tableaux, PREG_OFFSET_CAPTURE);

        foreach (ATTRIBUTS_BS as $attribut)
        {
            if (in_array($attribut, FABRIQUES_TOLERES[$relatif] ?? [], TRUE))
            {
                continue;
            }

            $vus = [];

            if (preg_match_all('/->data\(\s*[\'"]'.preg_quote($attribut, '/').'[\'"]\s*,/', $contenu, $m, PREG_OFFSET_CAPTURE))
            {
                foreach ($m[0] as $trouve)
                {
                    $vus[] = (int) $trouve[1];
                }
            }

            foreach ($tableaux[0] as $bloc)
            {
                if (preg_match('/[\'"]'.preg_quote($attribut, '/').'[\'"]\s*=>/', (string) $bloc[0]))
                {
                    $vus[] = (int) $bloc[1];
                }
            }

            foreach ($vus as $position)
            {
                $fabriques['data-'.$attribut][] = [$relatif, substr_count(substr($contenu, 0, $position), "\n") + 1];
            }
        }

        // ── 4 ter. Les attributs `data-*` NOMMÉS en argument ─────────────────────
        // `->attr('data-toggle', 'collapse')`, `['data-toggle' => …]`, `$attrs['data-toggle'] = …` :
        // le nom complet est une chaîne, suivie d'une virgule, d'une flèche ou d'un crochet. Ni la
        // recherche de `data-toggle=` ni celle de `->data('toggle'` ne la voient.
        foreach (ATTRIBUTS_BS as $attribut)
        {
            if (in_array($attribut, FABRIQUES_TOLERES[$relatif] ?? [], TRUE))
            {
                continue;
            }

            if (!preg_match_all('/[\'"]data-'.preg_quote($attribut, '/').'[\'"]\s*(?:,|=>|\])/', $contenu, $m, PREG_OFFSET_CAPTURE))
            {
                continue;
            }

            foreach ($m[0] as $trouve)
            {
                $nommes['data-'.$attribut][] = [$relatif, substr_count(substr($contenu, 0, (int) $trouve[1]), "\n") + 1];
            }
        }
    }

    // ── 5. Les classes livrées dans les DONNÉES ──────────────────────────────────
    // Le copyright par défaut vit dans `install/seed.sql`. Une classe morte y touche chaque
    // installation neuve, sans jamais passer par une vue.
    foreach (['install/seed.sql', 'install/demo.sql'] as $relatif)
    {
        if (!is_file($chemin = $racine.'/'.$relatif))
        {
            continue;
        }

        $contenu = (string) file_get_contents($chemin);

        foreach (array_keys($surveillees) as $classe)
        {
            // Les seeds portent le HTML échappé en entités : `class=&quot;float-right&quot;`.
            foreach (['class="'.$classe, 'class=&quot;'.$classe] as $motif)
            {
                $position = 0;

                while (($position = strpos($contenu, $motif, $position)) !== FALSE)
                {
                    $donnees[] = [$relatif, substr_count(substr($contenu, 0, $position), "\n") + 1, $classe];

                    $position += strlen($motif);
                }
            }
        }
    }

    // ── 6. Les sélecteurs hérités dans les FEUILLES du produit ───────────────────
    // Une règle `.badge-danger { … }` dans un thème maintient le nom en vie : le balisage peut
    // l'employer sans que rien ne casse. Bootstrap lui-même est écarté (c'est la référence), et
    // les commentaires aussi : une feuille a le droit d'expliquer ce qu'elle a remplacé.
    foreach (feuilles($racine) as $feuille)
    {
        if (str_ends_with($feuille, '/css/bootstrap.min.css'))
        {
            continue;
        }

        $relatif = substr($feuille, strlen($racine) + 1);

        foreach (selecteurs_herites((string) file_get_contents($feuille), $surveillees) as [$classe, $ligne])
        {
            $feuilles_h[$classe][] = [$relatif, $ligne];
        }
    }

    return [
        'orphelines' => $orphelines,
        'structures' => $structures,
        'feuilles_h' => $feuilles_h,
        'cellules'   => $cellules,
        'listes'     => $listes,
        'attributs'  => $attributs,
        'fabriques'  => $fabriques,
        'nommes'     => $nommes,
        'concats'    => $concats,
        'donnees'    => $donnees,
        'feuilles'   => count(feuilles($racine)),
    ];
}

/**
 * L'ÉPREUVE À L'ENVERS.
 *
 * Un contrôle vert ne prouve rien tant qu'on ne l'a pas vu **refuser** le défaut qu'il est censé
 * attraper. Le 2026-09-20, deux garde-fous écrits dans la journée se sont déclarés verts en étant
 * aveugles : l'un avait perdu, en corrigeant un faux positif, la capacité de voir quoi que ce soit ;
 * l'autre signalait ses propres commentaires. Cette vérification se refaisait à la main à chaque
 * séance ; elle vit désormais dans l'outil.
 *
 * On fabrique un faux produit minuscule, on y plante un défaut de chaque famille, et on exige que
 * chacun soit signalé. Puis on refait la même chose avec un produit PROPRE, et on exige le silence —
 * sans quoi un contrôle qui refuse tout passerait aussi cette épreuve.
 */
function epreuve(): int
{
    $racine = sys_get_temp_dir().'/nf-epreuve-bs-'.bin2hex(random_bytes(4));

    // Le faux produit : une feuille de style qui ne définit QUE du Bootstrap 5, pour qu'aucune
    // classe morte ne puisse passer pour couverte.
    $propre = [
        'css/bootstrap.min.css'        => '.float-end{float:right}.text-end{text-align:right}.g-0{--bs-gutter-x:0}.form-group{x:y}',
        'css/produit.css'              => '/* remplace .form-group */ .nf-field{margin-bottom:1rem} .btn-close{width:24px}',
        'modules/x/views/bon.tpl.php'  => '<div class="row g-0"><span class="float-end">ok</span><td class="text-end">1</td><select class="form-select"></select><span class="badge <?php echo badge_class($c) ?>">x</span></div>'
                                        .'<div class="carousel-indicators"><button type="button" data-bs-target="#c" data-bs-slide-to="0"></button></div><button type="button" class="carousel-control-prev" data-bs-target="#c"></button>'
                                        .'<button type="button" class="btn-close" data-bs-dismiss="modal"></button><div role="tabpanel"></div><button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#q"></button><div id="q" class="accordion-collapse collapse" data-bs-parent="#a"></div>'
                                        ."<style>\n#c .carousel-item > .row, #c .carousel-control-prev { width: 40px; }\n.menu-item { x: y; }\n</style>",
        'neofrag/libraries/bon.php'    => "<?php \$h->attr('class', 'float-end'); \$c = nf_bs_align(\$align, 'float'); \$b->data('bs-dismiss', 'modal')->data(['bs-toggle' => 'modal']); \$n->attr('data-bs-toggle', 'collapse'); \$a['data-bs-html'] = 'true';",
        'install/seed.sql'             => "INSERT INTO `nf_settings` VALUES ('nf_copyright', 'x <div class=&quot;float-end&quot;>y</div>');",
    ];

    // Les cinq défauts, un par famille détectée.
    $defauts = [
        'orphelines' => ['modules/x/views/mal.tpl.php',   '<div class="row no-gutters">rien ne la définit</div>'],
        'argument'   => ['neofrag/libraries/mal.php',     "<?php \$h->attr('class', 'has-danger');"],
        'attributs'  => ['modules/x/views/attr.tpl.php',  '<a data-bs-toggle="collapse" data-target="#x">x</a>'],
        'fabriques'  => ['neofrag/libraries/fab.php',     "<?php \$b->data('dismiss', 'modal'); \$c->data(['toggle' => 'modal']);"],
        'nommes'     => ['widgets/x/controllers/nom.php', "<?php \$n->attr('data-toggle', 'collapse'); \$a['data-content'] = 'x';"],
        'concats'    => ['neofrag/libraries/concat.php',  "<?php \$h->attr('class', 'float-'.\$align);"],
        'donnees'    => ['install/demo.sql',              "INSERT INTO `nf_settings` VALUES ('nf_copyright', 'x <div class=&quot;float-right&quot;>y</div>');"],
        'redefinie'  => ['modules/x/views/pont.tpl.php',  '<div class="form-group">une feuille la redéfinit, elle reste héritée</div>'],
        'feuilles_h' => ['themes/x/css/style.css',        '.badge-danger { color: red; }'],
        'cellules'   => ['modules/x/views/cellule.tpl.php', '<td class="col-12 col-lg-3">x</td>'],
        'listes'     => ['widgets/x/views/liste.tpl.php', '<select class="form-control form-control-sm" name="x"></select>'],
        'pastille'   => ['modules/x/models/score.php',    "<?php return '<span class=\"badge badge-'.\$c.'\">';"],
        'grille_bs3' => ['modules/x/views/grille.tpl.php', '<div class="col-sm-offset-3 col-sm-5">x</div>'],
        'renommage'  => ['widgets/x/views/panneau.tpl.php', '<div class="card-heading">x</div>'],
        'carrousel_ol'     => ['widgets/x/views/c1.tpl.php', '<ol class="carousel-indicators"><li data-bs-target="#c" data-bs-slide-to="0"></li></ol>'],
        'carrousel_fleche' => ['widgets/x/views/c2.tpl.php', '<a class="carousel-control-prev" href="#c" role="button" data-bs-slide="prev"></a>'],
        'fermeture_pleine' => ['neofrag/libraries/f.php',   "<?php return '<button type=\"button\" class=\"btn-close\" data-bs-dismiss=\"modal\"><span aria-hidden=\"true\">&times;</span></button>';"],
        'tabcard'          => ['widgets/x/views/t.tpl.php', '<div class="tab-pane" role="tabcard"></div>'],
        'accordeon_bs4'    => ['modules/x/views/a.tpl.php', '<button class="btn btn-link" type="button" data-bs-toggle="collapse" data-bs-target="#q">x</button>'],
        'parent_declencheur' => ['widgets/x/views/p.tpl.php', '<a data-bs-toggle="collapse" data-bs-parent="#x" href="#y">x</a>'],
        'fleche_bs4'       => ['themes/x/css/bulle.css',   '.bs-popover-auto[x-placement^="top"] { color: red; }'],
        'style_vue'        => ['widgets/x/views/s.tpl.php', "<style>\n#c-<?php echo \$id ?> .carousel-control {\n\twidth: 40px;\n}\n</style>\n<div></div>"],
        'item_carrousel'   => ['widgets/x/views/i.tpl.php', "<style>\n#partners-carousel .item > .row { padding: 0; }\n</style>"],
        'jquery'           => ['neofrag/libraries/j.php',   "<?php return '<button onclick=\"$(this).parents(\'.alert\').alert(\'close\');\">x</button>';"],
    ];

    $ecrire = function(string $relatif, string $contenu) use ($racine): void {
        $chemin = $racine.'/'.$relatif;
        @mkdir(dirname($chemin), 0777, TRUE);
        file_put_contents($chemin, $contenu);
    };

    $effacer = function(string $dossier) use (&$effacer): void {
        foreach (array_diff(scandir($dossier) ?: [], ['.', '..']) as $entree)
        {
            $chemin = $dossier.'/'.$entree;
            is_dir($chemin) ? $effacer($chemin) : @unlink($chemin);
        }

        @rmdir($dossier);
    };

    $echecs = [];

    // ── 1. Le produit propre doit passer en silence ──────────────────────────
    foreach ($propre as $relatif => $contenu)
    {
        $ecrire($relatif, $contenu);
    }

    $vu = analyser($racine);

    foreach (['orphelines', 'structures', 'feuilles_h', 'cellules', 'listes', 'attributs', 'fabriques', 'nommes', 'concats', 'donnees'] as $famille)
    {
        if ($vu[$famille])
        {
            $echecs[] = sprintf('produit PROPRE : %s signale %d chose(s) — faux positif',
                $famille, count($vu[$famille]));
        }
    }

    // ── 2. Chaque défaut, planté seul, doit être refusé ──────────────────────
    foreach ($defauts as $famille => [$relatif, $contenu])
    {
        $ecrire($relatif, $contenu);
        $vu = analyser($racine);

        // Un défaut « argument » ou « redéfinie » se range dans `orphelines`, une pastille dans
        // `concats` : ce sont les mêmes listes.
        $attendu = ['argument' => 'orphelines', 'redefinie' => 'orphelines', 'pastille' => 'concats', 'grille_bs3' => 'orphelines', 'renommage' => 'orphelines',
                    'carrousel_ol' => 'structures', 'carrousel_fleche' => 'structures', 'fermeture_pleine' => 'structures', 'tabcard' => 'structures',
                    'accordeon_bs4' => 'structures', 'parent_declencheur' => 'structures', 'jquery' => 'structures', 'fleche_bs4' => 'feuilles_h',
                    'style_vue' => 'feuilles_h', 'item_carrousel' => 'feuilles_h'][$famille] ?? $famille;

        if (!$vu[$attendu])
        {
            $echecs[] = sprintf('%-11s : le défaut planté dans %s n’a PAS été vu', $famille, $relatif);
        }

        @unlink($racine.'/'.$relatif);
    }

    // `install/demo.sql` est supprimé par la boucle : on le remet propre pour ne pas fausser un
    // éventuel ajout de famille plus bas.
    $effacer($racine);

    echo "ÉPREUVE À L'ENVERS de check-classes-bs4\n\n";
    printf("  %d famille(s) de défaut plantée(s), plus un produit propre.\n\n", count($defauts));

    if ($echecs)
    {
        echo "LE CONTRÔLE EST AVEUGLE OU TROP BAVARD :\n\n";

        foreach ($echecs as $echec)
        {
            echo '  '.$echec."\n";
        }

        echo "\nUn contrôle qui ne refuse pas le défaut qu'il vise ne protège rien.\n";

        return 1;
    }

    echo "  Chaque défaut a été refusé, et le produit propre est passé en silence.\n";
    echo "épreuve OK.\n";

    return 0;
}

if ($o['epreuve'])
{
    epreuve() === 0 ? nf_ok('auto-épreuve : chaque défaut est refusé, le produit propre passe en silence') : nf_echec('auto-épreuve : le contrôle est aveugle ou trop bavard');
}

/*
 * L'auto-épreuve tourne d'abord, en silence, à CHAQUE lancement.
 *
 * La mettre derrière une option aurait laissé ouverte la porte qu'elle sert à fermer : un contrôle
 * qui se déclare vert alors qu'un de ses détecteurs ne voit plus rien. Elle coûte neuf fichiers
 * temporaires et six analyses sur un arbre minuscule — rien de mesurable. `--sans-epreuve` existe
 * pour le jour où l'on débogue le contrôle lui-même.
 */
if (!$o['sans-epreuve'])
{
    ob_start();
    $verdict = epreuve();
    $detail  = (string) ob_get_clean();

    if ($verdict !== 0)
    {
        echo $detail;
        nf_refus("ce contrôle ne sait plus voir ce qu'il surveille : son verdict sur le produit ne voudrait rien dire");
    }
}

$surveillees = CLASSES_MORTES + classes_directionnelles() + classes_grille_bs3();

[
    'orphelines' => $orphelines,
    'structures' => $structures,
    'feuilles_h' => $feuilles_h,
    'cellules'   => $cellules,
    'listes'     => $listes,
    'attributs'  => $attributs,
    'fabriques'  => $fabriques,
    'nommes'     => $nommes,
    'concats'    => $concats,
    'donnees'    => $donnees,
    'feuilles'   => $nb_feuilles,
] = analyser($racine);

// ── Le verdict ───────────────────────────────────────────────────────────────
printf("%d classe(s) surveillée(s), %d attribut(s) Bootstrap, %d feuille(s) de style lues.\n\n",
    count($surveillees), count(ATTRIBUTS_BS), $nb_feuilles);

$problemes = 0;

if ($orphelines)
{
    echo "CLASSES DE BOOTSTRAP 3 OU 4 DANS LE BALISAGE — à migrer, même si une feuille les redéfinit :\n\n";

    foreach ($orphelines as $classe => $lieux)
    {
        $remplacant = CLASSES_MORTES[$classe] ?? classes_directionnelles()[$classe] ?? classes_grille_bs3()[$classe] ?? NULL;

        printf("  .%s  →  %s\n", $classe, $remplacant ?? 'pas d’équivalent direct, la vue est à réécrire');

        foreach ($lieux as [$fichier, $ligne])
        {
            printf("      %s:%d\n", $fichier, $ligne);
        }

        echo "\n";
        $problemes++;
    }
}

if ($structures)
{
    echo "STRUCTURES DE COMPOSANTS DE BOOTSTRAP 4 — les noms sont bons, le BALISAGE ne l'est pas :\n\n";

    foreach ($structures as [$fichier, $ligne, $remede])
    {
        printf("  %s:%d\n      %s\n", $fichier, $ligne, $remede);
        $problemes++;
    }

    echo "\n";
}

if ($feuilles_h)
{
    echo "SÉLECTEURS HÉRITÉS DANS LES FEUILLES DU PRODUIT — ils gardent en vie un nom que le balisage\n";
    echo "a perdu, ou devrait perdre :\n\n";

    foreach ($feuilles_h as $classe => $lieux)
    {
        printf("  %s  →  %s\n", isset(SELECTEURS_REMEDES[$classe]) ? $classe : '.'.$classe, SELECTEURS_REMEDES[$classe] ?? CLASSES_MORTES[$classe] ?? classes_directionnelles()[$classe] ?? 'à retirer, ou à renommer en classe du produit');

        foreach ($lieux as [$fichier, $ligne])
        {
            printf("      %s:%d\n", $fichier, $ligne);
        }

        echo "\n";
        $problemes++;
    }
}

if ($cellules)
{
    echo "CLASSE DE GRILLE SUR UNE CELLULE DE TABLEAU — en Bootstrap 5, c'est une largeur imposée\n";
    echo "(100 % à chaque cellule sous le point de rupture) : le tableau déborde :\n\n";

    foreach ($cellules as [$fichier, $ligne, $extrait])
    {
        printf("  %s:%d\n      %s\n", $fichier, $ligne, substr($extrait, 0, 110));
        $problemes++;
    }

    echo "\n";
}

if ($listes)
{
    echo "LISTE DÉROULANTE EN `form-control` — Bootstrap 5 lui retire sa flèche : `form-select` :\n\n";

    foreach ($listes as [$fichier, $ligne, $extrait])
    {
        printf("  %s:%d\n      %s\n", $fichier, $ligne, substr($extrait, 0, 110));
        $problemes++;
    }

    echo "\n";
}

if ($attributs)
{
    echo "ATTRIBUTS `data-*` SANS LE PRÉFIXE `bs` — Bootstrap 5 les ignore, le composant est muet :\n\n";

    foreach ($attributs as $attribut => $lieux)
    {
        printf("  %s=  →  data-bs-%s=\n", $attribut, substr($attribut, 5));

        foreach ($lieux as [$fichier, $ligne])
        {
            printf("      %s:%d\n", $fichier, $ligne);
        }

        echo "\n";
        $problemes++;
    }
}

if ($fabriques)
{
    echo "ATTRIBUTS `data-*` FABRIQUÉS PAR LE CŒUR — `->data('x', …)` écrit `data-x`, que Bootstrap 5\n";
    echo "ignore en silence. La clé doit porter le préfixe : `->data('bs-x', …)` :\n\n";

    foreach ($fabriques as $attribut => $lieux)
    {
        printf("  ->data('%s', …)  →  ->data('bs-%s', …)\n", substr($attribut, 5), substr($attribut, 5));

        foreach ($lieux as [$fichier, $ligne])
        {
            printf("      %s:%d\n", $fichier, $ligne);
        }

        echo "\n";
        $problemes++;
    }
}

if ($nommes)
{
    echo "ATTRIBUTS `data-*` NOMMÉS EN ARGUMENT — `->attr('data-x', …)` ou `\$attrs['data-x']` écrit `data-x`,\n";
    echo "que Bootstrap 5 ignore en silence. Le nom doit porter le préfixe : `data-bs-x` :\n\n";

    foreach ($nommes as $attribut => $lieux)
    {
        printf("  '%s'  →  'data-bs-%s'\n", $attribut, substr($attribut, 5));

        foreach ($lieux as [$fichier, $ligne])
        {
            printf("      %s:%d\n", $fichier, $ligne);
        }

        echo "\n";
        $problemes++;
    }
}

if ($concats)
{
    echo "CLASSE FABRIQUÉE PAR CONCATÉNATION — l'alignement passe par nf_bs_align(), la couleur d'une\n";
    echo "pastille par badge_class() :\n\n";

    foreach ($concats as [$fichier, $ligne, $extrait])
    {
        printf("  %s:%d\n      %s\n", $fichier, $ligne, substr($extrait, 0, 110));
        $problemes++;
    }

    echo "\n";
}

if ($donnees)
{
    echo "CLASSE MORTE LIVRÉE DANS LES DONNÉES — elle atteint chaque installation neuve :\n\n";

    foreach ($donnees as [$fichier, $ligne, $classe])
    {
        printf("  %s:%d  .%s  →  %s\n", $fichier, $ligne, $classe, CLASSES_MORTES[$classe] ?? '?');
        $problemes++;
    }

    echo "\nPenser aussi à la migration pour les installations DÉJÀ en service : corriger le seed\n";
    echo "ne touche que les sites créés après.\n\n";
}

if (!$problemes)
{
    nf_ok('aucun legs de Bootstrap 3 ou 4 : classes, structures de composants, feuilles, données et classes fabriquées');
}

echo "Deux issues pour une classe héritée : réécrire avec l'équivalent Bootstrap 5, ou — si elle sert\n";
echo "de crochet au produit — la renommer en classe du produit (préfixe nf-), stylée sous ce nom.\n";
echo "La rétablir sous son ancien nom dans une feuille n'est plus une issue.\n";

nf_echec("{$problemes} point(s) à traiter");
