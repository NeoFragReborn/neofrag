-- La FAQ se traduit (entrée m32 du tableau, 2026-10-15).
--
-- La question, sa réponse et le titre de ses rubriques vivaient dans `nf_faq_questions` et `nf_faq_categories` :
-- un site en six langues les montrait dans une seule. Ils passent dans deux tables de traductions, comme les
-- téléchargements et les liens ; les pages servent la meilleure (`Db::join_lang()` : la langue demandée, sinon le
-- français, sinon la première écrite).
--
-- Ce qui ne se traduit PAS reste où il est : la rubrique, l'ordre de tri, la publication et les dates.
--
-- Rien ne se perd et rien ne change à l'écran : le texte d'aujourd'hui est recopié dans toutes les langues
-- installées — c'est bien ce que tout visiteur voit aujourd'hui, quelle que soit sa langue.

CREATE TABLE IF NOT EXISTS `nf_faq_questions_lang` (
  `question_id` int(10) unsigned NOT NULL,
  `lang` varchar(5) NOT NULL,
  `question` varchar(500) NOT NULL DEFAULT '',
  `answer` mediumtext NOT NULL,
  PRIMARY KEY (`question_id`,`lang`),
  KEY `lang` (`lang`),
  CONSTRAINT `nf_faq_questions_lang_ibfk_1` FOREIGN KEY (`question_id`) REFERENCES `nf_faq_questions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `nf_faq_categories_lang` (
  `category_id` int(10) unsigned NOT NULL,
  `lang` varchar(5) NOT NULL,
  `title` varchar(150) NOT NULL DEFAULT '',
  PRIMARY KEY (`category_id`,`lang`),
  KEY `lang` (`lang`),
  CONSTRAINT `nf_faq_categories_lang_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `nf_faq_categories` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `nf_faq_questions_lang` (`question_id`, `lang`, `question`, `answer`)
SELECT `q`.`id`, `l`.`name`, `q`.`question`, `q`.`answer`
FROM `nf_faq_questions` `q`
JOIN `nf_addon` `l` ON `l`.`type_id` = (SELECT `id` FROM `nf_addon_type` WHERE `name` = 'language')
WHERE CHAR_LENGTH(`l`.`name`) = 2;

INSERT IGNORE INTO `nf_faq_categories_lang` (`category_id`, `lang`, `title`)
SELECT `c`.`id`, `l`.`name`, `c`.`title`
FROM `nf_faq_categories` `c`
JOIN `nf_addon` `l` ON `l`.`type_id` = (SELECT `id` FROM `nf_addon_type` WHERE `name` = 'language')
WHERE CHAR_LENGTH(`l`.`name`) = 2;

ALTER TABLE `nf_faq_questions` DROP COLUMN `question`, DROP COLUMN `answer`;
ALTER TABLE `nf_faq_categories` DROP COLUMN `title`;
