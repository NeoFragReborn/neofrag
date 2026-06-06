-- Boutique — catalogue d'items + achats. Module découplé (vend grades/cosmétiques/
-- perks/VIP en points, et merch physique plus tard en argent réel). Paiement
-- enfichable : points via gamification maintenant, Stripe gaté plus tard.

CREATE TABLE IF NOT EXISTS nf_shop_items (
	id              INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
	title           VARCHAR(150) NOT NULL,
	description     TEXT,
	icon            VARCHAR(50) NOT NULL DEFAULT 'fas fa-gift',
	price           INT NOT NULL DEFAULT 0,          -- en points
	type            VARCHAR(30) NOT NULL DEFAULT 'perk', -- group, cosmetic, perk, vip, merch
	payload         VARCHAR(255) NOT NULL DEFAULT '',-- group_id, clé perk, jours VIP…
	stock           INT NOT NULL DEFAULT -1,         -- -1 = illimité
	unique_per_user TINYINT(1) NOT NULL DEFAULT 1,
	active          TINYINT(1) NOT NULL DEFAULT 1,
	position        INT NOT NULL DEFAULT 0,
	created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (id),
	KEY idx_active (active, position)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS nf_shop_purchases (
	id         INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
	user_id    INT(11) UNSIGNED NOT NULL,
	item_id    INT(11) UNSIGNED NOT NULL,
	price_paid INT NOT NULL DEFAULT 0,
	created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (id),
	KEY idx_user (user_id),
	KEY idx_item (item_id),
	CONSTRAINT fk_shop_purchase_user FOREIGN KEY (user_id) REFERENCES nf_user(id) ON DELETE CASCADE,
	CONSTRAINT fk_shop_purchase_item FOREIGN KEY (item_id) REFERENCES nf_shop_items(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Deux items de démonstration (modifiables/supprimables en admin).
INSERT INTO nf_shop_items (title, description, icon, price, type, payload, stock, unique_per_user, active, position) VALUES
	('Pass Sans-Pub', 'Retire les publicités du site (avantage de soutien).', 'fas fa-ban', 500, 'perk', 'no_ads', -1, 1, 1, 1),
	('Badge Soutien', 'Un cosmétique pour afficher ton soutien à la communauté.', 'fas fa-heart', 200, 'cosmetic', 'supporter', -1, 1, 1, 2);

-- Enregistre + active le module shop.
INSERT INTO nf_addon (name, type_id, data)
SELECT 'shop', t.id, 'a:1:{s:7:"enabled";b:1;}'
FROM nf_addon_type t
WHERE t.name = 'module'
  AND NOT EXISTS (SELECT 1 FROM nf_addon WHERE name = 'shop');
