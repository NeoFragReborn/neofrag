<?php
declare(strict_types=1);
/**
 * check-db-colonne — une requête à UNE colonne rend des valeurs, pas des lignes : aucune n'est lue comme un tableau.
 *
 * Famille : statique
 *
 * Pourquoi ce contrôle existe
 * ---------------------------
 * `Db::get()` et `Db::row()` simplifient leur résultat quand la requête ne demande qu'une colonne :
 * `->select('id')->…->get()` rend `[3, 7, 9]`, et non `[['id' => 3], …]`. C'est commode, et c'est
 * un piège : lire `$ligne['id']` sur un entier ne lève rien — PHP rend NULL avec un simple
 * avertissement au journal, et le code continue sur une valeur vide.
 *
 * Deux fonctions en étaient mortes, trouvées le 2026-09-22 dans le journal de la démonstration :
 *
 *   - `modules/surveys` : aucune option n'était jamais reconnue, donc **aucun vote n'était
 *     enregistré** — et le votant lisait quand même « Merci pour ton vote ! » ;
 *   - le widget `forum` : aucune catégorie n'était retenue, et il n'affichait jamais aucun sujet.
 *
 * Ce que le contrôle vérifie
 * --------------------------
 * Un `->select('une_colonne')` terminé par `->get()` ou `->row()` (sans `FALSE`, qui garde les
 * lignes), dont le résultat est ensuite lu avec `$variable['…']` — que la variable soit celle d'un
 * `foreach` ouvert sur la requête, celle qui reçoit le résultat, ou celle d'un `foreach` ouvert sur
 * cette dernière. Une lecture gardée par `is_array($variable)` est acceptée.
 *
 * C'est un contrôle STATIQUE, et il cherche la forme qui s'écrit naturellement : celle des deux cas
 * trouvés. Il ne suit pas une valeur passée à une autre fonction.
 *
 * Usage
 * -----
 *   php tools/check-db-colonne.php             liste les lectures fautives, code 1 s'il y en a
 *   php tools/check-db-colonne.php --epreuve   ne joue QUE l'épreuve à l'envers
 *
 * L'épreuve à l'envers tourne de toute façon avant chaque analyse : un défaut planté doit être
 * refusé, une écriture correcte doit passer en silence — sans quoi le contrôle refuse de conclure.
 */

require __DIR__.'/lib/outil.php';
require __DIR__.'/lib/depot.php';

[$o]    = nf_options(['epreuve' => FALSE]);
$racine = nf_racine();

/** Jusqu'où, après la requête, on cherche la lecture fautive : une boucle ordinaire y tient. */
const PORTEE = 2500;

/**
 * Les lectures fautives d'une source PHP (commentaires déjà retirés).
 *
 * @return list<array{ligne: int, colonne: string, variable: string}>
 */
function analyser_source(string $src): array
{
    $fautes = [];

    if (!preg_match_all('/->select\(\s*([\'"])([^\'",]+)\1\s*\)/', $src, $m, PREG_OFFSET_CAPTURE))
    {
        return [];
    }

    foreach ($m[0] as $i => [$appel, $position])
    {
        $colonne = $m[2][$i][0];

        if (str_contains($colonne, '*'))
        {
            continue;
        }

        // L'instruction qui porte la requête : jusqu'au premier `;` ou à l'accolade d'une boucle.
        $fin = strcspn($src, ';{', $position);
        $instruction = substr($src, $position, $fin);

        if (!preg_match('/->(get|row)\(\s*\)/', $instruction))
        {
            continue;   // `get(FALSE)` garde les lignes, et une requête sans résultat lu n'est pas en cause
        }

        $variables = [];

        // a. `foreach (…->select('x')…->get() as $v)`
        if (preg_match('/\bas\s+(?:\$\w+\s*=>\s*)?\$(\w+)\s*\)\s*$/', $instruction, $v))
        {
            $variables[] = $v[1];
        }

        // b. `$r = …->select('x')…->get();`, puis éventuellement `foreach ($r as $v)`.
        // L'affectation retenue est la PLUS PROCHE de la requête, dans la même instruction : la
        // première version prenait la plus lointaine, et accusait `$check` dans
        // `($check = post_check(…)) && !is_array($is_subforum = $this->db->select('is_subforum')…)`.
        $debut = max(0, $position - 400);
        $avant = substr($src, $debut, $position - $debut);
        $avant = substr($avant, max((int) strrpos($avant, ';'), (int) strrpos($avant, '{'), (int) strrpos($avant, '}')));

        if (preg_match_all('/\$(\w+)\s*=(?![=>])/', $avant, $r))
        {
            $recoit      = (string) end($r[1]);
            $variables[] = $recoit;

            $suite = substr($src, $position + $fin, PORTEE);

            if (preg_match('/foreach\s*\(\s*\$'.$recoit.'\s+as\s+(?:\$\w+\s*=>\s*)?\$(\w+)\s*\)/', $suite, $f))
            {
                $variables[] = $f[1];
            }
        }

        $suite = substr($src, $position, PORTEE);

        foreach (array_unique($variables) as $variable)
        {
            if (!preg_match('/\$'.$variable.'\s*\[\s*[\'"]/', $suite, $lu, PREG_OFFSET_CAPTURE))
            {
                continue;
            }

            // Une lecture défendue — `is_array($v) ? $v['id'] : $v` — n'est pas fautive.
            $ligne_lue = substr($suite, (int) strrpos(substr($suite, 0, $lu[0][1]), "\n"), 300);

            if (preg_match('/is_array\(\s*\$'.$variable.'\s*\)/', strtok($ligne_lue, "\n") ?: ''))
            {
                continue;
            }

            $fautes[] = [
                'ligne'    => substr_count($src, "\n", 0, $position) + 1,
                'colonne'  => $colonne,
                'variable' => $variable,
            ];
        }
    }

    return $fautes;
}

/**
 * L'ÉPREUVE À L'ENVERS : les deux défauts réels, dans leur forme d'origine, doivent être refusés ;
 * leurs corrections, et les lectures qui gardent les lignes, doivent passer en silence.
 */
function epreuve(): array
{
    $echecs = [];

    $defauts = [
        'widget forum (foreach direct)' => "<?php foreach (\$this->db->select('category_id')->from('nf_forum_categories')->get() as \$category)\n{\n\t\$ids[] = \$category['category_id'];\n}",
        'sondages (variable puis boucle)' => "<?php \$rows = NeoFrag()->db->select('id')->from('o')->where('s', 1)->get();\nforeach (\$rows as \$row)\n{\n\tif (in_array((int)\$row['id'], \$x)) {}\n}",
        'row() à une colonne'            => "<?php \$u = \$this->db->select('username')->from('nf_user')->where('id', 1)->row();\necho \$u['username'];",
    ];

    $propres = [
        'valeur lue directement'         => "<?php foreach (\$this->db->select('category_id')->from('c')->get() as \$id)\n{\n\t\$ids[] = \$id;\n}",
        'deux colonnes'                  => "<?php foreach (\$this->db->select('id', 'title')->from('c')->get() as \$c)\n{\n\techo \$c['title'];\n}",
        'get(FALSE) garde les lignes'    => "<?php foreach (\$this->db->select('id')->from('c')->get(FALSE) as \$c)\n{\n\techo \$c['id'];\n}",
        'lecture défendue'               => "<?php foreach (\$this->db->select('id')->from('u')->get() as \$row)\n{\n\t\$uid = is_array(\$row) ? (int)\$row['id'] : (int)\$row;\n}",
        'select(*)'                      => "<?php \$r = \$this->db->select('*')->from('u')->row();\necho \$r['id'];",
        'affectation la plus proche'     => "<?php if ((\$check = post_check('forum_id')) && !is_array(\$is_sub = \$this->db->select('is_subforum')->from('f')->where('forum_id', \$check['forum_id'])->row())) {}",
        'row(FALSE) garde la ligne'      => "<?php \$current = \$this->db->select('parent_id')->from('m')->where('id', 1)->row(FALSE);\necho \$current['parent_id'];",
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
            $echecs[] = "faux positif : $nom (\${$vu[0]['variable']})";
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

    $echecs ? nf_echec('épreuve à l\'envers : le contrôle est aveugle ou trop bavard') : nf_ok('épreuve à l\'envers : les défauts plantés sont refusés, les écritures correctes passent');
}

if ($echecs)
{
    // Un contrôle qui ne voit plus ce qu'il vise ne doit pas se dire vert.
    nf_refus('épreuve à l\'envers ratée — '.implode(' ; ', $echecs));
}

$fautes   = [];
$fichiers = 0;

foreach (nf_fichiers(NF_DOSSIERS_PRODUIT, ['php']) as $rel => $chemin)
{
    $fichiers++;

    foreach (analyser_source(nf_sans_commentaires((string) file_get_contents($chemin))) as $faute)
    {
        $fautes[] = [$rel] + $faute;
    }
}

if (!$fautes)
{
    nf_ok(sprintf('aucune requête à une colonne lue comme un tableau (%d fichier(s), épreuve à l\'envers passée)', $fichiers));
}

echo "REQUÊTES À UNE COLONNE LUES COMME DES LIGNES — `get()` et `row()` rendent alors des VALEURS :\n\n";

foreach ($fautes as $faute)
{
    printf("  %s:%d\n      select('%s') puis \$%s['…'] — lire \$%s directement, ou demander get(FALSE)\n\n",
        $faute[0], $faute['ligne'], $faute['colonne'], $faute['variable'], $faute['variable']);
}

nf_echec(count($fautes).' lecture(s) d\'une valeur comme une ligne');
