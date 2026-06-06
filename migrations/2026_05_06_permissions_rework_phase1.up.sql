-- Refonte permissions Phase 1 (R1.1) — création nouveau schéma + migration data.
-- Voir docs/audits/2026-05-05/ et plan agile-coalescing-lecun.md.
--
-- Bugs fixés en passant :
-- - 15 perms moderation orphelines (id=group_id, no details) → rôles inopérants : recréées correctement
-- - Logique "default majoritaire" obscure : remplacée par sémantique ternaire allow/never/default explicite
--
-- Cette migration fait tout en une transaction :
-- 1. Backup (rename) ancien schéma
-- 2. Crée 5 nouvelles tables
-- 3. Seed rôles built-in (super_admin, member, visitor)
-- 4. Crée rôles depuis groupes existants (moderator_junior, moderator_senior avec inheritance)
-- 5. Migre les permissions de _backup_nf_access JOIN _backup_nf_access_details
-- 6. Recrée les permissions modération orphelines (fix bug 2026-05-04)
-- 7. Migre les assignations users via nf_users_groups (vide actuellement, mais code en place)
-- 8. Assigne tous les nf_user.admin=1 au rôle super_admin
--
-- Vérification post-migration : tools/verify_permissions_migration.php (lancé séparément)

-- ================================================================
-- 1) Backup des tables actuelles
-- ================================================================

RENAME TABLE nf_access         TO _backup_nf_access_2026_05_06;
RENAME TABLE nf_access_details TO _backup_nf_access_details_2026_05_06;

-- ================================================================
-- 2) Nouveau schéma
-- ================================================================

CREATE TABLE nf_roles (
	role_id        INT UNSIGNED NOT NULL AUTO_INCREMENT,
	name           VARCHAR(100) NOT NULL,
	title          VARCHAR(100) NOT NULL,
	description    TEXT,
	color          VARCHAR(20)  NOT NULL DEFAULT 'secondary',
	icon           VARCHAR(50)  NOT NULL DEFAULT 'fas fa-user-shield',
	parent_role_id INT UNSIGNED NULL,
	built_in       TINYINT(1)   NOT NULL DEFAULT 0,
	`order`        SMALLINT UNSIGNED NOT NULL DEFAULT 0,
	created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (role_id),
	UNIQUE KEY uniq_name (name),
	KEY idx_parent (parent_role_id),
	CONSTRAINT fk_roles_parent FOREIGN KEY (parent_role_id) REFERENCES nf_roles(role_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

CREATE TABLE nf_roles_lang (
	role_id     INT UNSIGNED NOT NULL,
	lang        VARCHAR(5)   NOT NULL,
	title       VARCHAR(100) NOT NULL,
	description TEXT,
	PRIMARY KEY (role_id, lang),
	CONSTRAINT fk_roles_lang_role FOREIGN KEY (role_id) REFERENCES nf_roles(role_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

CREATE TABLE nf_role_permissions (
	role_id    INT UNSIGNED NOT NULL,
	permission VARCHAR(100) NOT NULL,                    -- "module.action" ou "module.*"
	scope_id   INT UNSIGNED NOT NULL DEFAULT 0,           -- 0 = global
	authorized ENUM('allow','never','default') NOT NULL DEFAULT 'allow',
	PRIMARY KEY (role_id, permission, scope_id),
	KEY idx_perm (permission),
	KEY idx_perm_scope (permission, scope_id),
	CONSTRAINT fk_rp_role FOREIGN KEY (role_id) REFERENCES nf_roles(role_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

CREATE TABLE nf_users_roles (
	user_id INT UNSIGNED NOT NULL,
	role_id INT UNSIGNED NOT NULL,
	PRIMARY KEY (user_id, role_id),
	KEY idx_role (role_id),
	CONSTRAINT fk_ur_user FOREIGN KEY (user_id) REFERENCES nf_user(id)        ON DELETE CASCADE,
	CONSTRAINT fk_ur_role FOREIGN KEY (role_id) REFERENCES nf_roles(role_id)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

CREATE TABLE nf_groups_roles (
	group_id INT UNSIGNED NOT NULL,
	role_id  INT UNSIGNED NOT NULL,
	PRIMARY KEY (group_id, role_id),
	KEY idx_role (role_id),
	CONSTRAINT fk_gr_group FOREIGN KEY (group_id) REFERENCES nf_groups(group_id) ON DELETE CASCADE,
	CONSTRAINT fk_gr_role  FOREIGN KEY (role_id)  REFERENCES nf_roles(role_id)   ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- ================================================================
-- 3) Seed des rôles built-in (correspondent aux groupes auto admins/members/visitors)
-- ================================================================

INSERT INTO nf_roles (name, title, description, color, icon, built_in, `order`) VALUES
	('super_admin', 'Super Administrateur', 'Accès complet au système. Équivalent au flag nf_user.admin=1.', 'danger',  'fas fa-rocket',  1, 1),
	('member',      'Membre',               'Utilisateur connecté de base.',                                    'success', 'fas fa-user',    1, 90),
	('visitor',     'Visiteur',             'Utilisateur non authentifié (anonyme).',                           'info',    'fas fa-eye',     1, 100);

INSERT INTO nf_roles_lang (role_id, lang, title, description)
SELECT role_id, 'fr', title, description FROM nf_roles WHERE name IN ('super_admin','member','visitor');

INSERT INTO nf_roles_lang (role_id, lang, title, description) VALUES
	((SELECT role_id FROM nf_roles WHERE name='super_admin'), 'en', 'Super Administrator', 'Full system access. Equivalent to nf_user.admin=1 flag.'),
	((SELECT role_id FROM nf_roles WHERE name='member'),      'en', 'Member',              'Standard authenticated user.'),
	((SELECT role_id FROM nf_roles WHERE name='visitor'),     'en', 'Visitor',             'Anonymous (non-authenticated) user.');

-- super_admin a le wildcard *.* allow (en plus du bypass nf_user.admin=1 qui court-circuite quand même)
INSERT INTO nf_role_permissions (role_id, permission, scope_id, authorized)
SELECT role_id, '*.*', 0, 'allow' FROM nf_roles WHERE name='super_admin';

-- ================================================================
-- 4) Création des rôles depuis groupes existants (moderation_junior, moderation_senior)
-- ================================================================

-- moderator_junior (pas de parent, 1er rôle de la hiérarchie modération)
INSERT INTO nf_roles (name, title, description, color, icon, built_in, `order`)
SELECT
	g.name,
	IFNULL(gl.title, g.name),
	CONCAT('Rôle migré depuis le groupe nf_groups id ', g.group_id, '.'),
	g.color, g.icon, 0, 50
FROM nf_groups g
LEFT JOIN nf_groups_lang gl ON gl.group_id = g.group_id AND gl.lang = 'fr'
WHERE g.name = 'moderation_junior';

-- moderator_senior (inherits moderator_junior)
INSERT INTO nf_roles (name, title, description, color, icon, built_in, `order`, parent_role_id)
SELECT
	g.name,
	IFNULL(gl.title, g.name),
	CONCAT('Rôle migré depuis le groupe nf_groups id ', g.group_id, '. Hérite de moderation_junior.'),
	g.color, g.icon, 0, 51,
	(SELECT role_id FROM nf_roles WHERE name='moderation_junior')
FROM nf_groups g
LEFT JOIN nf_groups_lang gl ON gl.group_id = g.group_id AND gl.lang = 'fr'
WHERE g.name = 'moderation_senior';

-- Migration des langs depuis nf_groups_lang
INSERT INTO nf_roles_lang (role_id, lang, title, description)
SELECT r.role_id, gl.lang, gl.title, NULL
FROM nf_groups g
JOIN nf_groups_lang gl ON gl.group_id = g.group_id
JOIN nf_roles r ON r.name = g.name
WHERE g.name IN ('moderation_junior','moderation_senior');

-- ================================================================
-- 5) Migration des permissions depuis _backup_nf_access JOIN _backup_nf_access_details
-- ================================================================
--
-- Mapping entity (string dans backup) → role_id (nouveau) :
-- 'admins'             → super_admin
-- 'members'            → member
-- 'visitors'           → visitor
-- '1' (group_id 1)     → moderation_junior
-- '2' (group_id 2)     → moderation_senior
--
-- Mapping authorized :
-- '1' → 'allow'
-- '0' → 'never'

INSERT INTO nf_role_permissions (role_id, permission, scope_id, authorized)
SELECT
	CASE ad.entity
		WHEN 'admins'   THEN (SELECT role_id FROM nf_roles WHERE name='super_admin')
		WHEN 'members'  THEN (SELECT role_id FROM nf_roles WHERE name='member')
		WHEN 'visitors' THEN (SELECT role_id FROM nf_roles WHERE name='visitor')
		WHEN '1'        THEN (SELECT role_id FROM nf_roles WHERE name='moderation_junior')
		WHEN '2'        THEN (SELECT role_id FROM nf_roles WHERE name='moderation_senior')
		ELSE NULL
	END AS role_id,
	CONCAT(a.module, '.', a.action) AS permission,
	a.id AS scope_id,
	CASE ad.authorized WHEN '1' THEN 'allow' ELSE 'never' END AS authorized
FROM _backup_nf_access_2026_05_06 a
JOIN _backup_nf_access_details_2026_05_06 ad ON a.access_id = ad.access_id
WHERE ad.type = 'group'
  AND ad.entity IN ('admins','members','visitors','1','2')
ON DUPLICATE KEY UPDATE authorized = VALUES(authorized);

-- ================================================================
-- 6) FIX BUG : recréer les permissions modération depuis la migration cassée
-- ================================================================
-- Les 15 rows orphelines moderation (id=1 ou id=2 = group_id, sans details) sont remplacées par
-- des perms correctes attribuées aux rôles moderator_junior/moderator_senior avec scope_id=0 (global).
-- Liste issue de migrations/2026_05_04_moderation_roles.up.sql.

INSERT IGNORE INTO nf_role_permissions (role_id, permission, scope_id, authorized) VALUES
	-- moderator_junior : 6 perms
	((SELECT role_id FROM nf_roles WHERE name='moderation_junior'), 'moderation.view_reports',   0, 'allow'),
	((SELECT role_id FROM nf_roles WHERE name='moderation_junior'), 'moderation.handle_reports', 0, 'allow'),
	((SELECT role_id FROM nf_roles WHERE name='moderation_junior'), 'moderation.warn',           0, 'allow'),
	((SELECT role_id FROM nf_roles WHERE name='moderation_junior'), 'moderation.mute',           0, 'allow'),
	((SELECT role_id FROM nf_roles WHERE name='moderation_junior'), 'moderation.restrict',       0, 'allow'),
	((SELECT role_id FROM nf_roles WHERE name='moderation_junior'), 'moderation.mediation',      0, 'allow'),
	-- moderator_senior : 3 perms additionnelles (les 6 du junior sont héritées via parent_role_id)
	((SELECT role_id FROM nf_roles WHERE name='moderation_senior'), 'moderation.ban_temp',       0, 'allow'),
	((SELECT role_id FROM nf_roles WHERE name='moderation_senior'), 'moderation.approve',        0, 'allow'),
	((SELECT role_id FROM nf_roles WHERE name='moderation_senior'), 'moderation.access_private', 0, 'allow');

-- ================================================================
-- 7) Migration des assignations User → Role
-- ================================================================
-- a) Pour chaque user dans nf_users_groups (actuellement vide mais code en place pour le futur)
INSERT INTO nf_users_roles (user_id, role_id)
SELECT ug.user_id, r.role_id
FROM nf_users_groups ug
JOIN nf_groups g ON g.group_id = ug.group_id
JOIN nf_roles r  ON r.name = g.name
ON DUPLICATE KEY UPDATE role_id = VALUES(role_id);

-- b) Tous les nf_user.admin=1 reçoivent le rôle super_admin (en plus du bypass nf_user.admin)
INSERT IGNORE INTO nf_users_roles (user_id, role_id)
SELECT u.id, (SELECT role_id FROM nf_roles WHERE name='super_admin')
FROM nf_user u
WHERE u.admin = '1' AND u.deleted = '0';

-- ================================================================
-- 8) Audit log de la migration
-- ================================================================
INSERT INTO nf_audit_log (user_id, username, action, target_type, details, ip_address, success)
VALUES (
	NULL,
	'system',
	'permissions.migration.completed',
	'system',
	JSON_OBJECT(
		'migration', '2026_05_06_permissions_rework_phase1',
		'roles_created', (SELECT COUNT(*) FROM nf_roles),
		'permissions_migrated', (SELECT COUNT(*) FROM nf_role_permissions),
		'users_assigned', (SELECT COUNT(DISTINCT user_id) FROM nf_users_roles),
		'note', 'fix bug moderation_roles 2026-05-04 + sémantique allow/never/default + Roles autonomes'
	),
	'127.0.0.1',
	1
);
