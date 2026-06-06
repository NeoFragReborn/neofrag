-- Paiements Stripe — recharge de points + packs VIP (argent réel).
-- nf_payment_packs : catalogue des packs achetables. nf_payments : journal +
-- idempotence (event_id unique → un événement webhook n'est crédité qu'une fois).
-- Inerte tant que les clés Stripe ne sont pas configurées (config pay_stripe_*).

CREATE TABLE IF NOT EXISTS nf_payment_packs (
	id          INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
	kind        ENUM('points', 'vip') NOT NULL DEFAULT 'points',
	label       VARCHAR(150) NOT NULL,
	units       INT NOT NULL DEFAULT 0,           -- points crédités, ou jours de VIP
	price_cents INT NOT NULL DEFAULT 0,           -- prix en centimes
	currency    VARCHAR(3) NOT NULL DEFAULT 'eur',
	active      TINYINT(1) NOT NULL DEFAULT 1,
	position    INT NOT NULL DEFAULT 0,
	created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (id),
	KEY idx_active (active, position)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS nf_payments (
	id         INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
	event_id   VARCHAR(255) NOT NULL DEFAULT '',  -- id de l'événement Stripe (idempotence)
	session_id VARCHAR(255) NOT NULL DEFAULT '',
	user_id    INT(11) UNSIGNED NULL,
	kind       VARCHAR(10) NOT NULL DEFAULT '',
	units      INT NOT NULL DEFAULT 0,
	status     VARCHAR(20) NOT NULL DEFAULT 'completed',
	created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (id),
	UNIQUE KEY uniq_event (event_id),
	KEY idx_user (user_id),
	CONSTRAINT fk_payment_user FOREIGN KEY (user_id) REFERENCES nf_user(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Packs de démonstration (modifiables/désactivables en admin).
INSERT INTO nf_payment_packs (kind, label, units, price_cents, currency, active, position) VALUES
	('points', '1 000 points',  1000, 499,  'eur', 1, 1),
	('points', '5 000 points',  5000, 1999, 'eur', 1, 2),
	('vip',    'VIP 30 jours',   30,  500,  'eur', 1, 3);

INSERT INTO nf_addon (name, type_id, data)
SELECT 'payments', t.id, 'a:1:{s:7:"enabled";b:1;}'
FROM nf_addon_type t WHERE t.name = 'module'
  AND NOT EXISTS (SELECT 1 FROM nf_addon a JOIN nf_addon_type tt ON a.type_id=tt.id WHERE a.name='payments' AND tt.name='module');
