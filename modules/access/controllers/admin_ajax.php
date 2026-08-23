<?php
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\Access\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Admin_Ajax extends Controller_Module
{
	/**
	 * R1.10 — Matrice des permissions en MODALE (ouverte depuis button_access / le bouton
	 * « Permissions » du header admin). Réutilise build_matrix() + la vue admin/matrix ;
	 * la sauvegarde reste matrix_update (matrix.js, inchangé).
	 */
	public function matrix_modal($module_name, $type = 'default', $scope_id = 0)
	{
		$data = $this->model()->build_matrix($module_name, $type, $scope_id);

		if ($data === NULL)
		{
			return $this->modal($this->lang('Permissions'), 'fas fa-unlock-alt')
						->body('<div class="alert alert-warning" style="margin:0">'.$this->lang('Aucune permission à configurer pour ce module.').'</div>')
						->close();
		}

		$this->css('access')->js('matrix');

		return $this->modal($this->lang('Permissions de %s', $data['module_title']), ($data['module_icon'] ?: 'fas fa-unlock-alt'))
					->large()
					->body($this->view('admin/matrix', [
						'module_name'  => $data['module_name'],
						'module_title' => $data['module_title'],
						'type'         => $data['type'],
						'scope_id'     => $data['scope_id'],
						'access'       => $data['access'],
						'roles'        => $data['roles'],
						'matrix'       => $data['matrix']
					]))
					->close();
	}

	/**
	 * R1.3 — endpoint AJAX pour la matrice : set/clear une permission.
	 */
	public function matrix_update($role_id, $permission, $scope_id, $value)
	{
		$this->model()->set_permission($role_id, $permission, $scope_id, $value);

		return $this->json([
			'ok'         => TRUE,
			'role_id'    => (int)$role_id,
			'permission' => $permission,
			'scope_id'   => (int)$scope_id,
			'value'      => $value
		]);
	}

	// ================================================================
	// R1.5 — Endpoints AJAX pour assignations
	// ================================================================

	public function users_roles_assign($user_id, $role_id)
	{
		$this->model()->assign_role_to_user($user_id, $role_id);
		return $this->json(['ok' => TRUE, 'user_id' => $user_id, 'role_id' => $role_id]);
	}

	public function users_roles_unassign($user_id, $role_id)
	{
		$this->model()->unassign_role_from_user($user_id, $role_id);
		return $this->json(['ok' => TRUE, 'user_id' => $user_id, 'role_id' => $role_id]);
	}

	public function groups_roles_assign($group_id, $role_id)
	{
		$this->model()->assign_role_to_group($group_id, $role_id);
		return $this->json(['ok' => TRUE, 'group_id' => $group_id, 'role_id' => $role_id]);
	}

	public function groups_roles_unassign($group_id, $role_id)
	{
		$this->model()->unassign_role_from_group($group_id, $role_id);
		return $this->json(['ok' => TRUE, 'group_id' => $group_id, 'role_id' => $role_id]);
	}
}
