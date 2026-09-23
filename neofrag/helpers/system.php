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
 * Le critère n'est pas « est-ce dangereux » mais « est-ce que `install/demo.sql` le rétablit ». La
 * remise à zéro restaure le contenu, les mises en page, les menus et les membres. Elle ne restaure
 * NI les réglages, NI les addons installés, NI les rôles et permissions : ce qui est touché là
 * l'est définitivement. D'où cette liste.
 *
 * Deux entrées ne relèvent pas de la base du tout et méritent leur mot :
 *   - `addons` et `marketplace` installent des addons, donc ÉCRIVENT DES FICHIERS sur le serveur.
 *     Aucune remise à zéro de base de données ne rattrape ça ;
 *   - `emails` et `newsletter` envoient du courrier. Une démo ouverte ne doit pas pouvoir servir
 *     de relais.
 */
const NF_DEMO_MODULES_VERROUILLES = [
	'access',      // rôles et permissions — non restaurés par l'instantané
	'addons',      // installe/désinstalle : écrit des fichiers
	'emails',      // envoi de courrier
	'files',       // écrit des permissions de rôles (droits d'accès aux dossiers) — non restaurées
	'marketplace', // télécharge et extrait des archives : écrit des fichiers
	'media',       // la suppression efface aussi le FICHIER ; l'instantané ne restaure que la base
	'monitoring',  // sauvegardes, purges, mises à jour du cœur
	'newsletter',  // envoi de courrier en masse
	'payments',    // clés de passerelle de paiement
	'shop',        // adossé à payments
	'tools',       // outils d'exploitation
	'user',        // comptes et mots de passe, dont celui du compte de SECOURS, jamais restauré
	'webhooks',    // appelle des services externes
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

