ALTER TABLE `nf_forum_messages` DROP KEY `idx_identity`;
ALTER TABLE `nf_forum_messages` DROP COLUMN `identity_id`;
DROP TABLE IF EXISTS `nf_forum_identities`;
