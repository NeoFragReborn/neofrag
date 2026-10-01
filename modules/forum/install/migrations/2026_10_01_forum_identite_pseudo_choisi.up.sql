-- Le pseudo choisi d'une identité Discord (`/forum visibility custom`, 2026-10-01) ne
-- change qu'une fois tous les sept jours : la date du dernier changement est retenue.

ALTER TABLE `nf_forum_identities` ADD COLUMN `custom_changed_at` datetime DEFAULT NULL AFTER `custom_name`;
