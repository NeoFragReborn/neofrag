<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\NeoFrag\Models;

use NF\NeoFrag\Loadables\Model2;

class User extends Model2
{
	static public $icon = 'fas fa-user';

	static public function __schema()
	{
		return [
			'id'                 => self::field()->primary(),
			'username'           => self::field()->text(100),
			'password'           => self::field()->text(255),
			'salt'               => self::field()->text(32),
			'totp_secret'        => self::field()->text(255)->null(),
			'totp_enabled'       => self::field()->bool(),
			'email'              => self::field()->text(100),
			'registration_date'  => self::field()->datetime(),
			'last_activity_date' => self::field()->datetime()->null(),
			'admin'              => self::field()->bool(),
			'language'           => self::field()->depends('addon', '')->null(),
			'data'               => self::field()->serialized(),
			'deleted'            => self::field()->bool()
		];
	}

	static public function __title($model)
	{
		return $model->username;
	}

	public $__table = 'user';

	public function profile()
	{
		$profile = $this->model2('profile', $this->id);

		if (!$profile())
		{
			$profile->set('id', $this->id);
		}

		return $profile;
	}

	public function is_online()
	{
		if (!property_exists($this, 'online'))
		{
			if ($this->user->id == $this->id)
			{
				$this->online = TRUE;
			}
			else if ($this->id)
			{
				$this->online = $this->db	->select('MAX(last_activity) > DATE_SUB(NOW(), INTERVAL 5 MINUTE)')
											->from('nf_session')
											->where('user_id', $this->id)
											->row();
			}
			else
			{
				$this->online = FALSE;
			}
		}

		return $this->online;
	}

	public function groups()
	{
		return $this->groups->user_groups($this->id);
	}

	public function link($user_id = 0, $username = '', $prefix = '')
	{
		if (!$user_id)
		{
			$user_id  = $this->id;
			$username = $this->username;
		}

		if (!$username)
		{
			$username = $this->db->select('username')->from('nf_user')->where('id', $user_id)->row();
		}

		if (!$user_id || !$username)
		{
			return '';
		}

		$this->js('popover');

		return '<a data-popover-ajax="'.url('ajax/user/'.$user_id.'/'.url_title($username)).'" href="'.url('///user/'.$user_id.'/'.url_title($username)).'">'.$prefix.$username.'</a>';
	}

	/**
	 * Ce que le membre montre aux autres, s'il n'a rien choisi (chantier A, étape A2, 2026-10-05) : ses points,
	 * son karma et ses jours de VIP lui sont réservés ; son âge et sa présence en ligne (`statut`, avec sa
	 * dernière visite) sont montrés, comme avant. Il en décide dans « Confidentialité et données ».
	 */
	public const MONTRE_PAR_DEFAUT = ['points' => FALSE, 'karma' => FALSE, 'vip' => FALSE, 'age' => TRUE, 'statut' => TRUE];

	/** Le membre montre-t-il `$quoi` (une clé de MONTRE_PAR_DEFAUT) aux autres ? */
	public function montre_aux_autres(string $quoi): bool
	{
		$profil = $this->profile();

		return $profil() ? (bool) $profil->{'montrer_'.$quoi} : self::MONTRE_PAR_DEFAUT[$quoi];
	}

	/** Celui qui regarde voit-il `$quoi` de ce membre ? Le membre voit toujours le sien ; les autres, ce qu'il montre. */
	public function montre(string $quoi): bool
	{
		return ($this->id && (int) $this->user->id === (int) $this->id) || $this->montre_aux_autres($quoi);
	}

	public function avatar()
	{
		// Un membre qui cache sa présence n'a ni pastille « en ligne » ni pastille « hors ligne ».
		$presence = $this->montre('statut');

		return $this->html()
					->attr('class', 'avatar')
					->append_attr_if($presence && $this->is_online(),  'class', 'online')
					->append_attr_if($presence && !$this->is_online(), 'class', 'offline')
					->content($this->view('avatar'));
	}

	public function token()
	{
		// Un seul token actif par compte : une nouvelle demande invalide les précédentes
		// (sinon chaque email de reset laisse un lien valable derrière lui).
		NeoFrag()->db->where('user_id', $this->id)->delete('nf_user_token');

		$token = $this->module('user')->model2('token')->set('user', $this);

		do
		{
			$token->set('id', unique_id());
		}
		while (!$token->create());

		return $token->id;
	}

	public function password($password)
	{
		// Format moderne : argon2id ou bcrypt via password_hash() / password_verify()
		if (str_starts_with($this->password, '$argon2') || str_starts_with($this->password, '$2y$') || str_starts_with($this->password, '$2a$') || str_starts_with($this->password, '$2b$'))
		{
			if (password_verify($password, $this->password))
			{
				if (password_needs_rehash($this->password, PASSWORD_ARGON2ID))
				{
					$this	->set('password', password_hash($password, PASSWORD_ARGON2ID))
							->update();
				}

				return TRUE;
			}

			return FALSE;
		}

		// Format legacy PHPass — vérifier puis migrer vers argon2id
		if (NeoFrag()->password->is_valid($password.$this->salt, $this->password, $salt = $this->salt !== ''))
		{
			$this	->set('password', password_hash($password, PASSWORD_ARGON2ID))
					->set('salt', '')
					->update();

			return TRUE;
		}

		return FALSE;
	}

	public function set_password($password)
	{
		$this	->set('password', password_hash($password, PASSWORD_ARGON2ID))
				->set('salt', '');

		if ($this())
		{
			$this	->sessions()
					->where_if(NeoFrag()->user() && NeoFrag()->user->id == $this->id, 'id <>', NeoFrag()->session->id)
					->update([
						'user_id' => NULL
					]);
		}

		return $this;
	}

	public function sessions()
	{
		return NeoFrag()->collection('session')
						->where('_.user_id', $this->id);
	}

	public function name()
	{
		return $this->profile()->first_name.' '.$this->profile()->last_name;
	}

	public function delete()
	{
		$this	->set('deleted', TRUE)
				->update()
				->sessions()
				->update([
					'user_id' => NULL
				]);

		return $this;
	}
}
