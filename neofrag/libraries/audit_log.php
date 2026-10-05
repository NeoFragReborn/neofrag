<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * Audit log — trace les actions sensibles (login, changement mdp, 2FA, suppression compte, modifs admin).
 */

namespace NF\NeoFrag\Libraries;

use NF\NeoFrag\Library;

class Audit_Log extends Library
{
	/**
	 * Enregistre une action.
	 *
	 * @param string $action Identifiant de l'action (ex: "login.success", "user.password_changed", "totp.enabled")
	 * @param array $opts Options : user_id, username, target_type, target_id, details (string|array), success (bool, défaut TRUE)
	 */
	public function log($action, array $opts = []): void
	{
		$user = NeoFrag()->user();

		$details = $opts['details'] ?? NULL;
		if (is_array($details))
		{
			$details = json_encode($details, JSON_UNESCAPED_UNICODE);
		}

		$user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 500) : NULL;

		NeoFrag()->db->insert('nf_audit_log', [
			'user_id'     => $opts['user_id']     ?? ($user ? $user->id : NULL),
			'username'    => $opts['username']    ?? ($user ? $user->username : NULL),
			'action'      => $action,
			'target_type' => $opts['target_type'] ?? NULL,
			'target_id'   => isset($opts['target_id']) ? (string)$opts['target_id'] : NULL,
			'details'     => $details,
			'ip_address'  => Rate_Limit::client_ip(),
			'user_agent'  => $user_agent,
			'success'     => isset($opts['success']) ? (int)(bool)$opts['success'] : 1
		]);
	}

	/**
	 * Cleanup logs anciens (à appeler depuis un cron).
	 */
	public function gc($keep_days = 365): void
	{
		NeoFrag()->db->execute('DELETE FROM nf_audit_log WHERE created_at < DATE_SUB(NOW(), INTERVAL '.(int)$keep_days.' DAY)');
	}

	/**
	 * Ce qu'une action veut dire, pour un administrateur : « Mode débogage allumé » plutôt que
	 * `monitoring.debogage.allume`. Le tableau de bord montrait les identifiants bruts (relevé le
	 * 2026-10-02). Une action qu'on ne connaît pas — celle d'une extension — garde son identifiant.
	 */
	public function libelle(string $action): string
	{
		$libelles = [
			'addon.install'                          => NeoFrag()->lang('Extension installée'),
			'addon.update'                           => NeoFrag()->lang('Extension mise à jour'),
			'addon.uninstalled'                      => NeoFrag()->lang('Extension désinstallée'),
			'api.token.created'                      => NeoFrag()->lang('Clé d’API créée'),
			'api.token.revoked'                      => NeoFrag()->lang('Clé d’API révoquée'),
			'banlist.ip_added'                       => NeoFrag()->lang('Adresse IP bannie'),
			'banlist.ip_removed'                     => NeoFrag()->lang('Bannissement d’une adresse IP levé'),
			'core.updated'                           => NeoFrag()->lang('NeoFrag mis à jour'),
			'discord.commande'                       => NeoFrag()->lang('Commande Discord'),
			'discord.connexion'                      => NeoFrag()->lang('Connexion du bot Discord'),
			'discord.fonctionnalite'                 => NeoFrag()->lang('Fonctionnalité Discord réglée'),
			'discord.mise_en_place'                  => NeoFrag()->lang('Serveur Discord mis en place'),
			'login.success'                          => NeoFrag()->lang('Connexion réussie'),
			'login.failed'                           => NeoFrag()->lang('Échec de connexion'),
			'login.banned_blocked'                   => NeoFrag()->lang('Connexion refusée : compte banni'),
			'login.unvalidated'                      => NeoFrag()->lang('Connexion refusée : inscription pas encore validée'),
			'login.password_ok_totp_pending'         => NeoFrag()->lang('Mot de passe accepté, code de double authentification attendu'),
			'login.totp_success'                     => NeoFrag()->lang('Connexion avec double authentification'),
			'login.totp_failed'                      => NeoFrag()->lang('Code de double authentification refusé'),
			'moderation.mediation.opened'            => NeoFrag()->lang('Médiation ouverte'),
			'moderation.private_access'              => NeoFrag()->lang('Contenu privé consulté par la modération'),
			'moderation.sanction.approved'           => NeoFrag()->lang('Sanction approuvée'),
			'moderation.sanction.blocked_hierarchy'  => NeoFrag()->lang('Sanction refusée : hiérarchie'),
			'moderation.sanction.created'            => NeoFrag()->lang('Sanction prononcée'),
			'moderation.sanction.revoked'            => NeoFrag()->lang('Sanction levée'),
			'moderation.snapshot_download'           => NeoFrag()->lang('Preuve téléchargée'),
			'moderation.snapshot_download_private'   => NeoFrag()->lang('Preuve privée téléchargée'),
			'module.enabled'                         => NeoFrag()->lang('Module activé'),
			'module.disabled'                        => NeoFrag()->lang('Module désactivé'),
			'monitoring.adresse'                     => NeoFrag()->lang('Adresse du site enregistrée'),
			'monitoring.journal.vide'                => NeoFrag()->lang('Journal des erreurs vidé'),
			'monitoring.trace.vide'                  => NeoFrag()->lang('Trace des pages vidée'),
			'monitoring.traductions.vide'            => NeoFrag()->lang('Liste des traductions manquantes vidée'),
			'monitoring.debogage.allume'             => NeoFrag()->lang('Mode débogage allumé'),
			'monitoring.debogage.eteint'             => NeoFrag()->lang('Mode débogage éteint'),
			'monitoring.trace.allume'                => NeoFrag()->lang('Trace des pages allumée'),
			'monitoring.trace.eteint'                => NeoFrag()->lang('Trace des pages éteinte'),
			'monitoring.traductions.allume'          => NeoFrag()->lang('Relevé des traductions allumé'),
			'monitoring.traductions.eteint'          => NeoFrag()->lang('Relevé des traductions éteint'),
			'settings.saved'                         => NeoFrag()->lang('Paramètres enregistrés'),
			'theme.enabled'                          => NeoFrag()->lang('Thème activé'),
			'theme.deleted'                          => NeoFrag()->lang('Thème supprimé'),
			'totp.enabled'                           => NeoFrag()->lang('Double authentification activée'),
			'totp.disabled'                          => NeoFrag()->lang('Double authentification désactivée'),
			'totp.admin_reset'                       => NeoFrag()->lang('Double authentification réinitialisée'),
			'user.account_deleted'                   => NeoFrag()->lang('Compte supprimé par son titulaire'),
			'user.admin_deleted'                     => NeoFrag()->lang('Membre supprimé'),
			'user.admin_edited'                      => NeoFrag()->lang('Membre modifié'),
			'user.auth.linked'                       => NeoFrag()->lang('Compte externe lié'),
			'user.auth.unlinked'                     => NeoFrag()->lang('Compte externe délié'),
			'user.registered.external'               => NeoFrag()->lang('Inscription par un compte externe'),
			'user.validated'                         => NeoFrag()->lang('Adresse e-mail validée'),
			'webmaster.deleted'                      => NeoFrag()->lang('Fichier supprimé dans le gestionnaire'),
			'webmaster.dir_created'                  => NeoFrag()->lang('Dossier créé dans le gestionnaire'),
			'webmaster.file_saved'                   => NeoFrag()->lang('Fichier modifié dans le gestionnaire'),
			'webmaster.password_set'                 => NeoFrag()->lang('Mot de passe webmaster défini'),
			'webmaster.renamed'                      => NeoFrag()->lang('Fichier renommé dans le gestionnaire'),
			'webmaster.sudo_failed'                  => NeoFrag()->lang('Mot de passe webmaster refusé'),
			'webmaster.sudo_granted'                 => NeoFrag()->lang('Accès webmaster déverrouillé'),
		];

		if (isset($libelles[$action]))
		{
			return (string) $libelles[$action];
		}

		// Les permissions s'écrivent « permissions.<action> » : un seul libellé pour toutes.
		return str_starts_with($action, 'permissions.') ? (string) NeoFrag()->lang('Permissions modifiées') : $action;
	}
}
