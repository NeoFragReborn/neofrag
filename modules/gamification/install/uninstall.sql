-- NeoFrag Reborn — désinstall du module « gamification » — supprime ses tables (données perdues).
-- Généré par tools/extract-module-sql.php depuis la base vive. NE PAS éditer à la main.
-- Régénérer : docker compose exec -T web php tools/extract-module-sql.php

SET FOREIGN_KEY_CHECKS = 0;
SET NAMES utf8mb4;

DROP TABLE IF EXISTS `nf_vip`;
DROP TABLE IF EXISTS `nf_points_log`;
DROP TABLE IF EXISTS `nf_karma`;
DROP TABLE IF EXISTS `nf_user_points`;

SET FOREIGN_KEY_CHECKS = 1;
