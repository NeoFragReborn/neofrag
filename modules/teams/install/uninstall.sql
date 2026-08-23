-- NeoFrag Reborn — désinstall du module « teams » — supprime ses tables (données perdues).
-- Généré par tools/extract-module-sql.php depuis la base vive. NE PAS éditer à la main.
-- Régénérer : docker compose exec -T web php tools/extract-module-sql.php

SET FOREIGN_KEY_CHECKS = 0;
SET NAMES utf8mb4;

DROP TABLE IF EXISTS `nf_teams_users`;
DROP TABLE IF EXISTS `nf_teams_roles`;
DROP TABLE IF EXISTS `nf_teams_lang`;
DROP TABLE IF EXISTS `nf_teams`;

SET FOREIGN_KEY_CHECKS = 1;
