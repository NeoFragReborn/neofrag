<?php
declare(strict_types=1);
/**
 * check-instantanes — les SQL livrés s'importent (aucune clé en double) ; l'historique des migrations est complet.
 *
 * Famille : statique
 *
 * Pourquoi cet outil existe
 * -------------------------
 * La démonstration se remet à zéro toutes les quinze minutes en rejouant `install/demo.sql`. Le
 * 2026-09-22, une retouche à la main de ce fichier a donné à une disposition de Nebula le numéro
 * 92, déjà porté par une disposition de Blockcraft. MySQL refuse tout l'import à la première clé en
 * double : pendant quatorze heures, la démonstration ne s'est plus remise à zéro du tout, et ce que
 * les visiteurs y changeaient restait. Rien ne le disait — la tâche planifiée échouait sans bruit,
 * la page servie restait belle. Trouvé le 2026-09-23 en cherchant pourquoi une ligne survivait aux
 * remises à zéro.
 *
 * Ce qu'il vérifie
 * ----------------
 * Pour chaque fichier livré (`install/demo.sql`, `seed.sql`, `vitrine.sql`, `wiki.sql`) : dans les
 * `INSERT` ordinaires d'une même table, aucune clé primaire deux fois — sauf si un `DELETE FROM` de
 * toute la table les sépare. Les `INSERT IGNORE` et les `ON DUPLICATE KEY UPDATE` sont écrits pour
 * rencontrer une ligne existante : ils ne comptent pas.
 *
 * La clé primaire de chaque table est lue dans les `CREATE TABLE` du dépôt (`install/schema.sql`,
 * `migrations/`, `install/install.sql` de chaque addon). Une table dont la clé est introuvable est
 * nommée, pas devinée.
 *
 * L'épreuve à l'envers tourne à chaque lancement : un instantané fabriqué avec une clé en double
 * doit être refusé, le même sans doublon accepté.
 *
 * **L'historique des migrations** (2026-10-02) : une installation neuve importe `install/schema.sql`,
 * déjà à jour, qui doit donc marquer chaque migration du dépôt comme appliquée dans `nf_migrations`
 * — sinon le premier passage la rejouerait sur un schéma qui la contient déjà. Le test d'intégration
 * de l'installateur le vérifiait, mais seulement avec une base : la migration du captcha est partie
 * sans sa ligne, et seule la CI l'a vu. Ici, sans base : chaque `migrations/*.up.sql` a sa ligne,
 * aucune ligne ne nomme une migration absente ni ne se répète.
 *
 * Usage
 * -----
 *   php tools/check-instantanes.php
 */

require __DIR__.'/lib/outil.php';
require __DIR__.'/lib/sql.php';

nf_options([]);

$racine = nf_racine();

/**
 * Les clés primaires déclarées dans un texte SQL : table => liste des colonnes.
 *
 * @return array<string, list<string>>
 */
function cles_primaires(string $sql): array
{
    $cles = [];

    if (preg_match_all('/CREATE TABLE (?:IF NOT EXISTS )?`(\w+)` \((.*?)\n\)/s', $sql, $tables, PREG_SET_ORDER))
    {
        foreach ($tables as [, $table, $corps])
        {
            if (preg_match('/PRIMARY KEY \(([^)]*)\)/', $corps, $m))
            {
                $cles[$table] = array_map(static fn (string $c): string => trim(preg_replace('/\(\d+\)/', '', $c) ?? $c, " `"), explode(',', $m[1]));
            }
        }
    }

    return $cles;
}

/**
 * Les clés en double d'un instantané : [table, clé, position de la seconde occurrence].
 *
 * @param array<string, list<string>> $cles
 * @return array{doublons: list<array{0: string, 1: string, 2: int}>, sans_cle: list<string>}
 */
function doublons(string $sql, array $cles): array
{
    $doublons = [];
    $sans_cle = [];

    preg_match_all('/INSERT (IGNORE )?INTO `(\w+)` \([^)]*\) VALUES/', $sql, $entetes, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);

    foreach (array_unique(array_map(static fn (array $e): string => $e[2][0], $entetes)) as $table)
    {
        if (!isset($cles[$table]))
        {
            $sans_cle[] = $table;
            continue;
        }

        // Les instructions de CETTE table, dans l'ordre : INSERT (ordinaire ou non) et DELETE de toute la table.
        $evenements = [];

        foreach ($entetes as $e)
        {
            if ($e[2][0] === $table)
            {
                $fin_instruction = strpos($sql, ";\n", $e[0][1]);
                $instruction     = substr($sql, $e[0][1], $fin_instruction === FALSE ? NULL : $fin_instruction - $e[0][1]);
                $tolerant        = $e[1][0] !== '' || str_contains(substr($instruction, (int) strrpos($instruction, ')')), 'ON DUPLICATE KEY UPDATE');

                $evenements[] = ['position' => $e[0][1], 'fin' => $fin_instruction === FALSE ? strlen($sql) : $fin_instruction, 'tolerant' => $tolerant];
            }
        }

        if (preg_match_all('/DELETE FROM `'.preg_quote($table, '/').'`;/', $sql, $effacements, PREG_OFFSET_CAPTURE))
        {
            foreach ($effacements[0] as [, $position])
            {
                $evenements[] = ['position' => $position, 'effacement' => TRUE];
            }
        }

        usort($evenements, static fn (array $a, array $b): int => $a['position'] <=> $b['position']);

        $lu    = nf_sql_tuples($sql, $table);
        $index = array_flip($lu['colonnes']);

        if (array_diff($cles[$table], $lu['colonnes']))
        {
            continue; // l'INSERT n'écrit pas toute la clé : c'est la base qui la fournit
        }

        $vues = [];

        foreach ($evenements as $ev)
        {
            if (!empty($ev['effacement']))
            {
                $vues = [];
                continue;
            }

            foreach ($lu['tuples'] as $tuple)
            {
                if ($tuple['debut'] < $ev['position'] || $tuple['debut'] > $ev['fin'] || $ev['tolerant'])
                {
                    continue;
                }

                $cle = implode(' · ', array_map(static fn (string $c): string => (string) ($tuple['valeurs'][$index[$c]] ?? 'NULL'), $cles[$table]));

                if (isset($vues[$cle]))
                {
                    $doublons[] = [$table, $cle, $tuple['debut']];
                }

                $vues[$cle] = TRUE;
            }
        }
    }

    return ['doublons' => $doublons, 'sans_cle' => $sans_cle];
}

// ── L'épreuve à l'envers ────────────────────────────────────────────────────────────────────
$schema_epreuve = "CREATE TABLE `nf_x` (\n  `x_id` int NOT NULL,\n  `nom` text,\n  PRIMARY KEY (`x_id`)\n)";
$mauvais        = "DELETE FROM `nf_x`;\nINSERT INTO `nf_x` (`x_id`, `nom`) VALUES\n('82', 'a'),\n('92', 'b'),\n('92', 'c');\n";
$bon            = "DELETE FROM `nf_x`;\nINSERT INTO `nf_x` (`x_id`, `nom`) VALUES\n('82', 'a'),\n('92', 'b');\n"
                . "INSERT INTO `nf_x` (`x_id`, `nom`) VALUES ('92', 'b') ON DUPLICATE KEY UPDATE `nom` = 'b';\n"
                . "INSERT IGNORE INTO `nf_x` (`x_id`, `nom`) VALUES ('82', 'a');\n"
                . "DELETE FROM `nf_x`;\nINSERT INTO `nf_x` (`x_id`, `nom`) VALUES ('82', 'a');\n";

if (count(doublons($mauvais, cles_primaires($schema_epreuve))['doublons']) !== 1 || doublons($bon, cles_primaires($schema_epreuve))['doublons'] !== [])
{
    nf_refus('l\'épreuve à l\'envers échoue : le contrôle ne distingue plus un instantané fautif d\'un instantané sain');
}

// ── Les clés primaires du dépôt ─────────────────────────────────────────────────────────────
$cles = [];
$sources = array_merge(
    [$racine.'/install/schema.sql'],
    glob($racine.'/migrations/*.up.sql') ?: [],
    glob($racine.'/{modules,widgets,themes}/*/install/*.sql', GLOB_BRACE) ?: []
);

foreach ($sources as $source)
{
    $cles = array_merge($cles, cles_primaires((string) file_get_contents($source)));
}

printf("%d clé(s) primaire(s) lue(s) dans %d fichier(s) de schéma. Épreuve à l'envers : OK.\n\n", count($cles), count($sources));

// ── Les fichiers livrés ─────────────────────────────────────────────────────────────────────
$problemes = 0;
$inconnues = [];

foreach (['install/demo.sql', 'install/seed.sql', 'install/vitrine.sql', 'install/wiki.sql'] as $relatif)
{
    if (!is_file($chemin = $racine.'/'.$relatif))
    {
        continue;
    }

    $sql = (string) file_get_contents($chemin);
    $lu  = doublons($sql, $cles + cles_primaires($sql));

    foreach ($lu['doublons'] as [$table, $cle, $position])
    {
        printf("  %s:%d — `%s` : la clé %s est écrite deux fois. MySQL refuse TOUT l'import à cette ligne.\n",
            $relatif, substr_count(substr($sql, 0, $position), "\n") + 1, $table, $cle);
        $problemes++;
    }

    foreach ($lu['sans_cle'] as $table)
    {
        $inconnues[$table][] = $relatif;
    }
}

foreach ($inconnues as $table => $fichiers)
{
    nf_avertir(sprintf('%s : clé primaire introuvable dans les schémas du dépôt — non vérifiée (%s)', $table, implode(', ', $fichiers)));
}

// ── L'historique des migrations dans le schéma d'installation ─────────────────────────────────
// Lu par motif et non par nf_sql_tuples() : l'historique glisse un commentaire avant chaque ligne,
// et la lecture générale s'arrête au premier.
$schema    = (string) file_get_contents($racine.'/install/schema.sql');
$debut     = strpos($schema, 'INSERT INTO `nf_migrations`');
$fin       = $debut === FALSE ? FALSE : strpos($schema, "');\n", $debut);
$inscrites = [];

if ($debut !== FALSE && $fin !== FALSE && preg_match_all("/^\('\d+', '([^']+)'/m", substr($schema, $debut, $fin - $debut + 3), $m))
{
    $inscrites = $m[1];
}

$du_depot = array_map(static fn (string $f): string => basename($f, '.up.sql'), glob($racine.'/migrations/*.up.sql') ?: []);

foreach (array_diff($du_depot, $inscrites) as $nom)
{
    printf("  install/schema.sql — la migration %s n'est pas inscrite dans nf_migrations : une installation neuve la rejouerait.\n", $nom);
    $problemes++;
}

foreach (array_diff($inscrites, $du_depot) as $nom)
{
    printf("  install/schema.sql — nf_migrations inscrit %s, absente de migrations/.\n", $nom);
    $problemes++;
}

foreach (array_keys(array_filter(array_count_values($inscrites), static fn (int $n): bool => $n > 1)) as $nom)
{
    printf("  install/schema.sql — nf_migrations inscrit %s plusieurs fois.\n", $nom);
    $problemes++;
}

printf("Historique des migrations : %d dans migrations/, %d inscrite(s) dans install/schema.sql.\n", count($du_depot), count($inscrites));

if ($problemes)
{
    echo "\nUn instantané se régénère par son outil (tools/dump-demo.php pour la démonstration), il ne se retouche pas\nà la main. Pour une retouche inévitable, reprendre le numéro que porte la ligne dans la base.\n";
    nf_echec(sprintf('%d défaut(s) dans les fichiers SQL livrés', $problemes));
}

nf_ok('les fichiers SQL livrés s\'importent : aucune clé primaire écrite deux fois, historique des migrations complet');
