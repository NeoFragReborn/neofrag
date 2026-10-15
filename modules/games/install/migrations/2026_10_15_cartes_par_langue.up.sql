-- Le nom d'une carte de jeu se traduit (entrée m32 du tableau, 2026-10-15).
--
-- Le nom d'une carte de jeu n'avait pas de langue : un site en six langues le montrait dans une seule. Il passe dans une table de
-- traductions, comme les téléchargements, les liens, la FAQ, les sondages et les types de rendez-vous ; les pages
-- servent la meilleure (`Db::join_lang()` : la langue demandée, sinon le français, sinon la première écrite).
--
-- Ce qui ne se traduit PAS reste où il est : le jeu, l’image et l’ordre.
--
-- Rien ne se perd et rien ne change à l'écran : le texte d'aujourd'hui est recopié dans toutes les langues
-- installées — c'est bien ce que tout visiteur voit aujourd'hui, quelle que soit sa langue.

CREATE TABLE IF NOT EXISTS `nf_games_maps_lang` (
  `map_id` int(11) unsigned NOT NULL,
  `lang` varchar(5) NOT NULL,
  `title` varchar(100) NOT NULL DEFAULT '',
  PRIMARY KEY (`map_id`,`lang`),
  KEY `lang` (`lang`),
  CONSTRAINT `nf_games_maps_lang_ibfk_1` FOREIGN KEY (`map_id`) REFERENCES `nf_games_maps` (`map_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `nf_games_maps_lang` (`map_id`, `lang`, `title`)
SELECT `t`.`map_id`, `l`.`name`, `t`.`title`
FROM `nf_games_maps` `t`
JOIN `nf_addon` `l` ON `l`.`type_id` = (SELECT `id` FROM `nf_addon_type` WHERE `name` = 'language')
WHERE CHAR_LENGTH(`l`.`name`) = 2;

ALTER TABLE `nf_games_maps` DROP COLUMN `title`;
