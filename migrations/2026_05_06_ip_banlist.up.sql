-- =====================================================================
-- NeoFrag — IP Banlist (R2.0, 2026-05-06)
-- =====================================================================

CREATE TABLE IF NOT EXISTS nf_ip_banlist (
  ban_id      INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  ip          VARCHAR(45) NOT NULL,
  reason      VARCHAR(500) DEFAULT NULL,
  banned_by   INT(11) UNSIGNED DEFAULT NULL,
  expires_at  TIMESTAMP NULL DEFAULT NULL,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (ban_id),
  UNIQUE KEY uk_ip (ip),
  INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
