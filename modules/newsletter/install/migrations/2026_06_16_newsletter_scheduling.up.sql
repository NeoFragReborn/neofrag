-- Newsletter : envoi programmé + file d'attente batchée (traitée par le cron) + stats de livraison.
-- Les campagnes existantes sont déjà parties → status 'sent' par défaut (rétro-compatible).

ALTER TABLE `nf_newsletter_campaigns`
  ADD COLUMN `status` varchar(20) NOT NULL DEFAULT 'sent' AFTER `content`,
  ADD COLUMN `scheduled_at` timestamp NULL DEFAULT NULL AFTER `status`,
  ADD COLUMN `recipients_total` int(10) unsigned NOT NULL DEFAULT 0 AFTER `sent_to`,
  ADD COLUMN `failed_to` int(10) unsigned NOT NULL DEFAULT 0 AFTER `recipients_total`;

CREATE TABLE IF NOT EXISTS `nf_newsletter_queue` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `campaign_id` int(10) unsigned NOT NULL,
  `email` varchar(150) NOT NULL,
  `token` varchar(64) NOT NULL,
  `status` varchar(10) NOT NULL DEFAULT 'pending',
  `error` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `sent_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_campaign_status` (`campaign_id`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
