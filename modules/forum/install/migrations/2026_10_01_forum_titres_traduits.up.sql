-- Les titres des catégories et des forums se traduisent (2026-10-01).
--
-- Une catégorie et un forum gardent leur titre par défaut (`title`, `description`) ; ces deux tables
-- portent, langue par langue, une traduction facultative. L'affichage prend celle de la langue du
-- visiteur, sinon le titre par défaut : un forum jamais traduit s'affiche exactement comme avant.

CREATE TABLE IF NOT EXISTS `nf_forum_lang` (
  `forum_id` int(11) unsigned NOT NULL,
  `lang` varchar(5) NOT NULL,
  `title` varchar(100) NOT NULL DEFAULT '',
  `description` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`forum_id`,`lang`),
  CONSTRAINT `nf_forum_lang_ibfk_1` FOREIGN KEY (`forum_id`) REFERENCES `nf_forum` (`forum_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `nf_forum_categories_lang` (
  `category_id` int(11) unsigned NOT NULL,
  `lang` varchar(5) NOT NULL,
  `title` varchar(100) NOT NULL DEFAULT '',
  PRIMARY KEY (`category_id`,`lang`),
  CONSTRAINT `nf_forum_categories_lang_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `nf_forum_categories` (`category_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
