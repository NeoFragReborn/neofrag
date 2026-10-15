-- Retour arrière de 2026_10_15_campagnes_dons_par_langue : Le titre et la description d'une campagne de dons redevient monolingue.
--
-- Les colonnes reviennent, remplies de la MEILLEURE traduction gardée (le français d'abord, sinon la première
-- écrite) : on perd les autres langues, pas le texte.

ALTER TABLE `nf_donations_campaigns`
  ADD COLUMN `title` varchar(255) NOT NULL DEFAULT '' AFTER `name`,
  ADD COLUMN `description` text DEFAULT NULL AFTER `title`;

UPDATE `nf_donations_campaigns` `t`
SET `t`.`title` = COALESCE((SELECT `l`.`title` FROM `nf_donations_campaigns_lang` `l` WHERE `l`.`campaign_id` = `t`.`id` ORDER BY `l`.`lang` = 'fr' DESC, `l`.`lang` LIMIT 1), ''),
    `t`.`description` = (SELECT `l`.`description` FROM `nf_donations_campaigns_lang` `l` WHERE `l`.`campaign_id` = `t`.`id` ORDER BY `l`.`lang` = 'fr' DESC, `l`.`lang` LIMIT 1);

DROP TABLE IF EXISTS `nf_donations_campaigns_lang`;
