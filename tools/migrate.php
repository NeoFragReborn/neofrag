<?php
declare(strict_types=1);

/**
 * migrate — le runner des migrations SQL du cœur.
 *
 * Famille : outil
 *
 * Ce qu'il fait
 * -------------
 * Applique ou annule les fichiers `migrations/*.up.sql` / `*.down.sql` et trace l'état dans la
 * table `nf_migrations`. Conçu pour combler l'absence de runner : les .sql étaient appliqués à la
 * main, sans suivi, avec un risque de dérive. Le DDL MySQL est auto-commit : une migration qui
 * échoue en chemin laisse la base partiellement migrée — sauvegarder avant `up` en production.
 *
 * Connexion : `config/db.php`, surchargée par NF_DB_HOST/PORT/USER/PASS/NAME (cf. tools/lib/site.php).
 *
 * Usage
 * -----
 *   php tools/migrate.php status
 *   php tools/migrate.php up [--pretend]
 *   php tools/migrate.php down [--step=N] [--pretend] [--force]
 *   php tools/migrate.php baseline [--until=NAME]     marque comme appliquées sans exécuter (batch 0)
 */

require __DIR__.'/lib/outil.php';
require __DIR__.'/lib/site.php';
require __DIR__.'/lib/sql.php';

[$o, $reste] = nf_options(['pretend' => FALSE, 'force' => FALSE, 'step' => 1, 'until' => '']);

const MIGRATIONS_DIR = __DIR__.'/../migrations';
const TABLE          = 'nf_migrations';

$command = $reste[0] ?? 'status';
$db      = nf_connexion();

$db->query('CREATE TABLE IF NOT EXISTS '.TABLE.' (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name       VARCHAR(255) NOT NULL,
    batch      INT UNSIGNED NOT NULL,
    applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4') || nf_refus('création de la table '.TABLE.' : '.$db->error);

/** @return list<string> noms de migration (sans .up.sql), triés chronologiquement par le préfixe daté */
function discover(): array
{
    $names = array_map(static fn (string $f): string => basename($f, '.up.sql'), glob(MIGRATIONS_DIR.'/*.up.sql') ?: []);
    sort($names);

    return $names;
}

/** @return array<string, int> name => batch */
function applied(mysqli $db): array
{
    $out = [];
    $res = $db->query('SELECT name, batch FROM '.TABLE.' ORDER BY id ASC');

    while ($res && ($row = $res->fetch_assoc()))
    {
        $out[$row['name']] = (int) $row['batch'];
    }

    return $out;
}

/** Joue un fichier de migration ; refuse en nommant la migration si une instruction échoue. */
function run_sql_file(mysqli $db, string $file, string $name): void
{
    $sql = @file_get_contents($file);

    if ($sql === FALSE || trim($sql) === '')
    {
        nf_refus("fichier vide ou illisible : {$file}");
    }

    if (($erreur = nf_sql_jouer($db, $sql)) !== NULL)
    {
        nf_refus("échec {$name} : {$erreur} (la base peut être partiellement migrée — le DDL MySQL est auto-commit)");
    }
}

$all     = discover();
$applied = applied($db);

switch ($command)
{
    case 'status':
        $pending = array_values(array_diff($all, array_keys($applied)));
        $orphans = array_values(array_diff(array_keys($applied), $all));

        echo 'Migrations ('.count($all).' fichier(s), '.count($applied).' appliquée(s), '.count($pending)." en attente)\n\n";

        foreach ($all as $name)
        {
            if (isset($applied[$name]))
            {
                printf("  [x] %-60s (batch %d)\n", $name, $applied[$name]);
            }
            else
            {
                $down = is_file(MIGRATIONS_DIR."/{$name}.down.sql") ? '' : '  ⚠ pas de .down.sql';
                printf("  [ ] %-60s PENDING%s\n", $name, $down);
            }
        }

        foreach ($orphans as $name)
        {
            printf("  [?] %-60s APPLIQUÉE mais .up.sql absent\n", $name);
        }

        break;

    case 'up':
        $pending = array_values(array_diff($all, array_keys($applied)));

        if (!$pending)
        {
            echo "Rien à appliquer — base à jour.\n";
            break;
        }

        $batch = (int) nf_scalar($db, 'SELECT COALESCE(MAX(batch), 0) + 1 FROM '.TABLE);
        echo count($pending)." migration(s) à appliquer (batch {$batch})".($o['pretend'] ? ' [PRETEND]' : '')." :\n";

        foreach ($pending as $name)
        {
            echo "  → {$name} ... ";

            if ($o['pretend'])
            {
                echo "(pretend)\n";
                continue;
            }

            run_sql_file($db, MIGRATIONS_DIR."/{$name}.up.sql", $name);
            $stmt = $db->prepare('INSERT INTO '.TABLE.' (name, batch) VALUES (?, ?)');
            $stmt->bind_param('si', $name, $batch);
            $stmt->execute() || nf_refus("enregistrement {$name} : ".$db->error);
            echo "OK\n";
        }

        echo "Terminé.\n";
        break;

    case 'down':
        $step    = max(1, $o['step']);
        $targets = array_map('strval', nf_colonne($db, 'SELECT name FROM '.TABLE.' ORDER BY id DESC LIMIT '.$step));

        if (!$targets)
        {
            echo "Rien à annuler.\n";
            break;
        }

        echo count($targets).' migration(s) à annuler'.($o['pretend'] ? ' [PRETEND]' : '')." :\n";

        foreach ($targets as $name)
        {
            $file = MIGRATIONS_DIR."/{$name}.down.sql";
            echo "  ← {$name} ... ";

            if (!is_file($file))
            {
                if (!$o['force'])
                {
                    echo "\n";
                    nf_refus("pas de .down.sql pour {$name} (migration non réversible). Utiliser --force pour la dé-tracer sans rollback SQL");
                }

                echo '(pas de .down.sql, --force → dé-traçage seul) ';
            }
            elseif (!$o['pretend'])
            {
                run_sql_file($db, $file, $name);
            }

            if ($o['pretend'])
            {
                echo "(pretend)\n";
                continue;
            }

            $stmt = $db->prepare('DELETE FROM '.TABLE.' WHERE name = ?');
            $stmt->bind_param('s', $name);
            $stmt->execute() || nf_refus("dé-traçage {$name} : ".$db->error);
            echo "OK\n";
        }

        echo "Terminé.\n";
        break;

    case 'baseline':
        // Marque des migrations comme appliquées SANS exécuter leur SQL (batch 0) : pour adopter
        // une base déjà migrée (dump importé) sans que le runner ne ré-applique ce qui existe.
        $to_mark = [];

        foreach ($all as $name)
        {
            if (isset($applied[$name]) || ($o['until'] !== '' && strcmp($name, $o['until']) > 0))
            {
                continue;
            }

            $to_mark[] = $name;
        }

        if (!$to_mark)
        {
            echo "Rien à baseliner.\n";
            break;
        }

        echo count($to_mark)." migration(s) marquée(s) appliquée(s) sans exécution (baseline, batch 0) :\n";

        foreach ($to_mark as $name)
        {
            $stmt = $db->prepare('INSERT INTO '.TABLE.' (name, batch) VALUES (?, 0)');
            $stmt->bind_param('s', $name);
            $stmt->execute() || nf_refus("baseline {$name} : ".$db->error);
            echo "  ✓ {$name}\n";
        }

        echo "Terminé.\n";
        break;

    default:
        nf_refus("commande inconnue : {$command} — status | up [--pretend] | down [--step=N] [--pretend] [--force] | baseline [--until=NAME]");
}

exit(NF_OK);
