-- NeoFrag schéma de référence CŒUR LEAN (Tier 0) — structure des tables cœur + historique des migrations.
-- Généré par tools/dump-schema.php depuis la base vive (filtrée par tier). NE PAS éditer à la main.
-- Régénérer : docker compose exec web php tools/dump-schema.php

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET NAMES utf8mb4;

DROP TABLE IF EXISTS `nf_addon`;
CREATE TABLE `nf_addon` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `type_id` int(11) unsigned DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `data` mediumtext DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`,`type_id`),
  KEY `type_id` (`type_id`),
  CONSTRAINT `nf_addon_ibfk_1` FOREIGN KEY (`type_id`) REFERENCES `nf_addon_type` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `nf_addon_type`;
CREATE TABLE `nf_addon_type` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `nf_user`;
CREATE TABLE `nf_user` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL DEFAULT '',
  `salt` varchar(32) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `registration_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_activity_date` timestamp NULL DEFAULT NULL,
  `admin` enum('0','1') NOT NULL DEFAULT '0',
  `language` int(10) unsigned DEFAULT NULL,
  `data` mediumtext NOT NULL,
  `deleted` enum('0','1') NOT NULL DEFAULT '0',
  `totp_secret` varchar(255) DEFAULT NULL,
  `totp_enabled` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `username` (`username`),
  KEY `email` (`email`),
  KEY `language` (`language`),
  KEY `deleted` (`deleted`),
  CONSTRAINT `nf_user_ibfk_1` FOREIGN KEY (`language`) REFERENCES `nf_addon` (`id`) ON DELETE SET NULL ON UPDATE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `nf_user_auth`;
CREATE TABLE `nf_user_auth` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL,
  `authenticator_id` int(11) unsigned NOT NULL,
  `key` varchar(100) NOT NULL,
  `username` varchar(100) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`,`authenticator_id`,`key`),
  UNIQUE KEY `uk_authenticator_key` (`authenticator_id`,`key`),
  KEY `authenticator_id` (`authenticator_id`),
  CONSTRAINT `nf_user_auth_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `nf_user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `nf_user_auth_ibfk_2` FOREIGN KEY (`authenticator_id`) REFERENCES `nf_addon` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `nf_user_profile`;
CREATE TABLE `nf_user_profile` (
  `id` int(11) unsigned NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `avatar` int(11) unsigned DEFAULT NULL,
  `cover` int(11) unsigned DEFAULT NULL,
  `signature` mediumtext NOT NULL,
  `date_of_birth` date DEFAULT NULL,
  `sex` enum('male','female') DEFAULT NULL,
  `country` varchar(100) NOT NULL,
  `timezone` varchar(64) NOT NULL DEFAULT '',
  `location` varchar(100) NOT NULL,
  `quote` varchar(100) NOT NULL,
  `website` varchar(100) NOT NULL,
  `linkedin` varchar(100) NOT NULL,
  `github` varchar(100) NOT NULL,
  `instagram` varchar(100) NOT NULL,
  `twitch` varchar(100) NOT NULL,
  `montrer_points` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `montrer_karma` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `montrer_vip` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `montrer_age` tinyint(1) unsigned NOT NULL DEFAULT 1,
  `montrer_statut` tinyint(1) unsigned NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `avatar` (`avatar`),
  KEY `cover` (`cover`),
  CONSTRAINT `nf_user_profile_ibfk_2` FOREIGN KEY (`avatar`) REFERENCES `nf_file` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `nf_user_profile_ibfk_3` FOREIGN KEY (`id`) REFERENCES `nf_user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `nf_user_profile_ibfk_4` FOREIGN KEY (`cover`) REFERENCES `nf_file` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `nf_user_token`;
CREATE TABLE `nf_user_token` (
  `id` varchar(32) NOT NULL,
  `user_id` int(11) unsigned NOT NULL,
  `date` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `nf_user_token_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `nf_user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `nf_user_totp_recovery`;
CREATE TABLE `nf_user_totp_recovery` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `code_hash` varchar(255) NOT NULL,
  `used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  CONSTRAINT `nf_user_totp_recovery_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `nf_user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `nf_users_groups`;
CREATE TABLE `nf_users_groups` (
  `user_id` int(11) unsigned NOT NULL,
  `group_id` int(11) unsigned NOT NULL,
  PRIMARY KEY (`user_id`,`group_id`),
  KEY `group_id` (`group_id`),
  CONSTRAINT `nf_users_groups_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `nf_user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `nf_users_groups_ibfk_2` FOREIGN KEY (`group_id`) REFERENCES `nf_groups` (`group_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `nf_users_roles`;
CREATE TABLE `nf_users_roles` (
  `user_id` int(10) unsigned NOT NULL,
  `role_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`user_id`,`role_id`),
  KEY `idx_role` (`role_id`),
  CONSTRAINT `fk_ur_role` FOREIGN KEY (`role_id`) REFERENCES `nf_roles` (`role_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ur_user` FOREIGN KEY (`user_id`) REFERENCES `nf_user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `nf_groups`;
CREATE TABLE `nf_groups` (
  `group_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `color` varchar(20) NOT NULL,
  `icon` varchar(50) NOT NULL,
  `hidden` enum('0','1') NOT NULL DEFAULT '0',
  `auto` enum('0','1') NOT NULL DEFAULT '0',
  `order` smallint(6) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `nf_groups_lang`;
CREATE TABLE `nf_groups_lang` (
  `group_id` int(11) unsigned NOT NULL,
  `lang` varchar(5) NOT NULL,
  `title` varchar(100) NOT NULL,
  PRIMARY KEY (`group_id`,`lang`),
  KEY `lang` (`lang`),
  CONSTRAINT `nf_groups_lang_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `nf_groups` (`group_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `nf_groups_roles`;
CREATE TABLE `nf_groups_roles` (
  `group_id` int(10) unsigned NOT NULL,
  `role_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`group_id`,`role_id`),
  KEY `idx_role` (`role_id`),
  CONSTRAINT `fk_gr_group` FOREIGN KEY (`group_id`) REFERENCES `nf_groups` (`group_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_gr_role` FOREIGN KEY (`role_id`) REFERENCES `nf_roles` (`role_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `nf_roles`;
CREATE TABLE `nf_roles` (
  `role_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `title` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `color` varchar(20) NOT NULL DEFAULT 'secondary',
  `icon` varchar(50) NOT NULL DEFAULT 'fas fa-user-shield',
  `parent_role_id` int(10) unsigned DEFAULT NULL,
  `built_in` tinyint(1) NOT NULL DEFAULT 0,
  `order` smallint(5) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`role_id`),
  UNIQUE KEY `uniq_name` (`name`),
  KEY `idx_parent` (`parent_role_id`),
  CONSTRAINT `fk_roles_parent` FOREIGN KEY (`parent_role_id`) REFERENCES `nf_roles` (`role_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `nf_roles_lang`;
CREATE TABLE `nf_roles_lang` (
  `role_id` int(10) unsigned NOT NULL,
  `lang` varchar(5) NOT NULL,
  `title` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  PRIMARY KEY (`role_id`,`lang`),
  CONSTRAINT `fk_roles_lang_role` FOREIGN KEY (`role_id`) REFERENCES `nf_roles` (`role_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `nf_role_permissions`;
CREATE TABLE `nf_role_permissions` (
  `role_id` int(10) unsigned NOT NULL,
  `permission` varchar(100) NOT NULL,
  `scope_id` int(10) unsigned NOT NULL DEFAULT 0,
  `authorized` enum('allow','never','default') NOT NULL DEFAULT 'allow',
  PRIMARY KEY (`role_id`,`permission`,`scope_id`),
  KEY `idx_perm` (`permission`),
  KEY `idx_perm_scope` (`permission`,`scope_id`),
  CONSTRAINT `fk_rp_role` FOREIGN KEY (`role_id`) REFERENCES `nf_roles` (`role_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `nf_settings`;
CREATE TABLE `nf_settings` (
  `name` varchar(100) NOT NULL,
  `site` varchar(100) NOT NULL DEFAULT '',
  `lang` varchar(5) NOT NULL DEFAULT '',
  `value` mediumtext DEFAULT NULL,
  `type` enum('string','bool','int','list','array','float') NOT NULL DEFAULT 'string',
  PRIMARY KEY (`name`,`site`,`lang`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `nf_dispositions`;
CREATE TABLE `nf_dispositions` (
  `disposition_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `theme` varchar(100) NOT NULL,
  `page` varchar(100) NOT NULL,
  `zone` int(11) unsigned NOT NULL,
  `disposition` mediumtext NOT NULL,
  PRIMARY KEY (`disposition_id`),
  UNIQUE KEY `theme` (`theme`,`page`,`zone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `nf_widgets`;
CREATE TABLE `nf_widgets` (
  `widget_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `widget` varchar(100) NOT NULL,
  `type` varchar(100) NOT NULL,
  `title` varchar(100) DEFAULT NULL,
  `settings` mediumtext DEFAULT NULL,
  PRIMARY KEY (`widget_id`),
  KEY `widget_name` (`widget`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `nf_pages`;
CREATE TABLE `nf_pages` (
  `page_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `published` enum('0','1') NOT NULL DEFAULT '0',
  -- datetime : une `date` future programme/masque la page (cf. modules/pages/models/pages.php). TIMESTAMP plafonne à 2038.
  `date` datetime NOT NULL DEFAULT current_timestamp(),
  `layout` varchar(20) NOT NULL DEFAULT 'default',
  PRIMARY KEY (`page_id`),
  UNIQUE KEY `page` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `nf_pages_lang`;
CREATE TABLE `nf_pages_lang` (
  `page_id` int(11) unsigned NOT NULL,
  `lang` varchar(5) NOT NULL,
  `title` varchar(100) NOT NULL,
  `subtitle` varchar(100) NOT NULL,
  `content` mediumtext NOT NULL,
  PRIMARY KEY (`page_id`,`lang`),
  KEY `lang` (`lang`),
  CONSTRAINT `nf_pages_lang_ibfk_1` FOREIGN KEY (`page_id`) REFERENCES `nf_pages` (`page_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `nf_pages_instances`;
CREATE TABLE `nf_pages_instances` (
  `instance_id` int(11) NOT NULL AUTO_INCREMENT,
  `page_id` int(11) NOT NULL,
  `block` varchar(64) NOT NULL,
  `settings` mediumtext DEFAULT NULL,
  `position` int(11) NOT NULL DEFAULT 0,
  `enabled` enum('0','1') NOT NULL DEFAULT '1',
  PRIMARY KEY (`instance_id`),
  KEY `page_id` (`page_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `nf_menus`;
CREATE TABLE `nf_menus` (
  `menu_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL,
  `title` varchar(128) NOT NULL,
  PRIMARY KEY (`menu_id`),
  UNIQUE KEY `idx_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `nf_menus_items`;
CREATE TABLE `nf_menus_items` (
  `item_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `menu_id` int(11) unsigned NOT NULL,
  `parent_id` int(11) unsigned DEFAULT NULL,
  `title` varchar(128) NOT NULL,
  `url` varchar(255) NOT NULL DEFAULT '',
  `icon` varchar(64) NOT NULL DEFAULT '',
  `target` varchar(10) NOT NULL DEFAULT '',
  `position` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`item_id`),
  KEY `idx_menu` (`menu_id`,`parent_id`,`position`),
  CONSTRAINT `fk_menus_items_menu` FOREIGN KEY (`menu_id`) REFERENCES `nf_menus` (`menu_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `nf_comment`;
CREATE TABLE `nf_comment` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) unsigned DEFAULT NULL,
  `user_id` int(11) unsigned NOT NULL,
  `module_id` int(11) unsigned NOT NULL,
  `module` varchar(100) NOT NULL,
  `content` mediumtext NOT NULL,
  `date` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `parent_id` (`parent_id`),
  KEY `user_id` (`user_id`),
  KEY `module` (`module`),
  KEY `idx_comment_deleted` (`deleted_at`),
  CONSTRAINT `nf_comment_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `nf_comment` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `nf_comment_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `nf_user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `nf_reactions`;
CREATE TABLE `nf_reactions` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL,
  `content_type` varchar(50) NOT NULL,
  `content_id` int(11) unsigned NOT NULL,
  `reaction` varchar(16) NOT NULL DEFAULT 'love',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_reaction` (`user_id`,`content_type`,`content_id`),
  KEY `idx_content` (`content_type`,`content_id`),
  CONSTRAINT `fk_reactions_user` FOREIGN KEY (`user_id`) REFERENCES `nf_user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `nf_revisions`;
CREATE TABLE `nf_revisions` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `content_type` varchar(50) NOT NULL,
  `content_id` int(11) unsigned NOT NULL,
  `lang` varchar(5) NOT NULL DEFAULT '',
  `user_id` int(11) unsigned DEFAULT NULL,
  `summary` varchar(100) NOT NULL DEFAULT '',
  `data` mediumtext NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_content` (`content_type`,`content_id`,`id`),
  KEY `fk_revisions_user` (`user_id`),
  CONSTRAINT `fk_revisions_user` FOREIGN KEY (`user_id`) REFERENCES `nf_user` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `nf_notifications`;
CREATE TABLE `nf_notifications` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL,
  `actor_id` int(11) unsigned DEFAULT NULL,
  `type` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `url` varchar(255) NOT NULL DEFAULT '',
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_read` (`user_id`,`is_read`,`id`),
  KEY `fk_notif_actor` (`actor_id`),
  CONSTRAINT `fk_notif_actor` FOREIGN KEY (`actor_id`) REFERENCES `nf_user` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `nf_user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Les préférences de notifications de chaque membre (chantier A, étape A4). Cf. migrations/2026_10_05_preferences_de_notifications.
DROP TABLE IF EXISTS `nf_notifications_preferences`;
CREATE TABLE `nf_notifications_preferences` (
  `user_id` int(11) unsigned NOT NULL,
  `type` varchar(50) NOT NULL,
  `site` tinyint(1) unsigned NOT NULL DEFAULT 1,
  `email` tinyint(1) unsigned NOT NULL DEFAULT 1,
  PRIMARY KEY (`user_id`,`type`),
  CONSTRAINT `nf_notifications_preferences_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `nf_user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `nf_subscriptions`;
CREATE TABLE `nf_subscriptions` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL,
  `content_type` varchar(50) NOT NULL,
  `content_id` int(11) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_subscription` (`user_id`,`content_type`,`content_id`),
  KEY `idx_target` (`content_type`,`content_id`),
  CONSTRAINT `fk_subscription_user` FOREIGN KEY (`user_id`) REFERENCES `nf_user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `nf_sanctions`;
CREATE TABLE `nf_sanctions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `type` enum('warning','mute','ban_temp','ban_perm','restrict_upload','restrict_links','restrict_avatar','restrict_signature','restrict_comment','shadow_ban') NOT NULL,
  `scope` enum('global','forum','talks','comments','wiki','gallery','guestbook','profile','bugtracker','recruits') NOT NULL DEFAULT 'global',
  `reason` text NOT NULL,
  `duration_seconds` bigint(20) unsigned DEFAULT NULL,
  `starts_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NULL DEFAULT NULL,
  `issued_by` int(10) unsigned NOT NULL,
  `requires_approval` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `approved_by` int(10) unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `revoked_at` timestamp NULL DEFAULT NULL,
  `revoked_by` int(10) unsigned DEFAULT NULL,
  `revoke_reason` text DEFAULT NULL,
  `related_report_id` bigint(20) unsigned DEFAULT NULL,
  `notify_user` tinyint(1) unsigned NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_sanctions_active` (`user_id`,`type`,`expires_at`,`revoked_at`),
  KEY `idx_sanctions_scope` (`user_id`,`scope`,`expires_at`),
  KEY `idx_sanctions_pending_approval` (`requires_approval`,`approved_at`),
  KEY `idx_sanctions_related_report` (`related_report_id`),
  KEY `idx_sanctions_issued_by` (`issued_by`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `nf_reports`;
CREATE TABLE `nf_reports` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `reporter_id` int(10) unsigned DEFAULT NULL,
  `reporter_ip` varchar(45) NOT NULL,
  `target_type` varchar(50) NOT NULL,
  `target_id` varchar(100) NOT NULL,
  `target_user_id` int(10) unsigned DEFAULT NULL,
  `reason` enum('spam','harassment','illegal','nsfw','misinformation','duplicate','other') NOT NULL DEFAULT 'other',
  `comment` text DEFAULT NULL,
  `url` varchar(500) NOT NULL DEFAULT '',
  `content_snapshot` text DEFAULT NULL,
  `status` enum('pending','reviewed','actioned','dismissed','duplicate') NOT NULL DEFAULT 'pending',
  `handled_by` int(10) unsigned DEFAULT NULL,
  `handled_at` timestamp NULL DEFAULT NULL,
  `handled_action_id` bigint(20) unsigned DEFAULT NULL,
  `handled_note` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_reports_status_created` (`status`,`created_at`),
  KEY `idx_reports_target_user` (`target_user_id`,`status`),
  KEY `idx_reports_reporter` (`reporter_id`),
  KEY `idx_reports_target` (`target_type`,`target_id`),
  KEY `idx_reports_handled_action` (`handled_action_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `nf_reports_attachments_snapshot`;
CREATE TABLE `nf_reports_attachments_snapshot` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `report_id` bigint(20) unsigned NOT NULL,
  `original_file_id` int(10) unsigned DEFAULT NULL,
  `original_name` varchar(255) NOT NULL,
  `mime_type` varchar(100) NOT NULL DEFAULT '',
  `file_size` int(10) unsigned NOT NULL DEFAULT 0,
  `backup_path` varchar(500) NOT NULL,
  `sha256_hash` varchar(64) NOT NULL DEFAULT '',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_report` (`report_id`),
  CONSTRAINT `fk_snapshot_report` FOREIGN KEY (`report_id`) REFERENCES `nf_reports` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `nf_talks`;
CREATE TABLE `nf_talks` (
  `talk_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `type` enum('direct','group','public') NOT NULL DEFAULT 'public',
  `audience` enum('all','staff') NOT NULL DEFAULT 'all',
  `creator_id` int(11) unsigned DEFAULT NULL,
  `description` varchar(500) NOT NULL DEFAULT '',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`talk_id`),
  KEY `idx_type_deleted` (`type`,`deleted_at`),
  KEY `idx_creator` (`creator_id`),
  KEY `idx_talks_audience` (`audience`),
  CONSTRAINT `nf_talks_creator_fk` FOREIGN KEY (`creator_id`) REFERENCES `nf_user` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `nf_talks_participants`;
CREATE TABLE `nf_talks_participants` (
  `talk_id` int(11) unsigned NOT NULL,
  `user_id` int(11) unsigned NOT NULL,
  `role` enum('member','admin') NOT NULL DEFAULT 'member',
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_read_at` timestamp NULL DEFAULT NULL,
  `archived_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `notify_email` tinyint(1) unsigned NOT NULL DEFAULT 1,
  PRIMARY KEY (`talk_id`,`user_id`),
  KEY `idx_user_archived` (`user_id`,`archived_at`),
  KEY `idx_talk_role` (`talk_id`,`role`),
  KEY `idx_participants_deleted_at` (`deleted_at`),
  CONSTRAINT `nf_talks_part_talk_fk` FOREIGN KEY (`talk_id`) REFERENCES `nf_talks` (`talk_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `nf_talks_part_user_fk` FOREIGN KEY (`user_id`) REFERENCES `nf_user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `nf_talks_messages`;
CREATE TABLE `nf_talks_messages` (
  `message_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `talk_id` int(10) unsigned NOT NULL,
  `parent_id` int(10) unsigned DEFAULT NULL,
  `user_id` int(10) unsigned DEFAULT NULL,
  `message` mediumtext NOT NULL,
  `date` timestamp NOT NULL DEFAULT current_timestamp(),
  `edited_at` timestamp NULL DEFAULT NULL,
  `edited_by` int(11) unsigned DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_by` int(11) unsigned DEFAULT NULL,
  `deleted_reason` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`message_id`),
  KEY `idx_talk_date` (`talk_id`,`date`),
  KEY `idx_parent` (`parent_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_deleted` (`deleted_at`),
  FULLTEXT KEY `ft_message` (`message`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `nf_talks_attachments`;
CREATE TABLE `nf_talks_attachments` (
  `attachment_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `message_id` int(10) unsigned NOT NULL,
  `file_id` int(11) unsigned NOT NULL,
  `file_size` int(11) unsigned NOT NULL DEFAULT 0,
  `mime_type` varchar(100) NOT NULL DEFAULT '',
  PRIMARY KEY (`attachment_id`),
  KEY `idx_message` (`message_id`),
  KEY `idx_file` (`file_id`),
  CONSTRAINT `nf_talks_att_file_fk` FOREIGN KEY (`file_id`) REFERENCES `nf_file` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `nf_talks_att_msg_fk` FOREIGN KEY (`message_id`) REFERENCES `nf_talks_messages` (`message_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `nf_media`;
CREATE TABLE `nf_media` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `filename` varchar(255) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `mime_type` varchar(100) NOT NULL,
  `size_bytes` bigint(20) unsigned NOT NULL,
  `width` int(10) unsigned DEFAULT NULL,
  `height` int(10) unsigned DEFAULT NULL,
  `user_id` int(10) unsigned DEFAULT NULL,
  `title` varchar(200) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_mime` (`mime_type`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `nf_file`;
CREATE TABLE `nf_file` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `path` varchar(100) NOT NULL,
  `date` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `path` (`path`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `nf_file_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `nf_user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `nf_slider_slides`;
CREATE TABLE `nf_slider_slides` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `image_url` varchar(500) NOT NULL DEFAULT '',
  `title` varchar(200) NOT NULL DEFAULT '',
  `caption` mediumtext DEFAULT NULL,
  `link` varchar(500) NOT NULL DEFAULT '',
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_active_sort` (`active`,`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `nf_statistics`;
CREATE TABLE `nf_statistics` (
  `name` varchar(100) NOT NULL,
  `value` mediumtext NOT NULL,
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `nf_webhooks`;
CREATE TABLE `nf_webhooks` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(128) NOT NULL,
  `url` varchar(255) NOT NULL,
  `secret` varchar(128) NOT NULL DEFAULT '',
  `events` text NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_enabled` (`enabled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `nf_email_templates`;
CREATE TABLE `nf_email_templates` (
  `template_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(100) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `placeholders` text DEFAULT NULL,
  `module` varchar(50) DEFAULT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`template_id`),
  UNIQUE KEY `uk_key` (`key`),
  KEY `idx_module` (`module`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `nf_email_template_translations`;
CREATE TABLE `nf_email_template_translations` (
  `template_id` int(10) unsigned NOT NULL,
  `lang` varchar(5) NOT NULL,
  `subject` varchar(500) NOT NULL,
  `body` mediumtext NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`template_id`,`lang`),
  CONSTRAINT `fk_email_tpl_trans_tpl` FOREIGN KEY (`template_id`) REFERENCES `nf_email_templates` (`template_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `nf_i18n`;
CREATE TABLE `nf_i18n` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `lang_id` int(10) unsigned NOT NULL,
  `model` varchar(100) DEFAULT NULL,
  `model_id` int(10) unsigned DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `value` mediumtext NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `lang_id` (`lang_id`,`model`,`model_id`,`name`) USING BTREE,
  KEY `lang_id_2` (`lang_id`),
  KEY `model` (`model`),
  KEY `model_id` (`model_id`),
  KEY `name` (`name`),
  CONSTRAINT `nf_i18n_ibfk_1` FOREIGN KEY (`lang_id`) REFERENCES `nf_addon` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `nf_tracking`;
CREATE TABLE `nf_tracking` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `model` varchar(100) NOT NULL,
  `model_id` int(10) unsigned DEFAULT NULL,
  `date` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`,`model`,`model_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `nf_audit_log`;
CREATE TABLE `nf_audit_log` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned DEFAULT NULL,
  `username` varchar(100) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `target_type` varchar(100) DEFAULT NULL,
  `target_id` varchar(100) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `success` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_action` (`action`),
  KEY `idx_created` (`created_at`),
  KEY `idx_success` (`success`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `nf_cookie_consent`;
CREATE TABLE `nf_cookie_consent` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `consent_token` varchar(64) NOT NULL,
  `user_id` int(10) unsigned DEFAULT NULL,
  `consent_essentials` tinyint(1) NOT NULL DEFAULT 1,
  `consent_analytics` tinyint(1) NOT NULL DEFAULT 0,
  `consent_marketing` tinyint(1) NOT NULL DEFAULT 0,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_token` (`consent_token`),
  KEY `idx_user` (`user_id`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `nf_ip_banlist`;
CREATE TABLE `nf_ip_banlist` (
  `ban_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ip` varchar(45) NOT NULL,
  `reason` varchar(500) DEFAULT NULL,
  `banned_by` int(11) unsigned DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`ban_id`),
  UNIQUE KEY `uk_ip` (`ip`),
  KEY `idx_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `nf_rate_limit`;
CREATE TABLE `nf_rate_limit` (
  `rate_key` varchar(191) NOT NULL,
  `attempts` int(10) unsigned NOT NULL DEFAULT 0,
  `first_attempt_at` timestamp NULL DEFAULT NULL,
  `locked_until` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`rate_key`),
  KEY `idx_locked` (`locked_until`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `nf_session`;
CREATE TABLE `nf_session` (
  `id` varchar(32) NOT NULL,
  `user_id` int(11) unsigned DEFAULT NULL,
  `remember` enum('0','1') NOT NULL DEFAULT '0',
  `last_activity` timestamp NOT NULL DEFAULT current_timestamp(),
  `data` mediumtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `nf_session_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `nf_user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `nf_session_history`;
CREATE TABLE `nf_session_history` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL,
  `ip_address` varchar(39) NOT NULL,
  `host_name` varchar(100) NOT NULL,
  `referer` varchar(100) NOT NULL,
  `user_agent` varchar(250) NOT NULL,
  `auth` mediumtext DEFAULT NULL,
  `date` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `nf_session_history_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `nf_user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `nf_log_db`;
CREATE TABLE `nf_log_db` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `date` timestamp NOT NULL DEFAULT current_timestamp(),
  `action` enum('0','1','2') NOT NULL,
  `model` varchar(100) NOT NULL,
  `primaries` varchar(100) DEFAULT NULL,
  `data` mediumtext NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `nf_log_i18n`;
CREATE TABLE `nf_log_i18n` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `language` char(2) NOT NULL,
  `key` char(32) NOT NULL,
  `locale` mediumtext NOT NULL,
  `file` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `language` (`language`,`key`,`file`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `nf_user_fields_values`;
DROP TABLE IF EXISTS `nf_user_fields`;
-- Champs de profil definis par l'administrateur. Le profil fixe de nf_user_profile
-- couvre l'etat civil et les reseaux ; ces deux tables laissent une communaute ajouter ce qui lui
-- est propre — pseudo en jeu, plateforme, rang — sans livrer une migration a chaque fois.
CREATE TABLE `nf_user_fields` (
  `field_id`    int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name`        varchar(64) NOT NULL,
  `label`       varchar(200) NOT NULL,
  `description` varchar(255) NOT NULL DEFAULT '',
  `type`        enum('text','textarea','select','radio','checkbox','url','number','date') NOT NULL DEFAULT 'text',
  `options`     mediumtext DEFAULT NULL,
  `required`    tinyint(1) NOT NULL DEFAULT 0,
  `public`      tinyint(1) NOT NULL DEFAULT 0,
  `sort_order`  smallint(5) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`field_id`),
  UNIQUE KEY `uniq_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `nf_user_fields_values` (
  `field_id` int(11) unsigned NOT NULL,
  `user_id`  int(11) unsigned NOT NULL,
  `value`    mediumtext NOT NULL,
  PRIMARY KEY (`field_id`, `user_id`),
  KEY `idx_user` (`user_id`),
  CONSTRAINT `fk_user_fields_values_field` FOREIGN KEY (`field_id`) REFERENCES `nf_user_fields` (`field_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_user_fields_values_user`  FOREIGN KEY (`user_id`)  REFERENCES `nf_user` (`id`)             ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Le référencement de chaque site : titre et description par contenu, redirections, file
-- IndexNow. Cf. migrations/2026_10_03_referencement.
DROP TABLE IF EXISTS `nf_seo_meta`;
CREATE TABLE `nf_seo_meta` (
  `content_type` varchar(50) NOT NULL,
  `content_id` int(11) unsigned NOT NULL,
  `lang` varchar(5) NOT NULL DEFAULT '',
  `title` varchar(100) NOT NULL DEFAULT '',
  `description` varchar(255) NOT NULL DEFAULT '',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`content_type`,`content_id`,`lang`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `nf_redirects`;
CREATE TABLE `nf_redirects` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `source` varchar(255) NOT NULL,
  `target` varchar(500) NOT NULL,
  `hits` int(11) unsigned NOT NULL DEFAULT 0,
  `last_hit_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_source` (`source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `nf_indexnow`;
CREATE TABLE `nf_indexnow` (
  `url` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `lastmod` date DEFAULT NULL,
  `pending` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `gone` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `changed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`url`),
  KEY `idx_pending` (`pending`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `nf_migrations`;
CREATE TABLE `nf_migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `batch` int(10) unsigned NOT NULL,
  `applied_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `nf_addon_migrations`;
CREATE TABLE `nf_addon_migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(32) NOT NULL,
  `name` varchar(100) NOT NULL,
  `migration` varchar(191) NOT NULL,
  `batch` int(10) unsigned NOT NULL DEFAULT 0,
  `applied_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_addon_migration` (`type`,`name`,`migration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Historique des migrations (état appliqué à la génération de ce dump).
INSERT INTO `nf_migrations` (`id`, `name`, `batch`, `applied_at`) VALUES
('1', '2026_05_04_forum_attachments_use_nf_file', '0', '2026-06-01 10:41:51'),
('2', '2026_05_04_forum_refonte_phase1', '0', '2026-06-01 10:41:51'),
('3', '2026_05_04_moderation_attachments_snapshot', '0', '2026-06-01 10:41:51'),
('4', '2026_05_04_moderation_phase1', '0', '2026-06-01 10:41:51'),
('5', '2026_05_04_moderation_roles', '0', '2026-06-01 10:41:51'),
('6', '2026_05_04_talks_audience_staff', '0', '2026-06-01 10:41:51'),
('7', '2026_05_04_talks_unified', '0', '2026-06-01 10:41:51'),
('8', '2026_05_04_talks_user_soft_delete', '0', '2026-06-01 10:41:51'),
('9', '2026_05_04_user_messages_extension', '0', '2026-06-01 10:41:51'),
('10', '2026_05_06_email_templates', '1', '2026-06-01 10:41:51'),
('11', '2026_05_06_ip_banlist', '1', '2026-06-01 10:41:51'),
('12', '2026_05_06_permissions_rework_phase1', '1', '2026-06-01 10:41:51'),
('14', '2026_06_01_recruits_custom_fields', '2', '2026-06-01 21:12:12'),
('15', '2026_06_02_reactions', '3', '2026-06-02 12:59:15'),
('16', '2026_06_02_revisions', '4', '2026-06-02 13:45:28'),
('17', '2026_06_02_notifications', '5', '2026-06-02 17:13:28'),
('18', '2026_06_02_subscriptions', '6', '2026-06-02 18:17:01'),
('19', '2026_06_02_soft_delete', '7', '2026-06-02 20:01:52'),
('20', '2026_06_02_soft_delete_gallery', '8', '2026-06-02 20:20:31'),
('21', '2026_06_03_forum_category_image', '9', '2026-06-03 10:10:49'),
('22', '2026_06_03_gamification_karma', '10', '2026-06-03 13:49:22'),
('23', '2026_06_03_gamification_points', '11', '2026-06-03 14:03:24'),
('24', '2026_06_03_shop', '12', '2026-06-03 14:21:05'),
('25', '2026_06_03_gamification_vip', '13', '2026-06-03 14:24:48'),
('26', '2026_06_03_ads', '14', '2026-06-03 14:42:04'),
('27', '2026_06_03_forum_vip_category', '15', '2026-06-03 16:31:21'),
('28', '2026_06_03_payments_stripe', '16', '2026-06-03 16:38:16'),
('29', '2026_06_03_feeds', '17', '2026-06-03 17:22:21'),
('30', '2026_06_03_widget_video', '18', '2026-06-03 17:32:27'),
('31', '2026_06_03_user_timezone', '19', '2026-06-03 17:36:07'),
('32', '2026_06_03_classifieds', '20', '2026-06-03 17:47:05'),
('33', '2026_06_04_comments_soft_delete', '21', '2026-06-04 08:54:54'),
('34', '2026_06_04_menu', '22', '2026-06-04 18:57:41'),
('35', '2026_06_04_webhooks', '23', '2026-06-04 19:17:40'),
('36', '2026_06_05_pages_layout', '24', '2026-06-04 22:18:10'),
('37', '2026_06_05_scheduled_publishing', '25', '2026-06-05 09:28:10'),
('38', '2026_06_10_user_token_date', '26', '2026-06-11 07:44:12'),
('39', '2026_06_10_utf8mb4_unicode', '27', '2026-06-11 07:57:43'),
('40', '2026_06_11_email_templates_i18n', '28', '2026-06-11 09:08:07'),
('41', '2026_06_12_reactions_emoji', '29', '2026-06-12 13:27:23'),
('42', '2026_06_13_pages_instances', '30', '2026-06-13 10:17:10'),
-- nf_pages.date passée de TIMESTAMP à DATETIME (limite 2038). schema.sql porte déjà le type corrigé →
-- migration marquée comme déjà-appliquée pour un install neuf (baseline). Cf. migrations/2026_08_23_pages_date_datetime.
('43', '2026_08_23_pages_date_datetime', '31', '2026-08-23 00:00:00'),
-- Duree de conservation de l'historique des connexions. Le reglage est pose par
-- seed.sql pour une installation neuve -> la migration n'a rien a y faire, elle est marquee
-- comme deja-appliquee. Cf. migrations/2026_09_20_session_history_retention.
('44', '2026_09_20_session_history_retention', '32', '2026-09-20 00:00:00'),
-- Champs de profil definis par l'administrateur. Les deux tables sont ci-dessus ->
-- migration marquee comme deja-appliquee pour une installation neuve.
('45', '2026_09_20_user_custom_fields', '33', '2026-09-20 00:00:00'),
-- Classes directionnelles de Bootstrap 4 restees dans les reglages. Le copyright livre par
-- seed.sql porte desormais `float-end` -> la migration n'a rien a corriger sur une
-- installation neuve, elle est marquee comme deja appliquee.
-- Cf. migrations/2026_09_20_bootstrap5_float.
('46', '2026_09_20_bootstrap5_float', '34', '2026-09-20 00:00:00'),
-- Interrupteur du service worker (PWA). seed.sql pose deja `nf_pwa` a 0 sur une installation
-- neuve : la migration n'a rien a y faire, elle est marquee comme deja appliquee.
-- Cf. migrations/2026_09_22_pwa_service_worker.
('47', '2026_09_22_pwa_service_worker', '35', '2026-09-22 00:00:00'),

-- Nebula rend son menu DANS sa barre, et non plus une seconde fois sous l'en-tete. La
-- configuration livree porte deja le resultat : la migration n'a rien a rejouer ici.
-- Cf. migrations/2026_09_22_nebula_menu_unique.
('48', '2026_09_22_nebula_menu_unique', '36', '2026-09-22 00:00:00'),

-- Couleurs des groupes et roles de moderation : seed.sql les livre deja en `warning` et
-- `danger`, noms que Bootstrap connait. La migration n'a rien a corriger ici.
-- Cf. migrations/2026_09_23_couleurs_des_groupes.
('49', '2026_09_23_couleurs_des_groupes', '37', '2026-09-23 00:00:00'),

-- Bouton des modeles d'e-mails : seed.sql le livre deja dans la teinte lisible.
-- Cf. migrations/2026_09_23_bouton_des_emails.
('50', '2026_09_23_bouton_des_emails', '38', '2026-09-23 00:00:00'),

-- Titres des groupes de moderation dans les six langues : seed.sql les livre deja.
-- Cf. migrations/2026_09_23_groupes_six_langues.
('51', '2026_09_23_groupes_six_langues', '39', '2026-09-23 00:00:00'),

-- Choix du theme ferme sur le site vitrine : une installation neuve est en nebula, et
-- install/vitrine.sql ferme lui-meme le choix quand il pose le theme vitrine.
-- Cf. migrations/2026_09_23_choix_du_theme.
('52', '2026_09_23_choix_du_theme', '40', '2026-09-23 00:00:00'),

-- Avatar des comptes lies en 255 caracteres : nf_user_auth ci-dessus est deja a cette taille.
-- Cf. migrations/2026_10_01_avatar_des_connexions.
('53', '2026_10_01_avatar_des_connexions', '41', '2026-10-01 00:00:00'),

-- Ancien reglage du rattrapage des migrations : une installation neuve ne l'a jamais eu.
-- Cf. migrations/2026_10_01_reglage_des_migrations.
('54', '2026_10_01_reglage_des_migrations', '42', '2026-10-01 00:00:00'),

-- Un compte externe ne se lie qu'a un membre : nf_user_auth ci-dessus porte deja uk_authenticator_key.
-- Cf. migrations/2026_10_01_compte_externe_unique.
('55', '2026_10_01_compte_externe_unique', '43', '2026-10-01 00:00:00'),

-- Le fournisseur du captcha : install/seed.sql pose deja nf_captcha_provider = altcha.
-- Cf. migrations/2026_10_02_captcha_fournisseur.
('56', '2026_10_02_captcha_fournisseur', '44', '2026-10-02 00:00:00'),

-- Le référencement : nf_seo_meta, nf_redirects et nf_indexnow sont ci-dessus.
-- Cf. migrations/2026_10_03_referencement.
('57', '2026_10_03_referencement', '45', '2026-10-03 00:00:00'),

-- Ce que montre le profil public : nf_user_profile ci-dessus porte deja les colonnes montrer_*.
-- Cf. migrations/2026_10_05_profil_visibilite.
('58', '2026_10_05_profil_visibilite', '46', '2026-10-05 00:00:00'),

-- Les droits des membres : install/seed.sql leur donne deja ce que les visiteurs ont.
-- Cf. migrations/2026_10_05_droits_des_membres.
('59', '2026_10_05_droits_des_membres', '47', '2026-10-05 00:00:00'),

-- Les préférences de notifications : nf_notifications_preferences ci-dessus.
-- Cf. migrations/2026_10_05_preferences_de_notifications.
('60', '2026_10_05_preferences_de_notifications', '48', '2026-10-05 00:00:00');


SET FOREIGN_KEY_CHECKS = 1;
