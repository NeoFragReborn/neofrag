<?php
declare(strict_types=1);

/**
 * maintenance — tâches de maintenance périodiques, à lancer par un cron externe.
 *
 * Famille : outil
 * Diffusion : publique
 *
 * Ce qu'il fait
 * -------------
 * NeoFrag n'a pas d'ordonnanceur interne, les modules tournent en contexte HTTP. Deux tâches :
 *   - trash    : purge définitive du contenu soft-deleted (deleted_at) plus vieux que
 *                nf_trash_retention_days jours (défaut 30). Balaie TOUTES les tables ayant une
 *                colonne deleted_at ; les FK ON DELETE CASCADE nettoient les tables liées ;
 *   - accounts : purge des comptes jamais confirmés (last_activity_date IS NULL) inscrits il y a
 *                plus de nf_unconfirmed_retention_days jours (défaut 7). Ne tourne QUE si la
 *                validation d'inscription est active, sinon last_activity NULL = simple compte
 *                jamais connecté, à garder. Les admins ne sont jamais purgés.
 *
 * La PARUTION du contenu programmé n'est PAS ici — elle nécessite le framework et passe par
 * l'endpoint HTTP gardé par jeton : `curl -fsS "https://<site>/monitoring/cron?key=<nf_cron_key>"`.
 * `tests/Integration/MaintenanceDbTest.php` fige le SQL exact de ces deux purges.
 *
 * Usage
 * -----
 *   php tools/maintenance.php [trash|accounts|all] [--pretend]
 *   (cron)  * /15 * * * *  php /var/www/html/tools/maintenance.php all >> /var/log/nf-maint.log 2>&1
 */

require __DIR__.'/lib/outil.php';
require __DIR__.'/lib/site.php';

[$o, $reste] = nf_options(['pretend' => FALSE]);

const TRASH_DEFAULT_DAYS       = 30;
const UNCONFIRMED_DEFAULT_DAYS = 7;

$task = $reste[0] ?? 'all';

if (!in_array($task, ['trash', 'accounts', 'all'], TRUE))
{
    nf_refus("tâche inconnue : {$task} — trash, accounts ou all");
}

$db = nf_connexion();

if ($o['pretend'])
{
    echo "-- MODE --pretend : aucune suppression réelle.\n";
}

/** Rétention (jours) depuis un setting, bornée à >= 1, avec défaut. */
function retention_days(mysqli $db, string $setting, int $default): int
{
    $value = nf_reglage($db, $setting);

    return $value === NULL || (int) $value < 1 ? $default : (int) $value;
}

if ($task === 'trash' || $task === 'all')
{
    $days   = retention_days($db, 'nf_trash_retention_days', TRASH_DEFAULT_DAYS);
    $tables = nf_colonne($db, "SELECT table_name FROM information_schema.columns WHERE table_schema = DATABASE() AND column_name = 'deleted_at' ORDER BY table_name");
    $total  = 0;

    if (!$tables)
    {
        echo "trash : aucune table soft-delete.\n";
    }

    foreach ($tables as $table)
    {
        $where = "`deleted_at` IS NOT NULL AND `deleted_at` < (NOW() - INTERVAL {$days} DAY)";
        $count = (int) nf_scalar($db, "SELECT COUNT(*) FROM `{$table}` WHERE {$where}");

        if ($count > 0 && !$o['pretend'])
        {
            $db->query("DELETE FROM `{$table}` WHERE {$where}");
        }

        if ($count > 0)
        {
            echo "trash : {$table} — {$count} élément(s) ".($o['pretend'] ? 'à purger' : 'purgé(s)')."\n";
            $total += $count;
        }
    }

    if ($tables)
    {
        echo "trash : {$total} élément(s) au total (rétention {$days} j).\n";
    }
}

if ($task === 'accounts' || $task === 'all')
{
    if (!(int) nf_reglage($db, 'nf_registration_validation'))
    {
        echo "accounts : validation d'inscription désactivée — purge ignorée (last_activity NULL ≠ non confirmé).\n";
    }
    else
    {
        $days  = retention_days($db, 'nf_unconfirmed_retention_days', UNCONFIRMED_DEFAULT_DAYS);
        $where = "`last_activity_date` IS NULL AND `admin` = '0' AND `registration_date` < (NOW() - INTERVAL {$days} DAY)";
        $count = (int) nf_scalar($db, "SELECT COUNT(*) FROM `nf_user` WHERE {$where}");

        if ($count > 0 && !$o['pretend'])
        {
            $db->query("DELETE FROM `nf_user` WHERE {$where}");
        }

        echo "accounts : {$count} compte(s) non confirmé(s) ".($o['pretend'] ? 'à purger' : 'purgé(s)')." (rétention {$days} j).\n";
    }
}

exit(NF_OK);
