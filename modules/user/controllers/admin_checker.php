<?php
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
		return [
			$this	->collection('user')
					->where('deleted', FALSE)
					->filters(
						$this	->form2()
								->rule($this->form_text('username')
											->title('Pseudo')
											->data($this->collection('user')->select('username')->where('deleted', FALSE)->array())
											->filter('_.username LIKE')
								)
								->rule($this->form_text('email')
											->title('Email')
											->data($this->collection('user')->select('email')->where('deleted', FALSE)->array())
											->filter('_.email LIKE')
								)
					)
					->paginate($page)
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

	public function _groups_edit()
	{
		if ($group = $this->groups->check_group(func_get_args()))
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

		if ($group = $this->groups->check_group(func_get_args()))
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
								->limit(200)
								->get();

		return [$rows];
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
			$this->error->not_found();
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
			$this->error->not_found();
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
			$this->error->not_found();
			return;
		}

		return [$user];
	}
}
