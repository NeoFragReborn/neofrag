<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * Système de permissions refondu (R1) — Roles autonomes + sémantique allow/never/default + wildcards + inheritance + scope hiérarchique.
 *
 * Architecture :
 * - nf_roles : ensembles nommés de permissions (réutilisables, single-inheritance)
 * - nf_role_permissions : (role_id, permission "module.action" ou "module.*", scope_id, authorized enum)
 * - nf_users_roles : assignation directe user → role
 * - nf_groups_roles : assignation bulk group → role
 *
 * API publique :
 * - can($permission, $scope=0, $user_id=null) : nouvelle API
 * - __invoke($module, $action, $id=0, $group_id=null, $user_id=null) : alias rétro-compat (legacy ->access())
 *
 * Logique can() déterministe :
 *   1. Si user.admin = 1 → ALLOW (super-admin god-mode bypass)
 *   2. Calcule les rôles effectifs (assignations user + groups + inheritance chain)
 *   3. Un seul NEVER (exact ou wildcard ou *.*) bloque tout → DENY
 *   4. Au moins un ALLOW (exact ou wildcard ou *.*) autorise → ALLOW
 *   5. Sinon → DENY (default)
 *
 * Scope hiérarchique : si scope > 0, on regarde scope spécifique d'abord, puis fallback scope=0 (global).
 */

namespace NF\NeoFrag\Core;

use NF\NeoFrag\Core;

class Access extends Core
{
	/**
	 * Cache : [role_id][permission][scope_id] = 'allow'|'never'|'default'
	 */
	private $_role_perms = [];

	/**
	 * Cache : [role_id] = parent_role_id (int) | null
	 */
	private $_role_parents = [];

	/**
	 * Cache : [role_name] = role_id
	 */
	private $_role_id_by_name = [];

	/**
	 * Cache : [role_id] = role_name
	 */
	private $_role_name_by_id = [];

	/**
	 * Cache : [user_id] = [role_id, ...] (résolution complète : direct + groups + inheritance)
	 */
	private $_user_roles_cache = [];

	/**
	 * Cache : null|bool — résultat de admin() pour le user courant
	 */
	private $_admin_check_cache = NULL;

	public function __construct()
	{
		$this->reload();
	}

	/**
	 * Recharge tout depuis la DB. À appeler après chaque mutation.
	 */
	public function reload()
	{
		$this->_role_perms       = [];
		$this->_role_parents     = [];
		$this->_role_id_by_name  = [];
		$this->_role_name_by_id  = [];
		$this->invalidate_cache();

		// Charger les rôles (id, name, parent)
		$roles = $this->db()->select('role_id', 'name', 'parent_role_id')->from('nf_roles')->get();

		if ($roles === NULL)
		{
			header('HTTP/1.0 503 Service Unavailable');
			exit('Database error: nf_roles unreachable');
		}

		foreach ($roles as $r)
		{
			$rid = (int)$r['role_id'];
			$this->_role_id_by_name[$r['name']]  = $rid;
			$this->_role_name_by_id[$rid]        = $r['name'];
			$this->_role_parents[$rid]           = $r['parent_role_id'] !== NULL ? (int)$r['parent_role_id'] : NULL;
		}

		// Charger les permissions
		foreach ($this->db()->select('role_id', 'permission', 'scope_id', 'authorized')->from('nf_role_permissions')->get() as $rp)
		{
			$this->_role_perms[(int)$rp['role_id']][$rp['permission']][(int)$rp['scope_id']] = $rp['authorized'];
		}
	}

	/**
	 * Invalide les caches "computed" (à appeler quand un user change de groupe/role
	 * ou quand on veut re-vérifier admin sans relancer reload complet).
	 */
	public function invalidate_cache()
	{
		$this->_user_roles_cache = [];
		$this->_admin_check_cache = NULL;
	}

	// ================================================================
	// API publique nouvelle
	// ================================================================

	/**
	 * Vérifie si un user a une permission donnée.
	 *
	 * @param string   $permission Format "module.action" (ex: "forum.category_write")
	 * @param int      $scope      Scope contexte (ex: category_id). 0 = global.
	 * @param int|null $user_id    Si null, vérifie pour le user courant.
	 * @return bool
	 */
	public function can($permission, $scope = 0, $user_id = NULL)
	{
		$scope = (int)$scope;

		// R1.9 — Mode preview : si l'admin a activé "voir comme [role/user]", on override les rôles
		// uniquement quand on check pour le user courant (pas quand on check pour un user explicite).
		if ($user_id === NULL && ($preview = $this->_get_preview_target()))
		{
			if ($preview['type'] === 'role')
			{
				$roles = $this->_with_inheritance([$preview['id']]);
				return $this->_evaluate($roles, $permission, $scope);
			}
			else if ($preview['type'] === 'user')
			{
				if ($this->_user_is_admin_flag($preview['id']))
				{
					return TRUE;
				}
				return $this->_evaluer_membre($this->roles_for_user($preview['id']), $permission, $scope);
			}
		}

		// 1. Bypass super-admin (uniquement si on vérifie le user courant ou un user explicite admin=1)
		if ($user_id === NULL)
		{
			if ($this->user->admin)
			{
				return TRUE;
			}
			$user_id = $this->user() ? (int)$this->user->id : NULL;
		}
		else
		{
			$user_id = (int)$user_id;
			if ($this->_user_is_admin_flag($user_id))
			{
				return TRUE;
			}
		}

		// 2. Calcule rôles effectifs
		if ($user_id === NULL)
		{
			return $this->_evaluate([$this->_default_role_id(FALSE)], $permission, $scope);
		}

		return $this->_evaluer_membre($this->roles_for_user($user_id), $permission, $scope);
	}

	/**
	 * Le droit d'un membre connecté, selon ses rôles : ce qu'ils disent l'emporte — un droit accordé, ou refusé —, et sur
	 * tout ce dont ils ne disent rien, il a les droits d'un membre (rôle « member »). Le rôle « member » ne servait qu'à
	 * qui n'avait AUCUN rôle : un rôle donné en plus (Modérateur) le remplaçait, et un modérateur perdait le forum, la
	 * messagerie, tout ce qu'un membre peut faire (audit du 2026-10-09). Le « never » du rôle de base ne l'emporte pas sur
	 * un rôle qui accorde davantage (écrire dans un forum fermé aux membres, par exemple).
	 */
	private function _evaluer_membre(array $roles, $permission, $scope): bool
	{
		if ($roles && ($decision = $this->_decision($roles, $permission, $scope)) !== NULL)
		{
			return $decision;
		}

		return $this->_evaluate($this->_with_inheritance([$this->_default_role_id(TRUE)]), $permission, $scope);
	}

	/**
	 * Vérifie si UN GROUPE donné aurait la permission (utilisé pour la matrice admin "count" / preview).
	 * Ne tient pas compte de l'user courant ni du bypass admin.
	 *
	 * @param int|string $group_id  Soit un int (group DB), soit un nom de groupe auto ('admins','members','visitors')
	 * @param string     $permission
	 * @param int        $scope
	 * @return bool
	 */
	public function can_for_group($group_id, $permission, $scope = 0)
	{
		// Mapper group_id → role_ids
		// Pour les groupes auto (admins/members/visitors) : map vers les rôles built-in correspondants
		// Pour les groupes DB : look up nf_groups_roles
		$roles = [];

		if (is_string($group_id) && in_array($group_id, ['admins','members','visitors'], TRUE))
		{
			// Mapping pluriel (groupe auto NeoFrag) → singulier (rôle built-in)
			$map = ['admins' => 'super_admin', 'members' => 'member', 'visitors' => 'visitor'];
			$role_name = $map[$group_id];
			if (isset($this->_role_id_by_name[$role_name]))
			{
				$roles[] = $this->_role_id_by_name[$role_name];
			}
		}
		else
		{
			$gid   = (int)$group_id;
			$rows  = $this->db()->select('role_id')->from('nf_groups_roles')->where('group_id', $gid)->get(FALSE);
			$roles = array_map('intval', array_column($rows ?: [], 'role_id'));
		}

		if (empty($roles))
		{
			return FALSE;
		}

		// Inclure inheritance
		$roles = $this->_with_inheritance($roles);

		return $this->_evaluate($roles, $permission, (int)$scope);
	}

	/**
	 * Calcule les rôles effectifs d'un user (direct + via groups + inheritance chain).
	 * Cache par user_id.
	 *
	 * @param int|null $user_id null = visitor anonyme
	 * @return int[] role_ids
	 */
	public function roles_for_user($user_id = NULL)
	{
		if ($user_id === NULL)
		{
			$key = '__visitor__';
		}
		else
		{
			$user_id = (int)$user_id;
			$key = (string)$user_id;
		}

		if (isset($this->_user_roles_cache[$key]))
		{
			return $this->_user_roles_cache[$key];
		}

		$roles = [];

		if ($user_id !== NULL)
		{
			// Direct user → role
			foreach ($this->db()->select('role_id')->from('nf_users_roles')->where('user_id', $user_id)->get(FALSE) as $r)
			{
				$roles[] = (int)$r['role_id'];
			}

			// Via groups : récupère les groups DB du user puis leurs rôles
			$group_ids = [];
			foreach ($this->db()->select('group_id')->from('nf_users_groups')->where('user_id', $user_id)->get(FALSE) as $g)
			{
				$group_ids[] = (int)$g['group_id'];
			}

			if (!empty($group_ids))
			{
				foreach ($this->db()->select('role_id')->from('nf_groups_roles')->where('group_id', $group_ids)->get(FALSE) as $gr)
				{
					$roles[] = (int)$gr['role_id'];
				}
			}

			// Si user.admin=1 → toujours inclure super_admin role en plus du bypass
			$user_is_admin = $this->_user_is_admin_flag($user_id);
			if ($user_is_admin && isset($this->_role_id_by_name['super_admin']))
			{
				$roles[] = $this->_role_id_by_name['super_admin'];
			}
		}

		$roles = array_values(array_unique($roles));

		// Ajouter la chain d'inheritance
		$roles = $this->_with_inheritance($roles);

		return $this->_user_roles_cache[$key] = $roles;
	}

	/**
	 * Liste exhaustive des permissions effectives pour un user (pour la page admin "permissions effectives").
	 * Retourne un tableau associatif : [permission_string][scope_id] = ['allow'|'never'|'default', role_source]
	 */
	public function effective_permissions($user_id)
	{
		$user_id = (int)$user_id;
		$roles   = $this->roles_for_user($user_id);
		$out     = [];

		foreach ($roles as $rid)
		{
			if (!isset($this->_role_perms[$rid])) continue;

			foreach ($this->_role_perms[$rid] as $perm => $scopes)
			{
				foreach ($scopes as $scope => $value)
				{
					// NEVER prend toujours le dessus
					if (!isset($out[$perm][$scope]) || $value === 'never')
					{
						$out[$perm][$scope] = [
							'value'  => $value,
							'source' => $this->_role_name_by_id[$rid] ?? 'role_'.$rid
						];
					}
					// Sinon ALLOW > default
					else if ($value === 'allow' && $out[$perm][$scope]['value'] === 'default')
					{
						$out[$perm][$scope] = [
							'value'  => $value,
							'source' => $this->_role_name_by_id[$rid] ?? 'role_'.$rid
						];
					}
				}
			}
		}

		return $out;
	}

	// ================================================================
	// API publique LEGACY (alias rétro-compat pour ->access('module', 'action', $id))
	// ================================================================

	/**
	 * Alias historique. Garde la signature ->access($module, $action, $id, $group_id, $user_id).
	 * Convertit en appel can() / can_for_group() interne.
	 */
	public function __invoke($module, $action, $id = 0, $group_id = NULL, $user_id = NULL)
	{
		$permission = $module.'.'.$action;
		$scope      = (int)$id;

		if ($group_id !== NULL)
		{
			return $this->can_for_group($group_id, $permission, $scope);
		}

		return $this->can($permission, $scope, $user_id);
	}

	// ================================================================
	// Méthodes héritées (count, admin, init, delete, revoke)
	// ================================================================

	/**
	 * Affichage HTML compteurs autorisés / exclus / visiteurs exclus pour la matrice admin.
	 */
	public function count($module, $action, $id = 0)
	{
		$permission = $module.'.'.$action;
		$count      = [0, 0];

		foreach ($this->db->select('id')->from('nf_user')->where('deleted', '0')->get() as $row)
		{
			$uid = is_array($row) ? (int)$row['id'] : (int)$row;
			$count[(int)$this->can($permission, (int)$id, $uid)]++;
		}

		$output = [];

		if (!empty($count[1]))
		{
			$output[] = '<span class="text-success" data-bs-toggle="tooltip" title="'.$this->lang('Membres autorisés').'">'.icon('fas fa-check').' '.$count[1].'</span>';
		}

		if (!empty($count[0]))
		{
			$output[] = '<span class="text-danger" data-bs-toggle="tooltip" title="'.$this->lang('Membres exclus').'">'.icon('fas fa-ban').' '.$count[0].'</span>';
		}

		if (!$this->can_for_group('visitors', $permission, (int)$id))
		{
			$output[] = '<span class="text-info" data-bs-toggle="tooltip" title="'.$this->lang('Visiteurs exclus').'">'.icon('far fa-eye-slash').'</span>';
		}

		return implode(str_repeat('&nbsp;', 3), $output);
	}

	/**
	 * Détermine si le user courant a accès au panel admin (au moins une perm "admin" => TRUE).
	 * R1.9 : si mode preview actif, l'admin garde l'accès au panel admin (pour pouvoir sortir du preview),
	 * mais les checks can() dans les modules respectent le preview.
	 */
	public function admin()
	{
		if ($this->_admin_check_cache !== NULL)
		{
			return $this->_admin_check_cache;
		}

		$this->_admin_check_cache = FALSE;

		if ($this->user->admin)
		{
			return $this->_admin_check_cache = TRUE;
		}

		foreach ($this->model2('addon')->get('module') as $module)
		{
			if ($module->is_authorized())
			{
				return $this->_admin_check_cache = TRUE;
			}
		}

		return $this->_admin_check_cache;
	}

	/**
	 * Initialise les permissions d'un module au moment de son install (depuis $module->permissions()).
	 * Convention nouvelle : seed les rôles built-in (super_admin, member, visitor) selon le tableau init.
	 *
	 * Format historique de init :
	 *   ['action_name' => [['admins', TRUE], ['visitors', FALSE]], ...]
	 *
	 * Mapping entity → role_name :
	 *   'admins'   → super_admin (mais super_admin a déjà *.* allow donc no-op)
	 *   'members'  → member
	 *   'visitors' → visitor
	 *
	 * @param string $module_name
	 * @param string $type 'default'|'category'|... selon le module
	 * @param int    $id   scope (0 = global)
	 */
	public function init($module_name, $type = 'default', $id = 0)
	{
		$module = $this->module($module_name);
		$access = $module->get_permissions($type);

		if (empty($access['init']))
		{
			return $this;
		}

		$id = (int)$id;

		// Mapping entity historique → role name
		$entity_to_role = [
			'admins'   => 'super_admin',
			'members'  => 'member',
			'visitors' => 'visitor'
		];

		foreach ($access['init'] as $action => $entities)
		{
			$permission = $module_name.'.'.$action;

			/*
			 * Le rôle « member » n'hérite pas du rôle « visitor » : seul, `init` laissait les membres sans droit là où
			 * le NeoFrag d'origine leur en donnait — un membre connecté ne lisait plus le forum ni les galeries, et
			 * n'écrivait nulle part (vu le 2026-10-05 ; la refonte des droits de mai 2026 l'avait perdu). Les règles
			 * d'origine, quand `members` n'est pas nommé : une liste VIDE rend l'action publique (visiteurs et
			 * membres) ; ce qu'un visiteur peut, un membre le peut ; ce qui n'est refusé qu'aux visiteurs reste
			 * permis aux membres.
			 */
			$nommes = array_column((array) $entities, 0);

			if ($nommes === [])
			{
				$entities = [['visitors', TRUE], ['members', TRUE]];
			}
			else if (in_array('visitors', $nommes, TRUE) && !in_array('members', $nommes, TRUE))
			{
				$entities[] = ['members', TRUE];
			}

			foreach ($entities as $entity_pair)
			{
				list($entity, $authorized) = $entity_pair;

				$role_name = $entity_to_role[$entity] ?? null;
				if ($role_name === null || !isset($this->_role_id_by_name[$role_name]))
				{
					continue;
				}

				$role_id = $this->_role_id_by_name[$role_name];
				$value   = $authorized ? 'allow' : 'never';

				// INSERT ... ON DUPLICATE KEY UPDATE
				$this->db->replace('nf_role_permissions', [
					'role_id'    => $role_id,
					'permission' => $permission,
					'scope_id'   => $id,
					'authorized' => $value
				]);
			}
		}

		$this->reload();

		return $this;
	}

	/**
	 * Supprime toutes les permissions d'un module pour un scope donné (utilisé quand un module est uninstall ou catégorie deleted).
	 */
	public function delete($module, $id = 0)
	{
		$prefix = $module.'.';

		// On ne peut pas faire WHERE permission LIKE ... ET scope_id = $id directement sans gestion
		// du prefix dans le builder NeoFrag → on fait un select d'abord puis delete par batch
		$ids = $this->db	->select('role_id', 'permission', 'scope_id')
							->from('nf_role_permissions')
							->where('scope_id', (int)$id)
							->get();

		foreach ($ids as $rp)
		{
			if (strpos($rp['permission'], $prefix) === 0)
			{
				$this->db	->where('role_id',    (int)$rp['role_id'])
							->where('permission', $rp['permission'])
							->where('scope_id',   (int)$rp['scope_id'])
							->delete('nf_role_permissions');
			}
		}

		return $this;
	}

	/**
	 * Révoque toutes les assignations d'un groupe (legacy : appelé quand un groupe DB est supprimé).
	 */
	public function revoke($group_id)
	{
		$this->db	->where('group_id', (int)$group_id)
					->delete('nf_groups_roles');

		return $this;
	}

	// ================================================================
	// Helpers internes
	// ================================================================

	/**
	 * Évalue une permission contre un set de rôles (logique déterministe).
	 *
	 * @param int[]  $role_ids
	 * @param string $permission
	 * @param int    $scope
	 * @return bool
	 */
	private function _evaluate(array $role_ids, $permission, $scope)
	{
		return $this->_decision($role_ids, $permission, $scope) ?? FALSE;
	}

	/** TRUE si les rôles accordent, FALSE si l'un refuse (un seul « never » suffit), NULL s'ils ne disent rien. */
	private function _decision(array $role_ids, $permission, $scope): ?bool
	{
		$dot       = strpos($permission, '.');
		$wildcard  = $dot !== FALSE ? substr($permission, 0, $dot).'.*' : NULL;

		$found_allow = FALSE;

		foreach ($role_ids as $rid)
		{
			// Étape 1 : un seul NEVER (exact/wildcard/global) bloque tout
			if ($this->_lookup($rid, $permission, $scope) === 'never') return FALSE;
			if ($wildcard && $this->_lookup($rid, $wildcard, $scope) === 'never') return FALSE;
			if ($this->_lookup($rid, '*.*', $scope) === 'never') return FALSE;

			if ($scope > 0)
			{
				if ($this->_lookup($rid, $permission, 0) === 'never') return FALSE;
				if ($wildcard && $this->_lookup($rid, $wildcard, 0) === 'never') return FALSE;
				if ($this->_lookup($rid, '*.*', 0) === 'never') return FALSE;
			}

			// Étape 2 : marquage ALLOW (mais on continue à chercher des NEVER)
			if (!$found_allow)
			{
				if ($this->_lookup($rid, $permission, $scope) === 'allow') $found_allow = TRUE;
				else if ($wildcard && $this->_lookup($rid, $wildcard, $scope) === 'allow') $found_allow = TRUE;
				else if ($this->_lookup($rid, '*.*', $scope) === 'allow') $found_allow = TRUE;
				else if ($scope > 0)
				{
					if ($this->_lookup($rid, $permission, 0) === 'allow') $found_allow = TRUE;
					else if ($wildcard && $this->_lookup($rid, $wildcard, 0) === 'allow') $found_allow = TRUE;
					else if ($this->_lookup($rid, '*.*', 0) === 'allow') $found_allow = TRUE;
				}
			}
		}

		return $found_allow ? TRUE : NULL;
	}

	/**
	 * Lookup d'une perm exacte dans un rôle (et sa chaîne d'inheritance via reload qui pré-flatten).
	 * Note : on ne walk PAS l'inheritance ici car _with_inheritance est déjà appelé en amont
	 * dans roles_for_user / can_for_group. Le tableau $role_ids passé à _evaluate inclut déjà les parents.
	 */
	private function _lookup($role_id, $permission, $scope)
	{
		return $this->_role_perms[$role_id][$permission][$scope] ?? NULL;
	}

	/**
	 * Étend un set de rôles avec leur chaîne de parents (single inheritance).
	 *
	 * @param int[] $role_ids
	 * @return int[] avec parents inclus, dédupliqué
	 */
	private function _with_inheritance(array $role_ids)
	{
		$seen = [];

		foreach ($role_ids as $rid)
		{
			$cursor = $rid;
			$depth  = 0;
			while ($cursor !== NULL && !isset($seen[$cursor]) && $depth < 20)
			{
				$seen[$cursor] = TRUE;
				$cursor = $this->_role_parents[$cursor] ?? NULL;
				$depth++;
			}
		}

		return array_keys($seen);
	}

	/**
	 * Lookup direct flag nf_user.admin (sans bypass logic).
	 */
	private function _user_is_admin_flag($user_id)
	{
		static $cache = [];
		$user_id = (int)$user_id;

		if (!array_key_exists($user_id, $cache))
		{
			$row = $this->db()->select('admin')->from('nf_user')->where('id', $user_id)->row(FALSE);
			$cache[$user_id] = is_array($row) && isset($row['admin']) ? ($row['admin'] === '1' || $row['admin'] === 1) : FALSE;
		}

		return $cache[$user_id];
	}

	/**
	 * Retourne le role_id par défaut selon que l'user est authentifié ou pas.
	 */
	private function _default_role_id($is_authenticated)
	{
		$name = $is_authenticated ? 'member' : 'visitor';
		return $this->_role_id_by_name[$name] ?? 0;
	}

	/**
	 * Helper utilisé par les Models (pour mutation depuis l'admin UI).
	 * Garantit un role par nom — utile pour les built-in.
	 */
	public function role_id_by_name($name)
	{
		return $this->_role_id_by_name[$name] ?? NULL;
	}

	public function role_name_by_id($role_id)
	{
		return $this->_role_name_by_id[(int)$role_id] ?? NULL;
	}

	// ================================================================
	// R1.9 — Preview "Voir le site comme [Rôle / User]"
	// (équivalent Discord "View Server As Role")
	// ================================================================

	const PREVIEW_TTL_SECONDS = 1800; // 30 min

	/**
	 * Démarre le mode preview. Override les checks can() pour le user courant.
	 * Réservé aux super-admins (vérifié dans le checker).
	 */
	public function start_preview($type, $target_id, $target_label = NULL)
	{
		if (!in_array($type, ['role', 'user'], TRUE)) return FALSE;

		$payload = [
			'type'       => $type,
			'id'         => (int)$target_id,
			'label'      => $target_label ?: ($type === 'role' ? 'role #'.(int)$target_id : 'user #'.(int)$target_id),
			'started_at' => time(),
			'started_by' => $this->user() ? (int)$this->user->id : NULL
		];

		NeoFrag()->session->set('access_preview', 'data', $payload);

		if (NeoFrag()->events ?? NULL)
		{
			NeoFrag()->events->fire('permissions.preview.started', $payload);
		}

		return TRUE;
	}

	public function exit_preview()
	{
		$current = $this->_get_preview_target();

		NeoFrag()->session->destroy('access_preview', 'data');

		if (NeoFrag()->events ?? NULL)
		{
			NeoFrag()->events->fire('permissions.preview.ended', $current ?: ['type' => NULL]);
		}

		return TRUE;
	}

	/**
	 * Retourne les infos de preview actif pour le user courant.
	 * Auto-exit si TTL dépassé.
	 *
	 * @return array|null ['type', 'id', 'label', 'started_at', 'started_by'] ou null
	 */
	public function get_preview_target()
	{
		return $this->_get_preview_target();
	}

	private function _get_preview_target()
	{
		// Cache static par requête
		static $cached = NULL;
		static $resolved = FALSE;

		if ($resolved) return $cached;

		// Le bypass admin est REQUIS pour activer un preview ; donc check ici
		if (!$this->user->admin)
		{
			$resolved = TRUE;
			return $cached = NULL;
		}

		$payload = NeoFrag()->session('access_preview', 'data');
		if (!is_array($payload) || empty($payload['type']) || empty($payload['id']))
		{
			$resolved = TRUE;
			return $cached = NULL;
		}

		// TTL : auto-exit après 30 min
		if (!empty($payload['started_at']) && (time() - (int)$payload['started_at']) > self::PREVIEW_TTL_SECONDS)
		{
			NeoFrag()->session->destroy('access_preview', 'data');
			$resolved = TRUE;
			return $cached = NULL;
		}

		$resolved = TRUE;
		return $cached = $payload;
	}

	/**
	 * R1.9 — Equivalent "effective" du flag user->admin qui RESPECTE le mode preview.
	 *
	 * Utiliser CETTE méthode partout où on veut savoir "est-ce que le user effectif (compte tenu
	 * du preview) a les pouvoirs super-admin ?" pour cacher menus admin, boutons, etc.
	 *
	 * Diff avec $this->user->admin (lecture directe) :
	 * - Hors preview : retourne $this->user->admin (comportement legacy)
	 * - En preview type=user : retourne TRUE seulement si le target est lui-même admin
	 * - En preview type=role : retourne TRUE seulement si le rôle est super_admin (ou hérite de super_admin)
	 *
	 * Pour les endpoints qui DOIVENT rester admin-only même pendant le preview (sortie de preview,
	 * gestion preview elle-même), utiliser $this->user->admin directement.
	 */
	public function effective_admin()
	{
		if (!isset($this->user) || !$this->user())
		{
			return FALSE;
		}

		$preview = $this->_get_preview_target();

		if ($preview === NULL)
		{
			return (bool)$this->user->admin;
		}

		if ($preview['type'] === 'user')
		{
			return $this->_user_is_admin_flag((int)$preview['id']);
		}

		if ($preview['type'] === 'role')
		{
			$super = $this->_role_id_by_name['super_admin'] ?? NULL;
			if ($super === NULL) return FALSE;
			$roles = $this->_with_inheritance([(int)$preview['id']]);
			return in_array($super, array_map('intval', $roles), TRUE);
		}

		return FALSE;
	}

	/**
	 * R1.9 — Retourne un objet user "effectif" pour AFFICHAGE seulement (avatar, username) en mode preview.
	 *
	 * - Hors preview : retourne NULL (le caller doit fallback sur $this->user)
	 * - Preview type=user : retourne l'objet User du target
	 * - Preview type=role : retourne NULL (pas de user spécifique — afficher juste le nom du rôle)
	 *
	 * /!\ DOIT être utilisé UNIQUEMENT pour de l'affichage public (avatar, username).
	 * NE PAS utiliser pour récupérer email/préférences/MP du target — ce serait une violation de vie privée.
	 * Les controllers d'actions personnelles (account, talks, profile edit) doivent TOUJOURS utiliser
	 * $this->user (admin réel), jamais cette méthode.
	 */
	public function preview_user()
	{
		$preview = $this->_get_preview_target();

		if ($preview === NULL || $preview['type'] !== 'user')
		{
			return NULL;
		}

		$user_module = NeoFrag()->module('user');
		if (!$user_module)
		{
			return NULL;
		}

		$user = $user_module->model2('user', (int)$preview['id']);

		if (!$user || empty($user->id) || !empty($user->deleted))
		{
			return NULL;
		}

		return $user;
	}

	/**
	 * R1.9 — Label humain du target preview (pour affichage UI).
	 * Retourne le username pour type=user, le nom du rôle pour type=role, NULL hors preview.
	 */
	public function preview_label()
	{
		$preview = $this->_get_preview_target();
		return $preview ? ($preview['label'] ?? NULL) : NULL;
	}
}
