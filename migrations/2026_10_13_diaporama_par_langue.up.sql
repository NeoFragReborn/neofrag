-- Le diaporama se traduit (entrée m22 du tableau, ligne 0.48 du reste-à-faire, 2026-10-13).
--
-- Le titre et la légende d'une vue vivaient dans `nf_slider_slides` : un site en six langues les montrait dans une
-- seule. Relevé sur la démonstration en anglais, qui affichait « Bienvenue sur la démo ». Ils passent dans une table
-- de traductions, comme les pages, les équipes et les catégories ; le widget sert la meilleure (la langue demandée,
-- sinon le français, sinon la première écrite — `Db::join_lang()`).
--
-- Rien ne se perd et rien ne change à l'écran : le texte d'aujourd'hui est recopié dans TOUTES les langues installées
-- du site, puisque c'est bien ce que tout visiteur voit aujourd'hui, quelle que soit sa langue. L'administration en
-- traduit ensuite autant qu'elle veut : elle écrit dans la langue où elle travaille.

CREATE TABLE IF NOT EXISTS `nf_slider_slides_lang` (
  `slide_id` int(10) unsigned NOT NULL,
  `lang` varchar(5) NOT NULL,
  `title` varchar(200) NOT NULL DEFAULT '',
  `caption` mediumtext DEFAULT NULL,
  PRIMARY KEY (`slide_id`,`lang`),
  KEY `lang` (`lang`),
  CONSTRAINT `nf_slider_slides_lang_ibfk_1` FOREIGN KEY (`slide_id`) REFERENCES `nf_slider_slides` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `nf_slider_slides_lang` (`slide_id`, `lang`, `title`, `caption`)
SELECT `s`.`id`, `a`.`name`, `s`.`title`, `s`.`caption`
FROM `nf_slider_slides` `s`
JOIN `nf_addon` `a` ON `a`.`type_id` = (SELECT `id` FROM `nf_addon_type` WHERE `name` = 'language')
WHERE CHAR_LENGTH(`a`.`name`) = 2;

ALTER TABLE `nf_slider_slides` DROP COLUMN `title`, DROP COLUMN `caption`;
