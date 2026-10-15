-- Le libellé d'un lot de points à acheter se traduit (entrée m32 du tableau, 2026-10-15).
--
-- Le libellé d'un lot de points à acheter n'avait pas de langue : un site en six langues le montrait dans une seule. Il passe dans une table de
-- traductions ; les pages servent la meilleure (`Db::join_lang()` : la langue demandée, sinon le français, sinon
-- la première écrite).
--
-- Ce qui ne se traduit PAS reste où il est : le prix, le nombre de points, l’ordre et l’état.
--
-- Rien ne se perd et rien ne change à l'écran : le texte d'aujourd'hui est recopié dans toutes les langues
-- installées — c'est bien ce que tout visiteur voit aujourd'hui, quelle que soit sa langue.

CREATE TABLE IF NOT EXISTS `nf_payment_packs_lang` (
  `pack_id` int(11) unsigned NOT NULL,
  `lang` varchar(5) NOT NULL,
  `label` varchar(150) NOT NULL DEFAULT '',
  PRIMARY KEY (`pack_id`,`lang`),
  KEY `lang` (`lang`),
  CONSTRAINT `nf_payment_packs_lang_ibfk_1` FOREIGN KEY (`pack_id`) REFERENCES `nf_payment_packs` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `nf_payment_packs_lang` (`pack_id`, `lang`, `label`)
SELECT `t`.`id`, `l`.`name`, `t`.`label`
FROM `nf_payment_packs` `t`
JOIN `nf_addon` `l` ON `l`.`type_id` = (SELECT `id` FROM `nf_addon_type` WHERE `name` = 'language')
WHERE CHAR_LENGTH(`l`.`name`) = 2;

ALTER TABLE `nf_payment_packs` DROP COLUMN `label`;
