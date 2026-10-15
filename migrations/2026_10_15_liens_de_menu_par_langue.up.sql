-- Les liens des menus se traduisent (entrée m32 du tableau, 2026-10-15).
--
-- Le titre d'un lien de menu (« Nous rejoindre », « Règlement ») n'avait pas de langue : un site en six langues le
-- montrait dans une seule, dans la navigation, c'est-à-dire sur chaque page. Il passe dans une table de
-- traductions ; la navigation sert la meilleure (`Db::join_lang()` : la langue demandée, sinon le français, sinon la
-- première écrite).
--
-- Ce qui ne se traduit PAS : l'adresse, l'icône, l'ouverture, la position — et le NOM du menu lui-même (« La
-- communauté »), qui n'est montré qu'à l'administration, pour choisir quel menu un widget affiche.
--
-- Rien ne se perd et rien ne change à l'écran : le texte d'aujourd'hui est recopié dans toutes les langues
-- installées.

CREATE TABLE IF NOT EXISTS `nf_menus_items_lang` (
  `item_id` int(11) unsigned NOT NULL,
  `lang` varchar(5) NOT NULL,
  `title` varchar(128) NOT NULL DEFAULT '',
  PRIMARY KEY (`item_id`,`lang`),
  KEY `lang` (`lang`),
  CONSTRAINT `fk_menus_items_lang_item` FOREIGN KEY (`item_id`) REFERENCES `nf_menus_items` (`item_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `nf_menus_items_lang` (`item_id`, `lang`, `title`)
SELECT `i`.`item_id`, `l`.`name`, `i`.`title`
FROM `nf_menus_items` `i`
JOIN `nf_addon` `l` ON `l`.`type_id` = (SELECT `id` FROM `nf_addon_type` WHERE `name` = 'language')
WHERE CHAR_LENGTH(`l`.`name`) = 2;

ALTER TABLE `nf_menus_items` DROP COLUMN `title`;
