-- Retour arrière de 2026_10_15_champs_profil_par_langue : les champs de profil redeviennent monolingues.
--
-- Les colonnes reviennent, remplies de la MEILLEURE traduction gardée (le français d'abord, sinon la première
-- écrite) : on perd les autres langues, pas le texte.

ALTER TABLE `nf_user_fields`
  ADD COLUMN `label` varchar(200) NOT NULL DEFAULT '' AFTER `name`,
  ADD COLUMN `description` varchar(255) DEFAULT NULL AFTER `label`;

UPDATE `nf_user_fields` `f`
SET `f`.`label` = COALESCE((SELECT `l`.`label` FROM `nf_user_fields_lang` `l` WHERE `l`.`field_id` = `f`.`field_id` ORDER BY `l`.`lang` = 'fr' DESC, `l`.`lang` LIMIT 1), ''),
    `f`.`description` = (SELECT `l`.`description` FROM `nf_user_fields_lang` `l` WHERE `l`.`field_id` = `f`.`field_id` ORDER BY `l`.`lang` = 'fr' DESC, `l`.`lang` LIMIT 1);

DROP TABLE IF EXISTS `nf_user_fields_lang`;
