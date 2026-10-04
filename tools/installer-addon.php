<?php
declare(strict_types=1);

/**
 * installer-addon — installe un addon déjà présent sur le disque, comme le fait l'installeur du site.
 *
 * Famille : outil
 * Diffusion : publique
 *
 * Pourquoi
 * --------
 * Un addon livré par la mise à jour du cœur arrive sur le disque, mais n'est pas installé : il
 * attend qu'un administrateur passe par « Thèmes & Addons → Scanner le disque ». Sur nos propres
 * installations (production, démonstration, essai), ce geste se faisait à la main dans un
 * navigateur — et un geste à la main s'oublie, ou se fait sur deux sites sur trois. Le module `api`
 * (2026-10-01) en a donné l'occasion.
 *
 * L'outil fait les trois gestes de l'installeur (`Installer::install_complete()`), pour un seul
 * addon : jouer son `install/install.sql`, l'inscrire comme installé et activé dans `nf_addon`, et
 * marquer ses migrations comme appliquées (son `install.sql` porte déjà le schéma à jour). Les
 * trois sont idempotents : relancer l'outil sur un addon installé ne change rien.
 *
 * Usage
 * -----
 *   php tools/installer-addon.php module api
 *   php tools/installer-addon.php module api --pretend
 *
 * Codes retour : 0 OK · 2 mauvais usage ou addon absent du disque · 1 échec de l'installation.
 */

require __DIR__.'/lib/outil.php';
require __DIR__.'/lib/site.php';
require_once nf_racine().'/neofrag/installer.php';

use NF\NeoFrag\Installer;

[$o, $reste] = nf_options(['pretend' => FALSE]);

[$type, $nom] = array_pad($reste, 2, '');

$dossiers = ['module' => 'modules', 'widget' => 'widgets', 'theme' => 'themes'];

if (!isset($dossiers[$type]) || !preg_match('/^[a-z0-9_]+$/', $nom))
{
    nf_refus('usage : php tools/installer-addon.php <module|widget|theme> <nom>');
}

$racine  = nf_racine();
$dossier = $racine.'/'.$dossiers[$type].'/'.$nom;

if (!is_file($dossier.'/'.$nom.'.php'))
{
    nf_refus("{$type} « {$nom} » absent du disque ({$dossiers[$type]}/{$nom}/{$nom}.php)");
}

$db      = nf_connexion();
$type_id = nf_scalar($db, "SELECT id FROM nf_addon_type WHERE name = '".$db->real_escape_string($type)."'");

if ($type_id === NULL)
{
    nf_refus("type d'addon « {$type} » inconnu de cette installation (nf_addon_type)");
}

$deja = nf_scalar($db, 'SELECT id FROM nf_addon WHERE type_id = '.(int) $type_id." AND name = '".$db->real_escape_string($nom)."'") !== NULL;
$sql  = $dossier.'/install/install.sql';

if ($o['pretend'])
{
    echo '-- MODE --pretend : aucune écriture.'.PHP_EOL;
    echo ($deja ? 'déjà inscrit' : 'à inscrire').' · '.(is_file($sql) ? 'install.sql à jouer' : 'sans install.sql').PHP_EOL;
    exit(NF_OK);
}

try
{
    if (is_file($sql))
    {
        Installer::import_sql_file($db, $sql);
    }

    $donnees = serialize(['enabled' => TRUE]);
    $requete = $db->prepare('INSERT IGNORE INTO nf_addon (type_id, name, data) VALUES (?, ?, ?)');
    $id      = (int) $type_id;
    $requete->bind_param('iss', $id, $nom, $donnees);
    $requete->execute();
    $requete->close();

    Installer::baseline_addon_migrations($db, $type, $nom, $racine);
}
catch (\Throwable $e)
{
    nf_echec("installation de {$type}/{$nom} : ".$e->getMessage());
}

nf_ok($deja ? "{$type}/{$nom} était déjà installé : schéma et migrations revérifiés" : "{$type}/{$nom} installé et activé");
