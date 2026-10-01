-- NeoFrag Reborn — désinstall du module « forum » — supprime ses tables (données perdues).
-- Généré par tools/extract-module-sql.php depuis la base vive. NE PAS éditer à la main.
-- Régénérer : php tools/extract-module-sql.php

SET FOREIGN_KEY_CHECKS = 0;
SET NAMES utf8mb4;

DROP TABLE IF EXISTS `nf_forum_url`;
DROP TABLE IF EXISTS `nf_forum_track`;
DROP TABLE IF EXISTS `nf_forum_topics_read`;
DROP TABLE IF EXISTS `nf_forum_read`;
DROP TABLE IF EXISTS `nf_forum_mentions`;
DROP TABLE IF EXISTS `nf_forum_attachments`;
DROP TABLE IF EXISTS `nf_forum_messages`;
DROP TABLE IF EXISTS `nf_forum_topics`;
DROP TABLE IF EXISTS `nf_forum_categories_lang`;
DROP TABLE IF EXISTS `nf_forum_categories`;
DROP TABLE IF EXISTS `nf_forum_lang`;
DROP TABLE IF EXISTS `nf_forum`;

SET FOREIGN_KEY_CHECKS = 1;
