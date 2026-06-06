-- Notifications in-site. Chaque ligne = une notif pour un destinataire (user_id), déclenchée
-- par un acteur (actor_id) sur un contenu (type + url). Alimenté par appels directs depuis les
-- modules émetteurs (comments, reactions…), pas par les events (chargement lazy des modules).

CREATE TABLE IF NOT EXISTS nf_notifications (
	id         INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
	user_id    INT(11) UNSIGNED NOT NULL,
	actor_id   INT(11) UNSIGNED NULL DEFAULT NULL,
	type       VARCHAR(50) NOT NULL,
	title      VARCHAR(255) NOT NULL,
	url        VARCHAR(255) NOT NULL DEFAULT '',
	is_read    TINYINT(1) NOT NULL DEFAULT 0,
	created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (id),
	KEY idx_user_read (user_id, is_read, id),
	CONSTRAINT fk_notif_user  FOREIGN KEY (user_id)  REFERENCES nf_user(id) ON DELETE CASCADE,
	CONSTRAINT fk_notif_actor FOREIGN KEY (actor_id) REFERENCES nf_user(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO nf_addon (name, type_id, data)
SELECT 'notifications', t.id, 'a:1:{s:7:"enabled";b:1;}'
FROM nf_addon_type t
WHERE t.name = 'module'
  AND NOT EXISTS (SELECT 1 FROM nf_addon WHERE name = 'notifications');
