<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\User\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module_Checker;

class Checker extends Module_Checker
{
	public function index()
	{
		$this->error->unconnected();

		return [];
	}

	public function account()
	{
		$this->error->unconnected();

		// La liste des sessions qu'on calculait ici n'était affichée nulle part (son tableau dormait en
		// commentaire) : les sessions ouvertes viendront avec l'étape A3 du chantier de l'espace membre.
		return [];
	}

	public function profile()
	{
		$this->error->unconnected();

		return [];
	}

	public function privacy()
	{
		$this->error->unconnected();

		return [];
	}

	public function sessions($page = '')
	{
		$this->error->unconnected();

		return [NeoFrag()->collection('session_history')->where('_.user_id', $this->user->id)->order_by('_.date DESC')->paginate($page)];
	}

	/** Délier un compte externe : seulement l'un des siens. */
	public function _auth_unlink($lien_id)
	{
		$this->error->unconnected();

		$lien = $this->db	->select('a.id', 'ad.name')
							->from('nf_user_auth a')
							->join('nf_addon ad', 'ad.id = a.authenticator_id', 'INNER')
							->where('a.id', (int) $lien_id)
							->where('a.user_id', (int) $this->user->id)
							->row();

		return is_array($lien) && $lien ? [$lien] : NULL;
	}

	/**
	 * Fermer l'une de ses sessions ouvertes (chantier A, étape A3) : la sienne seulement, et pas celle-ci — la
	 * déconnexion est là pour elle.
	 */
	public function _session_fermer($session_id)
	{
		$this->error->unconnected();

		if ((string) $session_id !== (string) $this->session->id
			&& !$this->db->from('nf_session')->where('user_id', $this->user->id)->where('id', $session_id)->empty())
		{
			return [(string) $session_id];
		}
	}

	public function _sessions_fermer_autres()
	{
		$this->error->unconnected();

		return [];
	}

	/**
	 * Mes notifications (chantier A, étape A4) : vingt par page parmi les cinq cents dernières, la plus récente
	 * d'abord. Le module est du cœur ; sans lui, pas de page. Le contrôleur le reçoit, avec la page à montrer.
	 */
	public function notifications($page = '')
	{
		$this->error->unconnected();

		$notifications = $this->module('notifications');

		if (!$notifications instanceof \NF\Modules\Notifications\Notifications)
		{
			return NULL;
		}

		return [$notifications, $this->module->pagination->fix_items_per_page(20)->get_data($notifications->recent(500), $page)];
	}

	public function notifications_preferences()
	{
		$this->error->unconnected();

		$notifications = $this->module('notifications');

		return $notifications instanceof \NF\Modules\Notifications\Notifications ? [$notifications] : NULL;
	}

	public function security()
	{
		$this->error->unconnected();
		return [];
	}

	public function security_setup()
	{
		$this->error->unconnected();
		return [];
	}

	public function security_codes()
	{
		$this->error->unconnected();
		return [];
	}

	public function security_disable()
	{
		$this->error->unconnected();
		return [];
	}

	public function security_export()
	{
		$this->error->unconnected();
		return [];
	}

	public function security_delete()
	{
		$this->error->unconnected();
		return [];
	}

	public function lost_password($token)
	{
		$this->error_if($this->user());

		// Un lien « mot de passe oublié », et lui seul : un lien de validation ne choisit pas de mot de passe.
		if (($token = $this->model2('token', $token)) instanceof \NF\Modules\User\Models\Token && $token() && $token->type === 'mot_de_passe')
		{
			// Un lien de reset n'est valable qu'une heure.
			if ($token->date && $token->date->timestamp() < time() - 3600)
			{
				$token->delete();
				return;
			}

			return [$token];
		}
	}

	public function auth($provider)
	{
		if (($authenticator = $this->authenticator(str_replace('-', '_', $provider))) && $authenticator->is_setup())
		{
			return [$authenticator];
		}
	}

	/**
	 * `user/validation/{jeton}` : le lien de l'e-mail de validation d'une inscription (Index::validation),
	 * valable deux jours. Un lien inconnu, déjà servi ou expiré le dit, et renvoie à la connexion — qui fait
	 * partir un nouveau lien. Il ne vaut que pour un compte qui attend encore sa validation, et jamais un lien
	 * « mot de passe oublié » : celui-ci connectait sans rien demander pendant deux jours (audit du 2026-10-09).
	 */
	public function validation($token)
	{
		if ($this->user())
		{
			redirect();
		}

		$jeton = $this->model2('token', $token);

		$module = $this->module('user');

		if ($jeton instanceof \NF\Modules\User\Models\Token && $jeton() && $jeton->type === 'validation' && $module instanceof \NF\Modules\User\User && $module->a_valider($jeton->user)
			&& !($jeton->date && $jeton->date->timestamp() < time() - \NF\Modules\User\User::VALIDATION_DUREE))
		{
			return [$jeton];
		}

		if ($jeton && $jeton())
		{
			$jeton->delete();
		}

		notify($this->lang('Ce lien de validation n\'est plus valable : connectez-vous, un nouveau lien vous sera envoyé.'), 'warning');
		redirect();
	}

	/**
	 * `user/adresse/{jeton}` : le lien qui confirme une nouvelle adresse e-mail (Index::_adresse), valable deux jours
	 * (User::ADRESSE_DUREE). Il vaut connecté ou non : on l'ouvre souvent depuis un autre appareil que celui de la
	 * demande. Un lien inconnu ou expiré le dit, et renvoie au compte.
	 */
	public function _adresse($jeton)
	{
		$ligne = $this->db->select('user_id', 'email', 'created_at')->from('nf_user_email_change')->where('token', (string) $jeton)->row(FALSE);

		if ($ligne && strtotime((string) $ligne['created_at']) >= time() - \NF\Modules\User\User::ADRESSE_DUREE)
		{
			return [$ligne];
		}

		if ($ligne)
		{
			$this->db->where('token', (string) $jeton)->delete('nf_user_email_change');
		}

		notify($this->lang('Ce lien de confirmation n\'est plus valable : demande de nouveau le changement d\'adresse depuis ton compte.'), 'warning');
		redirect($this->user() ? 'user/account' : '');
	}

	public function _adresse_renvoyer()
	{
		$this->error->unconnected();

		return [];
	}

	public function _adresse_annuler()
	{
		$this->error->unconnected();

		return [];
	}

	/**
	 * `user/reglement` : une inscription par un compte externe, en attente de l'acceptation du règlement
	 * (Index::reglement). Rien en attente, une attente expirée, un visiteur déjà connecté, ou un connecteur
	 * qui n'est plus réglé : retour à l'accueil.
	 */
	public function reglement()
	{
		$nom           = $this->session('inscription_externe', 'authenticator');
		$donnees       = $this->session('inscription_externe', 'data');
		$expire        = (int) $this->session('inscription_externe', 'expire');
		$authenticator = is_string($nom) ? $this->authenticator($nom) : NULL;

		if ($this->user() || !is_array($donnees) || $expire < time() || !$authenticator || !$authenticator->is_setup())
		{
			$this->session->destroy('inscription_externe');
			redirect();
		}

		return [$authenticator, $donnees];
	}

	public function _auth($page = '')
	{
		$this->error->unconnected();

		return [$this->collection('auth')->where('_.user_id', $this->user->id)->order_by('_.id')->paginate($page)];
	}

	// Pendants non-AJAX de Ajax_Checker::login() / ::register(). On redirige au lieu d'`error_if`
	// (comme logout ci-dessous) : ces URLs sont atteintes depuis un lien d'en-tête, une page
	// d'erreur pour un visiteur déjà connecté ou une inscription fermée serait un faux négatif.
	public function login()
	{
		if ($this->user())
		{
			redirect();
		}

		return [];
	}

	public function registration()
	{
		if ($this->user() || !$this->config->nf_registration_status)
		{
			redirect();
		}

		return [];
	}

	public function logout()
	{
		if (!$this->user->id)
		{
			redirect('user');
		}

		return [];
	}

	public function _member($id, $username, $onglet = '')
	{
		if (($user = $this->model2('user', $id)->check($username)) && !$user->deleted && (int) $user->id !== nf_compte_masque()
			// Un onglet que ce profil n'a pas (module absent, rien à montrer) : page introuvable.
			&& in_array((string) $onglet, array_column($this->module->onglets_profil($user), 'onglet'), TRUE))
		{
			return [$user, (string) $onglet];
		}
	}
}
