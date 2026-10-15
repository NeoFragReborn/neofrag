-- Le libellé d'une question du formulaire de candidature se traduit (entrée m32 du tableau, 2026-10-15).
--
-- Le libellé d'une question du formulaire de candidature n'avait pas de langue : un site en six langues le montrait dans une seule. Il passe dans une table de
-- traductions ; les pages servent la meilleure (`Db::join_lang()` : la langue demandée, sinon le français, sinon
-- la première écrite).
--
-- Ce qui ne se traduit PAS reste où il est : le type, l’obligation et l’ordre — et les RÉPONSES des candidats, qui n’ont jamais eu de langue.
--
-- Rien ne se perd et rien ne change à l'écran : le texte d'aujourd'hui est recopié dans toutes les langues
-- installées — c'est bien ce que tout visiteur voit aujourd'hui, quelle que soit sa langue.

CREATE TABLE IF NOT EXISTS `nf_recruits_fields_lang` (
  `field_id` int(11) unsigned NOT NULL,
  `lang` varchar(5) NOT NULL,
  `label` varchar(200) NOT NULL DEFAULT '',
  PRIMARY KEY (`field_id`,`lang`),
  KEY `lang` (`lang`),
  CONSTRAINT `nf_recruits_fields_lang_ibfk_1` FOREIGN KEY (`field_id`) REFERENCES `nf_recruits_fields` (`field_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `nf_recruits_fields_lang` (`field_id`, `lang`, `label`)
SELECT `t`.`field_id`, `l`.`name`, `t`.`label`
FROM `nf_recruits_fields` `t`
JOIN `nf_addon` `l` ON `l`.`type_id` = (SELECT `id` FROM `nf_addon_type` WHERE `name` = 'language')
WHERE CHAR_LENGTH(`l`.`name`) = 2;

ALTER TABLE `nf_recruits_fields` DROP COLUMN `label`;
