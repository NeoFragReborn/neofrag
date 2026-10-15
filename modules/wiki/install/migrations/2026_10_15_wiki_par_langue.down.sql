-- Retour arrière de 2026_10_15_wiki_par_langue : le wiki redevient monolingue.
--
-- Les colonnes reviennent, remplies de la MEILLEURE traduction gardée (le français d'abord, sinon la première
-- écrite) : on perd les autres langues, pas le texte. L'historique garde toutes ses révisions, sans leur langue.

ALTER TABLE `nf_wiki_pages`
  ADD COLUMN `title` varchar(200) NOT NULL DEFAULT '' AFTER `slug`,
  ADD COLUMN `content` mediumtext DEFAULT NULL AFTER `title`;

UPDATE `nf_wiki_pages` `p`
SET `p`.`title` = COALESCE((SELECT `l`.`title` FROM `nf_wiki_pages_lang` `l` WHERE `l`.`page_id` = `p`.`id` ORDER BY `l`.`lang` = 'fr' DESC, `l`.`lang` LIMIT 1), ''),
    `p`.`content` = COALESCE((SELECT `l`.`content` FROM `nf_wiki_pages_lang` `l` WHERE `l`.`page_id` = `p`.`id` ORDER BY `l`.`lang` = 'fr' DESC, `l`.`lang` LIMIT 1), '');

ALTER TABLE `nf_wiki_pages` MODIFY `title` varchar(200) NOT NULL, MODIFY `content` mediumtext NOT NULL;

ALTER TABLE `nf_wiki_revisions` DROP KEY `idx_page_lang`, DROP COLUMN `lang`;

DROP TABLE IF EXISTS `nf_wiki_pages_lang`;
