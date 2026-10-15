-- Les événements se traduisent (entrée m31 du tableau, 2026-10-15).
--
-- Le titre d'un rendez-vous, son texte, sa note réservée aux inscrits et son lieu vivaient dans `nf_events` : un
-- site en six langues les montrait dans une seule — relevé sur la démonstration en anglais, qui annonçait
-- « Entraînement CS2 » et « Serveur communautaire ». Ils passent dans une table de traductions, comme le palmarès,
-- les équipes et le diaporama ; les pages servent la meilleure (`Db::join_lang()` : la langue demandée, sinon le
-- français, sinon la première écrite).
--
-- Ce qui n'a pas de langue reste dans `nf_events` : les dates, le type, l'image, la publication, la série, l'auteur.
--
-- Rien ne se perd et rien ne change à l'écran : le texte d'aujourd'hui est recopié dans toutes les langues
-- installées — c'est bien ce que tout visiteur voit aujourd'hui, quelle que soit sa langue.

CREATE TABLE IF NOT EXISTS `nf_events_lang` (
  `event_id` int(11) unsigned NOT NULL,
  `lang` varchar(5) NOT NULL,
  `title` varchar(100) NOT NULL DEFAULT '',
  `description` mediumtext DEFAULT NULL,
  `private_description` mediumtext DEFAULT NULL,
  `location` mediumtext DEFAULT NULL,
  PRIMARY KEY (`event_id`,`lang`),
  KEY `lang` (`lang`),
  CONSTRAINT `nf_events_lang_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `nf_events` (`event_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `nf_events_lang` (`event_id`, `lang`, `title`, `description`, `private_description`, `location`)
SELECT `e`.`event_id`, `l`.`name`, `e`.`title`, `e`.`description`, `e`.`private_description`, `e`.`location`
FROM `nf_events` `e`
JOIN `nf_addon` `l` ON `l`.`type_id` = (SELECT `id` FROM `nf_addon_type` WHERE `name` = 'language')
WHERE CHAR_LENGTH(`l`.`name`) = 2;

ALTER TABLE `nf_events`
  DROP COLUMN `title`, DROP COLUMN `description`, DROP COLUMN `private_description`, DROP COLUMN `location`;
