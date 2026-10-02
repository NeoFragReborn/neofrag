<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\User\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module_Checker;

class Admin_Checker extends Module_Checker
{
	public function index($page = '')
	{
		// La recherche de la liste : un bout de pseudo ou d'e-mail (GET, gardé par la pagination).
		$recherche = trim((string) $this->input->get->get('q'));
		$membres   = $this	->collection('user')
							->where('deleted', FALSE)
							->where('_.id !=', nf_compte_masque());

		if ($recherche !== '')
		{
			$membres->where('_.username LIKE', '%'.$recherche.'%', 'OR', '_.email LIKE', '%'.$recherche.'%');
		}

		return [
			$membres->filters(
						$this	->form2()
								->rule($this->form_text('username')
											->title('Pseudo')
											->data($this->collection('user')->select('username')->where('deleted', FALSE)->where('_.id !=', nf_compte_masque())->array())
											->filter('_.username LIKE')
								)
								->rule($this->form_text('email')
											->title('Email')
											->data($this->collection('user')->select('email')->where('deleted', FALSE)->array())
											->filter('_.email LIKE')
								)
					)
					->paginate($page),
			$recherche
		];
	}

	public function _export($format)
	{
		if (!$this->access->effective_admin())
		{
			return $this->error->unauthorized();
		}

		return [$format];
	}

	/**
	 * Un champ de profil existe-t-il ? Sinon la route rend 404, plutôt qu'une page d'édition vide.
	 */
	public function _fields_edit($field_id)
	{
		/** @var \NF\Modules\User\Models\Fields $fields */
		$fields = $this->model('fields');

		if ($champ = $fields->get_field($field_id))
		{
			return [
				$champ['field_id'],
				$champ['name'],
				$champ['label'],
				$champ['description'],
				$champ['type'],
				(string) $champ['options'],
				(bool) $champ['required'],
				(bool) $champ['public'],
			];
		}
	}

	public function _fields_delete($field_id)
	{
		/** @var \NF\Modules\User\Models\Fields $fields */
		$fields = $this->model('fields');

		if ($champ = $fields->get_field($field_id))
		{
			return [$champ['field_id'], $champ['label']];
		}
	}

	public function _groups_edit()
	{
		// La classe du CŒUR, désignée explicitement. Ni `$this->groups` ni `NeoFrag()->groups` ne
		// conviennent : le module `user` possède son propre modèle `groups`, du même nom, qui masque
		// la bibliothèque. L'appel échouait sur « Call to undefined method Models\Groups::check() »
		// et l'édition d'un groupe rendait un 404 — constaté par tools/check-liens.php.
		if ($group = NeoFrag('NF\NeoFrag\Core\Groups')->check_group(func_get_args()))
		{
			return [
				isset($group['id']) ? $group['id'] : 0,
				$group['unique_id'],
				$group['title'],
				$group['color'],
				$group['icon'],
				$group['hidden'],
				$group['auto']
			];
		}
	}

	public function _groups_delete()
	{
		$this->ajax();

		if ($group = NeoFrag('NF\NeoFrag\Core\Groups')->check_group(func_get_args()))
		{
			if (!$group['auto'])
			{
				return [$group['id'], $group['title']];
			}
		}
	}

	public function _sessions($page = '')
	{
		return [NeoFrag()->collection('session')->order_by('_.last_activity DESC')->paginate($page)];
	}

	public function _sessions_delete($session_id)
	{
		$this->ajax();

		if (!$this->db->from('nf_session')->where('id', $session_id)->empty())
		{
			$this->ajax();

			return [$session_id];
		}
	}

	public function _audit_log($page = '')
	{
		$rows = NeoFrag()->db	->select('id', 'user_id', 'username', 'action', 'target_type', 'target_id', 'details', 'ip_address', 'success', 'UNIX_TIMESTAMP(created_at) AS created_ts')
								->from('nf_audit_log')
								->order_by('id DESC')
								->limit(2000)
								->get();

		// 50 lignes par page : la page en alignait 200 d'un bloc, sur plus de 5 000 pixels.
		return [$this->module->pagination->fix_items_per_page(50)->get_data($rows, $page), count($rows)];
	}

	public function _totp_reset($user_id, $url_title = NULL)
	{
		$user = NeoFrag()->db	->select('id', 'username', 'totp_enabled')
								->from('nf_user')
								->where('id', (int)$user_id)
								->where('deleted', '0')
								->row(FALSE);

		if (!$user)
		{
			$this->error();
			return;
		}

		if (empty($user['totp_enabled']))
		{
			notify($this->lang('Le 2FA n\'est pas activé pour ce user.'), 'warning');
			redirect_back('admin/user');
		}

		return [$user];
	}

	public function _delete($user_id, $url_title = NULL)
	{
		$user_id = (int)$user_id;

		// Garde-fous : pas d'auto-suppression ni suppression du super-admin (id=1).
		if ($user_id === (int)$this->user->id || $user_id === 1)
		{
			notify($this->lang('Ce compte ne peut pas être supprimé ici.'), 'warning');
			redirect_back('admin/user');
		}

		$user = NeoFrag()->db	->select('id', 'username')
								->from('nf_user')
								->where('id', $user_id)
								->where('deleted', '0')
								->row(FALSE);

		if (!$user)
		{
			$this->error();
			return;
		}

		return [$user];
	}

	public function _edit($user_id, $url_title = NULL)
	{
		$user = NeoFrag()->db	->select('id', 'username', 'email', 'admin')
								->from('nf_user')
								->where('id', (int)$user_id)
								->where('deleted', '0')
								->row(FALSE);

		if (!$user)
		{
			$this->error();
			return;
		}

		return [$user];
	}
}
