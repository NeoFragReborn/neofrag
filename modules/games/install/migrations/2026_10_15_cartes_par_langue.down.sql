-- Retour arrière de 2026_10_15_cartes_par_langue : Le nom d'une carte de jeu redevient monolingue.
--
-- Les colonnes reviennent, remplies de la MEILLEURE traduction gardée (le français d'abord, sinon la première
-- écrite) : on perd les autres langues, pas le texte.

ALTER TABLE `nf_games_maps`
  ADD COLUMN `title` varchar(100) NOT NULL DEFAULT '' AFTER `game_id`;

UPDATE `nf_games_maps` `t`
SET `t`.`title` = COALESCE((SELECT `l`.`title` FROM `nf_games_maps_lang` `l` WHERE `l`.`map_id` = `t`.`map_id` ORDER BY `l`.`lang` = 'fr' DESC, `l`.`lang` LIMIT 1), '');

DROP TABLE IF EXISTS `nf_games_maps_lang`;
