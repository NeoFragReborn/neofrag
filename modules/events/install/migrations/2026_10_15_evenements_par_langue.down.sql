-- Retour arrière de 2026_10_15_evenements_par_langue : les événements redeviennent monolingues.
--
-- Les colonnes reviennent, remplies de la MEILLEURE traduction gardée (le français d'abord, sinon la première
-- écrite) : on perd les autres langues, pas le texte.

ALTER TABLE `nf_events`
  ADD COLUMN `title` varchar(100) NOT NULL DEFAULT '' AFTER `image_id`,
  ADD COLUMN `description` mediumtext NOT NULL AFTER `title`,
  ADD COLUMN `private_description` mediumtext NOT NULL AFTER `description`,
  ADD COLUMN `location` mediumtext NOT NULL AFTER `private_description`;

UPDATE `nf_events` `e`
SET `e`.`title` = COALESCE((SELECT `l`.`title` FROM `nf_events_lang` `l` WHERE `l`.`event_id` = `e`.`event_id` ORDER BY `l`.`lang` = 'fr' DESC, `l`.`lang` LIMIT 1), ''),
    `e`.`description` = COALESCE((SELECT `l`.`description` FROM `nf_events_lang` `l` WHERE `l`.`event_id` = `e`.`event_id` ORDER BY `l`.`lang` = 'fr' DESC, `l`.`lang` LIMIT 1), ''),
    `e`.`private_description` = COALESCE((SELECT `l`.`private_description` FROM `nf_events_lang` `l` WHERE `l`.`event_id` = `e`.`event_id` ORDER BY `l`.`lang` = 'fr' DESC, `l`.`lang` LIMIT 1), ''),
    `e`.`location` = COALESCE((SELECT `l`.`location` FROM `nf_events_lang` `l` WHERE `l`.`event_id` = `e`.`event_id` ORDER BY `l`.`lang` = 'fr' DESC, `l`.`lang` LIMIT 1), '');

DROP TABLE IF EXISTS `nf_events_lang`;
