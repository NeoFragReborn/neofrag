<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\Access\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Admin extends Controller_Module
{
	/**
	 * R1.3 — Vue matricielle complète d'un module : Roles × Permissions en 1 écran.
	 *
	 * Args résolus par le checker :
	 * - $current_module_name : module sélectionné (peut être NULL = page de sélection)
	 * - $modules_list        : liste de tous les modules avec permissions() (tabs)
	 * - $type                : 'default' | 'category' | ...
	 * - $scope_id            : 0 = global, sinon ID
	 * - $access_definition   : structure permissions() du module sélectionné
	 * - $roles               : liste des rôles à afficher en colonnes
	 * - $matrix_values       : précalcul [permission][role_id] = ['source'=>direct|inherited, 'value'=>allow|never|default, 'parent'=>name|null]
	 */
	public function _matrix($current_module_name = NULL, $modules_list = [], $type = 'default', $scope_id = 0, $access_definition = NULL, $roles = [], $matrix_values = [])
	{
		$this	->title($this->lang('Permissions — Vue matricielle'))
				->css('access')
				->js('matrix');

		if (!$current_module_name || !$access_definition)
		{
			return $this->admin_card('fas fa-th', $this->lang('Choisir un module'), $this->view('admin/matrix_modules', ['modules' => $modules_list]));
		}

		$module       = NeoFrag()->module($current_module_name);
		$module_title = $module->info()->title;

		$this	->subtitle($module_title)
				->icon($module->info()->icon);

		$change_btn = '<a class="btn btn-sm btn-outline-secondary" href="'.url('admin/access/matrix').'">'.icon('fas fa-th-large').' '.$this->lang('Changer de module').'</a>';

		return $this->admin_card($module->info()->icon, $this->lang('Permissions de %s', $module_title), $this->view('admin/matrix', [
			'module_name'  => $current_module_name,
			'module_title' => $module_title,
			'type'         => $type,
			'scope_id'     => $scope_id,
			'access'       => $access_definition,
			'roles'        => $roles,
			'matrix'       => $matrix_values
		]), '', $change_btn);
	}

	// ================================================================
	// R1.4 — Roles CRUD
	// ================================================================

	public function _roles($roles_data)
	{
		$this	->title($this->lang('Rôles'))
				->subtitle($this->lang('Gestion des rôles et de leurs permissions'))
				->css('access');

		$add_btn = '<a class="btn btn-sm btn-primary" href="'.url('admin/access/roles/add').'">'.icon('fas fa-plus').' '.$this->lang('Créer un rôle').'</a>';

		return $this->admin_card('fas fa-user-shield', $this->lang('Liste des rôles'), $this->view('admin/roles', ['roles' => $roles_data, 'jeton' => $this->csrf_token()]), '', $add_btn);
	}

	public function _roles_add()
	{
		$this	->title($this->lang('Créer un rôle'))
				->subtitle($this->lang('Nouveau rôle'));

		// Liste des rôles disponibles comme parent (pour l'inheritance dropdown)
		$parent_choices = ['' => '— '.$this->lang('Aucun').' —'];
		foreach ($this->db->select('role_id', 'title')->from('nf_roles')->order_by('`order`', 'name')->get(FALSE) as $r)
		{
			$parent_choices[(int)$r['role_id']] = $r['title'];
		}

		$this	->form()
				->add_rules('role', ['parent_choices' => $parent_choices])
				->add_submit($this->lang('Créer'), 'fas fa-plus')
				->add_back('admin/access/roles');

		if ($this->form()->is_valid($post))
		{
			$role_id = $this->model()->add_role($post['name'], $post['title'], [
				'description'    => $post['description'] ?: NULL,
				'color'          => $post['color']       ?: 'secondary',
				'icon'           => $post['icon']        ?: 'fas fa-user-shield',
				'parent_role_id' => !empty($post['parent_role_id']) ? (int)$post['parent_role_id'] : NULL
			]);

			notify($this->lang('Rôle "%s" créé', $post['title']));
			redirect_back('admin/access/roles');
		}

		return $this->admin_back('admin/access/roles', $this->lang('Rôles'))
			.$this->admin_card('fas fa-plus', $this->lang('Créer un rôle'), $this->form()->display());
	}

	public function _roles_edit($role)
	{
		$this	->title($this->lang('Éditer le rôle'))
				->subtitle($role['title'])
				->add_action('admin/access/matrix', $this->lang('Configurer les permissions'), 'fas fa-th');

		// Liste des rôles disponibles comme parent (exclure soi-même + descendants pour éviter cycles)
		$parent_choices = ['' => '— '.$this->lang('Aucun').' —'];
		foreach ($this->db->select('role_id', 'title')->from('nf_roles')->where('role_id !=', (int)$role['role_id'])->order_by('`order`', 'name')->get(FALSE) as $r)
		{
			$parent_choices[(int)$r['role_id']] = $r['title'];
		}

		$this	->form()
				->add_rules('role', [
					'parent_choices' => $parent_choices,
					'is_edit'        => TRUE,
					'built_in'       => $role['built_in'],
					'current'        => $role
				])
				->add_submit($this->lang('Enregistrer'))
				->add_back('admin/access/roles');

		if ($this->form()->is_valid($post))
		{
			$ok = $this->model()->update_role($role['role_id'], [
				'title'          => $post['title'],
				'description'    => $post['description'] ?: NULL,
				'color'          => $post['color']       ?: 'secondary',
				'icon'           => $post['icon']        ?: 'fas fa-user-shield',
				'parent_role_id' => !empty($post['parent_role_id']) ? (int)$post['parent_role_id'] : NULL
			]);

			if ($ok)
			{
				notify($this->lang('Rôle "%s" mis à jour', $post['title']));
				redirect_back('admin/access/roles');
			}
			else
			{
				notify($this->lang('Impossible de modifier ce rôle (built-in ou inexistant)'), 'danger');
			}
		}

		return $this->admin_back('admin/access/roles', $this->lang('Rôles'))
			.$this->admin_card('fas fa-edit', $this->lang('Éditer "%s"', $role['title']), $this->form()->display());
	}

	public function _roles_delete($role)
	{
		// Un lien d'action : sans jeton, un lien piégé supprimait un rôle chez un administrateur
		// connecté (et sur la démo, définitivement : les rôles ne sont pas restaurés). 2026-10-02.
		$this->check_csrf('admin/access/roles');

		if ($role['built_in'])
		{
			notify($this->lang('Impossible de supprimer un rôle built-in (super_admin, member, visitor)'), 'danger');
			redirect_back('admin/access/roles');
		}

		if ($this->model()->delete_role($role['role_id']))
		{
			notify($this->lang('Rôle "%s" supprimé', $role['title']));
		}
		else
		{
			notify($this->lang('Suppression échouée'), 'danger');
		}

		redirect_back('admin/access/roles');
	}

	public function _roles_clone($role)
	{
		$this	->title($this->lang('Cloner le rôle'))
				->subtitle($role['title']);

		$this	->form()
				->add_rules([
					'name' => [
						'label'   => $this->lang('Nom technique du nouveau rôle'),
						'rules'   => 'required|alpha_dash|min_length=3|max_length=100',
						'info'    => $this->lang('Identifiant interne (lettres, chiffres, _ et -). Doit être unique.'),
						'value'   => $role['name'].'_copy'
					],
					'title' => [
						'label' => $this->lang('Titre affiché'),
						'rules' => 'required|max_length=100',
						'value' => $role['title'].' (copie)'
					]
				])
				->add_submit($this->lang('Cloner'), 'fas fa-clone')
				->add_back('admin/access/roles');

		if ($this->form()->is_valid($post))
		{
			// Vérifier l'unicité du name
			$exists = $this->db->select('role_id')->from('nf_roles')->where('name', $post['name'])->row(FALSE);
			if ($exists)
			{
				notify($this->lang('Le nom "%s" existe déjà, choisis-en un autre', $post['name']), 'danger');
			}
			else
			{
				$new_id = $this->model()->clone_role($role['role_id'], $post['name'], $post['title']);

				if ($new_id)
				{
					notify($this->lang('Rôle cloné avec succès'));
					redirect('admin/access/roles/edit/'.$new_id.'/'.url_title($post['title']));
				}
				else
				{
					notify($this->lang('Clone échoué'), 'danger');
				}
			}
		}

		return $this->admin_back('admin/access/roles', $this->lang('Rôles'))
			.$this->admin_card('fas fa-copy', $this->lang('Cloner "%s"', $role['title']), $this->form()->display());
	}

	// ================================================================
	// R1.5 — Pages assignations users-roles, groups-roles, permissions effectives
	// ================================================================

	public function _users_roles($users_data, $roles_data)
	{
		$this	->title($this->lang('Assigner des rôles aux utilisateurs'))
				->subtitle($this->lang('Matrice users × roles'))
				->css('access')
				->js('users_roles');

		return $this->admin_card('fas fa-users-cog', $this->lang('Utilisateurs × Rôles'), $this->view('admin/users_roles', [
			'users' => $users_data,
			'roles' => $roles_data
		]));
	}

	public function _groups_roles($groups_data, $roles_data)
	{
		$this	->title($this->lang('Assigner des rôles aux groupes'))
				->subtitle($this->lang('Matrice groups × roles'))
				->css('access')
				->js('users_roles');

		return $this->admin_card('fas fa-layer-group', $this->lang('Groupes × Rôles'), $this->view('admin/groups_roles', [
			'groups' => $groups_data,
			'roles'  => $roles_data
		]));
	}

	// ================================================================
	// R1.9 — Preview "Voir le site comme [Rôle/User]"
	// ================================================================

	public function _preview_role($role)
	{
		$this->access->start_preview('role', $role['role_id'], $role['title']);
		notify($this->lang('Mode preview activé : tu vois le site comme le rôle "%s"', $role['title']));
		redirect('');
	}

	public function _preview_user($user)
	{
		$this->access->start_preview('user', $user['id'], $user['username']);
		notify($this->lang('Mode preview activé : tu vois le site comme %s', $user['username']));
		redirect('');
	}

	public function _preview_exit()
	{
		$this->access->exit_preview();
		notify($this->lang('Mode preview désactivé. Tu vois à nouveau le site avec tes propres permissions.'));
		redirect('admin/access/roles');
	}

	public function _user_permissions($user, $effective)
	{
		$this	->title($this->lang('Permissions effectives'))
				->subtitle($user['username'])
				->css('access');

		return $this->admin_back('admin/access/users-roles', $this->lang('Assignations'))
			.$this->admin_card('fas fa-key', $this->lang('Permissions effectives de %s', $user['username']), $this->view('admin/user_permissions', [
				'user'      => $user,
				'effective' => $effective
			]));
	}

}
