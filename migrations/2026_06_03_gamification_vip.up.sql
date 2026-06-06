-- Gamification / Phase 4 — VIP (statut premium à durée).
-- Statut VIP par membre avec expiration (vérifiée en lazy, pas de cron requis).
-- Octroyé par achat boutique (item type 'vip', payload = nombre de jours).

CREATE TABLE IF NOT EXISTS nf_vip (
	user_id    INT(11) UNSIGNED NOT NULL,
	expires_at DATETIME NOT NULL,
	source     VARCHAR(50) NOT NULL DEFAULT '',
	updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	PRIMARY KEY (user_id),
	KEY idx_expires (expires_at),
	CONSTRAINT fk_vip_user FOREIGN KEY (user_id) REFERENCES nf_user(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Item de démo : VIP 30 jours (rachetable pour prolonger → unique_per_user = 0).
INSERT INTO nf_shop_items (title, description, icon, price, type, payload, stock, unique_per_user, active, position)
VALUES ('VIP 30 jours', 'Statut VIP pendant 30 jours : badge exclusif et avantages.', 'fas fa-crown', 1000, 'vip', '30', -1, 0, 1, 0);
