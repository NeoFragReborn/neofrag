-- NeoFrag Reborn — install du module « forum » — tables propres au module.
-- Généré par tools/extract-module-sql.php depuis la base vive. NE PAS éditer à la main.
-- Régénérer : docker compose exec -T web php tools/extract-module-sql.php

SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS `nf_forum` (
  `forum_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) unsigned NOT NULL,
  `is_subforum` enum('0','1') NOT NULL DEFAULT '0',
  `title` varchar(100) NOT NULL,
  `description` varchar(255) NOT NULL DEFAULT '',
  `order` smallint(6) unsigned NOT NULL DEFAULT 0,
  `count_topics` int(11) unsigned NOT NULL DEFAULT 0,
  `count_messages` int(11) unsigned NOT NULL DEFAULT 0,
  `last_message_id` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`forum_id`),
  KEY `last_message_id` (`last_message_id`),
  CONSTRAINT `nf_forum_ibfk_1` FOREIGN KEY (`last_message_id`) REFERENCES `nf_forum_messages` (`message_id`) ON DELETE SET NULL ON UPDATE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_uca1400_ai_ci;

CREATE TABLE IF NOT EXISTS `nf_forum_categories` (
  `category_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(100) NOT NULL,
  `order` smallint(6) unsigned DEFAULT 0,
  `image_id` int(11) unsigned DEFAULT NULL,
  `vip_only` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_uca1400_ai_ci;

CREATE TABLE IF NOT EXISTS `nf_forum_topics` (
  `topic_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `forum_id` int(11) unsigned NOT NULL,
  `message_id` int(11) unsigned DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `status` enum('-2','-1','0','1') NOT NULL DEFAULT '0',
  `views` int(11) unsigned NOT NULL DEFAULT 0,
  `count_messages` int(11) unsigned NOT NULL DEFAULT 0,
  `last_message_id` int(11) unsigned DEFAULT NULL,
  `is_announced` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `is_locked` tinyint(1) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`topic_id`),
  UNIQUE KEY `last_message_id` (`last_message_id`),
  KEY `forum_id` (`forum_id`),
  KEY `message_id` (`message_id`),
  FULLTEXT KEY `ft_title` (`title`),
  CONSTRAINT `nf_forum_topics_ibfk_1` FOREIGN KEY (`forum_id`) REFERENCES `nf_forum` (`forum_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `nf_forum_topics_ibfk_2` FOREIGN KEY (`message_id`) REFERENCES `nf_forum_messages` (`message_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `nf_forum_topics_ibfk_3` FOREIGN KEY (`last_message_id`) REFERENCES `nf_forum_messages` (`message_id`) ON DELETE SET NULL ON UPDATE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_uca1400_ai_ci;

CREATE TABLE IF NOT EXISTS `nf_forum_messages` (
  `message_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `topic_id` int(11) unsigned NOT NULL,
  `parent_id` int(11) unsigned DEFAULT NULL,
  `user_id` int(11) unsigned DEFAULT NULL,
  `message` text DEFAULT NULL,
  `date` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_by` int(11) unsigned DEFAULT NULL,
  `deleted_reason` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`message_id`),
  KEY `topic_id` (`topic_id`),
  KEY `user_id` (`user_id`),
  KEY `idx_topic_date` (`topic_id`,`date`),
  KEY `idx_parent` (`parent_id`),
  KEY `idx_deleted_at` (`deleted_at`),
  FULLTEXT KEY `ft_message` (`message`),
  CONSTRAINT `nf_forum_messages_ibfk_1` FOREIGN KEY (`topic_id`) REFERENCES `nf_forum_topics` (`topic_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `nf_forum_messages_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `nf_user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `nf_forum_messages_parent_fk` FOREIGN KEY (`parent_id`) REFERENCES `nf_forum_messages` (`message_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_uca1400_ai_ci;

CREATE TABLE IF NOT EXISTS `nf_forum_attachments` (
  `attachment_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `message_id` int(11) unsigned NOT NULL,
  `file_id` int(11) unsigned NOT NULL,
  `file_size` int(11) unsigned NOT NULL,
  `mime_type` varchar(100) NOT NULL,
  PRIMARY KEY (`attachment_id`),
  KEY `idx_message` (`message_id`),
  KEY `idx_file` (`file_id`),
  CONSTRAINT `nf_forum_attachments_file_fk` FOREIGN KEY (`file_id`) REFERENCES `nf_file` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `nf_forum_attachments_message_fk` FOREIGN KEY (`message_id`) REFERENCES `nf_forum_messages` (`message_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_uca1400_ai_ci;

CREATE TABLE IF NOT EXISTS `nf_forum_mentions` (
  `mention_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `message_id` int(11) unsigned NOT NULL,
  `mentioned_user_id` int(11) unsigned NOT NULL,
  `mentioner_user_id` int(11) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `read_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`mention_id`),
  KEY `idx_message` (`message_id`),
  KEY `idx_mentioned_unread` (`mentioned_user_id`,`read_at`),
  KEY `nf_forum_mentions_mentioner_fk` (`mentioner_user_id`),
  CONSTRAINT `nf_forum_mentions_mentioner_fk` FOREIGN KEY (`mentioner_user_id`) REFERENCES `nf_user` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `nf_forum_mentions_message_fk` FOREIGN KEY (`message_id`) REFERENCES `nf_forum_messages` (`message_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `nf_forum_mentions_target_fk` FOREIGN KEY (`mentioned_user_id`) REFERENCES `nf_user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_uca1400_ai_ci;

CREATE TABLE IF NOT EXISTS `nf_forum_read` (
  `user_id` int(11) unsigned NOT NULL,
  `forum_id` int(11) unsigned NOT NULL DEFAULT 0,
  `date` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`user_id`,`forum_id`),
  CONSTRAINT `nf_forum_read_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `nf_user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_uca1400_ai_ci;

CREATE TABLE IF NOT EXISTS `nf_forum_topics_read` (
  `topic_id` int(11) unsigned NOT NULL,
  `user_id` int(11) unsigned NOT NULL,
  `date` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`topic_id`,`user_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `nf_forum_topics_read_ibfk_1` FOREIGN KEY (`topic_id`) REFERENCES `nf_forum_topics` (`topic_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `nf_forum_topics_read_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `nf_user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_uca1400_ai_ci;

CREATE TABLE IF NOT EXISTS `nf_forum_track` (
  `topic_id` int(11) unsigned NOT NULL,
  `user_id` int(11) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_notified_at` timestamp NULL DEFAULT NULL,
  `type` enum('topic','forum') NOT NULL DEFAULT 'topic',
  PRIMARY KEY (`topic_id`,`user_id`),
  KEY `user_id` (`user_id`),
  KEY `idx_user_type` (`user_id`,`type`),
  CONSTRAINT `nf_forum_track_ibfk_1` FOREIGN KEY (`topic_id`) REFERENCES `nf_forum_topics` (`topic_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `nf_forum_track_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `nf_user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_uca1400_ai_ci;

CREATE TABLE IF NOT EXISTS `nf_forum_url` (
  `forum_id` int(11) unsigned NOT NULL,
  `url` varchar(500) NOT NULL,
  `redirects` int(11) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`forum_id`),
  CONSTRAINT `nf_forum_url_ibfk_1` FOREIGN KEY (`forum_id`) REFERENCES `nf_forum` (`forum_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_uca1400_ai_ci;

SET FOREIGN_KEY_CHECKS = 1;
