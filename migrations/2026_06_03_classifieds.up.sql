-- Module Petites annonces : catégories + annonces membres (offre/demande), modération optionnelle.

CREATE TABLE IF NOT EXISTS nf_classifieds_categories (
	id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
	title      VARCHAR(100) NOT NULL,
	sort_order INT NOT NULL DEFAULT 0,
	PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS nf_classifieds (
	id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
	category_id INT UNSIGNED NOT NULL,
	user_id     INT UNSIGNED NULL,
	ad_type     ENUM('offer','request') NOT NULL DEFAULT 'offer',
	title       VARCHAR(150) NOT NULL,
	description TEXT NOT NULL,
	price       DECIMAL(10,2) NULL,
	contact     VARCHAR(255) NOT NULL DEFAULT '',
	image       VARCHAR(255) NOT NULL DEFAULT '',
	status      ENUM('pending','published','closed','rejected') NOT NULL DEFAULT 'published',
	views       INT UNSIGNED NOT NULL DEFAULT 0,
	created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	PRIMARY KEY (id),
	KEY category_id (category_id),
	KEY user_id (user_id),
	KEY status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO nf_classifieds_categories (title, sort_order)
SELECT * FROM (
	SELECT 'Matériel & high-tech' AS title, 1 AS sort_order UNION ALL
	SELECT 'Comptes & jeux', 2 UNION ALL
	SELECT 'Services', 3 UNION ALL
	SELECT 'Divers', 4
) AS seed
WHERE NOT EXISTS (SELECT 1 FROM nf_classifieds_categories);

INSERT INTO nf_addon (name, type_id, data)
SELECT 'classifieds', t.id, 'a:1:{s:7:"enabled";b:1;}'
FROM nf_addon_type t WHERE t.name = 'module'
  AND NOT EXISTS (SELECT 1 FROM nf_addon a JOIN nf_addon_type tt ON a.type_id=tt.id WHERE a.name='classifieds' AND tt.name='module');
