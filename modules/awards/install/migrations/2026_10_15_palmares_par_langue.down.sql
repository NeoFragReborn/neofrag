-- Retour arrière de 2026_10_15_palmares_par_langue : le palmarès redevient monolingue.
--
-- Les colonnes reviennent, remplies de la MEILLEURE traduction gardée (le français d'abord, sinon la première
-- écrite) : on perd les autres langues, pas le texte.

ALTER TABLE `nf_awards`
  ADD COLUMN `name` varchar(100) NOT NULL DEFAULT '' AFTER `image_id`,
  ADD COLUMN `location` varchar(100) NOT NULL DEFAULT '' AFTER `name`,
  ADD COLUMN `description` mediumtext NOT NULL AFTER `date`;

UPDATE `nf_awards` `a`
SET `a`.`name` = COALESCE((SELECT `l`.`name` FROM `nf_awards_lang` `l` WHERE `l`.`award_id` = `a`.`award_id` ORDER BY `l`.`lang` = 'fr' DESC, `l`.`lang` LIMIT 1), ''),
    `a`.`location` = COALESCE((SELECT `l`.`location` FROM `nf_awards_lang` `l` WHERE `l`.`award_id` = `a`.`award_id` ORDER BY `l`.`lang` = 'fr' DESC, `l`.`lang` LIMIT 1), ''),
    `a`.`description` = COALESCE((SELECT `l`.`description` FROM `nf_awards_lang` `l` WHERE `l`.`award_id` = `a`.`award_id` ORDER BY `l`.`lang` = 'fr' DESC, `l`.`lang` LIMIT 1), '');

DROP TABLE IF EXISTS `nf_awards_lang`;
