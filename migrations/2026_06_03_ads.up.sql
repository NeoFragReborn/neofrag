-- Régie publicitaire — bannières/HTML par emplacement, masquées pour les membres
-- ayant le perk 'no_ads' (boutique) ou le statut VIP. Module + widget plaçable.

CREATE TABLE IF NOT EXISTS nf_ads (
	id          INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
	title       VARCHAR(150) NOT NULL,
	placement   VARCHAR(50) NOT NULL DEFAULT 'sidebar',
	format      ENUM('image', 'html') NOT NULL DEFAULT 'image',
	image_url   VARCHAR(255) NOT NULL DEFAULT '',
	url         VARCHAR(255) NOT NULL DEFAULT '',
	html        TEXT,
	active      TINYINT(1) NOT NULL DEFAULT 1,
	starts_at   DATETIME NULL,
	ends_at     DATETIME NULL,
	position    INT NOT NULL DEFAULT 0,
	impressions INT UNSIGNED NOT NULL DEFAULT 0,
	clicks      INT UNSIGNED NOT NULL DEFAULT 0,
	created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (id),
	KEY idx_pick (placement, active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Enregistre le module ads + le widget ads.
INSERT INTO nf_addon (name, type_id, data)
SELECT 'ads', t.id, 'a:1:{s:7:"enabled";b:1;}'
FROM nf_addon_type t WHERE t.name = 'module'
  AND NOT EXISTS (SELECT 1 FROM nf_addon a JOIN nf_addon_type tt ON a.type_id=tt.id WHERE a.name='ads' AND tt.name='module');

INSERT INTO nf_addon (name, type_id, data)
SELECT 'ads', t.id, 'a:1:{s:7:"enabled";b:1;}'
FROM nf_addon_type t WHERE t.name = 'widget'
  AND NOT EXISTS (SELECT 1 FROM nf_addon a JOIN nf_addon_type tt ON a.type_id=tt.id WHERE a.name='ads' AND tt.name='widget');
