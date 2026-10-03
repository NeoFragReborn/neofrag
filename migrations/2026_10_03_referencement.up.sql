-- Le référencement de chaque site : trois tables du cœur.
--
--   nf_seo_meta        le titre et la description qu'un contenu donne aux moteurs, par langue — le
--                      « titre pour Google » d'une actualité, d'un billet, d'une page, d'une page du wiki.
--                      Vides, tout reste automatique. Même repérage que les révisions : type de contenu,
--                      identifiant, langue.
--   nf_redirects       les anciennes adresses et celles qui les remplacent : avant de répondre 404, le site
--                      cherche ici, et redirige (301) — un classement acquis ne se perd pas quand une page
--                      change d'adresse. `source` est le chemin sans la langue (`ancienne-page`), `target`
--                      un chemin du site ou une adresse complète.
--   nf_indexnow        les adresses du plan du site telles que la tâche planifiée les a vues à son dernier
--                      passage, avec leur date. Une adresse nouvelle, changée ou disparue y est marquée
--                      `pending`, jusqu'à ce qu'IndexNow (Bing, Yandex, Seznam, Naver…) l'ait reçue.
--                      `url` est comparée à l'octet près : deux adresses qui ne diffèrent que d'une
--                      majuscule sont deux pages.

CREATE TABLE IF NOT EXISTS `nf_seo_meta` (
  `content_type` varchar(50) NOT NULL,
  `content_id` int(11) unsigned NOT NULL,
  `lang` varchar(5) NOT NULL DEFAULT '',
  `title` varchar(100) NOT NULL DEFAULT '',
  `description` varchar(255) NOT NULL DEFAULT '',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`content_type`,`content_id`,`lang`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `nf_redirects` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `source` varchar(255) NOT NULL,
  `target` varchar(500) NOT NULL,
  `hits` int(11) unsigned NOT NULL DEFAULT 0,
  `last_hit_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_source` (`source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `nf_indexnow` (
  `url` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `lastmod` date DEFAULT NULL,
  `pending` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `gone` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `changed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`url`),
  KEY `idx_pending` (`pending`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
