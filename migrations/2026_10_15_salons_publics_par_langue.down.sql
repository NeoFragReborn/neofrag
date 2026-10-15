-- Retour arrière de 2026_10_15_salons_publics_par_langue : les salons publics redeviennent monolingues.
--
-- Le nom et la description d'un salon public reprennent la MEILLEURE traduction gardée (le français d'abord, sinon
-- la première écrite) : on perd les autres langues, pas le texte. Les conversations des membres n'ont jamais quitté
-- `nf_talks`.

UPDATE `nf_talks` `t`
JOIN `nf_talks_lang` `l` ON `l`.`talk_id` = `t`.`talk_id`
  AND `l`.`lang` = (SELECT `c`.`lang` FROM `nf_talks_lang` `c` WHERE `c`.`talk_id` = `t`.`talk_id` ORDER BY `c`.`lang` = 'fr' DESC, `c`.`lang` LIMIT 1)
SET `t`.`name` = `l`.`name`, `t`.`description` = `l`.`description`;

DROP TABLE IF EXISTS `nf_talks_lang`;
