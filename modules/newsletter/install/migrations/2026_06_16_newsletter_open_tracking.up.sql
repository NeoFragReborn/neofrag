-- Newsletter : suivi des ouvertures (pixel de tracking). Chaque destinataire de la file reçoit un
-- token unique ; un GET sur newsletter/track/<token> pose opened_at (une fois) et incrémente opened_to.

ALTER TABLE `nf_newsletter_queue`
  ADD COLUMN `track_token` varchar(32) DEFAULT NULL AFTER `token`,
  ADD COLUMN `opened_at` timestamp NULL DEFAULT NULL AFTER `sent_at`,
  ADD KEY `idx_track` (`track_token`);

ALTER TABLE `nf_newsletter_campaigns`
  ADD COLUMN `opened_to` int(10) unsigned NOT NULL DEFAULT 0 AFTER `failed_to`;
