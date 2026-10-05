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

	public function _session_delete($session_id)
	{
		$this->error->unconnected();

		if (!$this->db->from('nf_session')->where('user_id', $this->user->id)->where('id', $session_id)->empty())
		{
			$this->ajax();

			return [$session_id];
		}
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

		if (($token = $this->model2('token', $token)) && $token())
		{
			// Un lien de reset / validation n'est valable qu'une heure.
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
	 * partir un nouveau lien.
	 */
	public function validation($token)
	{
		if ($this->user())
		{
			redirect();
		}

		$jeton = $this->model2('token', $token);

		if ($jeton && $jeton() && !($jeton->date && $jeton->date->timestamp() < time() - \NF\Modules\User\User::VALIDATION_DUREE))
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
