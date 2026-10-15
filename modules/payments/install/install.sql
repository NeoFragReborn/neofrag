-- NeoFrag Reborn — install du module « payments » — tables propres au module.
-- Généré par tools/extract-module-sql.php depuis la base vive. NE PAS éditer à la main.
-- Régénérer : docker compose exec -T web php tools/extract-module-sql.php

SET FOREIGN_KEY_CHECKS = 0;
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `nf_payment_packs` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `kind` enum('points','vip','adhesion') NOT NULL DEFAULT 'points',
  `units` int(11) NOT NULL DEFAULT 0,
  `price_cents` int(11) NOT NULL DEFAULT 0,
  `currency` varchar(3) NOT NULL DEFAULT 'eur',
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `position` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_active` (`active`,`position`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `nf_payments` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `event_id` varchar(255) NOT NULL DEFAULT '',
  `session_id` varchar(255) NOT NULL DEFAULT '',
  `user_id` int(11) unsigned DEFAULT NULL,
  `kind` varchar(10) NOT NULL DEFAULT '',
  `units` int(11) NOT NULL DEFAULT 0,
  `status` varchar(20) NOT NULL DEFAULT 'completed',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_event` (`event_id`),
  KEY `idx_user` (`user_id`),
  CONSTRAINT `fk_payment_user` FOREIGN KEY (`user_id`) REFERENCES `nf_user` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `nf_payment_packs_lang` (
  `pack_id` int(11) unsigned NOT NULL,
  `lang` varchar(5) NOT NULL,
  `label` varchar(150) NOT NULL DEFAULT '',
  PRIMARY KEY (`pack_id`,`lang`),
  KEY `lang` (`lang`),
  CONSTRAINT `nf_payment_packs_lang_ibfk_1` FOREIGN KEY (`pack_id`) REFERENCES `nf_payment_packs` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `nf_adhesions` (
  `user_id` int(11) unsigned NOT NULL,
  `expires_at` datetime NOT NULL,
  `source` varchar(50) NOT NULL DEFAULT '',
  `reminded_at` datetime DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`user_id`),
  KEY `idx_expires` (`expires_at`),
  CONSTRAINT `fk_adhesion_user` FOREIGN KEY (`user_id`) REFERENCES `nf_user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
