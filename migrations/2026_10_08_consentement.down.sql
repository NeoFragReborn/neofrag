-- Retour à la table d'avant : trois cases figées, l'adresse IP et le navigateur.

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
