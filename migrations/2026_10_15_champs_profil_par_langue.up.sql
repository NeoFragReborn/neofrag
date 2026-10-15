-- Les champs de profil se traduisent (entrée m32 du tableau, 2026-10-15).
--
-- Le libellé d'un champ de profil (« Pseudo en jeu », « Plate-forme ») et son explication n'avaient pas de langue :
-- un site en six langues les montrait dans une seule, sur la fiche publique comme dans le formulaire du membre.
-- Ils passent dans une table de traductions ; les pages servent la meilleure (`Db::join_lang()`).
--
-- Ce qui ne se traduit PAS, et c'est VOULU : le nom technique (la clé qui relie la définition aux valeurs déjà
-- saisies), le type, l'obligation, la visibilité, l'ordre — et la LISTE D'OPTIONS d'un champ à choix. Une option
-- est enregistrée telle quelle dans la réponse du membre : la traduire ferait d'une réponse déjà donnée une
-- réponse introuvable. C'est la même raison qui a demandé, pour les sondages, que les réponses gardent leur
-- identité.
--
-- Rien ne se perd et rien ne change à l'écran : le texte d'aujourd'hui est recopié dans toutes les langues
-- installées.

CREATE TABLE IF NOT EXISTS `nf_user_fields_lang` (
  `field_id` int(11) unsigned NOT NULL,
  `lang` varchar(5) NOT NULL,
  `label` varchar(200) NOT NULL DEFAULT '',
  `description` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`field_id`,`lang`),
  KEY `lang` (`lang`),
  CONSTRAINT `nf_user_fields_lang_ibfk_1` FOREIGN KEY (`field_id`) REFERENCES `nf_user_fields` (`field_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `nf_user_fields_lang` (`field_id`, `lang`, `label`, `description`)
SELECT `f`.`field_id`, `l`.`name`, `f`.`label`, COALESCE(`f`.`description`, '')
FROM `nf_user_fields` `f`
JOIN `nf_addon` `l` ON `l`.`type_id` = (SELECT `id` FROM `nf_addon_type` WHERE `name` = 'language')
WHERE CHAR_LENGTH(`l`.`name`) = 2;

ALTER TABLE `nf_user_fields` DROP COLUMN `label`, DROP COLUMN `description`;
