-- Retour au pixel de suivi : le jeton de chaque destinataire, la date d'ouverture et le compteur de la lettre.

ALTER TABLE `nf_newsletter_queue`
  ADD COLUMN `track_token` varchar(32) DEFAULT NULL AFTER `token`,
  ADD COLUMN `opened_at` timestamp NULL DEFAULT NULL AFTER `sent_at`,
  ADD KEY `idx_track` (`track_token`);

ALTER TABLE `nf_newsletter_campaigns`
  ADD COLUMN `opened_to` int(10) unsigned NOT NULL DEFAULT 0 AFTER `failed_to`;
