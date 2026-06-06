-- NeoFrag Reborn — désinstall du module « newsletter » — supprime ses tables (données perdues).
-- Généré par tools/extract-module-sql.php depuis la base vive. NE PAS éditer à la main.
-- Régénérer : docker compose exec -T web php tools/extract-module-sql.php

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `nf_newsletter_subscribers`;
DROP TABLE IF EXISTS `nf_newsletter_campaigns`;

SET FOREIGN_KEY_CHECKS = 1;
