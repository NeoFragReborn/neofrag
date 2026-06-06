-- Gamification / Phase 1 — Karma (réputation).
-- Agrégat de réputation CACHÉ par membre (recalculé depuis les sources : réactions
-- reçues + contenu publié + ancienneté). Pas de FK polymorphe : les sources sont
-- recalculées par le module, cette table est un cache pour l'affichage.

CREATE TABLE IF NOT EXISTS nf_karma (
	user_id            INT(11) UNSIGNED NOT NULL,
	score              INT NOT NULL DEFAULT 0,
	reactions_received INT NOT NULL DEFAULT 0,
	content_count      INT NOT NULL DEFAULT 0,
	updated_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	PRIMARY KEY (user_id),
	KEY idx_score (score),
	CONSTRAINT fk_karma_user FOREIGN KEY (user_id) REFERENCES nf_user(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Enregistre + active le module gamification (data sérialisé PHP : ['enabled' => true]).
INSERT INTO nf_addon (name, type_id, data)
SELECT 'gamification', t.id, 'a:1:{s:7:"enabled";b:1;}'
FROM nf_addon_type t
WHERE t.name = 'module'
  AND NOT EXISTS (SELECT 1 FROM nf_addon WHERE name = 'gamification');
