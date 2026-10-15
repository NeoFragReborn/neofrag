-- Retour arrière de 2026_10_15_titres_widgets_par_langue : le titre donné à un widget redevient monolingue.
--
-- La colonne revient, remplie de la MEILLEURE traduction gardée (le français d'abord, sinon la première écrite) ;
-- un titre vide redevient NULL, « pas de titre donné ».

ALTER TABLE `nf_widgets`
  ADD COLUMN `title` varchar(100) DEFAULT NULL AFTER `type`;

UPDATE `nf_widgets` `w`
SET `w`.`title` = NULLIF((SELECT `l`.`title` FROM `nf_widgets_lang` `l` WHERE `l`.`widget_id` = `w`.`widget_id` ORDER BY `l`.`lang` = 'fr' DESC, `l`.`lang` LIMIT 1), '');

DROP TABLE IF EXISTS `nf_widgets_lang`;
