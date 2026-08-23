<?php
/**
 * https://neofr.ag
 * Model des permissions / rôles — adapté au nouveau schéma R1 (nf_roles + nf_role_permissions + nf_users_roles + nf_groups_roles).
 *
 * - set_permission / clear_permission : mutations de permission par rôle (matrice admin)
 * - add_role / update_role / delete_role / clone_role : CRUD des rôles (R1.4)
 * - assign_role_to_user / unassign / assign_to_group : assignation (R1.5)
 *
 * Compat legacy : add() et delete() conservés (signature ancienne) pour pas casser les call-sites existants
 * dans admin_ajax::update() qui les appelle. Mappés vers le nouveau schéma.
 */

namespace NF\Modules\Access\Models;

use NF\NeoFrag\Loadables\Model;

class Access extends Model
{
	// ================================================================
	// Nouvelle API : mutations atomiques sur le moteur R1
	// ================================================================

	/**
	 * Set une permission pour un rôle (insert ou update si existe).
	 *
	 * @param int    $role_id
	 * @param string $permission "module.action" ou "module.*"
	 * @param int    $scope_id    0 = global
	 * @param string $value       'allow' | 'never' | 'default'
	 */
	public function set_permission($role_id, $permission, $scope_id, $value)
	{
		$role_id = (int)$role_id;
		$scope_id = (int)$scope_id;

		if (!in_array($value, ['allow','never','default'], TRUE))
		{
			return $this;
		}

		// 'default' = absence d'override → DELETE row
		if ($value === 'default')
		{
			$this->db	->where('role_id',    $role_id)
						->where('permission', $permission)
						->where('scope_id',   $scope_id)
						->delete('nf_role_permissions');
		}
		else
		{
			$this->db->replace('nf_role_permissions', [
				'role_id'    => $role_id,
				'permission' => $permission,
				'scope_id'   => $scope_id,
				'authorized' => $value
			]);
		}

		// Invalider cache + reload
		NeoFrag()->access->reload();

		// Audit log via event (R1.6 listener dans modules/access/access.php)
		if (NeoFrag()->events ?? NULL)
		{
			NeoFrag()->events->fire('permissions.permission.changed', [
				'role_id'    => $role_id,
				'permission' => $permission,
				'scope_id'   => $scope_id,
				'value'      => $value
			]);
		}

		return $this;
	}

	// ================================================================
	// Construction de la matrice Permissions × Rôles (R1.3)
	// Partagé par la vue pleine page (admin/access/matrix) ET la modale AJAX
	// (admin/ajax/access/matrix-modal) — un seul calcul, deux enveloppes.
	// ================================================================

	/**
	 * Construit la matrice d'un module pour un scope donné.
	 *
	 * @param  string $module_name
	 * @param  string $type      'default' | 'category' | ... (avec fallback si absent)
	 * @param  int    $scope_id  0 = global
	 * @return array|null  ['module_name','module_title','module_icon','type','scope_id','access','roles','matrix'] ou NULL si pas de permissions
	 */
	public function build_matrix($module_name, $type = 'default', $scope_id = 0)
	{
		$module = NeoFrag()->module($module_name);

		if (!$module || !method_exists($module, 'permissions'))
		{
			return NULL;
		}

		$all_perms = $module->get_permissions($type);

		// Fallback : si le type demandé n'existe pas (ex: 'default' sur un module qui n'a que 'category'),
		// prendre le premier type disponible. Permet à button_access(scope=0) de marcher sur forum/talks/etc.
		if (!$all_perms || empty($all_perms['access']))
		{
			$all_module_perms = $module->permissions();
			if (is_array($all_module_perms) && !empty($all_module_perms))
			{
				foreach ($all_module_perms as $available_type => $candidate)
				{
					if (!empty($candidate['access']))
					{
						$type      = $available_type;
						$all_perms = $candidate;
						break;
					}
				}
			}
		}

		if (!$all_perms || empty($all_perms['access']))
		{
			return NULL;
		}

		$scope_id = (int)$scope_id;

		$rows = $this->db	->select('role_id', 'name', 'title', 'color', 'icon', 'parent_role_id', 'built_in', '`order`')
							->from('nf_roles')
							->order_by('`order`', 'name')
							->get(FALSE);

		$roles = [];
		foreach ($rows as $r)
		{
			$roles[(int)$r['role_id']] = [
				'role_id'        => (int)$r['role_id'],
				'name'           => $r['name'],
				'title'          => $r['title'],
				'color'          => $r['color'],
				'icon'           => $r['icon'],
				'parent_role_id' => $r['parent_role_id'] !== NULL ? (int)$r['parent_role_id'] : NULL,
				'built_in'       => (bool)$r['built_in']
			];
		}

		// Pré-calculer la matrice : pour chaque (permission, role) → ['value','source','parent_role_name'].
		$matrix = [];
		foreach ($all_perms['access'] as $category)
		{
			foreach ($category['access'] as $action => $info)
			{
				$permission = $module_name.'.'.$action;

				foreach ($roles as $role_id => $role)
				{
					$matrix[$permission][$role_id] = $this->_resolve_matrix_cell($role_id, $permission, $scope_id, $roles);
				}
			}
		}

		return [
			'module_name'  => $module_name,
			'module_title' => $module->info()->title,
			'module_icon'  => $module->info()->icon,
			'type'         => $type,
			'scope_id'     => $scope_id,
			'access'       => $all_perms,
			'roles'        => $roles,
			'matrix'       => $matrix
		];
	}

	/**
	 * Résout la valeur d'une cellule : direct dans le rôle, hérité du parent (walk), wildcard, ou default.
	 */
	private function _resolve_matrix_cell($role_id, $permission, $scope_id, $roles)
	{
		// Lookup direct dans nf_role_permissions pour ce rôle
		$row = $this->db	->select('authorized')
							->from('nf_role_permissions')
							->where('role_id',    $role_id)
							->where('permission', $permission)
							->where('scope_id',   $scope_id)
							->row(FALSE);

		if (is_array($row) && isset($row['authorized']))
		{
			return ['value' => $row['authorized'], 'source' => 'direct', 'parent_role_name' => NULL];
		}

		// Wildcard direct ?
		$dot = strpos($permission, '.');
		if ($dot !== FALSE)
		{
			$wildcard = substr($permission, 0, $dot).'.*';
			$row = $this->db->select('authorized')->from('nf_role_permissions')
							->where('role_id', $role_id)->where('permission', $wildcard)->where('scope_id', $scope_id)
							->row(FALSE);

			if (is_array($row) && isset($row['authorized']))
			{
				return ['value' => $row['authorized'], 'source' => 'wildcard', 'parent_role_name' => NULL];
			}
		}

		// Walk parent chain
		$cursor = $roles[$role_id]['parent_role_id'] ?? NULL;
		$depth  = 0;
		while ($cursor !== NULL && $depth < 20)
		{
			$row = $this->db->select('authorized')->from('nf_role_permissions')
							->where('role_id', $cursor)->where('permission', $permission)->where('scope_id', $scope_id)
							->row(FALSE);

			if (is_array($row) && isset($row['authorized']))
			{
				return [
					'value'            => $row['authorized'],
					'source'           => 'inherited',
					'parent_role_name' => $roles[$cursor]['title'] ?? $roles[$cursor]['name']
				];
			}

			$cursor = $roles[$cursor]['parent_role_id'] ?? NULL;
			$depth++;
		}

		// Aucune définition explicite → default
		return ['value' => 'default', 'source' => 'none', 'parent_role_name' => NULL];
	}

	// ================================================================
	// Roles CRUD (utilisé par R1.4)
	// ================================================================

	public function add_role($name, $title, array $opts = [])
	{
		$role_id = $this->db->insert('nf_roles', [
			'name'           => $name,
			'title'          => $title,
			'description'    => $opts['description']    ?? NULL,
			'color'          => $opts['color']          ?? 'secondary',
			'icon'           => $opts['icon']           ?? 'fas fa-user-shield',
			'parent_role_id' => isset($opts['parent_role_id']) ? (int)$opts['parent_role_id'] : NULL,
			'built_in'       => 0,
			'order'          => $opts['order']          ?? 50
		]);

		NeoFrag()->access->reload();

		if (NeoFrag()->events ?? NULL)
		{
			NeoFrag()->events->fire('permissions.role.created', [
				'role_id' => $role_id,
				'name'    => $name,
				'title'   => $title
			]);
		}

		return $role_id;
	}

	public function update_role($role_id, array $opts)
	{
		$role_id = (int)$role_id;

		// Built-in roles : ne peuvent être renommés / supprimés
		$row = $this->db->select('built_in', 'name')->from('nf_roles')->where('role_id', $role_id)->row(FALSE);
		if (!$row || $row['built_in'])
		{
			return FALSE;
		}

		$set = array_intersect_key($opts, array_flip(['title','description','color','icon','parent_role_id','order']));
		if (empty($set))
		{
			return FALSE;
		}

		$this->db	->where('role_id', $role_id)
					->update('nf_roles', $set);

		NeoFrag()->access->reload();

		if (NeoFrag()->events ?? NULL)
		{
			NeoFrag()->events->fire('permissions.role.updated', ['role_id' => $role_id, 'changes' => $set]);
		}

		return TRUE;
	}

	public function delete_role($role_id)
	{
		$role_id = (int)$role_id;

		$row = $this->db->select('built_in', 'name')->from('nf_roles')->where('role_id', $role_id)->row(FALSE);
		if (!$row || $row['built_in'])
		{
			return FALSE;
		}

		$name = $row['name'];

		// CASCADE prévu par les FK : supprime aussi nf_role_permissions, nf_users_roles, nf_groups_roles
		$this->db	->where('role_id', $role_id)
					->delete('nf_roles');

		NeoFrag()->access->reload();

		if (NeoFrag()->events ?? NULL)
		{
			NeoFrag()->events->fire('permissions.role.deleted', ['role_id' => $role_id, 'name' => $name]);
		}

		return TRUE;
	}

	/**
	 * Clone un rôle existant : copie ses permissions vers un nouveau rôle (one-shot, pas de lien).
	 */
	public function clone_role($source_role_id, $new_name, $new_title)
	{
		$source_role_id = (int)$source_role_id;

		$source = $this->db->select('color', 'icon', 'parent_role_id')->from('nf_roles')->where('role_id', $source_role_id)->row(FALSE);
		if (!$source)
		{
			return FALSE;
		}

		$new_id = $this->add_role($new_name, $new_title, [
			'color'          => $source['color'],
			'icon'           => $source['icon'],
			'parent_role_id' => $source['parent_role_id']
		]);

		// Copy permissions
		foreach ($this->db->select('permission', 'scope_id', 'authorized')->from('nf_role_permissions')->where('role_id', $source_role_id)->get(FALSE) as $rp)
		{
			$this->db->insert('nf_role_permissions', [
				'role_id'    => $new_id,
				'permission' => $rp['permission'],
				'scope_id'   => (int)$rp['scope_id'],
				'authorized' => $rp['authorized']
			]);
		}

		NeoFrag()->access->reload();

		if (NeoFrag()->events ?? NULL)
		{
			NeoFrag()->events->fire('permissions.role.cloned', [
				'role_id' => $new_id,
				'cloned_from' => $source_role_id,
				'name' => $new_name
			]);
		}

		return $new_id;
	}

	// ================================================================
	// Assignations (R1.5)
	// ================================================================

	public function assign_role_to_user($user_id, $role_id)
	{
		$this->db->replace('nf_users_roles', [
			'user_id' => (int)$user_id,
			'role_id' => (int)$role_id
		]);

		NeoFrag()->access->reload();

		if (NeoFrag()->events ?? NULL)
		{
			NeoFrag()->events->fire('permissions.user_role.assigned', ['user_id' => (int)$user_id, 'role_id' => (int)$role_id]);
		}

		return $this;
	}

	public function unassign_role_from_user($user_id, $role_id)
	{
		$this->db	->where('user_id', (int)$user_id)
					->where('role_id', (int)$role_id)
					->delete('nf_users_roles');

		NeoFrag()->access->reload();

		if (NeoFrag()->events ?? NULL)
		{
			NeoFrag()->events->fire('permissions.user_role.unassigned', ['user_id' => (int)$user_id, 'role_id' => (int)$role_id]);
		}

		return $this;
	}

	public function assign_role_to_group($group_id, $role_id)
	{
		$this->db->replace('nf_groups_roles', [
			'group_id' => (int)$group_id,
			'role_id'  => (int)$role_id
		]);

		NeoFrag()->access->reload();

		if (NeoFrag()->events ?? NULL)
		{
			NeoFrag()->events->fire('permissions.group_role.assigned', ['group_id' => (int)$group_id, 'role_id' => (int)$role_id]);
		}

		return $this;
	}

	public function unassign_role_from_group($group_id, $role_id)
	{
		$this->db	->where('group_id', (int)$group_id)
					->where('role_id',  (int)$role_id)
					->delete('nf_groups_roles');

		NeoFrag()->access->reload();

		if (NeoFrag()->events ?? NULL)
		{
			NeoFrag()->events->fire('permissions.group_role.unassigned', ['group_id' => (int)$group_id, 'role_id' => (int)$role_id]);
		}

		return $this;
	}

	// ================================================================
	// API legacy — appelée par admin_ajax.php existant pour la vue 1-perm-à-la-fois
	// Mappe l'ancien (entity, type) vers le nouveau (role_id) pour rétrocompat.
	// ================================================================

	/**
	 * Legacy : ajoute une permission à un ou plusieurs entities (groupes ou users).
	 * Mappe vers set_permission() du nouveau schéma.
	 */
	public function add($module, $action, $id, $type, $entities, $authorized)
	{
		$permission = $module.'.'.$action;
		$value      = $authorized ? 'allow' : 'never';

		foreach ((array)$entities as $entity)
		{
			$role_id = $this->_resolve_legacy_entity_to_role($entity, $type);

			if ($role_id)
			{
				$this->set_permission($role_id, $permission, (int)$id, $value);
			}
		}

		return $this;
	}

	/**
	 * Legacy : delete permission(s) pour un module/action/id, optionnellement filtré par type/entity.
	 */
	public function delete($module, $action, $id, $type = NULL, $entity = NULL)
	{
		$permission = $module.'.'.$action;

		if ($type && $entity !== NULL)
		{
			$role_id = $this->_resolve_legacy_entity_to_role($entity, $type);

			if ($role_id)
			{
				$this->set_permission($role_id, $permission, (int)$id, 'default');
			}
		}
		else
		{
			$this->db	->where('permission', $permission)
						->where('scope_id',   (int)$id)
						->delete('nf_role_permissions');

			NeoFrag()->access->reload();
		}

		return $this;
	}

	// ================================================================
	// Helpers
	// ================================================================

	private function _resolve_legacy_entity_to_role($entity, $type = 'group')
	{
		if ($type === 'user')
		{
			// Le legacy user-permission n'est plus supporté direct → on log et skip
			// (R1 a explicitement refusé ce pattern, utiliser un rôle dédié si besoin)
			return NULL;
		}

		// Mapping pluriel groupe auto → singulier rôle built-in
		$mapping = [
			'admins'   => 'super_admin',
			'members'  => 'member',
			'visitors' => 'visitor'
		];

		if (isset($mapping[$entity]))
		{
			return NeoFrag()->access->role_id_by_name($mapping[$entity]);
		}

		// Sinon : entity = group_id stringifié (ancien schéma) → mapper vers rôle de même nom
		// L'ancien schéma stockait entity = url_title(group_id) pour groups DB non-auto.
		// Dans le nouveau schéma, ces groupes ont des rôles correspondants par convention de migration.
		$group = NeoFrag()->db->select('name')->from('nf_groups')->where('group_id', (int)$entity)->row(FALSE);
		if ($group && isset($group['name']))
		{
			return NeoFrag()->access->role_id_by_name($group['name']);
		}

		return NULL;
	}
}
