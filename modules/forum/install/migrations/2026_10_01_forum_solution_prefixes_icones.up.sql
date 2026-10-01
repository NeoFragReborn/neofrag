-- Le forum se termine (2026-10-01) : une icone choisie par forum, un prefixe par sujet
-- (resolu, en cours, important…, traduisibles), et la reponse marquee « solution » d'un sujet.

ALTER TABLE `nf_forum` ADD COLUMN `icon` varchar(60) NOT NULL DEFAULT '' AFTER `description`;
ALTER TABLE `nf_forum_topics` ADD COLUMN `prefix_id` int(11) unsigned DEFAULT NULL AFTER `status`;
ALTER TABLE `nf_forum_topics` ADD COLUMN `solution_message_id` int(11) unsigned DEFAULT NULL AFTER `prefix_id`;

CREATE TABLE IF NOT EXISTS `nf_forum_prefixes` (
  `prefix_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(50) NOT NULL,
  `color` varchar(20) NOT NULL DEFAULT 'secondary',
  `order` smallint(6) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`prefix_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `nf_forum_prefixes_lang` (
  `prefix_id` int(11) unsigned NOT NULL,
  `lang` varchar(5) NOT NULL,
  `title` varchar(50) NOT NULL DEFAULT '',
  PRIMARY KEY (`prefix_id`,`lang`),
  CONSTRAINT `nf_forum_prefixes_lang_ibfk_1` FOREIGN KEY (`prefix_id`) REFERENCES `nf_forum_prefixes` (`prefix_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
