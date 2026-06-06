-- NeoFrag Reborn — install du module « games » — tables propres au module.
-- Généré par tools/extract-module-sql.php depuis la base vive. NE PAS éditer à la main.
-- Régénérer : docker compose exec -T web php tools/extract-module-sql.php

SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS `nf_games` (
  `game_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) unsigned DEFAULT NULL,
  `image_id` int(11) unsigned DEFAULT NULL,
  `icon_id` int(11) unsigned DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  PRIMARY KEY (`game_id`),
  KEY `image_id` (`image_id`),
  KEY `icon_id` (`icon_id`),
  KEY `parent_id` (`parent_id`),
  CONSTRAINT `nf_games_ibfk_1` FOREIGN KEY (`image_id`) REFERENCES `nf_file` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `nf_games_ibfk_2` FOREIGN KEY (`icon_id`) REFERENCES `nf_file` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `nf_games_ibfk_3` FOREIGN KEY (`parent_id`) REFERENCES `nf_games` (`game_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_uca1400_ai_ci;

CREATE TABLE IF NOT EXISTS `nf_games_lang` (
  `game_id` int(11) unsigned NOT NULL,
  `lang` varchar(5) NOT NULL,
  `title` varchar(100) NOT NULL,
  PRIMARY KEY (`game_id`,`lang`),
  KEY `lang` (`lang`),
  CONSTRAINT `nf_games_lang_ibfk_1` FOREIGN KEY (`game_id`) REFERENCES `nf_games` (`game_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_uca1400_ai_ci;

CREATE TABLE IF NOT EXISTS `nf_games_maps` (
  `map_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `game_id` int(11) unsigned NOT NULL,
  `image_id` int(11) unsigned DEFAULT NULL,
  `title` varchar(100) NOT NULL,
  PRIMARY KEY (`map_id`),
  KEY `game_id` (`game_id`),
  KEY `image_id` (`image_id`),
  CONSTRAINT `nf_games_maps_ibfk_1` FOREIGN KEY (`game_id`) REFERENCES `nf_games` (`game_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `nf_games_maps_ibfk_2` FOREIGN KEY (`image_id`) REFERENCES `nf_file` (`id`) ON DELETE SET NULL ON UPDATE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_uca1400_ai_ci;

CREATE TABLE IF NOT EXISTS `nf_games_modes` (
  `mode_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `game_id` int(11) unsigned NOT NULL,
  `title` varchar(100) NOT NULL,
  PRIMARY KEY (`mode_id`),
  KEY `game_id` (`game_id`),
  CONSTRAINT `nf_games_modes_ibfk_1` FOREIGN KEY (`game_id`) REFERENCES `nf_games` (`game_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_uca1400_ai_ci;

SET FOREIGN_KEY_CHECKS = 1;
