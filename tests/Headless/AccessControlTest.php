<?php
declare(strict_types=1);

namespace NF\Tests\Headless;

/**
 * Tests d'OBJET du contrôle d'accès (ACL). La décision vit 100 % en PHP en mémoire
 * (Access::_evaluate/_lookup sur la map _role_perms), donc INtestable en miroir SQL —
 * c'était la cible « impossible » signalée au §4. Le harnais headless câble le service
 * `access`, on insère des fixtures rôle/groupe/permission, on `reload()`, puis on exerce
 * la vraie logique de décision via can_for_group() (sans user courant ni bypass admin).
 *
 * Couvre : grant exact, wildcard `module.*`, override NEVER (deny gagne sur allow),
 * et fallback de scope (scope>0 retombe sur le grant global scope 0).
 */
final class AccessControlTest extends HeadlessTestCase
{
	private function createRole(): int
	{
		return (int) $this->db()->insert('nf_roles', [
			'name'  => 'role_'.substr(md5(uniqid('', true)), 0, 12),
			'title' => 'Test Role',
		]);
	}

	private function groupForRole(int $role_id): int
	{
		$gid = (int) $this->db()->insert('nf_groups', [
			'name'  => 'grp_'.substr(md5(uniqid('', true)), 0, 12),
			'color' => 'secondary',
			'icon'  => 'fas fa-users',
		]);
		$this->db()->insert('nf_groups_roles', ['group_id' => $gid, 'role_id' => $role_id]);

		return $gid;
	}

	private function grant(int $role_id, string $permission, string $authorized = 'allow', int $scope = 0): void
	{
		$this->db()->insert('nf_role_permissions', [
			'role_id'    => $role_id,
			'permission' => $permission,
			'scope_id'   => $scope,
			'authorized' => $authorized,
		]);
	}

	/** Recharge le cache ACL depuis la base (voit les fixtures de la transaction) et renvoie le service. */
	private function access()
	{
		\NeoFrag()->access->reload();

		return \NeoFrag()->access;
	}

	public function test_exact_permission_grant(): void
	{
		$role  = $this->createRole();
		$group = $this->groupForRole($role);
		$this->grant($role, 'testmod.read', 'allow');

		$access = $this->access();
		$this->assertTrue($access->can_for_group($group, 'testmod.read', 0), 'Permission exacte accordée.');
		$this->assertFalse($access->can_for_group($group, 'testmod.write', 0), 'Action non accordée → refus.');
	}

	public function test_wildcard_module_permission_covers_actions(): void
	{
		$role  = $this->createRole();
		$group = $this->groupForRole($role);
		$this->grant($role, 'wild.*', 'allow');

		$access = $this->access();
		$this->assertTrue($access->can_for_group($group, 'wild.anything', 0), 'Le wildcard module.* couvre toute action du module.');
		$this->assertFalse($access->can_for_group($group, 'other.anything', 0), 'Le wildcard ne déborde pas sur un autre module.');
	}

	public function test_never_overrides_allow(): void
	{
		$role  = $this->createRole();
		$group = $this->groupForRole($role);
		// Le module entier est autorisé (wildcard) MAIS une action précise est explicitement interdite.
		$this->grant($role, 'mod.*', 'allow');
		$this->grant($role, 'mod.secret', 'never');

		$access = $this->access();
		$this->assertFalse($access->can_for_group($group, 'mod.secret', 0), 'NEVER explicite l\'emporte sur le allow wildcard.');
		$this->assertTrue($access->can_for_group($group, 'mod.public', 0), 'Les autres actions du module restent autorisées.');
	}

	public function test_scope_falls_back_to_global_grant(): void
	{
		$role  = $this->createRole();
		$group = $this->groupForRole($role);
		// Accordé en GLOBAL (scope 0) uniquement.
		$this->grant($role, 'scoped.act', 'allow', 0);

		$access = $this->access();
		$this->assertTrue($access->can_for_group($group, 'scoped.act', 5), 'Un scope spécifique retombe sur le grant global (scope 0).');
	}
}
