<?php
declare(strict_types=1);

/**
 * check-restauration — éprouve, pour de vrai, le cycle sauvegarde → casse → restauration.
 *
 * Famille : cible
 * Diffusion : publique
 *
 * Pourquoi
 * --------
 * Le CMS savait depuis toujours PRENDRE une sauvegarde complète — fichiers et base — et la prenait
 * bien, juste avant chaque mise à jour du cœur. Il ne savait pas s'en reservir : aucun code, nulle
 * part, ne relisait ces archives. Une mise à jour interrompue à mi-parcours laissait donc un site
 * mi-ancien mi-neuf, avec le filet de sécurité posé à côté, intact et inutile.
 *
 * `tests/Unit/BackupRestoreTest.php` fige la logique de fichiers sur des archives fabriquées. C'est
 * nécessaire, et ça ne prouve pas l'essentiel : qu'une archive produite par le VRAI bouton est
 * lisible par la restauration, qu'un vidage MySQL complet repasse par `Db::import()`, et qu'un site
 * réellement abîmé redevient réellement sain. Ce contrôle-ci le prouve, en rejouant le flux HTTP
 * d'un administrateur : témoins plantés, vraie sauvegarde, site abîmé de quatre façons, vraie
 * restauration, puis chaque promesse vérifiée une par une.
 *
 * Il ne se lance pas tout seul : il ABÎME volontairement l'installation sur laquelle il tourne, et
 * ne la répare qu'en réussissant. D'où `--site-jetable`, obligatoire.
 *
 * Usage
 * -----
 *   php tools/check-restauration.php --compte=admin --motdepasse=… --site-jetable   (abîme le site !)
 *   php tools/check-restauration.php --compte=admin --motdepasse=… --site-jetable --trace
 *   php tools/check-restauration.php … --webmaster=<mot de passe>   si config/webmaster.php existe
 */

require __DIR__.'/lib/outil.php';
require __DIR__.'/lib/depot.php';
require __DIR__.'/lib/site.php';
require __DIR__.'/lib/serveur.php';

[$o] = nf_options(['compte' => '', 'motdepasse' => '', 'webmaster' => '', 'port' => 0, 'trace' => FALSE, 'site-jetable' => FALSE]);

// ── Le garde-fou : cet outil abîme le site avant de le réparer ──────────────
// Il n'y a pas de façon honnête d'éprouver une restauration sans casser quelque chose. Le refus par
// défaut est donc la seule position tenable : c'est à l'opérateur de dire, explicitement, que cette
// installation-là est sacrifiable.
if (!$o['site-jetable'])
{
    nf_refus("cet outil ABÎME volontairement l'installation sur laquelle il tourne, puis la répare en la\n"
        ."  restaurant. Si la restauration échoue — ce qu'il est là pour détecter — le site reste abîmé.\n"
        ."  Ne le lance JAMAIS sur une installation en service. Sur une installation d'épreuve :\n"
        ."  php tools/check-restauration.php --compte=<admin> --motdepasse=<...> --site-jetable");
}

if ($o['compte'] === '' || $o['motdepasse'] === '')
{
    nf_refus("il faut un compte administrateur : --compte=<identifiant> --motdepasse=<mot de passe>\n"
        ."  (aucun identifiant n'est codé dans le dépôt, et ce n'est pas un oubli)");
}

// Le site de démonstration est hors sujet : il se remet à zéro tout seul, par un instantané qui lui
// est propre. Une restauration y entrerait en concurrence avec cette remise à zéro.
if (nf_mode_demo())
{
    nf_refus("NEOFRAG_DEMO est actif : le site de démonstration a sa propre remise à zéro, l'épreuve n'y a pas de sens");
}

$racine  = nf_racine();
$db      = nf_connexion();
$serveur = nf_serveur(nf_port($o['port']));
$base    = $serveur->base;
$bocal   = nf_temp('cookies-'.getmypid().'.txt');

@unlink($bocal);
register_shutdown_function(static function () use ($bocal): void { @unlink($bocal); });

/** GET ou POST avec un bocal à cookies partagé : une VRAIE connexion, rejouée avec ses cookies. */
function requete(string $url, string $bocal, ?array $post = NULL, int $delai = 45, bool $ajax = FALSE): string
{
    global $o;

    $commande = sprintf('curl -sS -L --max-time %d -A %s -c %s -b %s', $delai, escapeshellarg(NF_AGENT), escapeshellarg($bocal), escapeshellarg($bocal));

    if ($ajax)
    {
        $commande .= ' -H '.escapeshellarg('X-Requested-With: XMLHttpRequest');
    }

    foreach ((array) $post as $nom => $valeur)
    {
        $commande .= ' --data-urlencode '.escapeshellarg($nom.'='.$valeur);
    }

    $reponse = (string) shell_exec($commande.' '.escapeshellarg($url).' 2>/dev/null');

    if ($o['trace'])
    {
        printf("    -> %s %s (%d octets)\n", $post === NULL ? 'GET ' : 'POST', $url, strlen($reponse));
    }

    return $reponse;
}

$echecs = 0;
$marque = bin2hex(random_bytes(4));

function juger(string $titre, bool $attendu, bool $obtenu, string $detail = ''): void
{
    global $echecs;

    $ok = ($attendu === $obtenu);

    if (!$ok)
    {
        $echecs++;
    }

    printf("  %-5s %-56s %s\n", $ok ? 'OK' : 'ÉCHEC', $titre, $detail);
}

// Les compteurs de limitation de débit rendraient l'épreuve non reproductible.
$db->query('DELETE FROM nf_rate_limit');

printf("Restauration d'une sauvegarde — épreuve réelle (site jetable)\n");
printf("Compte : %s · port %d · témoin %s\n", $o['compte'], $serveur->port, $marque);
printf("(compteurs de limitation de débit remis à zéro : l'épreuve doit être reproductible)\n\n");

// ── 1. Les témoins, plantés AVANT la sauvegarde ────────────────────────────
// Chacun répond à une promesse différente. Un témoin qui ne serait pas dans l'archive ne prouverait
// rien de la restauration : ils sont donc tous posés avant, et vérifiés dans l'archive.
$temoin_fichier = 'upload/epreuve-restauration-'.$marque.'.txt';
$temoin_contenu = 'temoin de restauration '.$marque;

@mkdir($racine.'/upload', 0755, TRUE);
file_put_contents($racine.'/'.$temoin_fichier, $temoin_contenu);

$table = 'nf_epreuve_restauration';
$db->query("DROP TABLE IF EXISTS `$table`");
$db->query("CREATE TABLE `$table` (`id` INT AUTO_INCREMENT PRIMARY KEY, `note` VARCHAR(64) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
$db->query("INSERT INTO `$table` (`note`) VALUES ('avant-$marque')");

$nettoyer = static function () use ($db, $table, $racine, $temoin_fichier, $marque): void {
    $db->query("DROP TABLE IF EXISTS `$table`");
    @unlink($racine.'/'.$temoin_fichier);
    @unlink($racine.'/neofrag/epreuve-vestige-'.$marque.'.php');
    @unlink($racine.'/cache/epreuve-cache-'.$marque.'.txt');
};

// ── 2. Connexion réelle ────────────────────────────────────────────────────
// Le formulaire de connexion n'est PAS garanti sur l'accueil : il vient d'un widget, qu'un thème
// peut ne pas placer. La modale `ajax/user/login`, elle, existe quel que soit le thème : c'est le
// point d'entrée fiable, l'accueil n'est qu'un raccourci.
$entrees = [
    ['url' => $base.'/fr',                 'ajax' => FALSE],
    ['url' => $base.'/fr/ajax/user/login', 'ajax' => TRUE],
];

$form      = NULL;
$connexion = NULL;

foreach ($entrees as $entree)
{
    $form = nf_formulaire(nf_balisage(requete($entree['url'], $bocal, NULL, 45, $entree['ajax'])), 'name="login"');

    if ($form !== NULL)
    {
        $connexion = $entree;
        break;
    }
}

if ($form === NULL || $connexion === NULL)
{
    $nettoyer();
    nf_refus("formulaire de connexion introuvable, ni sur l'accueil ni sur ajax/user/login");
}

$form['login']    = $o['compte'];
$form['password'] = $o['motdepasse'];

requete($connexion['url'], $bocal, $form, 45, $connexion['ajax']);

// La preuve se lit EN BASE, pas dans la page.
$id       = (int) nf_scalar($db, "SELECT id FROM nf_user WHERE username = '".$db->real_escape_string($o['compte'])."' AND deleted = '0' ORDER BY admin DESC, id LIMIT 1");
$connecte = $id > 0 && (int) nf_scalar($db, 'SELECT COUNT(*) FROM nf_session WHERE user_id = '.$id.' AND last_activity > DATE_SUB(NOW(), INTERVAL 2 MINUTE)') > 0;

juger('Connexion administrateur', TRUE, $connecte, $connecte ? sprintf('session ouverte pour le compte #%d', $id) : 'aucune session en base');

if (!$connecte)
{
    $nettoyer();
    nf_refus('sans session, le reste ne mesurerait rien');
}

// ── 3. Fenêtre sudo, si l'installation en exige une ────────────────────────
if (is_file($racine.'/config/webmaster.php'))
{
    if ($o['webmaster'] === '')
    {
        $nettoyer();
        nf_refus('config/webmaster.php existe : la restauration exige une fenêtre sudo — relancer avec --webmaster=<mot de passe webmaster>');
    }

    $reponse = requete($base.'/fr/admin/ajax/monitoring/sudo', $bocal, ['password' => $o['webmaster']]);
    $sudo    = str_contains($reponse, '"ok":true');

    juger('Fenêtre sudo webmaster ouverte', TRUE, $sudo, $sudo ? '' : 'mot de passe webmaster refusé');

    if (!$sudo)
    {
        $nettoyer();
        nf_echec('mot de passe webmaster refusé');
    }
}

// ── 4. La VRAIE sauvegarde, par son endpoint d'administration ──────────────
$avant = glob($racine.'/backups/*.zip') ?: [];

requete($base.'/fr/admin/ajax/monitoring/backup', $bocal, NULL, 600);

$apres   = glob($racine.'/backups/*.zip') ?: [];
$nouveau = array_values(array_diff($apres, $avant));

juger('La sauvegarde produit une archive', TRUE, count($nouveau) === 1,
    count($nouveau) === 1 ? basename($nouveau[0]) : count($nouveau).' archive(s) apparue(s)');

if (count($nouveau) !== 1)
{
    $nettoyer();
    nf_echec("sans archive, il n'y a rien à restaurer — journal du serveur : {$serveur->journal}");
}

$archive = $nouveau[0];
$slug    = basename($archive, '.zip');

// Cette archive est celle de l'épreuve : elle est effacée à la fin, réussite ou échec, pour ne pas
// laisser derrière soi un fichier qui porte toute la base en clair.
register_shutdown_function(static function () use ($archive): void { @unlink($archive); });

// ── 5. L'archive contient-elle vraiment ce qu'elle prétend ? ───────────────
$zip = new ZipArchive();

if ($zip->open($archive) !== TRUE)
{
    $nettoyer();
    nf_echec("l'archive que la sauvegarde vient d'écrire est illisible");
}

$contenu = [];

for ($i = 0; $i < $zip->numFiles; $i++)
{
    $contenu[(string) $zip->getNameIndex($i)] = TRUE;
}

$zip->close();

$compter = static fn (string $prefixe): int => count(array_filter(array_keys($contenu), static fn ($e) => str_starts_with($e, $prefixe)));

/** Fichiers réellement présents sous un dossier, pour confronter l'archive au disque. */
$sur_disque = static fn (string $dossier): int => count(nf_fichiers([$dossier], ['*'], [], FALSE));

juger('L\'archive porte la copie de la base', TRUE, isset($contenu['DATABASE.sql']), 'DATABASE.sql');

// Compter ne suffit pas : c'est l'ÉGALITÉ avec le disque qui compte. Un seuil laisserait passer une
// archive amputée de la moitié du cœur, et le balayage des vestiges prendrait alors les fichiers
// manquants pour des ajouts à supprimer — une restauration qui mutile au lieu de réparer.
$coeur  = $compter('neofrag/');
$disque = $sur_disque('neofrag');

juger('L\'archive porte TOUT le cœur du framework', TRUE, $coeur === $disque && $coeur > 0,
    sprintf('%d fichier(s) archivé(s) pour %d sur le disque', $coeur, $disque));

// `vendor/` ne figure pas dans la liste des dossiers du panneau, et un paquet de mise à jour le
// livre pourtant. Une archive sans vendor/ ne permet PAS d'annuler une mise à jour.
$vendor  = $compter('vendor/');
$vdisque = $sur_disque('vendor');

juger('L\'archive porte les dépendances (vendor/)', TRUE, $vdisque === 0 || ($vendor === $vdisque && $vendor > 0),
    $vdisque === 0 ? 'aucun vendor/ sur cette installation' : sprintf('%d fichier(s) archivé(s) pour %d sur le disque', $vendor, $vdisque));

juger('L\'archive porte le témoin déposé avant', TRUE, isset($contenu[$temoin_fichier]), $temoin_fichier);

// ── 6. On abîme le site, de quatre façons différentes ──────────────────────
// Quatre casses, quatre promesses distinctes de la restauration. Les mesurer séparément est ce qui
// permet de dire LAQUELLE a lâché, au lieu d'un « ça ne marche pas » inexploitable.
@unlink($racine.'/'.$temoin_fichier);                                                       // fichier effacé
file_put_contents($racine.'/neofrag/epreuve-vestige-'.$marque.'.php', '<?php // vestige');  // vestige du cœur
@mkdir($racine.'/cache', 0755, TRUE);
file_put_contents($racine.'/cache/epreuve-cache-'.$marque.'.txt', 'artefact perime');       // cache périmé
$db->query("INSERT INTO `$table` (`note`) VALUES ('apres-$marque')");                       // ligne en trop

// Le journal, lui, ne doit PAS être touché : c'est la trace de l'incident qu'on répare.
@mkdir($racine.'/logs', 0755, TRUE);
file_put_contents($racine.'/logs/php.log', 'epreuve-journal-'.$marque."\n", FILE_APPEND);

printf("\n  (site abîmé : témoin effacé, vestige déposé dans le cœur, cache pollué, ligne ajoutée)\n\n");

// ── 7. La VRAIE restauration, par son lien d'administration ───────────────
$panneau = requete($base.'/fr/admin/monitoring', $bocal);

if (!preg_match('#admin/monitoring/(?:restore|delete)/[^"?]+\?_=([a-f0-9]{32})#', $panneau, $t))
{
    $nettoyer();
    nf_echec('jeton de sécurité introuvable sur le panneau de surveillance (le bouton de restauration est-il bien rendu ?)');
}

$reponse = requete($base.'/fr/admin/monitoring/restore/'.rawurlencode($slug).'?_='.$t[1], $bocal, NULL, 600);

$annoncee = str_contains($reponse, 'Sauvegarde restaur') || str_contains($reponse, 'estaur&eacute;e');
$refusee  = str_contains($reponse, 'restauration a &eacute;chou') || str_contains($reponse, 'restauration a échou');

juger('La restauration se dit réussie', TRUE, $annoncee && !$refusee,
    $refusee ? 'le produit annonce un échec' : ($annoncee ? '' : 'aucune confirmation dans la page'));

// ── 8. Les promesses, vérifiées une par une ───────────────────────────────
$revenu = @file_get_contents($racine.'/'.$temoin_fichier);

juger('Un fichier effacé est remis en place', TRUE, $revenu === $temoin_contenu,
    $revenu === FALSE ? 'toujours absent' : ($revenu === $temoin_contenu ? $temoin_fichier : 'contenu différent'));

juger('Un vestige du cœur est balayé', TRUE, !is_file($racine.'/neofrag/epreuve-vestige-'.$marque.'.php'), 'neofrag/epreuve-vestige-'.$marque.'.php');
juger('Le cache est vidé de ses artefacts', TRUE, !is_file($racine.'/cache/epreuve-cache-'.$marque.'.txt'), 'cache/epreuve-cache-'.$marque.'.txt');

$notes = (string) (nf_scalar($db, "SELECT GROUP_CONCAT(`note` ORDER BY `note`) FROM `$table`") ?? '');

juger('La base retrouve son contenu d\'avant', TRUE, $notes === 'avant-'.$marque, $notes === '' ? 'table vide ou absente' : 'lignes : '.$notes);
juger('La base ne garde pas ce qui a été ajouté depuis', FALSE, str_contains($notes, 'apres-'),
    str_contains($notes, 'apres-') ? 'la ligne postérieure a survécu : le vidage n\'a pas remplacé les tables' : '');

$log = (string) @file_get_contents($racine.'/logs/php.log');

juger('Le journal de l\'incident n\'est pas écrasé', TRUE, str_contains($log, 'epreuve-journal-'.$marque), 'logs/php.log');

// ── 9. Le site répond-il encore ? ─────────────────────────────────────────
// Une restauration qui « réussit » en laissant un site qui ne s'affiche plus n'a rien restauré.
$code = nf_http($base.'/fr', ['suivre' => 3])['code'];

juger('Le site répond encore après restauration', TRUE, $code === 200, 'HTTP '.$code);

// ── 10. On remet les lieux en état ───────────────────────────────────────
$nettoyer();

$reste = @file_get_contents($racine.'/logs/php.log');

if (is_string($reste) && str_contains($reste, 'epreuve-journal-'.$marque))
{
    file_put_contents($racine.'/logs/php.log', str_replace('epreuve-journal-'.$marque."\n", '', $reste));
}

echo "\n";

if ($echecs)
{
    nf_echec(sprintf('%d épreuve(s) en échec — journal du serveur local : %s', $echecs, $serveur->journal));
}

nf_ok("une sauvegarde réelle remet réellement le site d'aplomb");
