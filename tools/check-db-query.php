<?php
declare(strict_types=1);

/**
 * check-db-query — une requête écrite à la main par le constructeur de requêtes part dans la même instruction : `->execute('…')`, ou `->query('…')` suivi de ce qui la lit.
 *
 * Famille : statique
 * Diffusion : publique
 *
 * Pourquoi ce contrôle existe
 * ---------------------------
 * `Db::query($sql)` n'envoie rien : il POSE la requête, et c'est le prochain `get()`, `row()`,
 * `results()`, `index()` ou `count()` qui l'envoie. Appelé seul, il ne fait rien sur le moment —
 * puis sa requête part À LA PLACE de la suivante, qui lui confie son `get()`.
 *
 * Trouvé le 2026-10-15 en mettant à jour le module du tutoriel « Astuces » : la table des
 * migrations d'addon était créée par un `->query('CREATE TABLE IF NOT EXISTS …')` seul. La lecture
 * des migrations déjà faites, juste après, envoyait donc ce CREATE et revenait vide : chaque mise à
 * jour d'un addon — par le marketplace ou par une archive — rejouait TOUTES ses migrations, et
 * celles qui écrivent des données les écrivaient une seconde fois.
 *
 * Ce que le contrôle vérifie
 * --------------------------
 * Toute instruction qui commence par le constructeur de requêtes — `$this->db`, `NeoFrag()->db`,
 * `$this->db()` ou `NeoFrag()->db()` — et appelle `->query(…)` doit, dans la même instruction,
 * appeler ensuite `->get(`, `->row(`, `->results(`, `->index(` ou `->count(`. Une requête qui ne
 * rend rien s'écrit `->execute('…')`. Les connexions `mysqli` de l'installeur, dont `query()`
 * exécute, ne commencent pas ainsi et ne sont pas en cause ; dans les pilotes (`neofrag/drivers/`),
 * `$this->db` est cette connexion même : ils sont laissés de côté.
 *
 * Usage
 * -----
 *   php tools/check-db-query.php             liste les requêtes posées sans être envoyées, code 1 s'il y en a
 *   php tools/check-db-query.php --epreuve   ne joue QUE l'épreuve à l'envers
 *
 * L'épreuve à l'envers tourne de toute façon avant chaque analyse : un défaut planté doit être
 * refusé, une écriture correcte doit passer en silence — sans quoi le contrôle refuse de conclure.
 */

require __DIR__.'/lib/outil.php';
require __DIR__.'/lib/depot.php';

[$o] = nf_options(['epreuve' => FALSE]);

/**
 * Les requêtes posées par `->query()` sans être envoyées, dans une source PHP (commentaires déjà retirés).
 *
 * @return list<int> les lignes fautives
 */
function analyser_source(string $src): array
{
    $fautes = [];

    if (!preg_match_all('/->\s*query\s*\(/', $src, $m, PREG_OFFSET_CAPTURE))
    {
        return [];
    }

    foreach ($m[0] as [, $position])
    {
        // L'instruction qui porte l'appel : depuis le dernier `;`, `{`, `}` ou `<?php` qui la précède, jusqu'au `;` qui la ferme.
        $avant  = substr($src, 0, $position);
        $bornes = array_filter([strrpos($avant, ';'), strrpos($avant, '{'), strrpos($avant, '}'), ($p = strrpos($avant, '<?php')) !== FALSE ? $p + 4 : FALSE], static fn ($b) => $b !== FALSE);
        $debut  = $bornes ? max($bornes) : -1;
        $instruction = ltrim(substr($src, $debut + 1, $position - $debut - 1));
        // La suite de l'instruction, chaînes vidées : un `->get(` écrit DANS la requête n'envoie rien.
        $suite  = (string) preg_replace('/\'(?:[^\'\\\\]|\\\\.)*\'|"(?:[^"\\\\]|\\\\.)*"/s', "''", substr($src, $position, nf_fin_instruction($src, $position) - $position));

        if (preg_match('/^(?:\$this|NeoFrag\(\))\s*->\s*db\b(?:\s*\(\s*\))?/', $instruction) && !preg_match('/->\s*(?:get|row|results|index|count)\s*\(/', $suite))
        {
            $fautes[] = substr_count($avant, "\n") + 1;
        }
    }

    return $fautes;
}

/**
 * La fin de l'instruction qui commence avant `$depuis` : le premier `;` hors des chaînes et des parenthèses.
 */
function nf_fin_instruction(string $src, int $depuis): int
{
    $profondeur = 0;
    $chaine     = '';
    $longueur   = strlen($src);

    for ($i = $depuis; $i < $longueur; $i++)
    {
        $c = $src[$i];

        if ($chaine !== '')
        {
            if ($c === '\\')
            {
                $i++;
            }
            elseif ($c === $chaine)
            {
                $chaine = '';
            }
        }
        elseif ($c === '"' || $c === "'")
        {
            $chaine = $c;
        }
        elseif ($c === '(')
        {
            $profondeur++;
        }
        elseif ($c === ')')
        {
            $profondeur--;
        }
        elseif ($c === ';' && $profondeur <= 0)
        {
            return $i;
        }
    }

    return $longueur;
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
        'CREATE posé seul'               => "<?php\n\$this->db->query(\n\t'CREATE TABLE IF NOT EXISTS `nf_a` (`id` int)'\n);",
        'NeoFrag()->db sur une ligne'    => "<?php NeoFrag()->db->query('UPDATE nf_b SET n = n + 1');",
        'db() avec parenthèses'          => "<?php if (\$x) { NeoFrag()->db()->query('DELETE FROM nf_c'); }",
        'un get() dans la chaîne'        => "<?php \$this->db->query('INSERT INTO nf_d VALUES (\"->get(\")');",
    ];

    $propres = [
        'execute()'                      => "<?php \$this->db->execute('CREATE TABLE IF NOT EXISTS `nf_a` (`id` int)');",
        'query() puis get()'             => "<?php \$lignes = \$this->db->query('SELECT * FROM nf_b')->get();",
        'query() puis results()'         => "<?php \$r = \$db()->query('SELECT 1')->results(); NeoFrag()->db()->query('SELECT 2')\n\t->results();",
        'query() puis row()'             => "<?php \$n = NeoFrag()->db->query('SELECT COUNT(*) FROM nf_c')->row();",
        'mysqli de l\'installeur'        => "<?php \$db->query('SET FOREIGN_KEY_CHECKS=0'); \$res = @\$db->query('SELECT 1');",
        'une autre query()'              => "<?php \$this->url->query(['page' => 2]);",
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
    // Dans un pilote, `$this->db` est la connexion `mysqli` elle-même, dont `query()` exécute.
    if (str_starts_with($rel, 'neofrag/drivers/'))
    {
        continue;
    }

    $fichiers++;

    foreach (analyser_source(nf_sans_commentaires((string) file_get_contents($chemin))) as $ligne)
    {
        $fautes[] = $rel.':'.$ligne;
    }
}

if (!$fautes)
{
    nf_ok(sprintf('toute requête écrite à la main part dans son instruction (%d fichier(s), épreuve à l\'envers passée)', $fichiers));
}

echo "REQUÊTES POSÉES SANS ÊTRE ENVOYÉES — `Db::query()` attend le get() suivant, qui enverra la sienne à sa place :\n\n";

foreach ($fautes as $faute)
{
    echo "  $faute\n      écrire ->execute('…') pour une requête qui ne rend rien, ou ->query('…')->get() pour la lire\n\n";
}

nf_echec(count($fautes).' requête(s) posée(s) sans être envoyée(s)');
