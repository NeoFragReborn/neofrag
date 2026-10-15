-- Retour arrière de 2026_10_15_faq_par_langue : la FAQ redevient monolingue.
--
-- Les colonnes reviennent, remplies de la MEILLEURE traduction gardée (le français d'abord, sinon la première
-- écrite) : on perd les autres langues, pas le texte.

ALTER TABLE `nf_faq_questions`
  ADD COLUMN `question` varchar(500) NOT NULL DEFAULT '' AFTER `category_id`,
  ADD COLUMN `answer` mediumtext NOT NULL AFTER `question`;

ALTER TABLE `nf_faq_categories`
  ADD COLUMN `title` varchar(150) NOT NULL DEFAULT '' AFTER `id`;

UPDATE `nf_faq_questions` `q`
SET `q`.`question` = COALESCE((SELECT `l`.`question` FROM `nf_faq_questions_lang` `l` WHERE `l`.`question_id` = `q`.`id` ORDER BY `l`.`lang` = 'fr' DESC, `l`.`lang` LIMIT 1), ''),
    `q`.`answer` = COALESCE((SELECT `l`.`answer` FROM `nf_faq_questions_lang` `l` WHERE `l`.`question_id` = `q`.`id` ORDER BY `l`.`lang` = 'fr' DESC, `l`.`lang` LIMIT 1), '');

UPDATE `nf_faq_categories` `c`
SET `c`.`title` = COALESCE((SELECT `l`.`title` FROM `nf_faq_categories_lang` `l` WHERE `l`.`category_id` = `c`.`id` ORDER BY `l`.`lang` = 'fr' DESC, `l`.`lang` LIMIT 1), '');

DROP TABLE IF EXISTS `nf_faq_questions_lang`;
DROP TABLE IF EXISTS `nf_faq_categories_lang`;
