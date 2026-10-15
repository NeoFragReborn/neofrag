-- Retour arrière de 2026_10_14_partenaires_par_langue : un partenaire ne garde qu'une langue.
--
-- Les lignes en trop sont effacées avant de rétrécir la clé — le français d'abord, sinon la première écrite —,
-- sans quoi l'ALTER échouerait sur un doublon.

DELETE `l` FROM `nf_partners_lang` `l`
JOIN (
  SELECT `partner_id`, (SELECT `lang` FROM `nf_partners_lang` `g`
                        WHERE `g`.`partner_id` = `p`.`partner_id`
                        ORDER BY `g`.`lang` = 'fr' DESC, `g`.`lang` LIMIT 1) AS `garde`
  FROM (SELECT DISTINCT `partner_id` FROM `nf_partners_lang`) `p`
) `k` ON `k`.`partner_id` = `l`.`partner_id` AND `k`.`garde` <> `l`.`lang`;

ALTER TABLE `nf_partners_lang` DROP PRIMARY KEY, ADD PRIMARY KEY (`partner_id`);

ALTER TABLE `nf_partners_lang` MODIFY `partner_id` int(11) unsigned NOT NULL AUTO_INCREMENT;
