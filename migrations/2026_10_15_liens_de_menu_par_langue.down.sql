-- Retour arrière de 2026_10_15_liens_de_menu_par_langue : les liens de menu redeviennent monolingues.
--
-- La colonne revient, remplie de la MEILLEURE traduction gardée (le français d'abord, sinon la première écrite) :
-- on perd les autres langues, pas le texte.

ALTER TABLE `nf_menus_items`
  ADD COLUMN `title` varchar(128) NOT NULL DEFAULT '' AFTER `parent_id`;

UPDATE `nf_menus_items` `i`
SET `i`.`title` = COALESCE((SELECT `l`.`title` FROM `nf_menus_items_lang` `l` WHERE `l`.`item_id` = `i`.`item_id` ORDER BY `l`.`lang` = 'fr' DESC, `l`.`lang` LIMIT 1), '');

DROP TABLE IF EXISTS `nf_menus_items_lang`;
