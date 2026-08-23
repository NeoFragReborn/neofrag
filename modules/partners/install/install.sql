-- NeoFrag Reborn — install du module « partners » — tables propres au module.
-- Généré par tools/extract-module-sql.php depuis la base vive. NE PAS éditer à la main.
-- Régénérer : docker compose exec -T web php tools/extract-module-sql.php

SET FOREIGN_KEY_CHECKS = 0;
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `nf_partners` (
  `partner_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `logo_light` int(11) unsigned DEFAULT NULL,
  `logo_dark` int(11) unsigned DEFAULT NULL,
  `website` varchar(100) NOT NULL,
  `facebook` varchar(100) NOT NULL,
  `twitter` varchar(100) NOT NULL,
  `code` varchar(50) NOT NULL,
  `count` int(11) unsigned NOT NULL DEFAULT 0,
  `order` tinyint(6) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`partner_id`),
  KEY `image_id` (`logo_light`),
  KEY `logo_dark` (`logo_dark`),
  CONSTRAINT `nf_partners_ibfk_1` FOREIGN KEY (`logo_light`) REFERENCES `nf_file` (`id`) ON DELETE SET NULL ON UPDATE SET NULL,
  CONSTRAINT `nf_partners_ibfk_2` FOREIGN KEY (`logo_dark`) REFERENCES `nf_file` (`id`) ON DELETE SET NULL ON UPDATE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `nf_partners_lang` (
  `partner_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `lang` varchar(5) NOT NULL,
  `title` varchar(100) NOT NULL,
  `description` mediumtext NOT NULL,
  PRIMARY KEY (`partner_id`),
  KEY `lang` (`lang`),
  CONSTRAINT `nf_partners_lang_ibfk_1` FOREIGN KEY (`partner_id`) REFERENCES `nf_partners` (`partner_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
