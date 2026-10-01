DROP TABLE IF EXISTS `nf_forum_prefixes_lang`;
DROP TABLE IF EXISTS `nf_forum_prefixes`;
ALTER TABLE `nf_forum_topics` DROP COLUMN `solution_message_id`;
ALTER TABLE `nf_forum_topics` DROP COLUMN `prefix_id`;
ALTER TABLE `nf_forum` DROP COLUMN `icon`;
