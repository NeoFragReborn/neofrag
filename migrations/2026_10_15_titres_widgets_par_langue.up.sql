-- Le titre donné à un widget se traduit (entrée m32 du tableau, 2026-10-15).
--
-- Un widget porte le titre de son type, traduit comme toute l'interface (« Derniers sujets », « Latest topics ») ;
-- l'éditeur en direct permet de lui en donner un autre. Ce titre-là n'avait pas de langue : écrit en français, il
-- s'affichait en français partout. Il passe dans une table de traductions.
--
-- Un titre VIDE écrit pour une langue garde son sens : « le titre du widget », traduit — c'est ainsi qu'on rend à
-- l'anglais le titre d'origine quand le français en a un autre. Un widget sans aucune ligne n'a pas de titre donné.
--
-- Rien ne se perd et rien ne change à l'écran : seuls les widgets qui ont un titre donné le voient recopié dans
-- toutes les langues installées.

CREATE TABLE IF NOT EXISTS `nf_widgets_lang` (
  `widget_id` int(11) unsigned NOT NULL,
  `lang` varchar(5) NOT NULL,
  `title` varchar(100) NOT NULL DEFAULT '',
  PRIMARY KEY (`widget_id`,`lang`),
  KEY `lang` (`lang`),
  CONSTRAINT `fk_widgets_lang_widget` FOREIGN KEY (`widget_id`) REFERENCES `nf_widgets` (`widget_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `nf_widgets_lang` (`widget_id`, `lang`, `title`)
SELECT `w`.`widget_id`, `l`.`name`, `w`.`title`
FROM `nf_widgets` `w`
JOIN `nf_addon` `l` ON `l`.`type_id` = (SELECT `id` FROM `nf_addon_type` WHERE `name` = 'language')
WHERE CHAR_LENGTH(`l`.`name`) = 2
  AND `w`.`title` IS NOT NULL AND `w`.`title` <> '';

ALTER TABLE `nf_widgets` DROP COLUMN `title`;
