-- Retour arrière de 2026_10_15_lots_paiement_par_langue : Le libellé d'un lot de points à acheter redevient monolingue.
--
-- Les colonnes reviennent, remplies de la MEILLEURE traduction gardée (le français d'abord, sinon la première
-- écrite) : on perd les autres langues, pas le texte.

ALTER TABLE `nf_payment_packs`
  ADD COLUMN `label` varchar(150) NOT NULL DEFAULT '' AFTER `id`;

UPDATE `nf_payment_packs` `t`
SET `t`.`label` = COALESCE((SELECT `l`.`label` FROM `nf_payment_packs_lang` `l` WHERE `l`.`pack_id` = `t`.`id` ORDER BY `l`.`lang` = 'fr' DESC, `l`.`lang` LIMIT 1), '');

DROP TABLE IF EXISTS `nf_payment_packs_lang`;
