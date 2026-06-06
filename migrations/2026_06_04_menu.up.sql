-- Module menu : constructeur de menus nommés réutilisables. Un menu (nf_menus) regroupe des items
-- hiérarchiques (nf_menus_items, parent_id auto-référencé sans FK pour rester souple ; les enfants
-- sont supprimés en code). Les items sont rendus par le widget navigation via leur menu nommé.

CREATE TABLE IF NOT EXISTS nf_menus (
	menu_id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
	name    VARCHAR(64)  NOT NULL,
	title   VARCHAR(128) NOT NULL,
	PRIMARY KEY (menu_id),
	UNIQUE KEY idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS nf_menus_items (
	item_id   INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
	menu_id   INT(11) UNSIGNED NOT NULL,
	parent_id INT(11) UNSIGNED NULL DEFAULT NULL,
	title     VARCHAR(128) NOT NULL,
	url       VARCHAR(255) NOT NULL DEFAULT '',
	icon      VARCHAR(64)  NOT NULL DEFAULT '',
	target    VARCHAR(10)  NOT NULL DEFAULT '',
	position  INT(11)      NOT NULL DEFAULT 0,
	PRIMARY KEY (item_id),
	KEY idx_menu (menu_id, parent_id, position),
	CONSTRAINT fk_menus_items_menu FOREIGN KEY (menu_id) REFERENCES nf_menus(menu_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO nf_addon (name, type_id, data)
SELECT 'menu', t.id, 'a:1:{s:7:"enabled";b:1;}'
FROM nf_addon_type t
WHERE t.name = 'module'
  AND NOT EXISTS (SELECT 1 FROM nf_addon WHERE name = 'menu');
