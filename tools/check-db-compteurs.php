<?php
declare(strict_types=1);

/**
 * check-db-compteurs — un compteur (vues, clics) ne fait pas avancer la date de modification de sa ligne.
 *
 * Famille : statique
 * Diffusion : publique
 *
 * Pourquoi ce contrôle existe
 * ---------------------------
 * Une vingtaine de tables déclarent `updated_at … ON UPDATE current_timestamp()` : la base remet la
 * colonne à l'heure à CHAQUE écriture de la ligne, quelle qu'elle soit. Le compteur de vues du wiki
 * (`UPDATE nf_wiki_pages SET views = views + 1`) en est une : chaque visite réécrivait la date, et la
 * page annonçait « modifiée le » à l'heure de la dernière visite (trouvé le 2026-10-02 en comparant
 * deux visites à une minute d'écart). Les petites annonces avaient le même défaut.
 *
 * La parade est d'écrire la colonne sur elle-même — `SET views = views + 1, updated_at = updated_at` —,
 * ce qui la soustrait à la mise à jour automatique.
 *
 * Ce que le contrôle vérifie
 * --------------------------
 * Il relève dans les schémas livrés (install/schema.sql, modules/<x>/install/*.sql et leurs
 * migrations, neofrag/install/*.php) les colonnes `ON UPDATE current_timestamp`, table par table.
 * Puis il cherche dans le produit les compteurs écrits sur ces tables — `UPDATE nf_… SET a = a + 1`
 * dans une requête, ou `->update('nf_…', 'a = a + 1')` avec le constructeur — et refuse ceux qui ne
 * réécrivent pas la colonne sur elle-même.
 *
 * Usage
 * -----
 *   php tools/check-db-compteurs.php             liste les compteurs fautifs, code 1 s'il y en a
 *   php tools/check-db-compteurs.php --epreuve   ne joue QUE l'épreuve à l'envers
 *
 * L'épreuve à l'envers tourne de toute façon avant chaque analyse : un défaut planté doit être
 * refusé, une écriture correcte doit passer en silence — sans quoi le contrôle refuse de conclure.
 */

require __DIR__.'/lib/outil.php';
require __DIR__.'/lib/depot.php';

[$o] = nf_options(['epreuve' => FALSE]);

/**
 * Les colonnes mises à jour d'elles-mêmes, par table, d'après un ou plusieurs schémas SQL.
 *
 * @return array<string, list<string>>
 */
function colonnes_automatiques(string $sql): array
{
    $tables = [];

    // CREATE TABLE nf_x ( … ) : chaque ligne de colonne qui porte ON UPDATE current_timestamp.
    if (preg_match_all('/CREATE TABLE\s+(?:IF NOT EXISTS\s+)?`?(nf_\w+)`?\s*\((.*?)\)\s*(?:ENGINE|DEFAULT CHARSET|;|\'|")/is', $sql, $m, PREG_SET_ORDER))
    {
        foreach ($m as [, $table, $corps])
        {
            if (preg_match_all('/^\s*`?(\w+)`?\s+(?:timestamp|datetime)\b[^\n]*ON UPDATE current_timestamp/im', $corps, $c))
            {
                $tables[$table] = array_values(array_unique(array_merge($tables[$table] ?? [], $c[1])));
            }
        }
    }

    // ALTER TABLE nf_x ADD|MODIFY|CHANGE … `col` timestamp … ON UPDATE current_timestamp.
    if (preg_match_all('/ALTER TABLE\s+`?(nf_\w+)`?([^;]*?)ON UPDATE current_timestamp/is', $sql, $m, PREG_SET_ORDER))
    {
        foreach ($m as [, $table, $milieu])
        {
            if (preg_match_all('/`?(\w+)`?\s+(?:timestamp|datetime)\b/i', $milieu, $c))
            {
                $tables[$table] = array_values(array_unique(array_merge($tables[$table] ?? [], [end($c[1])])));
            }
        }
    }

    return $tables;
}

/**
 * Les compteurs fautifs d'une source PHP (commentaires déjà retirés).
 *
 * @param array<string, list<string>> $automatiques
 * @return list<array{ligne: int, table: string, colonne: string}>
 */
function analyser_source(string $src, array $automatiques): array
{
    $fautes = [];
    $motifs = [
        // UPDATE nf_x SET a = a + 1[, …] [WHERE …] — dans une chaîne passée à execute().
        '/UPDATE\s+`?(nf_\w+)`?\s+SET\s+((?:(?!WHERE)[^\'"])*?`?(\w+)`?\s*=\s*`?\3`?\s*[+-]\s*\d+(?:(?!WHERE)[^\'"])*)/i',
        // ->update('nf_x', 'a = a + 1[, …]') — le constructeur de requêtes.
        '/->\s*update\(\s*[\'"](nf_\w+)[\'"]\s*,\s*[\'"]([^\'"]*?`?(\w+)`?\s*=\s*`?\3`?\s*[+-]\s*\d+[^\'"]*)[\'"]/i',
    ];

    foreach ($motifs as $motif)
    {
        if (!preg_match_all($motif, $src, $m, PREG_SET_ORDER | PREG_OFFSET_CAPTURE))
        {
            continue;
        }

        foreach ($m as $trouve)
        {
            $table = $trouve[1][0];
            $set   = $trouve[2][0];

            foreach ($automatiques[$table] ?? [] as $colonne)
            {
                if (!preg_match('/`?'.preg_quote($colonne, '/').'`?\s*=\s*`?'.preg_quote($colonne, '/').'`?/i', $set))
                {
                    $fautes[] = ['ligne' => substr_count(substr($src, 0, $trouve[0][1]), "\n") + 1, 'table' => $table, 'colonne' => $colonne];
                }
            }
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

    $schema = colonnes_automatiques("CREATE TABLE IF NOT EXISTS `nf_pages_x` (\n  `id` int NOT NULL,\n  `views` int NOT NULL DEFAULT 0,\n  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),\n  PRIMARY KEY (`id`)\n) ENGINE=InnoDB;\n"
        ."ALTER TABLE nf_annonces_x ADD COLUMN `modifie` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp();");

    if (($schema['nf_pages_x'] ?? []) !== ['updated_at'] || ($schema['nf_annonces_x'] ?? []) !== ['modifie'])
    {
        $echecs[] = 'schéma mal lu : '.json_encode($schema);
    }

    $defauts = [
        'compteur dans execute()'      => "<?php \$this->db->execute('UPDATE nf_pages_x SET views = views + 1 WHERE id = '.(int) \$id);",
        'compteur du constructeur'     => "<?php NeoFrag()->db->where('id', \$id)->update('nf_pages_x', 'views = views + 1');",
        'colonne ajoutée par ALTER'    => "<?php \$db->execute(\"UPDATE nf_annonces_x SET clicks = clicks + 1 WHERE id = 3\");",
        'avec des accents graves'      => "<?php \$db->execute('UPDATE `nf_pages_x` SET `views` = `views` + 1');",
    ];

    $propres = [
        'colonne gardée'               => "<?php \$this->db->execute('UPDATE nf_pages_x SET views = views + 1, updated_at = updated_at WHERE id = '.(int) \$id);",
        'colonne gardée (constructeur)' => "<?php NeoFrag()->db->where('id', \$id)->update('nf_pages_x', 'views = views + 1, updated_at = updated_at');",
        'table sans colonne auto'      => "<?php \$this->db->execute('UPDATE nf_autre SET views = views + 1 WHERE id = 1');",
        'écriture ordinaire'           => "<?php \$this->db->where('id', 1)->update('nf_pages_x', ['views' => 0]);",
    ];

    foreach ($defauts as $nom => $src)
    {
        if (!analyser_source($src, $schema))
        {
            $echecs[] = "défaut NON vu : $nom";
        }
    }

    foreach ($propres as $nom => $src)
    {
        if (analyser_source($src, $schema))
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

$sql = '';

foreach (array_merge(['install/schema.sql' => NF_RACINE.'/install/schema.sql'], nf_fichiers(['modules'], ['sql']), nf_fichiers(['neofrag/install'], ['php'])) as $chemin)
{
    $sql .= (string) @file_get_contents($chemin)."\n";
}

$automatiques = colonnes_automatiques($sql);

if (!$automatiques)
{
    nf_refus('aucune colonne ON UPDATE trouvée dans les schémas : le relevé est cassé, pas le produit');
}

$fautes   = [];
$fichiers = 0;

foreach (nf_fichiers(NF_DOSSIERS_PRODUIT, ['php']) as $rel => $chemin)
{
    $fichiers++;

    foreach (analyser_source(nf_sans_commentaires((string) file_get_contents($chemin)), $automatiques) as $faute)
    {
        $fautes[] = $faute + ['fichier' => $rel];
    }
}

if (!$fautes)
{
    nf_ok(sprintf('aucun compteur ne fait avancer une date de modification (%d table(s) à colonne automatique, %d fichier(s), épreuve à l\'envers passée)', count($automatiques), $fichiers));
}

echo "COMPTEURS QUI RÉÉCRIVENT LA DATE DE MODIFICATION — la colonne suit ON UPDATE current_timestamp :\n\n";

foreach ($fautes as $faute)
{
    printf("  %s:%d  %s.%s\n      ajouter « , %s = %s » au SET : une visite ne modifie pas la ligne\n\n", $faute['fichier'], $faute['ligne'], $faute['table'], $faute['colonne'], $faute['colonne'], $faute['colonne']);
}

nf_echec(count($fautes).' compteur(s) qui réécrivent une date de modification');
