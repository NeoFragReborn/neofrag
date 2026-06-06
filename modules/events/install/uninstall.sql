-- NeoFrag Reborn — désinstall du module « events » — supprime ses tables (données perdues).
-- Généré par tools/extract-module-sql.php depuis la base vive. NE PAS éditer à la main.
-- Régénérer : docker compose exec -T web php tools/extract-module-sql.php

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `nf_events_matches_rounds`;
DROP TABLE IF EXISTS `nf_events_matches_opponents`;
DROP TABLE IF EXISTS `nf_events_matches`;
DROP TABLE IF EXISTS `nf_events_participants`;
DROP TABLE IF EXISTS `nf_events_types`;
DROP TABLE IF EXISTS `nf_events`;

SET FOREIGN_KEY_CHECKS = 1;
