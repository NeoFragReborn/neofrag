-- Le titre, l'accroche, la description et les prérequis d'une offre de recrutement se traduit (entrée m32 du tableau, 2026-10-15).
--
-- Le titre, l'accroche, la description et les prérequis d'une offre de recrutement n'avait pas de langue : un site en six langues le montrait dans une seule. Il passe dans une table de
-- traductions ; les pages servent la meilleure (`Db::join_lang()` : la langue demandée, sinon le français, sinon
-- la première écrite).
--
-- Ce qui ne se traduit PAS reste où il est : l’équipe, le rôle, le nombre de places, les dates, l’état et les candidatures.
--
-- Rien ne se perd et rien ne change à l'écran : le texte d'aujourd'hui est recopié dans toutes les langues
-- installées — c'est bien ce que tout visiteur voit aujourd'hui, quelle que soit sa langue.

CREATE TABLE IF NOT EXISTS `nf_recruits_lang` (
  `recruit_id` int(11) unsigned NOT NULL,
  `lang` varchar(5) NOT NULL,
  `title` varchar(100) NOT NULL DEFAULT '',
  `introduction` mediumtext DEFAULT NULL,
  `description` mediumtext DEFAULT NULL,
  `requierments` mediumtext DEFAULT NULL,
  PRIMARY KEY (`recruit_id`,`lang`),
  KEY `lang` (`lang`),
  CONSTRAINT `nf_recruits_lang_ibfk_1` FOREIGN KEY (`recruit_id`) REFERENCES `nf_recruits` (`recruit_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `nf_recruits_lang` (`recruit_id`, `lang`, `title`, `introduction`, `description`, `requierments`)
SELECT `t`.`recruit_id`, `l`.`name`, `t`.`title`, `t`.`introduction`, `t`.`description`, `t`.`requierments`
FROM `nf_recruits` `t`
JOIN `nf_addon` `l` ON `l`.`type_id` = (SELECT `id` FROM `nf_addon_type` WHERE `name` = 'language')
WHERE CHAR_LENGTH(`l`.`name`) = 2;

ALTER TABLE `nf_recruits` DROP COLUMN `title`, DROP COLUMN `introduction`, DROP COLUMN `description`, DROP COLUMN `requierments`;
