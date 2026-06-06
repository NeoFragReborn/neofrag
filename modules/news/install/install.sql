-- NeoFrag Reborn — install du module « news » — tables propres au module.
-- Généré par tools/extract-module-sql.php depuis la base vive. NE PAS éditer à la main.
-- Régénérer : docker compose exec -T web php tools/extract-module-sql.php

SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS `nf_news` (
  `news_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `category_id` int(11) unsigned NOT NULL,
  `user_id` int(11) unsigned NOT NULL,
  `image_id` int(11) unsigned DEFAULT NULL,
  `date` timestamp NOT NULL DEFAULT current_timestamp(),
  `published` enum('0','1') NOT NULL DEFAULT '0',
  `announced_at` timestamp NULL DEFAULT NULL,
  `views` int(11) unsigned NOT NULL DEFAULT 0,
  `vote` enum('0','1') NOT NULL DEFAULT '0',
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_by` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`news_id`),
  KEY `category_id` (`category_id`),
  KEY `user_id` (`user_id`),
  KEY `image_id` (`image_id`),
  KEY `idx_deleted_at` (`deleted_at`),
  KEY `idx_schedule` (`published`,`announced_at`,`date`),
  CONSTRAINT `nf_news_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `nf_user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `nf_news_ibfk_2` FOREIGN KEY (`image_id`) REFERENCES `nf_file` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `nf_news_ibfk_4` FOREIGN KEY (`category_id`) REFERENCES `nf_news_categories` (`category_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_uca1400_ai_ci;

CREATE TABLE IF NOT EXISTS `nf_news_lang` (
  `news_id` int(11) unsigned NOT NULL,
  `lang` varchar(5) NOT NULL,
  `title` varchar(100) NOT NULL,
  `introduction` text NOT NULL,
  `content` text NOT NULL,
  `tags` text NOT NULL,
  PRIMARY KEY (`news_id`,`lang`),
  KEY `lang` (`lang`),
  CONSTRAINT `nf_news_lang_ibfk_1` FOREIGN KEY (`news_id`) REFERENCES `nf_news` (`news_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_uca1400_ai_ci;

CREATE TABLE IF NOT EXISTS `nf_news_categories` (
  `category_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `image_id` int(11) unsigned DEFAULT NULL,
  `icon_id` int(11) unsigned DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  PRIMARY KEY (`category_id`),
  KEY `image_id` (`image_id`),
  KEY `icon_id` (`icon_id`),
  CONSTRAINT `nf_news_categories_ibfk_1` FOREIGN KEY (`image_id`) REFERENCES `nf_file` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `nf_news_categories_ibfk_2` FOREIGN KEY (`icon_id`) REFERENCES `nf_file` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_uca1400_ai_ci;

CREATE TABLE IF NOT EXISTS `nf_news_categories_lang` (
  `category_id` int(11) unsigned NOT NULL,
  `lang` varchar(5) NOT NULL,
  `title` varchar(100) NOT NULL,
  PRIMARY KEY (`category_id`,`lang`),
  KEY `lang` (`lang`),
  CONSTRAINT `nf_news_categories_lang_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `nf_news_categories` (`category_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_uca1400_ai_ci;

SET FOREIGN_KEY_CHECKS = 1;
