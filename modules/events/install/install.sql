-- NeoFrag Reborn — install du module « events » — tables propres au module.
-- Généré par tools/extract-module-sql.php depuis la base vive. NE PAS éditer à la main.
-- Régénérer : docker compose exec -T web php tools/extract-module-sql.php

SET FOREIGN_KEY_CHECKS = 0;
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `nf_events` (
  `event_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `type_id` int(11) unsigned NOT NULL,
  `user_id` int(11) unsigned NOT NULL,
  `image_id` int(11) unsigned DEFAULT NULL,
  `title` varchar(100) NOT NULL,
  `description` mediumtext NOT NULL,
  `private_description` mediumtext NOT NULL,
  `location` mediumtext NOT NULL,
  -- datetime pour les dates d'événement et de masquage (toutes FUTURES) : TIMESTAMP plafonne au
  -- 19/01/2038 → un événement au-delà est rejeté en mode strict. `reminder_sent_at` reste TIMESTAMP
  -- (marqueur « rappel envoyé le », toujours ~présent).
  `date` datetime NOT NULL DEFAULT current_timestamp(),
  `date_end` datetime NULL DEFAULT NULL,
  `published` enum('0','1') NOT NULL DEFAULT '0',
  `publish_date` datetime NULL DEFAULT NULL,
  `reminder_sent_at` timestamp NULL DEFAULT NULL,
  `series_id` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`event_id`),
  KEY `user_id` (`user_id`),
  KEY `type_id` (`type_id`) USING BTREE,
  KEY `image_id` (`image_id`),
  KEY `idx_series` (`series_id`),
  CONSTRAINT `nf_events_ibfk_1` FOREIGN KEY (`type_id`) REFERENCES `nf_events_types` (`type_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `nf_events_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `nf_user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `nf_events_ibfk_3` FOREIGN KEY (`image_id`) REFERENCES `nf_file` (`id`) ON DELETE SET NULL ON UPDATE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `nf_events_types` (
  `type_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `type` smallint(5) unsigned NOT NULL DEFAULT 0,
  `title` varchar(100) NOT NULL,
  `color` varchar(20) NOT NULL,
  `icon` varchar(50) NOT NULL,
  PRIMARY KEY (`type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `nf_events_participants` (
  `event_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `status` smallint(6) unsigned NOT NULL,
  PRIMARY KEY (`event_id`,`user_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `nf_events_participants_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `nf_events` (`event_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `nf_events_participants_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `nf_user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `nf_events_matches` (
  `event_id` int(11) unsigned NOT NULL,
  `team_id` int(11) unsigned NOT NULL,
  `opponent_id` int(11) unsigned NOT NULL,
  `mode_id` int(11) unsigned DEFAULT NULL,
  `webtv` varchar(100) NOT NULL,
  `website` varchar(100) NOT NULL,
  PRIMARY KEY (`event_id`),
  KEY `opponent_id` (`opponent_id`),
  KEY `mode_id` (`mode_id`),
  KEY `team_id` (`team_id`),
  CONSTRAINT `nf_events_matches_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `nf_events` (`event_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `nf_events_matches_ibfk_2` FOREIGN KEY (`opponent_id`) REFERENCES `nf_events_matches_opponents` (`opponent_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `nf_events_matches_opponents` (
  `opponent_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `image_id` int(11) unsigned DEFAULT NULL,
  `title` varchar(100) NOT NULL,
  `website` varchar(100) NOT NULL,
  `country` varchar(5) NOT NULL,
  PRIMARY KEY (`opponent_id`),
  KEY `image_id` (`image_id`),
  CONSTRAINT `nf_events_matches_opponents_ibfk_1` FOREIGN KEY (`image_id`) REFERENCES `nf_file` (`id`) ON DELETE SET NULL ON UPDATE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `nf_events_matches_rounds` (
  `round_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `event_id` int(11) unsigned NOT NULL,
  `map_id` int(11) unsigned DEFAULT NULL,
  `score1` int(11) NOT NULL,
  `score2` int(11) NOT NULL,
  PRIMARY KEY (`round_id`),
  KEY `event_id` (`event_id`),
  KEY `map_id` (`map_id`),
  CONSTRAINT `nf_events_matches_rounds_ibfk_2` FOREIGN KEY (`event_id`) REFERENCES `nf_events` (`event_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
