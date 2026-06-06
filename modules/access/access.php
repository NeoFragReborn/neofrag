<?php
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\Access;

use NF\NeoFrag\Addons\Module;

class Access extends Module
{
	protected function __info()
	{
		return [
			'title'       => $this->lang('Permissions'),
			'description' => 'Gestion des permissions par groupe d\'utilisateurs et par module.',
			'icon'        => 'fas fa-unlock-alt',
			'link'        => 'https://neofr.ag',
			'author'      => 'Michaël BILCOT & Jérémy VALENTIN <contact@neofrag.com>',
			'license'     => 'LGPLv3 <https://neofr.ag/license>',
			'version'     => '1.0',
			'admin'       => FALSE,
			'routes'      => [
				// R1.3 — vue matricielle (rôles × permissions par module)
				'admin/matrix'                              => '_matrix',
				'admin/matrix/{url_title}'                  => '_matrix',
				'admin/matrix/{url_title}/{url_title}/{id}' => '_matrix',

				// R1.4 — Roles CRUD
				'admin/roles'                               => '_roles',
				'admin/roles/add'                           => '_roles_add',
				'admin/roles/edit/{id}/{url_title}'         => '_roles_edit',
				'admin/roles/delete/{id}/{url_title}'       => '_roles_delete',
				'admin/roles/clone/{id}/{url_title}'        => '_roles_clone',

				// R1.5 — Assignations users/groups → roles + permissions effectives
				'admin/users-roles'                         => '_users_roles',
				'admin/groups-roles'                        => '_groups_roles',
				'admin/user-permissions/{id}/{url_title}'   => '_user_permissions',

				// R1.9 — Preview "Voir le site comme [Rôle/User]"
				'admin/preview/role/{id}'                   => '_preview_role',
				'admin/preview/user/{id}'                   => '_preview_user',
				'admin/preview/exit'                        => '_preview_exit'
			]
		];
	}

	/**
	 * R1.6 — Listeners qui logent toutes les mutations permissions dans nf_audit_log.
	 * Tous les events fired par Models\Access::* sont captés ici.
	 */
	public function __init()
	{
		if (empty(NeoFrag()->events))
		{
			return;
		}

		$audit = function($action, $details) {
			(new \NF\NeoFrag\Libraries\Audit_Log($this))->log('permissions.'.$action, [
				'target_type' => 'permissions',
				'details'     => $details
			]);
		};

		$events = NeoFrag()->events;

		$events->on('permissions.role.created',         function($p) use ($audit){ $audit('role_created',         $p); });
		$events->on('permissions.role.updated',         function($p) use ($audit){ $audit('role_updated',         $p); });
		$events->on('permissions.role.deleted',         function($p) use ($audit){ $audit('role_deleted',         $p); });
		$events->on('permissions.role.cloned',          function($p) use ($audit){ $audit('role_cloned',          $p); });
		$events->on('permissions.permission.changed',   function($p) use ($audit){ $audit('permission_changed',   $p); });
		$events->on('permissions.user_role.assigned',   function($p) use ($audit){ $audit('user_role_assigned',   $p); });
		$events->on('permissions.user_role.unassigned', function($p) use ($audit){ $audit('user_role_unassigned', $p); });
		$events->on('permissions.group_role.assigned', function($p) use ($audit){ $audit('group_role_assigned', $p); });
		$events->on('permissions.group_role.unassigned', function($p) use ($audit){ $audit('group_role_unassigned', $p); });
	}
}
