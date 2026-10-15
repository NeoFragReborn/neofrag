-- Retour arrière de 2026_10_15_champs_candidature_par_langue : Le libellé d'une question du formulaire de candidature redevient monolingue.
--
-- Les colonnes reviennent, remplies de la MEILLEURE traduction gardée (le français d'abord, sinon la première
-- écrite) : on perd les autres langues, pas le texte.

ALTER TABLE `nf_recruits_fields`
  ADD COLUMN `label` varchar(200) NOT NULL DEFAULT '' AFTER `field_id`;

UPDATE `nf_recruits_fields` `t`
SET `t`.`label` = COALESCE((SELECT `l`.`label` FROM `nf_recruits_fields_lang` `l` WHERE `l`.`field_id` = `t`.`field_id` ORDER BY `l`.`lang` = 'fr' DESC, `l`.`lang` LIMIT 1), '');

DROP TABLE IF EXISTS `nf_recruits_fields_lang`;
