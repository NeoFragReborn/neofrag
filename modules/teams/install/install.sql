-- NeoFrag Reborn — install du module « teams » — tables propres au module.
-- Généré par tools/extract-module-sql.php depuis la base vive. NE PAS éditer à la main.
-- Régénérer : docker compose exec -T web php tools/extract-module-sql.php

SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS `nf_teams` (
  `team_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `game_id` int(11) unsigned NOT NULL,
  `image_id` int(11) unsigned DEFAULT NULL,
  `icon_id` int(11) unsigned DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `order` smallint(6) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`team_id`),
  KEY `activity_id` (`game_id`),
  KEY `image_id` (`image_id`),
  KEY `icon_id` (`icon_id`),
  CONSTRAINT `nf_teams_ibfk_1` FOREIGN KEY (`image_id`) REFERENCES `nf_file` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `nf_teams_ibfk_2` FOREIGN KEY (`icon_id`) REFERENCES `nf_file` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `nf_teams_ibfk_3` FOREIGN KEY (`game_id`) REFERENCES `nf_games` (`game_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_uca1400_ai_ci;

CREATE TABLE IF NOT EXISTS `nf_teams_lang` (
  `team_id` int(11) unsigned NOT NULL,
  `lang` varchar(5) NOT NULL,
  `title` varchar(100) NOT NULL,
  `description` text NOT NULL,
  PRIMARY KEY (`team_id`,`lang`),
  KEY `lang` (`lang`),
  CONSTRAINT `nf_teams_lang_ibfk_2` FOREIGN KEY (`team_id`) REFERENCES `nf_teams` (`team_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_uca1400_ai_ci;

CREATE TABLE IF NOT EXISTS `nf_teams_roles` (
  `role_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(100) NOT NULL,
  `order` smallint(6) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_uca1400_ai_ci;

CREATE TABLE IF NOT EXISTS `nf_teams_users` (
  `team_id` int(11) unsigned NOT NULL,
  `user_id` int(11) unsigned NOT NULL,
  `role_id` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`team_id`,`user_id`),
  KEY `user_id` (`user_id`),
  KEY `role_id` (`role_id`),
  CONSTRAINT `nf_teams_users_ibfk_1` FOREIGN KEY (`team_id`) REFERENCES `nf_teams` (`team_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `nf_teams_users_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `nf_user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `nf_teams_users_ibfk_3` FOREIGN KEY (`role_id`) REFERENCES `nf_teams_roles` (`role_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_uca1400_ai_ci;

SET FOREIGN_KEY_CHECKS = 1;
