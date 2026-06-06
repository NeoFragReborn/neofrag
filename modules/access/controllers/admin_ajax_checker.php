<?php
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\Access\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module_Checker;

class Admin_Ajax_Checker extends Module_Checker
{
	// ================================================================
	// R1.5 — Checkers pour assignations
	// ================================================================

	public function users_roles_assign()   { return $this->_check_user_role(); }
	public function users_roles_unassign() { return $this->_check_user_role(); }
	public function groups_roles_assign()  { return $this->_check_group_role(); }
	public function groups_roles_unassign(){ return $this->_check_group_role(); }

	private function _check_user_role()
	{
		$this->extension('json');
		if (!$this->user() || !$this->access->effective_admin()) return;

		if (list($user_id, $role_id) = array_values(post_check('user_id', 'role_id')))
		{
			$user_id = (int)$user_id;
			$role_id = (int)$role_id;

			$ue = NeoFrag()->db->select('id')->from('nf_user')->where('id', $user_id)->where('deleted', '0')->row(FALSE);
			$re = NeoFrag()->db->select('role_id')->from('nf_roles')->where('role_id', $role_id)->row(FALSE);
			if (!$ue || !$re) return;

			return [$user_id, $role_id];
		}
	}

	private function _check_group_role()
	{
		$this->extension('json');
		if (!$this->user() || !$this->access->effective_admin()) return;

		if (list($group_id, $role_id) = array_values(post_check('group_id', 'role_id')))
		{
			$group_id = (int)$group_id;
			$role_id  = (int)$role_id;

			$ge = NeoFrag()->db->select('group_id')->from('nf_groups')->where('group_id', $group_id)->row(FALSE);
			$re = NeoFrag()->db->select('role_id')->from('nf_roles')->where('role_id', $role_id)->row(FALSE);
			if (!$ge || !$re) return;

			return [$group_id, $role_id];
		}
	}

	/**
	 * R1.3 — checker pour _matrix_update : valide POST {role_id, permission, scope_id, value}.
	 */
	public function matrix_update()
	{
		$this->extension('json');

		if (!$this->user() || !$this->access->effective_admin())
		{
			return;
		}

		if (list($role_id, $permission, $scope_id, $value) = array_values(post_check('role_id', 'permission', 'scope_id', 'value')))
		{
			$role_id  = (int)$role_id;
			$scope_id = (int)$scope_id;

			if (!in_array($value, ['allow','never','default'], TRUE))
			{
				return;
			}

			// Vérifier que le role_id existe et n'est pas built_in super_admin (qu'on protège)
			$role = NeoFrag()->db->select('built_in', 'name')->from('nf_roles')->where('role_id', $role_id)->row(FALSE);
			if (!$role)
			{
				return;
			}

			// Permettre les modifs sur tous les rôles (même built_in pour ajuster member/visitor),
			// SAUF super_admin qui doit garder ses pouvoirs (sécurité anti-lockout)
			if ($role['name'] === 'super_admin')
			{
				return;
			}

			// Validation permission format : "module.action" ou "module.*"
			if (!preg_match('/^[a-z0-9_-]+\.([a-z0-9_-]+|\*)$/i', $permission))
			{
				return;
			}

			return [$role_id, $permission, $scope_id, $value];
		}
	}

}
