-- Le nom et la description d'un salon PUBLIC se traduisent (entrée m32 du tableau, 2026-10-15).
--
-- `nf_talks` porte deux sortes de lignes : les salons publics, que l'administration crée et nomme (« Salon
-- général »), et les conversations des membres (à deux, ou en groupe), qu'ils nomment eux-mêmes. Les premiers ont
-- un texte à traduire ; les secondes, non — un membre écrit dans sa langue, et renommer une conversation doit
-- changer son nom pour TOUS ses participants.
--
-- D'où une forme un peu différente des autres traductions : la table `nf_talks_lang` ne porte QUE les salons
-- publics, et `nf_talks.name` / `description` RESTENT — c'est le texte d'une conversation, et pour un salon public
-- le dernier écrit, en secours. Une lecture prend la traduction quand elle existe : COALESCE(traduction, base).
--
-- Rien ne se perd et rien ne change à l'écran : le texte des salons publics est recopié dans toutes les langues
-- installées.

CREATE TABLE IF NOT EXISTS `nf_talks_lang` (
  `talk_id` int(11) unsigned NOT NULL,
  `lang` varchar(5) NOT NULL,
  `name` varchar(150) NOT NULL DEFAULT '',
  `description` varchar(500) NOT NULL DEFAULT '',
  PRIMARY KEY (`talk_id`,`lang`),
  KEY `lang` (`lang`),
  CONSTRAINT `fk_talks_lang_talk` FOREIGN KEY (`talk_id`) REFERENCES `nf_talks` (`talk_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `nf_talks_lang` (`talk_id`, `lang`, `name`, `description`)
SELECT `t`.`talk_id`, `l`.`name`, `t`.`name`, `t`.`description`
FROM `nf_talks` `t`
JOIN `nf_addon` `l` ON `l`.`type_id` = (SELECT `id` FROM `nf_addon_type` WHERE `name` = 'language')
WHERE CHAR_LENGTH(`l`.`name`) = 2
  AND `t`.`type` = 'public';
