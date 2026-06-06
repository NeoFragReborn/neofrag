-- NeoFrag Reborn — désinstall du module « gallery » — supprime ses tables (données perdues).
-- Généré par tools/extract-module-sql.php depuis la base vive. NE PAS éditer à la main.
-- Régénérer : docker compose exec -T web php tools/extract-module-sql.php

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `nf_gallery_images`;
DROP TABLE IF EXISTS `nf_gallery_categories_lang`;
DROP TABLE IF EXISTS `nf_gallery_categories`;
DROP TABLE IF EXISTS `nf_gallery_lang`;
DROP TABLE IF EXISTS `nf_gallery`;

SET FOREIGN_KEY_CHECKS = 1;
