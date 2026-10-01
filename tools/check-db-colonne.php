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
 * La requête peut aussi être construite en plusieurs temps — `$q = …->select('id')…;`, des
 * conditions ajoutées, puis `$q->…->get()` —, et le résultat lu par `array_column(…, 'id')`, qui
 * ne trouve alors rien et rend une liste vide sans un mot : la liste des tickets ouverts du
 * Bugtracker revenait vide ainsi, et le bot Discord ne donnait de fil à aucun (2026-10-01).
 *
 * C'est un contrôle STATIQUE, et il cherche les formes qui s'écrivent naturellement : celles des
 * cas trouvés. Il ne suit pas une valeur passée à une autre fonction.
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
            // Une requête gardée dans une variable et terminée plus loin (`$q->…->get()`) : c'est là
            // que son résultat est lu. Sinon — `get(FALSE)`, ou aucun résultat lu —, rien en cause.
            if (preg_match('/\$(\w+)\s*=(?![=>])[^;{}]*$/', substr($src, max(0, $position - 400), min(400, $position)), $q)
                && ($suite_requete = requete_terminee_plus_loin($src, $q[1], $position + $fin)) !== NULL)
            {
                [$position, $fin] = $suite_requete;
                $instruction      = substr($src, $position, $fin);
            }
            else
            {
                continue;
            }
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

        // c. `array_column(…->get(), 'id')` : la colonne cherchée n'existe pas dans une liste de valeurs.
        $debut_instruction = substr($src, max(0, $position - 400), min(400, $position));
        $debut_instruction = substr($debut_instruction, max((int) strrpos($debut_instruction, ';'), (int) strrpos($debut_instruction, '{'), (int) strrpos($debut_instruction, '}')));

        if (preg_match('/array_column\(\s*(?:\(array\)\s*)?(?:\$[\w>-]+|NeoFrag\(\)[\w>-]*)?\s*$/', $debut_instruction))
        {
            $fautes[] = [
                'ligne'    => substr_count($src, "\n", 0, $position) + 1,
                'colonne'  => $colonne,
                'variable' => 'array_column',
            ];

            continue;
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
 * La suite d'une requête gardée dans `$variable` : la première instruction, après `$apres`, qui la
 * termine par `->get()` ou `->row()`. Rend [position, longueur] de cette instruction, ou NULL si la
 * requête est terminée en gardant les lignes (`get(FALSE)`) ou jamais dans la portée.
 *
 * @return array{0: int, 1: int}|null
 */
function requete_terminee_plus_loin(string $src, string $variable, int $apres): ?array
{
    $suite = substr($src, $apres, PORTEE);

    if (!preg_match_all('/\$'.$variable.'\s*->/', $suite, $usages, PREG_OFFSET_CAPTURE))
    {
        return NULL;
    }

    foreach ($usages[0] as [, $decalage])
    {
        $position    = $apres + $decalage;
        $fin         = strcspn($src, ';{', $position);
        $instruction = substr($src, $position, $fin);

        if (preg_match('/->(get|row)\(\s*\)/', $instruction))
        {
            return [$position, $fin];
        }

        if (preg_match('/->(get|row)\(/', $instruction))
        {
            return NULL;   // `get(FALSE)` : les lignes sont gardées
        }
    }

    return NULL;
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
        'requête en deux temps (Bugtracker)' => "<?php \$q = \$this->db->select('id')->from('t')->where('id >', 0);\nif (\$o)\n{\n\t\$q->where('status', ['open']);\n}\nreturn array_map('intval', array_column((array) \$q->order_by('id')->limit(5)->get(), 'id'));",
        'array_column direct'            => "<?php \$ids = array_column(\$this->db->select('id')->from('t')->get(), 'id');",
    ];

    $propres = [
        'valeur lue directement'         => "<?php foreach (\$this->db->select('category_id')->from('c')->get() as \$id)\n{\n\t\$ids[] = \$id;\n}",
        'deux colonnes'                  => "<?php foreach (\$this->db->select('id', 'title')->from('c')->get() as \$c)\n{\n\techo \$c['title'];\n}",
        'get(FALSE) garde les lignes'    => "<?php foreach (\$this->db->select('id')->from('c')->get(FALSE) as \$c)\n{\n\techo \$c['id'];\n}",
        'lecture défendue'               => "<?php foreach (\$this->db->select('id')->from('u')->get() as \$row)\n{\n\t\$uid = is_array(\$row) ? (int)\$row['id'] : (int)\$row;\n}",
        'select(*)'                      => "<?php \$r = \$this->db->select('*')->from('u')->row();\necho \$r['id'];",
        'affectation la plus proche'     => "<?php if ((\$check = post_check('forum_id')) && !is_array(\$is_sub = \$this->db->select('is_subforum')->from('f')->where('forum_id', \$check['forum_id'])->row())) {}",
        'row(FALSE) garde la ligne'      => "<?php \$current = \$this->db->select('parent_id')->from('m')->where('id', 1)->row(FALSE);\necho \$current['parent_id'];",
        'deux temps, valeurs lues'       => "<?php \$q = \$this->db->select('id')->from('t');\nif (\$o)\n{\n\t\$q->where('a', 1);\n}\nreturn array_map('intval', (array) \$q->get());",
        'deux temps, get(FALSE)'         => "<?php \$q = \$this->db->select('id')->from('t');\nforeach (\$q->get(FALSE) as \$r)\n{\n\techo \$r['id'];\n}",
        'array_column à deux colonnes'   => "<?php \$ids = array_column(\$this->db->select('id', 'title')->from('t')->get(), 'title', 'id');",
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
    if ($faute['variable'] === 'array_column')
    {
        printf("  %s:%d\n      select('%s') puis array_column(…) — get() rend déjà la liste des valeurs : la lire directement\n\n",
            $faute[0], $faute['ligne'], $faute['colonne']);

        continue;
    }

    printf("  %s:%d\n      select('%s') puis \$%s['…'] — lire \$%s directement, ou demander get(FALSE)\n\n",
        $faute[0], $faute['ligne'], $faute['colonne'], $faute['variable'], $faute['variable']);
}

nf_echec(count($fautes).' lecture(s) d\'une valeur comme une ligne');
