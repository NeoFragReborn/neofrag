-- Newsletter : segmentation des campagnes — cibler tous les confirmés, les membres uniquement,
-- ou les abonnés membres d'un groupe donné. Le filtre s'applique au moment du snapshot de la file.

ALTER TABLE `nf_newsletter_campaigns`
  ADD COLUMN `segment` varchar(20) NOT NULL DEFAULT 'all' AFTER `content`,
  ADD COLUMN `segment_group_id` int(10) unsigned DEFAULT NULL AFTER `segment`;
