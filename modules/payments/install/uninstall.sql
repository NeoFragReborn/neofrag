-- NeoFrag Reborn — désinstall du module « payments » — supprime ses tables (données perdues).
-- Généré par tools/extract-module-sql.php depuis la base vive. NE PAS éditer à la main.
-- Régénérer : docker compose exec -T web php tools/extract-module-sql.php

SET FOREIGN_KEY_CHECKS = 0;
SET NAMES utf8mb4;

DROP TABLE IF EXISTS `nf_payments`;
DROP TABLE IF EXISTS `nf_payment_packs`;

DROP TABLE IF EXISTS `nf_payment_packs_lang`;

DROP TABLE IF EXISTS `nf_adhesions`;

SET FOREIGN_KEY_CHECKS = 1;
