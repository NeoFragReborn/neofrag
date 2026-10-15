-- NeoFrag Reborn — install du module « collectives » — tables propres au module.
-- Généré par tools/extract-module-sql.php depuis la base vive. NE PAS éditer à la main.
-- Régénérer : docker compose exec -T web php tools/extract-module-sql.php

SET FOREIGN_KEY_CHECKS = 0;
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `nf_collectives` (
  `collective_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `category_id` int(11) unsigned DEFAULT NULL,
  `role_id` int(10) unsigned DEFAULT NULL,
  `role_moderation_id` int(10) unsigned DEFAULT NULL,
  `discord` tinyint(1) NOT NULL DEFAULT 1,
  `discord_channel_id` varchar(32) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`collective_id`),
  KEY `role_id` (`role_id`),
  KEY `role_moderation_id` (`role_moderation_id`),
  CONSTRAINT `nf_collectives_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `nf_roles` (`role_id`) ON DELETE SET NULL,
  CONSTRAINT `nf_collectives_ibfk_2` FOREIGN KEY (`role_moderation_id`) REFERENCES `nf_roles` (`role_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `nf_collectives_members` (
  `collective_id` int(11) unsigned NOT NULL,
  `user_id` int(11) unsigned NOT NULL,
  `rank` varchar(16) NOT NULL DEFAULT 'membre',
  `added_by` int(11) unsigned DEFAULT NULL,
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`collective_id`,`user_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `nf_collectives_members_ibfk_1` FOREIGN KEY (`collective_id`) REFERENCES `nf_collectives` (`collective_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `nf_collectives_members_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `nf_user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
