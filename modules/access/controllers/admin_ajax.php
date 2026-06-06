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
