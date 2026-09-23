<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\User\Models\User;

class Update extends \NF\NeoFrag\Actions\Update
{
	protected $_ajax = FALSE;

	protected function check($user)
	{
		return !$user->deleted;
	}

	protected function action($user)
	{
		$form_groups = $this
			->form()
			->add_rules([
				'groups' => [
					'type'   => 'checkbox',
					'values' => array_filter($this->groups(), function($group){
						return !$group['auto'] || $group['auto'] == 'neofrag' || $group['users'] !== NULL;
					}),
					'rules'  => 'required'
				]
			])
			->save();

		if ($form_groups->is_valid($post))
		{
			$this->db	->where('user_id', $user->id)
						->delete('nf_users_groups');

			$this->db	->where('id', $user->id)
						->update('nf_user', [
							'admin' => FALSE
						]);

			if (in_array('admins', $post['groups']))
			{
				$this->db	->where('id', $user->id)
							->update('nf_user', [
								'admin' => TRUE
							]);
			}

			foreach ($post['groups'] as $group_id)
			{
				if ($this->groups()[$group_id]['auto'])
				{
					continue;
				}

				$this->db->insert('nf_users_groups', [
					'user_id'  => $user->id,
					'group_id' => $group_id
				]);
			}

			notify(NeoFrag()->lang('Groupes du membre édités'));

			redirect_back('admin/user');
		}

		$this->module()	->title($this->lang('Édition du membre'))
						->subtitle($user->username)
						->css('groups')
						->js('groups');

		return $this->row()
					->append(
						$this	->col()
								->size('col-12 col-lg-7')
								->append(
									$this	->form2('username email new_password', $user)
											->success(function($user){
												if ($user->password_new)
												{
													$user->set_password($user->password_new);
												}

												$user->update();

												notify($this->lang('Membre modifié'));
												redirect('admin/user/user/update/'.$user->url());
											})
											->panel()
											->title('Membre')
								)
								->append(
									$this	->form2('profile', $user->profile())
											->panel()
											->title('Profil', 'fas fa-pencil-alt')
								)
								->append(
									$this	->form2('profile_socials', $user->profile())
											->panel()
											->title('Liens', 'fas fa-globe')
								)
					)
					->append(
						$this	->col()
								->size('col-12 col-lg-5')
								->append(
									$this	->panel()
											->heading($this->lang('Groupes').'<a href="'.\url('admin/access/user-permissions/'.$user->id.'/'.\url_title($user->username)).'" class="float-end btn btn-sm btn-outline-info" data-bs-toggle="tooltip" title="'.$this->lang('Voir permissions effectives').'">'.\icon('fas fa-key').' '.$this->lang('Permissions').'</a>', 'fas fa-users')
											->body($this->view('admin/groups', [
												'user_id' => $user->id,
												'form_id' => $form_groups->token()
											]))
								)
								->append(
									$this	->form2('avatar', $user->profile())
											->panel()
											->title($this->lang('Avatar'), 'fas fa-user-circle')
								)
								->append(
									$this	->form2('cover', $user->profile())
											->panel()
											->title($this->lang('Photo de couverture'), 'far fa-image')
								)
								->append(
									$this	->table2('session', $user->sessions(), $this->lang('Aucune session active'))
											->panel()
											->title($this->lang('Sessions actives'), 'fas fa-globe')
								)
					);
	}
}
