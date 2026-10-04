<?php
declare(strict_types=1);

/**
 * check-db-delete — une suppression par le constructeur de requêtes nomme sa table : `->delete('nf_…')`, jamais `->delete()`.
 *
 * Famille : statique
 * Diffusion : publique
 *
 * Pourquoi ce contrôle existe
 * ---------------------------
 * `Db::delete($table)` exige la table. Une écriture héritée la donnait à `from()` et appelait
 * `->delete()` sans argument : PHP lève alors une `ArgumentCountError`, la page tombe, et ce qui
 * devait être supprimé ne l'est pas. Rien ne le signalait — l'analyse statique ne connaît pas le
 * type de `$this->db`, et aucun test ne passait par ces chemins.
 *
 * Quatre fonctions en étaient mortes, trouvées le 2026-10-01 en éprouvant la suppression d'une
 * catégorie du Blog :
 *
 *   - la **désinscription de la newsletter** ne désinscrivait personne ;
 *   - la suppression d'un abonné dans l'administration de la newsletter ;
 *   - la **désactivation de la double authentification** laissait les codes de secours en base ;
 *   - la **suppression de son compte** marquait le compte supprimé, puis tombait avant de fermer ses
 *     sessions, d'effacer ses codes de secours et d'écrire au journal d'audit.
 *
 * Ce que le contrôle vérifie
 * --------------------------
 * Toute instruction qui commence par le constructeur de requêtes — `$this->db`, `NeoFrag()->db`,
 * `$this->db()` ou `NeoFrag()->db()` — et se termine par `->delete()` sans argument. La suppression
 * d'un modèle ou d'une collection (`->model2(…)->delete()`, `->collection(…)->delete()`), qui ne
 * prend pas de table, n'est pas en cause.
 *
 * Usage
 * -----
 *   php tools/check-db-delete.php             liste les suppressions sans table, code 1 s'il y en a
 *   php tools/check-db-delete.php --epreuve   ne joue QUE l'épreuve à l'envers
 *
 * L'épreuve à l'envers tourne de toute façon avant chaque analyse : un défaut planté doit être
 * refusé, une écriture correcte doit passer en silence — sans quoi le contrôle refuse de conclure.
 */

require __DIR__.'/lib/outil.php';
require __DIR__.'/lib/depot.php';

[$o] = nf_options(['epreuve' => FALSE]);

/**
 * Les suppressions sans table d'une source PHP (commentaires déjà retirés).
 *
 * @return list<int> les lignes fautives
 */
function analyser_source(string $src): array
{
    $fautes = [];

    if (!preg_match_all('/->\s*delete\(\s*\)/', $src, $m, PREG_OFFSET_CAPTURE))
    {
        return [];
    }

    foreach ($m[0] as [, $position])
    {
        // L'instruction qui porte l'appel : depuis le dernier `;`, `{`, `}` ou `<?php` qui la précède.
        $avant  = substr($src, 0, $position);
        $bornes = array_filter([strrpos($avant, ';'), strrpos($avant, '{'), strrpos($avant, '}'), ($p = strrpos($avant, '<?php')) !== FALSE ? $p + 4 : FALSE], static fn ($b) => $b !== FALSE);
        $debut  = $bornes ? max($bornes) : -1;
        $instruction = ltrim(substr($src, $debut + 1, $position - $debut - 1));

        if (preg_match('/^(?:\$this|NeoFrag\(\))\s*->\s*db\b(?:\s*\(\s*\))?/', $instruction) && !preg_match('/->\s*(?:model2?|collection)\s*\(/', $instruction))
        {
            $fautes[] = substr_count($avant, "\n") + 1;
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
    $echecs = [];

    $defauts = [
        'from() puis delete()'           => "<?php\nNeoFrag()->db\t->from('nf_a')\n\t->where('id', 1)\n\t->delete();",
        'sur une ligne'                  => "<?php \$this->db->from('nf_s')->where('user_id', \$u)->delete();",
        'db() avec parenthèses'          => "<?php NeoFrag()->db()->from('nf_t')->where('id', 2)->delete();",
        'where() seul'                   => "<?php if (\$x) { \$this->db->where('id', 3)->delete(); }",
    ];

    $propres = [
        'table nommée'                   => "<?php \$this->db->where('id', 1)->delete('nf_a');",
        'suppression d\'un modèle'       => "<?php NeoFrag()->model2('file', \$this->db->select('image_id')->from('nf_e')->where('id', 1)->row())->delete();",
        'suppression d\'une collection'  => "<?php NeoFrag()->collection('tracking')->where('user_id', 1)->delete();",
        'objet quelconque'               => "<?php \$token->delete();",
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
        if (analyser_source($src))
        {
            $echecs[] = "faux positif : $nom";
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

    foreach (analyser_source(nf_sans_commentaires((string) file_get_contents($chemin))) as $ligne)
    {
        $fautes[] = $rel.':'.$ligne;
    }
}

if (!$fautes)
{
    nf_ok(sprintf('toute suppression par le constructeur de requêtes nomme sa table (%d fichier(s), épreuve à l\'envers passée)', $fichiers));
}

echo "SUPPRESSIONS SANS TABLE — `Db::delete()` exige la table, l'appel lève une ArgumentCountError :\n\n";

foreach ($fautes as $faute)
{
    echo "  $faute\n      écrire ->where(…)->delete('nf_…') : la table va dans delete(), pas dans from()\n\n";
}

nf_echec(count($fautes).' suppression(s) sans table');
