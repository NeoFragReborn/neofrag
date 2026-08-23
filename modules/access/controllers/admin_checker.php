<?php
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\Access\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module_Checker;

class Admin_Checker extends Module_Checker
{
	// ================================================================
	// R1.4 — Roles CRUD checkers
	// ================================================================

	public function _roles()
	{
		if (!$this->user() || !$this->access->effective_admin())
		{
			$this->error->unauthorized();
			return;
		}

		// Liste des rôles avec compteurs (perms, users, groups)
		$rows = $this->db	->select(
			'r.role_id', 'r.name', 'r.title', 'r.description', 'r.color', 'r.icon',
			'r.parent_role_id', 'r.built_in', 'r.`order`',
			'p.title AS parent_title',
			'(SELECT COUNT(*) FROM nf_role_permissions WHERE role_id=r.role_id) AS perm_count',
			'(SELECT COUNT(*) FROM nf_users_roles      WHERE role_id=r.role_id) AS user_count',
			'(SELECT COUNT(*) FROM nf_groups_roles     WHERE role_id=r.role_id) AS group_count'
		)
			->from('nf_roles r')
			->join('nf_roles p', 'p.role_id = r.parent_role_id')
			->order_by('r.`order`', 'r.name')
			->get(FALSE);

		return [$rows];
	}

	public function _roles_add()
	{
		if (!$this->user() || !$this->access->effective_admin())
		{
			$this->error->unauthorized();
			return;
		}
		return [];
	}

	public function _roles_edit($role_id, $url_title = NULL)
	{
		if (!$this->user() || !$this->access->effective_admin())
		{
			$this->error->unauthorized();
			return;
		}

		$role = $this->db	->select('role_id', 'name', 'title', 'description', 'color', 'icon', 'parent_role_id', 'built_in')
							->from('nf_roles')
							->where('role_id', (int)$role_id)
							->row(FALSE);

		if (!$role)
		{
			$this->error->not_found();
			return;
		}

		return [$role];
	}

	public function _roles_delete($role_id, $url_title = NULL)
	{
		if (!$this->user() || !$this->access->effective_admin())
		{
			$this->error->unauthorized();
			return;
		}

		$role = $this->db	->select('role_id', 'name', 'title', 'built_in')
							->from('nf_roles')
							->where('role_id', (int)$role_id)
							->row(FALSE);

		if (!$role)
		{
			$this->error->not_found();
			return;
		}

		return [$role];
	}

	public function _roles_clone($role_id, $url_title = NULL)
	{
		if (!$this->user() || !$this->access->effective_admin())
		{
			$this->error->unauthorized();
			return;
		}

		$role = $this->db	->select('role_id', 'name', 'title', 'built_in')
							->from('nf_roles')
							->where('role_id', (int)$role_id)
							->row(FALSE);

		if (!$role)
		{
			$this->error->not_found();
			return;
		}

		return [$role];
	}

	// ================================================================
	// R1.5 — checkers assignations users/groups → roles + permissions effectives
	// ================================================================

	public function _users_roles()
	{
		if (!$this->user() || !$this->access->effective_admin())
		{
			$this->error->unauthorized();
			return;
		}

		// Liste des users non-deleted avec leurs rôles assignés
		$users = $this->db	->select('u.id', 'u.username', 'u.admin')
							->from('nf_user u')
							->where('u.deleted', '0')
							->order_by('u.username')
							->get(FALSE);

		// Pour chaque user, récupérer les rôles assignés via nf_users_roles
		$assignments = [];
		foreach ($this->db->select('user_id', 'role_id')->from('nf_users_roles')->get(FALSE) as $a)
		{
			$assignments[(int)$a['user_id']][(int)$a['role_id']] = TRUE;
		}

		foreach ($users as &$u)
		{
			$u['roles'] = $assignments[(int)$u['id']] ?? [];
		}
		unset($u);

		// Liste des rôles non built_in (admin/member/visitor pas assignables manuellement, sauf super_admin pour donner explicitement le god-mode)
		$roles = $this->db	->select('role_id', 'name', 'title', 'color', 'icon', 'built_in')
							->from('nf_roles')
							->order_by('`order`', 'name')
							->get(FALSE);

		return [$users, $roles];
	}

	public function _groups_roles()
	{
		if (!$this->user() || !$this->access->effective_admin())
		{
			$this->error->unauthorized();
			return;
		}

		// Liste des groupes DB non-auto (les auto admins/members/visitors sont en mémoire, pas en DB)
		$groups = $this->db	->select('g.group_id', 'g.name', 'g.color', 'g.icon', 'IFNULL(gl.title, g.name) AS title')
							->from('nf_groups g')
							->join('nf_groups_lang gl', 'gl.group_id = g.group_id AND gl.lang = "fr"', 'LEFT')
							->order_by('g.`order`', 'g.name')
							->get(FALSE);

		// Pour chaque group, récupérer les rôles assignés
		$assignments = [];
		foreach ($this->db->select('group_id', 'role_id')->from('nf_groups_roles')->get(FALSE) as $a)
		{
			$assignments[(int)$a['group_id']][(int)$a['role_id']] = TRUE;
		}

		foreach ($groups as &$g)
		{
			$g['roles'] = $assignments[(int)$g['group_id']] ?? [];
		}
		unset($g);

		$roles = $this->db	->select('role_id', 'name', 'title', 'color', 'icon', 'built_in')
							->from('nf_roles')
							->order_by('`order`', 'name')
							->get(FALSE);

		return [$groups, $roles];
	}

	// ================================================================
	// R1.9 — Preview checkers (super-admin nf_user.admin=1 uniquement, anti-escalade)
	// ================================================================

	public function _preview_role($role_id)
	{
		// Volontairement $this->user->admin direct (pas effective_admin) :
		// l'admin réel doit pouvoir changer de cible de preview sans sortir, même si effective_admin = FALSE.
		if (!$this->user() || !$this->user->admin)
		{
			$this->error->unauthorized();
			return;
		}

		$role = $this->db	->select('role_id', 'name', 'title', 'built_in')
							->from('nf_roles')
							->where('role_id', (int)$role_id)
							->row(FALSE);

		if (!$role)
		{
			$this->error->not_found();
			return;
		}

		// Preview super_admin n'a aucun sens (god-mode = même chose qu'avant)
		if ($role['name'] === 'super_admin')
		{
			notify($this->lang('Le preview du rôle super_admin est inutile (même comportement que ton accès actuel).'), 'warning');
			redirect_back('admin/access/roles');
		}

		return [$role];
	}

	public function _preview_user($user_id)
	{
		// Volontairement $this->user->admin direct (pas effective_admin) :
		// l'admin réel doit pouvoir changer de cible de preview sans sortir, même si effective_admin = FALSE.
		if (!$this->user() || !$this->user->admin)
		{
			$this->error->unauthorized();
			return;
		}

		$user = $this->db	->select('id', 'username', 'admin')
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

	public function _preview_exit()
	{
		// Pas de check admin : tout le monde peut sortir d'un mode preview
		// (au cas où l'admin aurait perdu le bypass admin temporairement par accident)
		return [];
	}

	public function _user_permissions($user_id, $url_title = NULL)
	{
		if (!$this->user() || !$this->access->effective_admin())
		{
			$this->error->unauthorized();
			return;
		}

		$user = $this->db	->select('id', 'username', 'admin')
							->from('nf_user')
							->where('id', (int)$user_id)
							->where('deleted', '0')
							->row(FALSE);

		if (!$user)
		{
			$this->error->not_found();
			return;
		}

		$effective = NeoFrag()->access->effective_permissions((int)$user_id);

		// Trier par permission alphabétique
		ksort($effective);

		return [$user, $effective];
	}

	/**
	 * R1.3 — checker pour la vue matrice.
	 * Auth : super-admin uniquement (raison : modifier les permissions est une action sensible).
	 */
	public function _matrix($module_name = NULL, $type = 'default', $scope_id = 0)
	{
		if (!$this->user() || !$this->access->effective_admin())
		{
			$this->error->unauthorized();
			return;
		}

		// Liste des modules ayant des permissions déclarées (pour le sélecteur)
		$modules_list = [];
		foreach (NeoFrag()->model2('addon')->get('module') as $module)
		{
			if (method_exists($module, 'permissions') && $module->permissions())
			{
				$modules_list[$module->info()->name] = [
					'name'  => $module->info()->name,
					'title' => $module->info()->title,
					'icon'  => $module->info()->icon
				];
			}
		}

		ksort($modules_list);

		// Si pas de module sélectionné → on retourne juste la liste
		if (!$module_name)
		{
			return [NULL, $modules_list, 'default', 0, NULL, [], []];
		}

		$data = $this->model()->build_matrix($module_name, $type, $scope_id);

		if ($data === NULL)
		{
			$this->error->not_found();
			return;
		}

		return [$data['module_name'], $modules_list, $data['type'], $data['scope_id'], $data['access'], $data['roles'], $data['matrix']];
	}
}
