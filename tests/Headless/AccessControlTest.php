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

	/** Le rôle intégré d'un nom, ou le test sauté s'il manque à la base. */
	private function role(string $nom): int
	{
		$id = (int) $this->db()->select('role_id')->from('nf_roles')->where('name', $nom)->row();

		if (!$id)
		{
			$this->markTestSkipped("rôle « {$nom} » absent de la base de test");
		}

		return $id;
	}

	/**
	 * Les droits que `init` sème pour un forum neuf (2026-10-05). Le rôle « member » n'héritant pas du rôle
	 * « visitor », un membre connecté ne lisait plus le forum et n'y écrivait plus ; les règles d'origine sont
	 * rétablies : ce qu'un visiteur peut, un membre le peut ; ce qui n'est refusé qu'aux visiteurs reste permis
	 * aux membres ; ce qui est réservé aux administrateurs le reste.
	 */
	public function test_init_donne_aux_membres_ce_que_l_origine_leur_donnait(): void
	{
		$this->role('member');
		$scope  = 900000 + random_int(1, 99999);
		$access = $this->access();
		$access->init('forum', 'category', $scope);

		$this->assertTrue($access->can_for_group('visitors', 'forum.category_read', $scope));
		$this->assertTrue($access->can_for_group('members', 'forum.category_read', $scope), 'Un membre lit ce qu\'un visiteur lit.');
		$this->assertFalse($access->can_for_group('visitors', 'forum.category_write', $scope));
		$this->assertTrue($access->can_for_group('members', 'forum.category_write', $scope), 'Refusé aux seuls visiteurs : permis aux membres.');
		$this->assertFalse($access->can_for_group('members', 'forum.category_modify', $scope), 'Réservé aux administrateurs.');
	}

	/** Une liste vide dans `init` rend l'action publique, comme à l'origine : visiteurs et membres. */
	public function test_init_liste_vide_rend_l_action_publique(): void
	{
		$this->role('member');
		$scope  = 900000 + random_int(1, 99999);
		$access = $this->access();
		$access->init('gallery', 'gallery', $scope);

		$this->assertTrue($access->can_for_group('visitors', 'gallery.gallery_see', $scope));
		$this->assertTrue($access->can_for_group('members', 'gallery.gallery_see', $scope));
		$this->assertFalse($access->can_for_group('members', 'gallery.gallery_post', $scope), 'Réservé aux administrateurs.');
	}

	/** La migration des sites existants : les règles des visiteurs gagnent leur double pour les membres, sans rien écraser. */
	public function test_la_migration_donne_aux_membres_les_droits_des_visiteurs(): void
	{
		$membre   = $this->role('member');
		$visiteur = $this->role('visitor');
		$a = 800000 + random_int(1, 49999);
		$b = $a + 50000;

		$this->grant($visiteur, 'forum.category_read', 'allow', $a);
		$this->grant($visiteur, 'forum.category_write', 'never', $a);
		$this->grant($visiteur, 'forum.category_read', 'allow', $b);
		$this->grant($membre, 'forum.category_read', 'never', $b);   // une règle déjà posée pour les membres

		$resultat = $this->db()->import((string) file_get_contents(NEOFRAG_CMS.'/migrations/2026_10_05_droits_des_membres.up.sql'));
		$this->assertTrue($resultat === TRUE, 'La migration s\'exécute : '.(is_string($resultat) ? $resultat : ''));

		$access = $this->access();
		$this->assertTrue($access->can_for_group('members', 'forum.category_read', $a), 'Le membre lit ce que le visiteur lit.');
		$this->assertTrue($access->can_for_group('members', 'forum.category_write', $a), 'Il écrit là où seuls les visiteurs sont refusés.');
		$this->assertFalse($access->can_for_group('members', 'forum.category_read', $b), 'Une règle des membres déjà posée reste.');
	}
}
