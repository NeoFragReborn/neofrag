-- Webhooks sortants : à chaque événement (news publiée, nouveau membre…), POST signé HMAC vers les
-- URLs configurées (Discord, Zapier, n8n…). events = liste CSV d'événements abonnés ('*' = tous).

CREATE TABLE IF NOT EXISTS nf_webhooks (
	id         INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
	title      VARCHAR(128) NOT NULL,
	url        VARCHAR(255) NOT NULL,
	secret     VARCHAR(128) NOT NULL DEFAULT '',
	events     TEXT NOT NULL,
	enabled    TINYINT(1) NOT NULL DEFAULT 1,
	created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (id),
	KEY idx_enabled (enabled)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO nf_addon (name, type_id, data)
SELECT 'webhooks', t.id, 'a:1:{s:7:"enabled";b:1;}'
FROM nf_addon_type t
WHERE t.name = 'module'
  AND NOT EXISTS (SELECT 1 FROM nf_addon WHERE name = 'webhooks');
