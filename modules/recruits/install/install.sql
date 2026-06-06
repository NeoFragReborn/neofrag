-- NeoFrag Reborn — install du module « recruits » — tables propres au module.
-- Généré par tools/extract-module-sql.php depuis la base vive. NE PAS éditer à la main.
-- Régénérer : docker compose exec -T web php tools/extract-module-sql.php

SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS `nf_recruits` (
  `recruit_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(100) NOT NULL,
  `introduction` text NOT NULL,
  `description` text NOT NULL,
  `requierments` text NOT NULL,
  `date` timestamp NOT NULL DEFAULT current_timestamp(),
  `user_id` int(11) unsigned NOT NULL,
  `size` int(11) NOT NULL,
  `role` varchar(60) NOT NULL,
  `icon` varchar(60) NOT NULL,
  `date_end` date DEFAULT NULL,
  `closed` enum('0','1') NOT NULL DEFAULT '0',
  `team_id` int(11) unsigned DEFAULT NULL,
  `image_id` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`recruit_id`),
  KEY `image_id` (`image_id`),
  KEY `user_id` (`user_id`),
  KEY `team_id` (`team_id`),
  CONSTRAINT `nf_recruits_ibfk_1` FOREIGN KEY (`team_id`) REFERENCES `nf_teams` (`team_id`) ON DELETE SET NULL ON UPDATE SET NULL,
  CONSTRAINT `nf_recruits_ibfk_2` FOREIGN KEY (`image_id`) REFERENCES `nf_file` (`id`) ON DELETE SET NULL ON UPDATE SET NULL,
  CONSTRAINT `nf_recruits_ibfk_3` FOREIGN KEY (`user_id`) REFERENCES `nf_user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_uca1400_ai_ci;

CREATE TABLE IF NOT EXISTS `nf_recruits_fields` (
  `field_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `recruit_id` int(11) unsigned NOT NULL,
  `label` varchar(200) NOT NULL,
  `type` enum('text','textarea') NOT NULL DEFAULT 'text',
  `required` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` smallint(5) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`field_id`),
  KEY `idx_recruit` (`recruit_id`),
  CONSTRAINT `fk_recruits_fields_recruit` FOREIGN KEY (`recruit_id`) REFERENCES `nf_recruits` (`recruit_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

CREATE TABLE IF NOT EXISTS `nf_recruits_candidacies` (
  `candidacy_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `recruit_id` int(11) unsigned NOT NULL,
  `date` timestamp NOT NULL DEFAULT current_timestamp(),
  `user_id` int(11) unsigned DEFAULT NULL,
  `pseudo` varchar(60) NOT NULL,
  `email` varchar(100) NOT NULL,
  `date_of_birth` date DEFAULT NULL,
  `presentation` text NOT NULL,
  `motivations` text NOT NULL,
  `experiences` text NOT NULL,
  `status` enum('1','2','3') NOT NULL DEFAULT '1',
  `reply` text DEFAULT NULL,
  `custom` longtext DEFAULT NULL,
  PRIMARY KEY (`candidacy_id`),
  KEY `recruit_id` (`recruit_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `nf_recruits_candidacies_ibfk_1` FOREIGN KEY (`recruit_id`) REFERENCES `nf_recruits` (`recruit_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `nf_recruits_candidacies_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `nf_user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_uca1400_ai_ci;

CREATE TABLE IF NOT EXISTS `nf_recruits_candidacies_votes` (
  `vote_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `candidacy_id` int(11) unsigned NOT NULL,
  `user_id` int(11) unsigned NOT NULL,
  `vote` enum('0','1') NOT NULL DEFAULT '0',
  `comment` text NOT NULL,
  PRIMARY KEY (`vote_id`),
  KEY `candidacy_id` (`candidacy_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `nf_recruits_candidacies_votes_ibfk_1` FOREIGN KEY (`candidacy_id`) REFERENCES `nf_recruits_candidacies` (`candidacy_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `nf_recruits_candidacies_votes_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `nf_user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_uca1400_ai_ci;

SET FOREIGN_KEY_CHECKS = 1;
