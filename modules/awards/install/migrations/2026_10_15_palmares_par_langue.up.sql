-- Le palmarès se traduit (entrée m31 du tableau, 2026-10-15).
--
-- Le nom d'un titre remporté, le lieu et le récit vivaient dans `nf_awards` : un site en six langues les montrait
-- dans une seule — relevé sur la démonstration en anglais, qui affichait « Finale régionale ». Ils passent dans une
-- table de traductions, comme les équipes, les jeux et le diaporama ; les pages servent la meilleure
-- (`Db::join_lang()` : la langue demandée, sinon le français, sinon la première écrite).
--
-- Ce qui ne se traduit PAS reste dans `nf_awards` : la date, le rang, le nombre de participants, la plate-forme
-- (« PC », « PS5 »), l'équipe, le jeu et l'image.
--
-- Rien ne se perd et rien ne change à l'écran : le texte d'aujourd'hui est recopié dans toutes les langues
-- installées — c'est bien ce que tout visiteur voit aujourd'hui, quelle que soit sa langue.

CREATE TABLE IF NOT EXISTS `nf_awards_lang` (
  `award_id` int(11) unsigned NOT NULL,
  `lang` varchar(5) NOT NULL,
  `name` varchar(100) NOT NULL DEFAULT '',
  `location` varchar(100) NOT NULL DEFAULT '',
  `description` mediumtext DEFAULT NULL,
  PRIMARY KEY (`award_id`,`lang`),
  KEY `lang` (`lang`),
  CONSTRAINT `nf_awards_lang_ibfk_1` FOREIGN KEY (`award_id`) REFERENCES `nf_awards` (`award_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `nf_awards_lang` (`award_id`, `lang`, `name`, `location`, `description`)
SELECT `a`.`award_id`, `l`.`name`, `a`.`name`, `a`.`location`, `a`.`description`
FROM `nf_awards` `a`
JOIN `nf_addon` `l` ON `l`.`type_id` = (SELECT `id` FROM `nf_addon_type` WHERE `name` = 'language')
WHERE CHAR_LENGTH(`l`.`name`) = 2;

ALTER TABLE `nf_awards` DROP COLUMN `name`, DROP COLUMN `location`, DROP COLUMN `description`;
