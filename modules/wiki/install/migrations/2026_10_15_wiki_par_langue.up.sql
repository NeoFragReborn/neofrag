-- Les pages du wiki se traduisent (entrée m32 du tableau, 2026-10-15).
--
-- Le titre et le texte d'une page n'avaient pas de langue : un site en six langues les montrait dans une seule. Ils
-- passent dans une table de traductions ; les pages servent la meilleure (`Db::join_lang()` : la langue demandée,
-- sinon le français, sinon la première écrite).
--
-- Ce qui ne se traduit PAS : l'adresse (`slug`, la même dans toutes les langues), la page parente, l'ordre, l'état
-- et les vues.
--
-- L'HISTORIQUE suit la langue : une révision garde désormais la langue de la version qu'elle archive (`lang`), et
-- l'historique d'une langue ne montre que les siennes. Les révisions d'avant n'ont pas de langue (NULL) : elles
-- restent dans l'historique de toutes.
--
-- Rien ne se perd et rien ne change à l'écran : le texte d'aujourd'hui est recopié dans toutes les langues
-- installées.

CREATE TABLE IF NOT EXISTS `nf_wiki_pages_lang` (
  `page_id` int(10) unsigned NOT NULL,
  `lang` varchar(5) NOT NULL,
  `title` varchar(200) NOT NULL DEFAULT '',
  `content` mediumtext DEFAULT NULL,
  PRIMARY KEY (`page_id`,`lang`),
  KEY `lang` (`lang`),
  CONSTRAINT `nf_wiki_pages_lang_ibfk_1` FOREIGN KEY (`page_id`) REFERENCES `nf_wiki_pages` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `nf_wiki_pages_lang` (`page_id`, `lang`, `title`, `content`)
SELECT `p`.`id`, `l`.`name`, `p`.`title`, `p`.`content`
FROM `nf_wiki_pages` `p`
JOIN `nf_addon` `l` ON `l`.`type_id` = (SELECT `id` FROM `nf_addon_type` WHERE `name` = 'language')
WHERE CHAR_LENGTH(`l`.`name`) = 2;

ALTER TABLE `nf_wiki_revisions`
  ADD COLUMN `lang` varchar(5) DEFAULT NULL AFTER `page_id`,
  ADD KEY `idx_page_lang` (`page_id`,`lang`);

ALTER TABLE `nf_wiki_pages` DROP COLUMN `title`, DROP COLUMN `content`;
