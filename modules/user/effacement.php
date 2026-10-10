<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 */

namespace NF\Modules\User;

/**
 * L'effacement d'un compte, le même qu'il soit demandé par le membre (Mon compte → Sécurité) ou fait par un
 * administrateur (2026-10-08). Jusque-là, l'administrateur ne faisait que fermer le compte : l'adresse
 * changeait, mais le pseudo, le profil (nom, naissance, lieu, signature, liens), les comptes liés,
 * l'historique des connexions et les notifications restaient en base. Or le droit à l'effacement (RGPD,
 * art. 17) ne dépend pas de qui appuie sur le bouton.
 *
 * Ce qui reste : ce que le membre a publié (sous un pseudo neutre, `supprime-<id>`), les journaux de sécurité
 * et les traces du consentement, qui se gardent pour prouver, le temps de leur durée de conservation
 * (Session::_menage_du_jour()).
 */
trait Effacement
{
	/**
	 * @param string|null $garder la session à déconnecter sans l'effacer — celle du membre qui efface son propre
	 *                            compte : effacée, elle emportait le message « Ton compte a été supprimé », qui ne
	 *                            s'affichait jamais. NULL : toutes les sessions du compte sont fermées.
	 */
	protected function _effacer_donnees(int $user_id, ?string $garder = NULL): void
	{
		// Les comptes externes liés : leur clé identifie la personne chez le service. Le bot Discord du site
		// apprend la déliaison et retire les rôles du membre.
		foreach ((array) $this->db->select('a.key', 'ad.name')->from('nf_user_auth a')->join('nf_addon ad', 'ad.id = a.authenticator_id', 'INNER')->where('a.user_id', $user_id)->get() as $lien)
		{
			$this->_compte_externe_change('unlinked', (string) $lien['name'], $user_id, (string) $lien['key']);
		}

		$profil = $this->db->select('avatar', 'cover')->from('nf_user_profile')->where('id', $user_id)->row(FALSE);

		// Vides, ou NULL pour les colonnes qui l'admettent (la signature et les textes ne l'admettent pas).
		$this->db	->where('id', $user_id)
					->update('nf_user_profile', array_fill_keys(['first_name', 'last_name', 'signature', 'country', 'timezone', 'location', 'quote', 'website', 'linkedin', 'github', 'instagram', 'twitch'], '') + array_fill_keys(['avatar', 'cover', 'date_of_birth', 'sex'], NULL)
						// Un compte effacé ne montre plus rien de lui.
						+ array_fill_keys(['montrer_points', 'montrer_karma', 'montrer_vip', 'montrer_age', 'montrer_statut'], 0));

		foreach (is_array($profil) ? array_filter([(int) ($profil['avatar'] ?? 0), (int) ($profil['cover'] ?? 0)]) : [] as $fichier)
		{
			$this->model2('file', $fichier)->delete();
		}

		foreach (['nf_user_auth', 'nf_session_history', 'nf_user_totp_recovery', 'nf_user_token', 'nf_user_email_change', 'nf_user_inactivite', 'nf_user_fields_values', 'nf_notifications', 'nf_notifications_preferences', 'nf_users_roles', 'nf_users_groups', 'nf_newsletter_subscribers'] as $table) // couplage: newsletter — table_exists() plus bas : sans le module, rien à effacer
		{
			if ($this->db->table_exists($table))
			{
				$this->db->where('user_id', $user_id)->delete($table);
			}
		}

		if ($garder === NULL)
		{
			$this->db->where('user_id', $user_id)->delete('nf_session');
		}
		else
		{
			$this->db->where('user_id', $user_id)->where('id <>', $garder)->delete('nf_session');
		}

		$this->db	->where('id', $user_id)
					->update('nf_user', [
						'username'     => 'supprime-'.$user_id,
						'email'        => 'deleted-'.$user_id.'@deleted.local',
						'password'     => '',
						'salt'         => '',
						'data'         => '',
						'totp_secret'  => NULL,
						'totp_enabled' => 0,
						'deleted'      => '1',
					]);
	}

	/**
	 * Un compte Discord lié ou délié : le bot Discord du site l'apprend par le fil de l'API, et donne ou retire
	 * aussitôt les rôles du membre. Les autres comptes externes (GitHub, Google) ne concernent aucun bot.
	 *
	 * couplage(api): facultatif — sans le module api, `Module::__load` rend NULL et rien n'est inscrit.
	 */
	protected function _compte_externe_change(string $quoi, string $authentificateur, int $user_id, string $cle): void
	{
		if ($authentificateur !== 'discord' || $cle === '')
		{
			return;
		}

		$evenement = 'user.discord.'.$quoi;
		$charge    = ['user_id' => $user_id, 'discord_id' => $cle];

		$this->events->fire($evenement, $charge);

		if (($api = \NF\NeoFrag\Addons\Module::__load(\NeoFrag(), ['api'])) instanceof \NF\Modules\Api\Api)
		{
			$api->consigner($evenement, $charge);
		}
	}
}
