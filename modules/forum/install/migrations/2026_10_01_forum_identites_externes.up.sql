-- Les auteurs venus d'ailleurs (API REST, 2026-10-01) : un message écrit depuis Discord
-- par quelqu'un qui n'a pas lié son compte au site appartient à une IDENTITÉ externe, et non à un
-- membre. Le nom affiché se calcule à la lecture selon le mode de l'identité (public : son pseudo ;
-- invité : un nom anonyme stable ; personnalisé : un pseudo choisi) — changer de mode change tous
-- ses messages sans les réécrire.

CREATE TABLE IF NOT EXISTS `nf_forum_identities` (
  `identity_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `provider` varchar(20) NOT NULL,
  `external_id` varchar(64) NOT NULL,
  `username` varchar(100) NOT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `mode` enum('public','guest','custom') NOT NULL DEFAULT 'public',
  `custom_name` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`identity_id`),
  UNIQUE KEY `uk_provider_external` (`provider`,`external_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `nf_forum_messages` ADD COLUMN `identity_id` int(10) unsigned DEFAULT NULL AFTER `user_id`;
ALTER TABLE `nf_forum_messages` ADD KEY `idx_identity` (`identity_id`);
