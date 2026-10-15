<?php
declare(strict_types=1);

/**
 * check-colonnes-retirees — une colonne qu'une migration retire n'est plus dans le schéma d'une installation neuve.
 *
 * Famille : statique
 * Diffusion : publique
 *
 * Pourquoi ce contrôle existe
 * ---------------------------
 * Une installation neuve ne joue pas les migrations : elle prend `install.sql` tel quel, et marque les migrations
 * comme faites. Quand une migration retire une colonne (`ALTER TABLE … DROP COLUMN`) — le nom d'un type passe dans
 * une table de traductions, par exemple —, `install.sql` doit la perdre aussi. Sinon le site mis à jour n'a plus la
 * colonne, et le site neuf la garde, obligatoire et sans valeur par défaut : le code, écrit pour le premier, ne la
 * remplit plus, et chaque création échoue sur le second (« Field 'title' doesn't have a default value »).
 *
 * Trouvé le 2026-10-15, au banc de la 1.2.50 : sept colonnes, dans cinq modules (types de rendez-vous, catégories
 * d'annonces, cartes et modes de jeu, rôles d'équipe, émissions), retirées par leurs migrations et restées dans
 * leur `install.sql`. Sur une installation neuve, aucun de ces éléments ne pouvait se créer.
 *
 * Ce que le contrôle vérifie
 * --------------------------
 * Pour chaque `ALTER TABLE t … DROP COLUMN c` d'une migration d'addon (`<type>s/<nom>/install/migrations/*.up.sql`),
 * le `CREATE TABLE t` de son `install/install.sql` ne déclare pas `c` ; pour une migration du cœur (`migrations/`),
 * celui de `install/schema.sql`.
 *
 * Usage
 * -----
 *   php tools/check-colonnes-retirees.php             liste les colonnes retirées encore déclarées, code 1 s'il y en a
 *   php tools/check-colonnes-retirees.php --epreuve   ne joue QUE l'épreuve à l'envers
 */

require __DIR__.'/lib/outil.php';

[$o] = nf_options(['epreuve' => FALSE]);

/**
 * Les colonnes qu'un SQL de migration retire : [table, colonne].
 *
 * @return list<array{0: string, 1: string}>
 */
function colonnes_retirees(string $sql): array
{
    $retirees = [];

    preg_match_all('/ALTER\s+TABLE\s+`?([a-z0-9_]+)`?(.*?);/is', $sql, $alters, PREG_SET_ORDER);

    foreach ($alters as [, $table, $corps])
    {
        preg_match_all('/DROP\s+COLUMN\s+(?:IF\s+EXISTS\s+)?`?([a-z0-9_]+)`?/i', $corps, $colonnes);

        foreach ($colonnes[1] as $colonne)
        {
            $retirees[] = [strtolower($table), strtolower($colonne)];
        }
    }

    return $retirees;
}

/** Les colonnes que le CREATE TABLE de `$table` déclare dans un schéma, ou NULL si la table n'y est pas. */
function colonnes_declarees(string $schema, string $table): ?array
{
    if (!preg_match('/CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?`'.preg_quote($table, '/').'`\s*\((.*?)\n\)/is', $schema, $m))
    {
        return NULL;
    }

    preg_match_all('/^\s*`([a-z0-9_]+)`/im', $m[1], $colonnes);

    return array_map('strtolower', $colonnes[1]);
}

/**
 * Les colonnes retirées par `$migration` et encore déclarées par `$schema`.
 *
 * @return list<string> « table.colonne »
 */
function encore_declarees(string $migration, string $schema): array
{
    $fautes = [];

    foreach (colonnes_retirees($migration) as [$table, $colonne])
    {
        if (in_array($colonne, colonnes_declarees($schema, $table) ?? [], TRUE))
        {
            $fautes[] = $table.'.'.$colonne;
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
    $schema = "CREATE TABLE IF NOT EXISTS `nf_t` (\n  `id` int(10) unsigned NOT NULL,\n  `title` varchar(100) NOT NULL,\n  `color` varchar(20) NOT NULL,\n  PRIMARY KEY (`id`)\n) ENGINE=InnoDB;\n"
            ."CREATE TABLE IF NOT EXISTS `nf_u` (\n  `id` int(10) unsigned NOT NULL\n) ENGINE=InnoDB;\n";
    $echecs = [];

    $defauts = [
        'DROP COLUMN simple'           => "ALTER TABLE `nf_t` DROP COLUMN `title`;",
        'sans accents graves'          => "ALTER TABLE nf_t ADD KEY x (id), DROP COLUMN title;",
        'IF EXISTS, sur deux lignes'   => "ALTER TABLE `nf_t`\n  DROP COLUMN IF EXISTS `color`;",
    ];

    $propres = [
        'colonne absente du schéma'    => "ALTER TABLE `nf_t` DROP COLUMN `description`;",
        'table absente du schéma'      => "ALTER TABLE `nf_v` DROP COLUMN `title`;",
        'une autre table'              => "ALTER TABLE `nf_u` DROP COLUMN `title`;",
        'un ajout'                     => "ALTER TABLE `nf_t` ADD COLUMN `title2` varchar(10) NOT NULL DEFAULT '';",
    ];

    foreach ($defauts as $nom => $sql)
    {
        if (!encore_declarees($sql, $schema))
        {
            $echecs[] = "défaut NON vu : $nom";
        }
    }

    foreach ($propres as $nom => $sql)
    {
        if (encore_declarees($sql, $schema))
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
    nf_refus('épreuve à l\'envers ratée — '.implode(' ; ', $echecs));
}

$racine = nf_racine();
$fautes = [];
$vues   = 0;

// Les migrations des addons, contre leur install.sql ; celles du cœur, contre install/schema.sql.
$jeux = [];

foreach (glob($racine.'/{modules,widgets,themes}/*/install/migrations/*.up.sql', GLOB_BRACE) ?: [] as $migration)
{
    $jeux[] = [$migration, dirname($migration, 2).'/install.sql'];
}

foreach (glob($racine.'/migrations/*.up.sql') ?: [] as $migration)
{
    $jeux[] = [$migration, $racine.'/install/schema.sql'];
}

foreach ($jeux as [$migration, $schema])
{
    $vues++;

    foreach (is_file($schema) ? encore_declarees((string) file_get_contents($migration), (string) file_get_contents($schema)) : [] as $colonne)
    {
        $fautes[] = [substr($migration, strlen($racine) + 1), substr($schema, strlen($racine) + 1), $colonne];
    }
}

if (!$fautes)
{
    nf_ok(sprintf('aucune colonne retirée par une migration ne reste dans le schéma d\'une installation neuve (%d migration(s), épreuve à l\'envers passée)', $vues));
}

echo "COLONNES RETIRÉES PAR UNE MIGRATION, ENCORE DÉCLARÉES POUR UNE INSTALLATION NEUVE :\n\n";

foreach ($fautes as [$migration, $schema, $colonne])
{
    echo "  {$colonne}\n      retirée par {$migration}\n      encore dans {$schema} : l'y retirer aussi\n\n";
}

nf_echec(count($fautes).' colonne(s) retirée(s) encore déclarée(s)');
