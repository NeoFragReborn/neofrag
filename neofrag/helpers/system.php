<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

function is_windows(): bool
{
	return strtoupper(substr(PHP_OS, 0, 3)) == 'WIN';
}

// Site de démonstration : NEOFRAG_DEMO=TRUE (config/neofrag.php).
function nf_demo(): bool
{
	return defined('NEOFRAG_DEMO') && NEOFRAG_DEMO;
}

/**
 * Le compte que le site ne montre nulle part : sur une DÉMONSTRATION, son compte de secours.
 *
 * C'est le plus ancien administrateur, celui que l'installeur a créé. La remise à zéro des quinze
 * minutes ne le touche jamais (cf. tools/dump-demo.php), les outils du dépôt s'en servent pour
 * ouvrir leurs sessions de mesure, et personne ne s'y connecte. Il apparaissait pourtant dans
 * l'annuaire, la recherche, les membres en ligne et sur sa propre page, sous le nom « ci-admin »
 * (signalé le 2026-09-23). Hors démonstration : 0, aucun compte n'est masqué.
 *
 * S'emploie comme `->where('u.id !=', nf_compte_masque())` : l'identifiant 0 n'existe pas, et la
 * condition est alors toujours vraie. La requête est isolée (`standalone`) parce que la fonction
 * est appelée AU MILIEU de la construction d'une autre.
 */
function nf_compte_masque(): int
{
	static $id = NULL;

	if ($id === NULL)
	{
		$id = nf_demo() ? (int) NeoFrag()->db->standalone(static function($db){
			return $db->select('MIN(id)')->from('nf_user')->where('admin', '1')->row();
		}) : 0;
	}

	return $id;
}

/**
 * Modules d'administration dont les ÉCRITURES restent refusées sur un site de démonstration.
 *
 * Pourquoi une liste, et pourquoi celle-ci
 * ----------------------------------------
 * Le verrou d'origine refusait TOUTE écriture dans l'administration. Un visiteur voyait les écrans
 * et ne pouvait rien essayer — ce qui vide une démo de son intérêt. L'idée est donc de laisser
 * modifier ce que la remise à zéro horaire sait défaire, et de refuser le reste.
 *
 * Le critère : « est-ce que `install/demo.sql` le rétablit, et rien d'autre ne peut-il en sortir ».
 * La remise à zéro restaure le contenu, les mises en page, les menus, les membres et les réglages
 * ordinaires. Elle ne restaure NI les réglages SENSIBLES (serveur d'envoi, clés, clé du cron :
 * tools/dump-demo.php les exclut), NI les addons installés, NI les rôles et leurs attributions : ce
 * qui est touché là l'est définitivement. D'où cette liste. (Les PERMISSIONS des rôles, elles, sont
 * restaurées depuis le 2026-10-02 : créer une galerie ou une page en écrit.) Les réglages du site y sont aussi : la démo est
 * partagée, et un visiteur qui la mettait en maintenance la fermait à tous (2026-10-02).
 *
 * Deux entrées ne relèvent pas de la base du tout et méritent leur mot :
 *   - `addons` et `marketplace` installent des addons, donc ÉCRIVENT DES FICHIERS sur le serveur.
 *     Aucune remise à zéro de base de données ne rattrape ça ;
 *   - `emails` et `newsletter` envoient du courrier. Une démo ouverte ne doit pas pouvoir servir
 *     de relais.
 */
const NF_DEMO_MODULES_VERROUILLES = [
	'access',      // rôles et leurs attributions (nf_roles, nf_users_roles) — non restaurés par l'instantané
	'addons',      // installe/désinstalle : écrit des fichiers
	'api',         // des clés d'accès au site pour des programmes extérieurs
	'discord',     // la clé d'un bot, et des actions sur un vrai serveur Discord (mise en place, rôles)
	'emails',      // envoi de courrier
	'donations',   // l'adresse PayPal des campagnes : un visiteur détournait les dons des autres
	'files',       // le gestionnaire de fichiers montre et touche les fichiers du serveur
	'gamification', // le barème : des réglages créés à la volée, que la remise à zéro ne retire pas
	'live_editor', // les mises en page : un widget HTML écrit par un visiteur exécuterait son script chez tous les autres
	'marketplace', // télécharge et extrait des archives : écrit des fichiers
	'media',       // la suppression efface aussi le FICHIER ; l'instantané ne restaure que la base
	'moderation',  // liste noire d'IP et sanctions, non restaurées : un visiteur bloquait même la remise à zéro
	'monitoring',  // sauvegardes, purges, mises à jour du cœur
	'newsletter',  // envoi de courrier en masse
	'payments',    // clés de passerelle de paiement
	'settings',    // réglages du site : serveur d'envoi et clés (non restaurés), maintenance (pour tous)
	'shop',        // adossé à payments
	'tools',       // outils d'exploitation
	'user',        // comptes et mots de passe, dont celui du compte de SECOURS, jamais restauré
	'webhooks',    // appelle des services externes
];

/**
 * Les requêtes qui ne font que LIRE, dans un module verrouillé, et que le filet de la démonstration
 * laisse passer (`controleur::methode`). Le reste — tout envoi, toute action par lien porteuse d'un
 * jeton — est refusé avant le contrôleur (cf. nf_demo_requete_refusee()).
 */
const NF_DEMO_LECTURES = [
	'live_editor' => [
		'admin_ajax::widget_admin',     // le formulaire d'un widget, pour le montrer
		'admin_ajax::widget_settings',  // idem, à partir de sa place dans la disposition
	],
	'monitoring' => [
		'admin_ajax::index',    // l'état du site (monitoring.json) : lecture, mise en cache du manifeste
		'admin_ajax::fs_list',  // le gestionnaire de fichiers : une arborescence fictive en démo
		'admin_ajax::fs_read',  // idem, un contenu fictif
	],
];

/**
 * Le filet de la démonstration : cette requête d'administration agit-elle sur un module verrouillé ?
 *
 * Une requête « agit » quand c'est un envoi (POST, AJAX compris) ou un lien porteur d'un jeton
 * (`?_=`, la convention des suppressions, purges et bascules). Les gardes de chaque écran restent ;
 * ce filet, posé dans Output avant le checker et le contrôleur, rattrape celles qui manqueraient :
 * sur la démo, aucun accès ne doit passer (demandé le 2026-10-02).
 */
function nf_demo_requete_refusee(string $module, string $controleur, string $methode): bool
{
	if (!nf_demo() || nf_demo_ecriture_permise($module))
	{
		return FALSE;
	}

	if (in_array($controleur.'::'.$methode, NF_DEMO_LECTURES[$module] ?? [], TRUE))
	{
		return FALSE;
	}

	if (strtolower((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'post' || isset($_GET['_']))
	{
		return TRUE;
	}

	// Un lien SANS jeton n'est pas pour autant une lecture : la suppression d'un rôle, la création de
	// la clé du bot, l'export des membres s'ouvraient par un simple lien (audit du 2026-10-02). Le nom
	// de la méthode le dit : un de ces mots, et c'est une action.
	return (bool) array_intersect(explode('_', strtolower(trim($methode, '_'))), NF_DEMO_MOTS_D_ACTION);
}

/** Les mots qui, dans le nom d'une méthode d'administration, désignent une action et non une page. */
const NF_DEMO_MOTS_D_ACTION = [
	'delete', 'remove', 'supprimer', 'purge', 'vider', 'reset', 'clear', 'export', 'download',
	'telecharger', 'preview', 'cle', 'key', 'token', 'restore', 'restaurer', 'backup', 'sort', 'toggle',
	'enable', 'disable', 'activer', 'desactiver', 'install', 'uninstall', 'assign', 'unassign', 'clone',
	'revoke', 'revoquer', 'sync', 'send', 'envoyer', 'test', 'regenerer', 'diagnostic', 'adresse',
	'webmaster', 'sudo', 'totp', 'unlink', 'appliquer', 'annuler', 'commande', 'approve', 'reject',
];

/** Nom du module servant la requête courante, ou NULL s'il n'est pas encore résolu. */
function nf_module_courant(): ?string
{
	$module = NeoFrag()->output->module();

	return $module ? (string) $module->info()->name : NULL;
}

/**
 * L'écriture demandée est-elle permise sur un site de démonstration ?
 *
 * Rend TRUE hors mode démo : la fonction ne décide que du cas démo. Sans argument, elle interroge
 * le module de la requête courante.
 */
function nf_demo_ecriture_permise(?string $module = NULL): bool
{
	if (!nf_demo())
	{
		return TRUE;
	}

	$module ??= nf_module_courant();

	// C'est une liste de REFUS, pas une liste d'autorisations : un module absent de la liste est
	// permis. Le choix est assumé — l'inverse obligerait à inscrire chaque module de contenu, et un
	// oubli rendrait une partie de la démo muette sans rien dire. En contrepartie, **tout nouveau
	// module qui toucherait à la configuration doit être ajouté ici**, sans quoi il sera ouvert en
	// démo. Le contrôle `tools/check-demo-lock.php` le rappelle.
	return $module !== NULL && $module !== '' && !in_array($module, NF_DEMO_MODULES_VERROUILLES, TRUE);
}

/**
 * Les migrations du cœur arrivées avec un nouveau code — par le bouton de mise à jour comme par FTP.
 * Jusqu'au 2026-10-01, seule l'INSTALLATION les appliquait : un site mis à jour gardait sa base
 * ancienne sous un code neuf. Nos trois sites ne l'ont jamais vu, leurs déploiements lançant
 * `tools/migrate.php up` à la main.
 *
 * Elle et `Installer` sont livrés ensemble dans `neofrag/`, réécrit à chaque mise à jour : dans une
 * même requête, ils sont toujours de la même version.
 *
 * Retourne les migrations appliquées ; NULL si une autre requête les applique en ce moment (rien
 * n'est alors conclu). Un site sans table de suivi — antérieur au runner — n'est pas touché :
 * rejouer tout l'historique sur une base qui l'a déjà reçu casserait plus qu'il ne répare.
 *
 * @return string[]|null
 */
function nf_migrations_du_code(string $root): ?array
{
	require_once $root.'/neofrag/installer.php';

	$installer = \NF\NeoFrag\Installer::class;

	if (($cfg = $installer::read_db_config($root.'/config')) === NULL || !is_dir($root.'/migrations'))
	{
		return [];
	}

	$db = $installer::connect($cfg);

	try
	{
		if (!$installer::table_exists($db, 'nf_migrations'))
		{
			return [];
		}

		// Deux visiteurs arrivent en même temps sur un site qui vient de changer de code : un seul
		// applique, l'autre passe son tour sans attendre.
		$verrou = $db->query("SELECT GET_LOCK('nf_migrations', 0)");

		if (!$verrou instanceof \mysqli_result || (int) ($verrou->fetch_row()[0] ?? 0) !== 1)
		{
			return NULL;
		}

		try
		{
			// Le cœur d'abord, puis chaque addon installé : un module livré avec le cœur reçoit son code
			// neuf par la même mise à jour, et ses migrations doivent suivre (2026-10-01).
			return array_merge($installer::run_migrations($db, $root.'/migrations')['applied'], $installer::run_addon_migrations($db, $root));
		}
		finally
		{
			$db->query("SELECT RELEASE_LOCK('nf_migrations')");
		}
	}
	finally
	{
		$db->close();
	}
}

