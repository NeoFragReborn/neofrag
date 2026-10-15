-- NeoFrag Reborn — install du module « faq » — tables propres au module.
-- Généré par tools/extract-module-sql.php depuis la base vive. NE PAS éditer à la main.
-- Régénérer : docker compose exec -T web php tools/extract-module-sql.php

SET FOREIGN_KEY_CHECKS = 0;
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `nf_faq_categories` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `nf_faq_categories_lang` (
  `category_id` int(10) unsigned NOT NULL,
  `lang` varchar(5) NOT NULL,
  `title` varchar(150) NOT NULL DEFAULT '',
  PRIMARY KEY (`category_id`,`lang`),
  KEY `lang` (`lang`),
  CONSTRAINT `nf_faq_categories_lang_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `nf_faq_categories` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `nf_faq_questions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `category_id` int(10) unsigned NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `published` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_cat` (`category_id`,`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `nf_faq_questions_lang` (
  `question_id` int(10) unsigned NOT NULL,
  `lang` varchar(5) NOT NULL,
  `question` varchar(500) NOT NULL DEFAULT '',
  `answer` mediumtext NOT NULL,
  PRIMARY KEY (`question_id`,`lang`),
  KEY `lang` (`lang`),
  CONSTRAINT `nf_faq_questions_lang_ibfk_1` FOREIGN KEY (`question_id`) REFERENCES `nf_faq_questions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
