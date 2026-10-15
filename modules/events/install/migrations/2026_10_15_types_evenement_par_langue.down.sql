-- Retour arrière de 2026_10_15_types_evenement_par_langue : Le nom d'un type de rendez-vous (« Entraînement », « Match officiel », « Tournoi ») redevient monolingue.
--
-- La colonne revient, remplie de la MEILLEURE traduction gardée (le français d'abord, sinon la première écrite) :
-- on perd les autres langues, pas le texte.

ALTER TABLE `nf_events_types` ADD COLUMN `title` varchar(100) NOT NULL DEFAULT '' AFTER `type`;

UPDATE `nf_events_types` `t`
SET `t`.`title` = COALESCE((SELECT `l`.`title` FROM `nf_events_types_lang` `l` WHERE `l`.`type_id` = `t`.`type_id` ORDER BY `l`.`lang` = 'fr' DESC, `l`.`lang` LIMIT 1), '');

DROP TABLE IF EXISTS `nf_events_types_lang`;
