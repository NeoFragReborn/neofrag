-- Retour arrière de 2026_10_15_recrutement_par_langue : Le titre, l'accroche, la description et les prérequis d'une offre de recrutement redevient monolingue.
--
-- Les colonnes reviennent, remplies de la MEILLEURE traduction gardée (le français d'abord, sinon la première
-- écrite) : on perd les autres langues, pas le texte.

ALTER TABLE `nf_recruits`
  ADD COLUMN `title` varchar(100) NOT NULL DEFAULT '' AFTER `recruit_id`,
  ADD COLUMN `introduction` mediumtext DEFAULT NULL AFTER `title`,
  ADD COLUMN `description` mediumtext DEFAULT NULL AFTER `introduction`,
  ADD COLUMN `requierments` mediumtext DEFAULT NULL AFTER `description`;

UPDATE `nf_recruits` `t`
SET `t`.`title` = COALESCE((SELECT `l`.`title` FROM `nf_recruits_lang` `l` WHERE `l`.`recruit_id` = `t`.`recruit_id` ORDER BY `l`.`lang` = 'fr' DESC, `l`.`lang` LIMIT 1), ''),
    `t`.`introduction` = (SELECT `l`.`introduction` FROM `nf_recruits_lang` `l` WHERE `l`.`recruit_id` = `t`.`recruit_id` ORDER BY `l`.`lang` = 'fr' DESC, `l`.`lang` LIMIT 1),
    `t`.`description` = (SELECT `l`.`description` FROM `nf_recruits_lang` `l` WHERE `l`.`recruit_id` = `t`.`recruit_id` ORDER BY `l`.`lang` = 'fr' DESC, `l`.`lang` LIMIT 1),
    `t`.`requierments` = (SELECT `l`.`requierments` FROM `nf_recruits_lang` `l` WHERE `l`.`recruit_id` = `t`.`recruit_id` ORDER BY `l`.`lang` = 'fr' DESC, `l`.`lang` LIMIT 1);

DROP TABLE IF EXISTS `nf_recruits_lang`;
