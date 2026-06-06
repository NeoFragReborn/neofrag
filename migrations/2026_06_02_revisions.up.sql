-- Historique de révisions générique. Un snapshot JSON du contenu (par langue) à chaque
-- enregistrement (création / édition / restauration). Polymorphe via (content_type, content_id),
-- donc pas de FK vers le contenu (la table cible dépend de content_type).

CREATE TABLE IF NOT EXISTS nf_revisions (
	id           INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
	content_type VARCHAR(50) NOT NULL,
	content_id   INT(11) UNSIGNED NOT NULL,
	lang         VARCHAR(5) NOT NULL DEFAULT '',
	user_id      INT(11) UNSIGNED NULL DEFAULT NULL,
	summary      VARCHAR(100) NOT NULL DEFAULT '',
	data         MEDIUMTEXT NOT NULL,
	created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (id),
	KEY idx_content (content_type, content_id, id),
	CONSTRAINT fk_revisions_user FOREIGN KEY (user_id) REFERENCES nf_user(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Enregistre + active le module revisions (data sérialisé PHP : ['enabled' => true]).
INSERT INTO nf_addon (name, type_id, data)
SELECT 'revisions', t.id, 'a:1:{s:7:"enabled";b:1;}'
FROM nf_addon_type t
WHERE t.name = 'module'
  AND NOT EXISTS (SELECT 1 FROM nf_addon WHERE name = 'revisions');
