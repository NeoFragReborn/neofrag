-- Réactions / likes génériques. Un "like" par utilisateur sur un contenu identifié par
-- (content_type, content_id) — polymorphe (comment, forum_message, article, news…), donc
-- pas de clé étrangère (la table cible dépend de content_type).

CREATE TABLE IF NOT EXISTS nf_reactions (
	id           INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
	user_id      INT(11) UNSIGNED NOT NULL,
	content_type VARCHAR(50) NOT NULL,
	content_id   INT(11) UNSIGNED NOT NULL,
	created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (id),
	UNIQUE KEY uniq_reaction (user_id, content_type, content_id),
	KEY idx_content (content_type, content_id),
	CONSTRAINT fk_reactions_user FOREIGN KEY (user_id) REFERENCES nf_user(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Enregistre + active le module reactions (data sérialisé PHP : ['enabled' => true]).
INSERT INTO nf_addon (name, type_id, data)
SELECT 'reactions', t.id, 'a:1:{s:7:"enabled";b:1;}'
FROM nf_addon_type t
WHERE t.name = 'module'
  AND NOT EXISTS (SELECT 1 FROM nf_addon WHERE name = 'reactions');
