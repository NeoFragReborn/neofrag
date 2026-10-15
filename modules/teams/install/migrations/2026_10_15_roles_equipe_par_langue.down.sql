-- Retour arrière de 2026_10_15_roles_equipe_par_langue : Le nom d'un rôle dans une équipe (« Capitaine », « Remplaçant », « Coach ») redevient monolingue.
--
-- La colonne revient, remplie de la MEILLEURE traduction gardée (le français d'abord, sinon la première écrite) :
-- on perd les autres langues, pas le texte.

ALTER TABLE `nf_teams_roles` ADD COLUMN `title` varchar(100) NOT NULL DEFAULT '';

UPDATE `nf_teams_roles` `t`
SET `t`.`title` = COALESCE((SELECT `l`.`title` FROM `nf_teams_roles_lang` `l` WHERE `l`.`role_id` = `t`.`role_id` ORDER BY `l`.`lang` = 'fr' DESC, `l`.`lang` LIMIT 1), '');

DROP TABLE IF EXISTS `nf_teams_roles_lang`;
