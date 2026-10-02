<?php
declare(strict_types=1);
/**
 * check-actions-admin — dans l'administration, un bouton « modifier » est neutre, un bouton « supprimer » est un contour rouge avec une corbeille.
 *
 * Famille : statique
 *
 * Pourquoi ce contrôle existe
 * ---------------------------
 * La charte de l'administration (docs/guide/create-a-module.md, « La charte de l'administration ») est
 * sobre : la couleur est réservée à ce qui appelle une action. Les boutons d'action des lignes, eux,
 * variaient d'une page à l'autre — modifier en bleu ciel (les boutons communs), en teal (35 pages
 * écrites à la main) ou en bouton plein (le diaporama, les dons) ; supprimer en contour rouge, en rouge
 * plein, avec une croix ou une corbeille (relevé sur les captures des 56 pages, 2026-10-02). Ils ont été
 * harmonisés d'un coup ; ce contrôle les garde ainsi.
 *
 * Ce que le contrôle vérifie
 * --------------------------
 * Dans les contrôleurs et les vues d'administration des modules et du thème d'administration, chaque
 * bouton `btn` qui ne porte qu'une icône :
 *   - de modification (crayon) porte `btn-outline-secondary` ;
 *   - de suppression (croix, corbeille) porte `btn-outline-danger` et l'icône `far fa-trash-alt`.
 *   - pour toute autre action (aperçu, dupliquer, restaurer…), n'est jamais plein de couleur : un
 *     contour (`btn-outline-…`).
 * Un bouton qui porte un texte (« Supprimer la sélection ») n'est pas concerné. Les boutons communs du
 * cœur (`button_update()`, `button_delete()`, `button_access()`) suivent la même règle à la source.
 *
 * Usage
 * -----
 *   php tools/check-actions-admin.php             liste les boutons hors charte, code 1 s'il y en a
 *   php tools/check-actions-admin.php --epreuve   ne joue QUE l'épreuve à l'envers
 */

require __DIR__.'/lib/outil.php';

[$o]    = nf_options(['epreuve' => FALSE]);
$racine = nf_racine();

const ICONES_MODIFIER  = ['fa-edit', 'fa-pencil-alt', 'fa-pen', 'fa-pencil'];
const ICONES_SUPPRIMER = ['fa-times', 'fa-trash', 'fa-trash-alt', 'fa-xmark'];

/**
 * Les boutons hors charte d'une source (contrôleur ou gabarit).
 *
 * @return list<array{ligne: int, bouton: string, attendu: string}>
 */
function analyser_source(string $src): array
{
    // <a|button … class="btn …" …> puis une icône seule, puis la fermeture. Les attributs peuvent porter
    // du PHP (une balise d'ouverture PHP et sa fermeture) ou une flèche (`$item->id`), jamais une autre balise.
    $motif = '#<(a|button)\b(?:<\?php(?:(?!\?>).)*\?>|->|[^<>])*?\bclass="btn\b([^"]*)"(?:<\?php(?:(?!\?>).)*\?>|->|[^<>])*>\s*(?:<\?php echo icon\(\'([^\']+)\'\) \?>|<i class="([^"]+)"></i>|\'\.icon\(\'([^\']+)\'\)\.\')\s*</\1>#s';
    $fautes = [];

    preg_match_all($motif, $src, $boutons, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);

    foreach ($boutons as $b)
    {
        $icone   = ($b[3][0] ?? '') !== '' ? $b[3][0] : ((($b[4][0] ?? '') !== '') ? $b[4][0] : ($b[5][0] ?? ''));
        $classes = preg_split('/\s+/', trim($b[2][0])) ?: [];
        $ligne   = substr_count($src, "\n", 0, $b[0][1]) + 1;
        $est     = static fn (array $liste): bool => (bool) array_filter($liste, static fn (string $i): bool => (bool) preg_match('/(?<![\w-])'.preg_quote($i, '/').'(?![\w-])/', $icone));

        if ($est(ICONES_SUPPRIMER))
        {
            if (!in_array('btn-outline-danger', $classes, TRUE) || !preg_match('/(?<![\w-])far fa-trash-alt(?![\w-])/', $icone))
            {
                $fautes[] = ['ligne' => $ligne, 'bouton' => trim($b[2][0]).' · '.$icone, 'attendu' => 'btn-outline-danger · far fa-trash-alt'];
            }
        }
        else if ($est(ICONES_MODIFIER) && !in_array('btn-outline-secondary', $classes, TRUE))
        {
            $fautes[] = ['ligne' => $ligne, 'bouton' => trim($b[2][0]).' · '.$icone, 'attendu' => 'btn-outline-secondary'];
        }
        // Les autres actions d'une ligne (aperçu, dupliquer, verrouiller…) : jamais un bouton plein de
        // couleur — les rôles montraient un aperçu orange et un « cloner » bleu, pleins.
        else if (array_intersect($classes, ['btn-primary', 'btn-info', 'btn-warning', 'btn-success', 'btn-danger', 'btn-dark']))
        {
            $fautes[] = ['ligne' => $ligne, 'bouton' => trim($b[2][0]).' · '.$icone, 'attendu' => 'un contour (btn-outline-…), pas un bouton plein'];
        }
    }

    return $fautes;
}

/** L'épreuve à l'envers : les écarts plantés sont refusés, les boutons de la charte passent. */
function epreuve(): array
{
    $echecs = [];

    $defauts = [
        'modifier en teal'         => '<a class="btn btn-sm btn-outline-primary" href="#"><i class="fas fa-pencil-alt"></i></a>',
        'modifier plein'           => '<a class="btn btn-sm btn-primary" href="<?php echo url(\'x\') ?>"><?php echo icon(\'fas fa-edit\') ?></a>',
        'supprimer plein'          => '<a class="btn btn-sm btn-danger" href="#"><i class="far fa-trash-alt"></i></a>',
        'supprimer par une croix'  => '<a class="btn btn-sm btn-outline-danger" href="\'.url(\'x/\'.$e->id).\'"><i class="fas fa-times"></i></a>',
        'cloner en plein'          => '<a class="btn btn-sm btn-info" href="#"><i class="fas fa-copy"></i></a>',
        'bouton du cœur'           => "<?php \$b = '<button class=\"btn btn-sm btn-outline-info\">'.icon('fas fa-pen').'</button>';",
    ];

    $propres = [
        'modifier neutre'          => '<a class="btn btn-sm btn-outline-secondary" href="#"><i class="fas fa-pencil-alt"></i></a>',
        'supprimer de la charte'   => '<a class="btn btn-sm btn-outline-danger" href="<?php echo url(\'x\') ?>"><?php echo icon(\'far fa-trash-alt\') ?></a>',
        'supprimer avec un texte'  => '<a class="btn btn-sm btn-outline-danger" href="#"><i class="far fa-trash-alt"></i> Supprimer la sélection</a>',
        'réinitialiser un filtre'  => '<a class="btn btn-sm btn-light" href="#"><i class="fas fa-times"></i> Réinitialiser</a>',
        'aperçu en contour'        => '<a class="btn btn-sm btn-outline-warning" href="#"><i class="fas fa-eye"></i></a>',
        'restaurer'                => '<a class="btn btn-sm btn-outline-success" href="#"><i class="fas fa-trash-restore"></i></a>',
        'bouton voisin'            => '<a class="btn btn-sm btn-light" href="<?php echo url(\'x\') ?>"><?php echo icon($v ? \'fas fa-eye\' : \'fas fa-eye-slash\') ?></a><a class="btn btn-sm btn-outline-secondary" href="#"><?php echo icon(\'fas fa-edit\') ?></a>',
    ];

    foreach ($defauts as $nom => $src)
    {
        if (!analyser_source($src))
        {
            $echecs[] = "défaut NON vu : $nom";
        }
    }

    foreach ($propres as $nom => $src)
    {
        if ($vu = analyser_source($src))
        {
            $echecs[] = "faux positif : $nom ({$vu[0]['bouton']})";
        }
    }

    return $echecs;
}

$echecs = epreuve();

if ($o['epreuve'])
{
    foreach ($echecs as $echec)
    {
        echo "  $echec\n";
    }

    $echecs ? nf_echec('épreuve à l\'envers : le contrôle est aveugle ou trop bavard') : nf_ok('épreuve à l\'envers : les écarts plantés sont refusés, les boutons de la charte passent');
}

if ($echecs)
{
    // Un contrôle qui ne voit plus ce qu'il vise ne doit pas se dire vert.
    nf_refus('épreuve à l\'envers ratée — '.implode(' ; ', $echecs));
}

$fichiers = array_merge(
    glob($racine.'/modules/*/controllers/admin*.php') ?: [],
    glob($racine.'/modules/*/views/admin/*.php') ?: [],
    glob($racine.'/modules/*/views/admin/*/*.php') ?: [],
    glob($racine.'/themes/admin/views/*.php') ?: []
);

$fautes = [];

foreach ($fichiers as $chemin)
{
    foreach (analyser_source((string) file_get_contents($chemin)) as $faute)
    {
        $fautes[] = [substr(str_replace('\\', '/', $chemin), strlen(str_replace('\\', '/', $racine)) + 1)] + $faute;
    }
}

if (!$fautes)
{
    nf_ok(sprintf('les boutons d\'action de l\'administration suivent la charte (%d fichier(s), épreuve à l\'envers passée)', count($fichiers)));
}

echo "BOUTONS D'ACTION HORS CHARTE — modifier : btn-outline-secondary ; supprimer : btn-outline-danger et far fa-trash-alt :\n\n";

foreach ($fautes as $faute)
{
    printf("  %s:%d\n      %s — attendu : %s\n\n", $faute[0], $faute['ligne'], $faute['bouton'], $faute['attendu']);
}

nf_echec(count($fautes).' bouton(s) d\'action hors charte');
