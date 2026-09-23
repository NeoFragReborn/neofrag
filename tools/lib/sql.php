<?php
declare(strict_types=1);

/**
 * sql — produire et jouer du SQL depuis la base vive.
 *
 * Pourquoi
 * --------
 * Cinq outils régénèrent des fichiers SQL livrés (`install/schema.sql`, `seed.sql`, `demo.sql`,
 * `wiki.sql`, les `install.sql` des modules) et deux en jouent (`migrate`, `prepare-test-db`). Ils
 * recopiaient les mêmes fonctions : les INSERT d'une table, `SHOW CREATE TABLE` sans compteur
 * d'auto-incrément, la collation MariaDB 11 (`uca1400`) ramenée à une collation que MySQL 8 et
 * MariaDB 10.6 connaissent — sans quoi le paquet est ININSTALLABLE sur la plupart des hébergements.
 *
 * Usage
 * -----
 *   $sql  = nf_sql_entete('dump-schema', 'schéma de référence');
 *   $sql .= nf_sql_show_create($db, 'nf_user').";\n";
 *   $sql .= nf_sql_inserts($db, 'nf_settings');
 */

require_once __DIR__.'/site.php';

/** L'en-tête de tout fichier SQL généré : il dit d'où il vient et comment le refaire. */
function nf_sql_entete(string $outil, string $quoi): string
{
    return "-- NeoFrag Reborn — {$quoi}.\n"
        ."-- Généré par tools/{$outil}.php depuis la base vive. NE PAS éditer à la main.\n"
        ."-- Régénérer : php tools/{$outil}.php\n\n";
}

/** Les tables vives, triées, hors résidus de migration (`_backup_*`, `_tmp*`). */
function nf_sql_tables(mysqli $db): array
{
    $tables = array_filter(array_map('strval', nf_colonne($db, 'SHOW TABLES')),
        static fn (string $t): bool => !str_starts_with($t, '_backup_') && !str_starts_with($t, '_tmp'));

    sort($tables);

    return array_values($tables);
}

/**
 * Le `CREATE TABLE` d'une table, portable : sans compteur `AUTO_INCREMENT` (un schéma de
 * référence ne fige pas un identifiant de départ) et en collation universelle.
 */
function nf_sql_show_create(mysqli $db, string $table, bool $si_absente = FALSE): string
{
    $resultat = $db->query("SHOW CREATE TABLE `{$table}`");
    $ligne    = $resultat instanceof mysqli_result ? $resultat->fetch_row() : NULL;

    if (!$ligne)
    {
        nf_refus("SHOW CREATE TABLE `{$table}` : ".$db->error);
    }

    $create = (string) preg_replace('/ AUTO_INCREMENT=\d+/', '', (string) $ligne[1]);
    $create = nf_sql_collation_portable($create);

    if ($si_absente)
    {
        $create = (string) preg_replace('/^CREATE TABLE `/', 'CREATE TABLE IF NOT EXISTS `', $create, 1);
    }

    return $create;
}

/**
 * Ramène les collations MariaDB 11 (`utf8mb4_uca1400_ai_ci`) à `utf8mb4_unicode_ci`, que MySQL 5.7+
 * et MariaDB 10+ connaissent tous. La base de développement tourne sous MariaDB 11 : sans cette
 * normalisation, chaque `CREATE TABLE` échoue en « Unknown collation » chez l'hébergeur.
 */
function nf_sql_collation_portable(string $sql): string
{
    return (string) preg_replace('/(utf8mb[34])_uca1400_ai_ci/', '$1_unicode_ci', $sql);
}

/**
 * Les `INSERT` d'une table.
 *
 * @param callable|null $transform  fn(array $ligne, list<string> $colonnes): array — neutralise ou force des valeurs
 * @param callable|null $filter     fn(array $ligne): bool — ne garde que les lignes vraies
 */
function nf_sql_inserts(mysqli $db, string $table, string $where = '', ?callable $transform = NULL, ?callable $filter = NULL): string
{
    [$colonnes, $lignes] = nf_sql_lignes($db, $table, $where, $transform, $filter);

    if (!$lignes)
    {
        return "-- {$table} : aucune donnée.\n";
    }

    return "INSERT INTO `{$table}` ({$colonnes}) VALUES\n".implode(",\n", $lignes).";\n";
}

/**
 * Comme nf_sql_inserts(), en `INSERT … ON DUPLICATE KEY UPDATE` : une ligne apparue depuis la
 * prise de l'instantané reste en place au lieu de disparaître. `$colonnes_maj` nomme les colonnes
 * remises à jour ; `['*']` les remet toutes sauf `id`, la clé qui identifie la ligne.
 *
 * @param list<string> $colonnes_maj
 */
function nf_sql_upserts(mysqli $db, string $table, array $colonnes_maj, string $where = ''): string
{
    [$colonnes, $lignes, $noms] = nf_sql_lignes($db, $table, $where);

    if (!$lignes)
    {
        return "-- {$table} : aucune donnée.\n";
    }

    if ($colonnes_maj === ['*'])
    {
        $colonnes_maj = array_values(array_diff($noms, ['id']));
    }

    $maj = array_map(static fn (string $c): string => "`{$c}` = VALUES(`{$c}`)", $colonnes_maj);

    return "INSERT INTO `{$table}` ({$colonnes}) VALUES\n".implode(",\n", $lignes)
        ."\nON DUPLICATE KEY UPDATE ".implode(', ', $maj).";\n";
}

/** @return array{0: string, 1: list<string>, 2: list<string>}  liste des colonnes échappée, tuples, noms de colonnes */
function nf_sql_lignes(mysqli $db, string $table, string $where, ?callable $transform = NULL, ?callable $filter = NULL): array
{
    $resultat = $db->query("SELECT * FROM `{$table}` {$where}");

    if (!$resultat instanceof mysqli_result || $resultat->num_rows === 0)
    {
        return ['', [], []];
    }

    $noms   = array_map(static fn ($champ): string => $champ->name, $resultat->fetch_fields());
    $lignes = [];

    while ($ligne = $resultat->fetch_assoc())
    {
        if ($filter && !$filter($ligne))
        {
            continue;
        }

        if ($transform)
        {
            $ligne = $transform($ligne, $noms);
        }

        $valeurs = array_map(static fn ($v): string => $v === NULL ? 'NULL' : "'".$db->real_escape_string((string) $v)."'", array_values($ligne));
        $lignes[] = '('.implode(', ', $valeurs).')';
    }

    return ['`'.implode('`, `', $noms).'`', $lignes, $noms];
}

/**
 * Joue un texte SQL complet (plusieurs instructions) et rend le message d'erreur, ou NULL si tout
 * est passé. Les résultats intermédiaires sont drainés pour attraper une erreur au milieu du lot —
 * le DDL MySQL est auto-commit, la base peut alors être partiellement migrée : l'appelant le dit.
 */
function nf_sql_jouer(mysqli $db, string $sql): ?string
{
    if (trim($sql) === '')
    {
        return NULL;
    }

    if (!$db->multi_query($sql))
    {
        return $db->error;
    }

    do
    {
        if ($resultat = $db->store_result())
        {
            $resultat->free();
        }

        if ($db->errno)
        {
            return $db->error;
        }
    }
    while ($db->more_results() && $db->next_result());

    return $db->errno ? $db->error : NULL;
}

/** Joue un fichier SQL ; rend le message d'erreur, ou NULL. */
function nf_sql_jouer_fichier(mysqli $db, string $chemin): ?string
{
    $sql = @file_get_contents($chemin);

    if ($sql === FALSE)
    {
        return "fichier illisible : {$chemin}";
    }

    return nf_sql_jouer($db, $sql);
}

/**
 * Les lignes d'une table dans un fichier SQL déjà écrit, sans base : chaque tuple de ses
 * `INSERT INTO `table` (…) VALUES (…), (…);`, avec sa position dans le texte.
 *
 * Sert à VÉRIFIER un fichier livré (le contenu attendu est-il celui qui est écrit ?) et à en
 * réécrire quelques lignes sans le régénérer tout entier. Les valeurs sont rendues désséchappées ;
 * `NULL` reste NULL.
 *
 * @return array{colonnes: list<string>, tuples: list<array{debut: int, fin: int, valeurs: list<?string>}>}
 */
function nf_sql_tuples(string $sql, string $table): array
{
    $colonnes = [];
    $tuples   = [];
    $motif    = '/INSERT INTO `'.preg_quote($table, '/').'` \(([^)]*)\) VALUES\s*/';

    if (!preg_match_all($motif, $sql, $entetes, PREG_OFFSET_CAPTURE))
    {
        return ['colonnes' => [], 'tuples' => []];
    }

    $echappements = ['0' => "\0", 'n' => "\n", 'r' => "\r", 'Z' => "\x1a", 't' => "\t", 'b' => "\x08"];

    foreach ($entetes[0] as $k => [$texte, $position])
    {
        $colonnes = array_map(static fn (string $c): string => trim($c, " `"), explode(',', $entetes[1][$k][0]));
        $i        = $position + strlen($texte);
        $n        = strlen($sql);

        // Une suite de tuples séparés par des virgules, jusqu'au point-virgule.
        while ($i < $n && $sql[$i] === '(')
        {
            $debut   = $i++;
            $valeurs = [];

            while ($i < $n && $sql[$i] !== ')')
            {
                while ($sql[$i] === ' ' || $sql[$i] === ',') { $i++; }

                if ($sql[$i] === "'")
                {
                    $valeur = '';

                    for ($i++; $i < $n && $sql[$i] !== "'"; $i++)
                    {
                        if ($sql[$i] === '\\' && $i + 1 < $n)
                        {
                            $suivant = $sql[++$i];
                            $valeur .= $echappements[$suivant] ?? $suivant;
                            continue;
                        }

                        $valeur .= $sql[$i];
                    }

                    $i++;
                    $valeurs[] = $valeur;
                }
                else
                {
                    $fin_brut  = strcspn($sql, ',)', $i);
                    $brut      = trim(substr($sql, $i, $fin_brut));
                    $valeurs[] = strtoupper($brut) === 'NULL' ? NULL : $brut;
                    $i        += $fin_brut;
                }
            }

            $tuples[] = ['debut' => $debut, 'fin' => ++$i, 'valeurs' => $valeurs];

            while ($i < $n && ($sql[$i] === ',' || ctype_space($sql[$i]))) { $i++; }
        }
    }

    return ['colonnes' => $colonnes, 'tuples' => $tuples];
}

/** Une valeur écrite comme nf_sql_lignes() l'écrit (même échappement que mysqli::real_escape_string). */
function nf_sql_valeur(?string $valeur): string
{
    return $valeur === NULL ? 'NULL' : "'".strtr($valeur, ["\\" => "\\\\", "\0" => "\\0", "\n" => "\\n", "\r" => "\\r", "'" => "\\'", '"' => '\\"', "\x1a" => "\\Z"])."'";
}
