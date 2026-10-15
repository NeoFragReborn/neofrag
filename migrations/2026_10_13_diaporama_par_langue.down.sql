-- Retour arrière de 2026_10_13_diaporama_par_langue : le diaporama redevient monolingue.
--
-- Les colonnes reviennent, remplies de la MEILLEURE traduction gardée (le français d'abord, sinon la première
-- écrite) : on perd les autres langues, pas le texte.

ALTER TABLE `nf_slider_slides`
  ADD COLUMN `title` varchar(200) NOT NULL DEFAULT '' AFTER `image_url`,
  ADD COLUMN `caption` mediumtext DEFAULT NULL AFTER `title`;

UPDATE `nf_slider_slides` `s`
SET `s`.`title` = COALESCE((SELECT `l`.`title` FROM `nf_slider_slides_lang` `l` WHERE `l`.`slide_id` = `s`.`id` ORDER BY `l`.`lang` = 'fr' DESC, `l`.`lang` LIMIT 1), ''),
    `s`.`caption` = (SELECT `l`.`caption` FROM `nf_slider_slides_lang` `l` WHERE `l`.`slide_id` = `s`.`id` ORDER BY `l`.`lang` = 'fr' DESC, `l`.`lang` LIMIT 1);

DROP TABLE IF EXISTS `nf_slider_slides_lang`;
