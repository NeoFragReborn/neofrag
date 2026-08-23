<?php
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

	public function account($page = '')
	{
		$this->error->unconnected();

		return [$this->user->sessions()->order_by('_.last_activity DESC')->paginate($page)];
	}

	public function profile()
	{
		$this->error->unconnected();

		return [];
	}

	public function sessions($page = '')
	{
		$this->error->unconnected();

		return [NeoFrag()->collection('session_history')->where('_.user_id', $this->user->id)->order_by('_.date DESC')->paginate($page)];
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

	public function _member($id, $username)
	{
		if (($user = $this->model2('user', $id)->check($username)) && !$user->deleted)
		{
			return [$user];
		}
	}
}
