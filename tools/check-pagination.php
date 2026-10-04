<?php
declare(strict_types=1);

/**
 * check-pagination — une liste que le checker découpe en pages affiche les liens de ses pages.
 *
 * Famille : statique
 * Diffusion : publique
 *
 * Pourquoi ce contrôle existe
 * ---------------------------
 * Un checker découpe une liste — `->paginate($page)` sur une collection, `->pagination->get_data($lignes,
 * $page)` sur un tableau — et le contrôleur la rend. Si le contrôleur oublie
 * `->pagination->get_pagination()`, la page montre les 10 ou 20 premiers éléments, sans lien vers la
 * suite : les autres sont inatteignables, et le compteur de la carte ne dit que la page affichée.
 *
 * Cinq listes en étaient là, trouvées le 2026-10-02 pendant la refonte de l'administration : les
 * membres et les commentaires (20 par page), les palmarès et les recrutements (10), « Mes abonnements »
 * du forum. Sur la démonstration, qui en compte moins d'une page, rien ne se voyait.
 *
 * Ce que le contrôle vérifie
 * --------------------------
 * Pour chaque méthode d'un checker (`controllers/<x>_checker.php`, `controllers/checker.php`) qui
 * découpe une liste, la méthode du même nom du contrôleur (`<x>.php`, `index.php`) doit rendre la
 * pagination : `get_pagination()` ou `->pagination->panel()`, un tableau (`table2()`, `->table()`) qui la
 * rend lui-même, ou un
 * appel à une autre méthode du contrôleur qui la rend (`return $this->index($liste)`).
 *
 * Une liste qui tient toujours sur une page (une ligne par fournisseur d'identité…) le dit par un
 * commentaire `// pagination : <raison>` dans la méthode du contrôleur.
 *
 * Usage
 * -----
 *   php tools/check-pagination.php             liste les listes sans liens de pages, code 1 s'il y en a
 *   php tools/check-pagination.php --epreuve   ne joue QUE l'épreuve à l'envers
 *
 * L'épreuve à l'envers tourne de toute façon avant chaque analyse : un défaut planté doit être
 * refusé, une écriture correcte doit passer en silence — sans quoi le contrôle refuse de conclure.
 */

require __DIR__.'/lib/outil.php';
require __DIR__.'/lib/depot.php';

[$o] = nf_options(['epreuve' => FALSE]);

/**
 * Les méthodes d'une classe, nom → corps (approché : jusqu'à la méthode suivante).
 *
 * @return array<string, string>
 */
function methodes(string $src): array
{
    $methodes = [];

    if (preg_match_all('/(?:public|protected|private)\s+function\s+(\w+)\s*\(/', $src, $m, PREG_OFFSET_CAPTURE))
    {
        foreach ($m[1] as $i => [$nom, $position])
        {
            $fin = $m[0][$i + 1][1] ?? strlen($src);
            $methodes[$nom] = substr($src, $position, $fin - $position);
        }
    }

    return $methodes;
}

/** Le corps rend-il la pagination, lui-même ou par une autre méthode de la classe (deux relais au plus) ? */
function rend_la_pagination(string $corps, array $methodes, int $profondeur = 0): bool
{
    if (preg_match('/get_pagination\s*\(|pagination\s*->\s*panel\s*\(|table2\s*\(|->\s*table\s*\(|\/\/\s*pagination\s*:/', $corps))
    {
        return TRUE;
    }

    if ($profondeur < 2 && preg_match_all('/\$this\s*->\s*(\w+)\s*\(/', $corps, $appels))
    {
        foreach (array_unique($appels[1]) as $appel)
        {
            if (isset($methodes[$appel]) && rend_la_pagination($methodes[$appel], $methodes, $profondeur + 1))
            {
                return TRUE;
            }
        }
    }

    return FALSE;
}

/**
 * Les listes découpées que le contrôleur rend sans leurs liens.
 *
 * @return list<string> les noms des méthodes fautives
 */
function analyser(string $checker, string $controleur): array
{
    $fautes   = [];
    $rendus   = methodes($controleur);

    foreach (methodes($checker) as $nom => $corps)
    {
        if (!preg_match('/->\s*paginate\s*\(|->\s*get_data\s*\(/', $corps) || !isset($rendus[$nom]))
        {
            continue;
        }

        if (!rend_la_pagination($rendus[$nom], $rendus))
        {
            $fautes[] = $nom;
        }
    }

    return $fautes;
}

/**
 * L'épreuve à l'envers : des défauts plantés, des écritures correctes.
 *
 * @return list<string> les échecs de l'épreuve
 */
function epreuve(): array
{
    $echecs  = [];
    $checker = "<?php class A_Checker {\n"
        ."public function index(\$page = '') { return [\$this->collection('user')->paginate(\$page)]; }\n"
        ."public function _liste(\$page = '') { return [\$this->module->pagination->get_data(\$this->model()->tout(), \$page)]; }\n"
        ."public function _vue(\$id) { return [\$id]; }\n}";

    $defauts = [
        'collection sans liens'   => "<?php class A { public function index(\$membres) { foreach (\$membres->get() as \$m) {} return \$html; }\n public function _liste(\$l) { return \$this->pagination->get_pagination(); } }",
        'tableau sans liens'      => "<?php class A { public function index(\$m) { return \$this->index2(\$m); }\n public function _liste(\$lignes) { foreach (\$lignes as \$l) {} return \$this->admin_card('x', 't', \$b); } }",
    ];

    $propres = [
        'liens rendus'            => "<?php class A { public function index(\$m) { \$b .= \$m->pagination->get_pagination(); return \$b; }\n public function _liste(\$l) { return \$this->module->pagination->get_pagination(); } }",
        'tableau standard'        => "<?php class A { public function index(\$m) { return \$this->table2('user', \$m); }\n public function _liste(\$l) { return \$this->table2(\$l); } }",
        'délégué à index()'       => "<?php class A { public function index(\$m) { return \$m->pagination->get_pagination(); }\n public function _liste(\$l) { return \$this->index(\$l); } }",
        'panneau, par deux relais' => "<?php class A { public function index(\$m) { \$p->append(\$this->module->pagination->panel()); }\n public function _liste(\$l) { return \$this->_filtre(\$l); }\n private function _filtre(\$l) { return \$this->index(\$l); } }",
        'une page, dit'           => "<?php class A { public function index(\$m) { // pagination : une ligne par fournisseur\n return \$x; }\n public function _liste(\$l) { // pagination : jamais plus de trois\n return \$y; } }",
    ];

    foreach ($defauts as $nom => $controleur)
    {
        if (!analyser($checker, $controleur))
        {
            $echecs[] = "défaut NON vu : $nom";
        }
    }

    foreach ($propres as $nom => $controleur)
    {
        if ($vu = analyser($checker, $controleur))
        {
            $echecs[] = "faux positif : $nom (".implode(', ', $vu).')';
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

$fautes = [];
$paires = 0;

foreach (nf_fichiers(['modules'], ['php']) as $rel => $chemin)
{
    $rel = str_replace('\\', '/', $rel);

    if (!preg_match('#^(modules/\w+/controllers/)(?:(\w+)_checker|checker)\.php$#', $rel, $m))
    {
        continue;
    }

    $cible = $m[1].(($m[2] ?? '') !== '' ? $m[2] : 'index').'.php';

    if (!is_file(NF_RACINE.'/'.$cible))
    {
        continue;
    }

    $paires++;

    // Le checker sans ses commentaires ; le contrôleur avec, pour lire « // pagination : <raison> ».
    foreach (analyser(nf_sans_commentaires((string) file_get_contents($chemin)), (string) file_get_contents(NF_RACINE.'/'.$cible)) as $nom)
    {
        $fautes[] = $cible.' :: '.$nom;
    }
}

if (!$fautes)
{
    nf_ok(sprintf('chaque liste découpée en pages affiche ses liens (%d paire(s) checker / contrôleur, épreuve à l\'envers passée)', $paires));
}

echo "LISTES DÉCOUPÉES SANS LIENS DE PAGES — les éléments au-delà de la première page sont inatteignables :\n\n";

foreach ($fautes as $faute)
{
    echo "  $faute\n      rendre ->pagination->get_pagination() sous la liste (ou // pagination : <raison> si elle tient toujours sur une page)\n\n";
}

nf_echec(count($fautes).' liste(s) sans liens de pages');
