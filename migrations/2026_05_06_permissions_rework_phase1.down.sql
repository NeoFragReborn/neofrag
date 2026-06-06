-- Rollback Refonte permissions Phase 1.
-- Drop les 5 nouvelles tables + restore nf_access et nf_access_details depuis backups.

-- 1) Drop dans l'ordre inverse des FK
DROP TABLE IF EXISTS nf_groups_roles;
DROP TABLE IF EXISTS nf_users_roles;
DROP TABLE IF EXISTS nf_role_permissions;
DROP TABLE IF EXISTS nf_roles_lang;
DROP TABLE IF EXISTS nf_roles;

-- 2) Restore via renommage inverse
DROP TABLE IF EXISTS nf_access;
DROP TABLE IF EXISTS nf_access_details;

RENAME TABLE _backup_nf_access_2026_05_06         TO nf_access;
RENAME TABLE _backup_nf_access_details_2026_05_06 TO nf_access_details;
