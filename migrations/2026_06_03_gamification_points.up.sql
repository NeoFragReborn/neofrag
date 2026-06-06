-- Gamification / Phase 2 — Points (monnaie virtuelle).
-- Solde par membre + journal des transactions (gains/dépenses) pour l'audit et
-- le calcul des plafonds anti-farm quotidiens. Le module gamification est déjà
-- enregistré (migration karma), on ne touche pas nf_addon ici.

CREATE TABLE IF NOT EXISTS nf_user_points (
	user_id    INT(11) UNSIGNED NOT NULL,
	total      INT NOT NULL DEFAULT 0,
	earned     INT NOT NULL DEFAULT 0,
	spent      INT NOT NULL DEFAULT 0,
	updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	PRIMARY KEY (user_id),
	CONSTRAINT fk_upoints_user FOREIGN KEY (user_id) REFERENCES nf_user(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS nf_points_log (
	id         INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
	user_id    INT(11) UNSIGNED NOT NULL,
	amount     INT NOT NULL,                 -- signé : positif = gain, négatif = dépense
	type       VARCHAR(50) NOT NULL,         -- 'comment', 'forum_topic', 'spend', …
	reason     VARCHAR(255) NOT NULL DEFAULT '',
	created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (id),
	KEY idx_user (user_id),
	KEY idx_user_type_date (user_id, type, created_at),
	CONSTRAINT fk_plog_user FOREIGN KEY (user_id) REFERENCES nf_user(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
