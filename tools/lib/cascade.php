<?php
declare(strict_types=1);
/**
 * cascade — lire des feuilles CSS et dire qui gagne, sans rendre de page.
 *
 * Diffusion : publique
 *
 * La feuille d'un widget ou d'un module est servie APRÈS celle du thème. À égalité de
 * spécificité, c'est donc l'addon qui gagne : une règle de thème qui vise une classe d'addon ne
 * s'applique pas, et rien ne le dit. `check-cascade` s'en sert pour refuser ces déclarations ;
 * `ToolsCascadeTest` l'éprouve sur ce qu'elle doit attraper ET sur ce qu'elle doit laisser.
 *
 * On ne construit pas un moteur CSS : on ne tranche que les cas où la réponse est certaine.
 */

/**
 * Les règles d'une feuille, à plat : un élément par sélecteur et par propriété.
 *
 * Un gabarit de thème est du PHP : on remplace ce qu'il interpole pour ne garder que le CSS. La
 * profondeur des accolades dit ce qui est dans un `@media` ou un `@layer`.
 *
 * @return list<array{selecteur: string, propriete: string, valeur: string, important: bool, couche: bool, ligne: int}>
 */
function nf_cascade_regles(string $source): array
{
    // On remplace par AUTANT de retours à la ligne : sinon les numéros rapportés dérivent, et un
    // rapport qui envoie à la mauvaise ligne ne sert à rien.
    $garde_les_lignes = static fn (array $t): string => str_repeat("\n", substr_count($t[0], "\n"));

    $source = (string) preg_replace_callback('/<\?(?:php\b|=).*?\?>/s', $garde_les_lignes, $source);
    $source = (string) preg_replace_callback('#/\*.*?\*/#s', $garde_les_lignes, $source);

    $regles     = [];
    $tampon     = '';
    $selecteurs = [];
    $couches    = [];
    $ligne      = 1;
    $longueur   = strlen($source);

    for ($i = 0; $i < $longueur; $i++)
    {
        $c = $source[$i];

        if ($c === "\n")
        {
            $ligne++;
        }

        if ($c === '{')
        {
            $tete   = trim((string) preg_replace('/\s+/', ' ', $tampon));
            $tampon = '';

            // `@media`, `@supports`, `@layer`, `@keyframes` : on empile sans sélecteur, en retenant les couches.
            if ($tete !== '' && $tete[0] === '@')
            {
                $selecteurs[] = NULL;
                $couches[]    = (bool) preg_match('/^@layer\b/', $tete);
            }
            else
            {
                $selecteurs[] = $tete;
                $couches[]    = FALSE;
            }

            continue;
        }

        if ($c === '}')
        {
            $tete = $selecteurs ? end($selecteurs) : NULL;

            if (is_string($tete) && $tete !== '' && trim($tampon) !== '')
            {
                $dans_couche = in_array(TRUE, $couches, TRUE);

                foreach (explode(';', $tampon) as $declaration)
                {
                    if (!str_contains($declaration, ':'))
                    {
                        continue;
                    }

                    [$propriete, $valeur] = explode(':', $declaration, 2);

                    $propriete = strtolower(trim($propriete));
                    $valeur    = trim($valeur);

                    if ($propriete === '' || !preg_match('/^-?[a-z][a-z0-9-]*$/', $propriete))
                    {
                        continue;   // ni une propriété, ni une variable : un reste de sélecteur
                    }

                    foreach (explode(',', $tete) as $un)
                    {
                        $un = trim($un);

                        if ($un === '')
                        {
                            continue;
                        }

                        $regles[] = [
                            'selecteur' => $un,
                            'propriete' => $propriete,
                            'valeur'    => $valeur,
                            'important' => (bool) preg_match('/!\s*important/i', $valeur),
                            'couche'    => $dans_couche,
                            'ligne'     => $ligne,
                        ];
                    }
                }
            }

            array_pop($selecteurs);
            array_pop($couches);
            $tampon = '';

            continue;
        }

        $tampon .= $c;
    }

    return $regles;
}

/**
 * La spécificité d'un sélecteur : [identifiants, classes, éléments].
 *
 * Les pseudo-éléments comptent comme un élément, les pseudo-classes comme une classe, le contenu
 * de `:not(…)` compte, son enveloppe non, et `*` ne compte pas.
 *
 * @return array{0: int, 1: int, 2: int}
 */
function nf_cascade_specificite(string $selecteur): array
{
    $selecteur = (string) preg_replace('/:(?:not|is|where)\(/', ' ', $selecteur);
    $selecteur = str_replace(')', ' ', $selecteur);

    return [
        preg_match_all('/#[A-Za-z0-9_-]+/', $selecteur),
        preg_match_all('/\.[A-Za-z0-9_-]+/', $selecteur)
            + preg_match_all('/\[[^\]]*\]/', $selecteur)
            + preg_match_all('/(?<!:):(?!:)(?!not\b|is\b|where\b)[a-z-]+/i', $selecteur),
        preg_match_all('/::[a-z-]+/i', $selecteur)
            + preg_match_all('/(?:^|[\s>+~])([a-z][a-z0-9]*)/i', $selecteur),
    ];
}

/** Le dernier bloc d'un sélecteur : ce qu'il vise vraiment. */
function nf_cascade_dernier_bloc(string $selecteur): string
{
    $morceaux = preg_split('/\s*[\s>+~]\s*/', trim($selecteur)) ?: [''];

    return (string) end($morceaux);
}

/** Les classes d'un bloc de sélecteur, triées. @return list<string> */
function nf_cascade_classes(string $bloc): array
{
    preg_match_all('/\.([A-Za-z0-9_-]+)/', $bloc, $trouves);

    $classes = $trouves[1];
    sort($classes);

    return array_values(array_unique($classes));
}

/**
 * Les états d'un sélecteur : pseudo-classes et pseudo-élément.
 *
 * `.b:hover` ne s'applique que SOUS la souris : il n'écrase pas la déclaration au repos. Une règle
 * d'addon ne couvre celle du thème que si elle ne demande aucun état de plus qu'elle.
 *
 * @return list<string>
 */
function nf_cascade_etats(string $selecteur): array
{
    preg_match_all('/::?[A-Za-z-]+(?:\([^)]*\))?/', $selecteur, $trouves);

    $etats = [];

    foreach ($trouves[0] as $un)
    {
        // `:not(…)`, `:is(…)` et `:where(…)` ne sont pas des états : ils restreignent la cible.
        if (preg_match('/^:(?:not|is|where)\(/', $un))
        {
            continue;
        }

        $etats[] = strtolower((string) preg_replace('/\([^)]*\)/', '', $un));
    }

    sort($etats);

    return array_values(array_unique($etats));
}

/**
 * Les sélecteurs d'attribut d'un bloc : eux aussi restreignent la cible.
 *
 * `.nf-espace-lien[href$="/logout"]:hover` ne vise QUE le lien de déconnexion : il n'écrase pas la
 * règle que le thème écrit pour tous les liens du menu. Le nom de l'élément, lui, ne restreint
 * rien en pratique : `span.monitoring-icon-status` nomme l'élément que le module écrit lui-même,
 * donc toutes les instances de la classe.
 *
 * @return list<string>
 */
function nf_cascade_attributs(string $bloc): array
{
    preg_match_all('/\[[^\]]*\]/', $bloc, $trouves);

    $attributs = $trouves[0];
    sort($attributs);

    return array_values(array_unique($attributs));
}

/**
 * Les formes courtes, par ce qu'elles posent VRAIMENT.
 *
 * `font: 2em/1 X` pose `line-height` — c'est le piège de la leçon 13, qui a laissé un mélange que
 * personne n'avait dessiné. À l'inverse, `border: 1px solid red` ne pose PAS `border-radius` :
 * ranger les deux dans une même « famille border » ferait crier à tort. D'où cette table, qui
 * nomme le contenu de chaque forme courte au lieu de le deviner par son préfixe.
 *
 * @var array<string, list<string>>
 */
const NF_CASCADE_FORMES = [
    'font'            => ['font-style', 'font-variant', 'font-weight', 'font-stretch', 'font-size', 'line-height', 'font-family'],
    'background'      => ['background-color', 'background-image', 'background-position', 'background-size', 'background-repeat', 'background-attachment', 'background-origin', 'background-clip'],
    'border'          => ['border-top-width', 'border-right-width', 'border-bottom-width', 'border-left-width', 'border-top-style', 'border-right-style', 'border-bottom-style', 'border-left-style', 'border-top-color', 'border-right-color', 'border-bottom-color', 'border-left-color'],
    'border-width'    => ['border-top-width', 'border-right-width', 'border-bottom-width', 'border-left-width'],
    'border-style'    => ['border-top-style', 'border-right-style', 'border-bottom-style', 'border-left-style'],
    'border-color'    => ['border-top-color', 'border-right-color', 'border-bottom-color', 'border-left-color'],
    'border-top'      => ['border-top-width', 'border-top-style', 'border-top-color'],
    'border-right'    => ['border-right-width', 'border-right-style', 'border-right-color'],
    'border-bottom'   => ['border-bottom-width', 'border-bottom-style', 'border-bottom-color'],
    'border-left'     => ['border-left-width', 'border-left-style', 'border-left-color'],
    'border-radius'   => ['border-top-left-radius', 'border-top-right-radius', 'border-bottom-right-radius', 'border-bottom-left-radius'],
    'margin'          => ['margin-top', 'margin-right', 'margin-bottom', 'margin-left'],
    'padding'         => ['padding-top', 'padding-right', 'padding-bottom', 'padding-left'],
    'inset'           => ['top', 'right', 'bottom', 'left'],
    'overflow'        => ['overflow-x', 'overflow-y'],
    'gap'             => ['row-gap', 'column-gap'],
    'grid-gap'        => ['row-gap', 'column-gap'],
    'place-items'     => ['align-items', 'justify-items'],
    'place-content'   => ['align-content', 'justify-content'],
    'place-self'      => ['align-self', 'justify-self'],
    'flex'            => ['flex-grow', 'flex-shrink', 'flex-basis'],
    'flex-flow'       => ['flex-direction', 'flex-wrap'],
    'transition'      => ['transition-property', 'transition-duration', 'transition-timing-function', 'transition-delay'],
    'animation'       => ['animation-name', 'animation-duration', 'animation-timing-function', 'animation-delay', 'animation-iteration-count', 'animation-direction', 'animation-fill-mode', 'animation-play-state'],
    'list-style'      => ['list-style-type', 'list-style-position', 'list-style-image'],
    'text-decoration' => ['text-decoration-line', 'text-decoration-color', 'text-decoration-style', 'text-decoration-thickness'],
    'grid'            => ['grid-template-rows', 'grid-template-columns', 'grid-template-areas', 'grid-auto-rows', 'grid-auto-columns', 'grid-auto-flow'],
    'grid-template'   => ['grid-template-rows', 'grid-template-columns', 'grid-template-areas'],
    'grid-area'       => ['grid-row-start', 'grid-row-end', 'grid-column-start', 'grid-column-end'],
    'grid-row'        => ['grid-row-start', 'grid-row-end'],
    'grid-column'     => ['grid-column-start', 'grid-column-end'],
];

/**
 * Ce qu'une déclaration pose vraiment : une forme longue se pose elle-même, une forme courte pose
 * son contenu. Deux déclarations se disputent un élément dès que ces listes se croisent.
 *
 * @return list<string>
 */
function nf_cascade_touche(string $propriete): array
{
    $propriete = (string) preg_replace('/^-(?:webkit|moz|ms|o)-/', '', strtolower(trim($propriete)));

    return NF_CASCADE_FORMES[$propriete] ?? [$propriete];
}

/** Deux déclarations peuvent-elles se disputer la même valeur ? */
function nf_cascade_conflit(string $une, string $autre): bool
{
    return (bool) array_intersect(nf_cascade_touche($une), nf_cascade_touche($autre));
}

/**
 * La règle d'addon écrase-t-elle, à coup sûr, celle du thème ?
 *
 * Vrai seulement si l'addon s'applique partout où s'applique le thème ET gagne la cascade :
 *
 *   - l'addon porte un SEUL bloc de sélecteur : aucun contexte à vérifier ;
 *   - ses classes sont un sous-ensemble du dernier bloc du thème ;
 *   - il ne demande aucun état ni attribut de plus ;
 *   - sa spécificité est supérieure ou égale (il est servi après) ;
 *   - et le thème ne s'est pas protégé par `!important`.
 *
 * Tout le reste est laissé : un contrôle qui crie à tort n'est plus lu.
 */
function nf_cascade_couvre(string $addon, string $theme, bool $theme_important = FALSE): bool
{
    $addon = trim($addon);
    $theme = trim($theme);

    if ($theme_important)
    {
        return FALSE;
    }

    if ((preg_split('/\s*[\s>+~]\s*/', $addon) ?: []) !== [$addon])
    {
        return FALSE;
    }

    $classes_addon = nf_cascade_classes($addon);

    if (!$classes_addon)
    {
        return FALSE;
    }

    $bloc = nf_cascade_dernier_bloc($theme);

    if (array_diff($classes_addon, nf_cascade_classes($bloc)))
    {
        return FALSE;
    }

    if (array_diff(nf_cascade_etats($addon), nf_cascade_etats($theme)))
    {
        return FALSE;
    }

    if (array_diff(nf_cascade_attributs($addon), nf_cascade_attributs($bloc)))
    {
        return FALSE;
    }

    return (nf_cascade_specificite($addon) <=> nf_cascade_specificite($theme)) >= 0;
}
