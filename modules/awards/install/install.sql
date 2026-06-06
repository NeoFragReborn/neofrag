-- NeoFrag Reborn — install du module « awards » — tables propres au module.
-- Généré par tools/extract-module-sql.php depuis la base vive. NE PAS éditer à la main.
-- Régénérer : docker compose exec -T web php tools/extract-module-sql.php

SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS `nf_awards` (
  `award_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `team_id` int(11) unsigned DEFAULT NULL,
  `game_id` int(11) unsigned NOT NULL,
  `image_id` int(11) unsigned DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `location` varchar(100) NOT NULL,
  `date` date NOT NULL,
  `description` text NOT NULL,
  `platform` varchar(100) NOT NULL,
  `ranking` int(11) unsigned NOT NULL,
  `participants` int(11) unsigned NOT NULL,
  PRIMARY KEY (`award_id`),
  KEY `image_id` (`image_id`),
  KEY `game_id` (`game_id`),
  KEY `team_id` (`team_id`),
  CONSTRAINT `nf_awards_ibfk_1` FOREIGN KEY (`team_id`) REFERENCES `nf_teams` (`team_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `nf_awards_ibfk_2` FOREIGN KEY (`game_id`) REFERENCES `nf_games` (`game_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `nf_awards_ibfk_3` FOREIGN KEY (`image_id`) REFERENCES `nf_file` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_uca1400_ai_ci;

SET FOREIGN_KEY_CHECKS = 1;
