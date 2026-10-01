-- NeoFrag Reborn — désinstall du module « api » — supprime ses tables (données perdues).
-- Généré par tools/extract-module-sql.php depuis la base vive. NE PAS éditer à la main.
-- Régénérer : php tools/extract-module-sql.php

SET FOREIGN_KEY_CHECKS = 0;
SET NAMES utf8mb4;

DROP TABLE IF EXISTS `nf_api_events`;
DROP TABLE IF EXISTS `nf_api_tokens`;

SET FOREIGN_KEY_CHECKS = 1;
