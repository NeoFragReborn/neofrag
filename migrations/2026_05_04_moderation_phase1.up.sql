-- Phase 1 modération étendue site-wide.
-- Tables : nf_reports (signalements user) + nf_sanctions (sanctions actives + historique).
-- nf_audit_log existant continue à servir pour l'historique générique des actions admin.
-- nf_rate_limit existant continue à servir pour rate-limit anti-spam des reports.

CREATE TABLE IF NOT EXISTS nf_reports (
	id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	reporter_id     INT UNSIGNED NULL,
	reporter_ip     VARCHAR(45) NOT NULL,
	target_type     VARCHAR(50) NOT NULL,
	target_id       VARCHAR(100) NOT NULL,
	target_user_id  INT UNSIGNED NULL,
	reason          ENUM('spam','harassment','illegal','nsfw','misinformation','duplicate','other') NOT NULL DEFAULT 'other',
	comment         TEXT NULL,
	url             VARCHAR(500) NOT NULL DEFAULT '',
	content_snapshot TEXT NULL,
	status          ENUM('pending','reviewed','actioned','dismissed','duplicate') NOT NULL DEFAULT 'pending',
	handled_by      INT UNSIGNED NULL,
	handled_at      TIMESTAMP NULL DEFAULT NULL,
	handled_action_id BIGINT UNSIGNED NULL,
	handled_note    TEXT NULL,
	created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (id),
	INDEX idx_reports_status_created (status, created_at),
	INDEX idx_reports_target_user (target_user_id, status),
	INDEX idx_reports_reporter (reporter_id),
	INDEX idx_reports_target (target_type, target_id),
	INDEX idx_reports_handled_action (handled_action_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS nf_sanctions (
	id                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	user_id           INT UNSIGNED NOT NULL,
	type              ENUM('warning','mute','ban_temp','ban_perm','restrict_upload','restrict_links','restrict_avatar','restrict_signature','restrict_comment','shadow_ban') NOT NULL,
	scope             ENUM('global','forum','talks','comments','wiki','gallery','guestbook','profile','bugtracker','recruits') NOT NULL DEFAULT 'global',
	reason            TEXT NOT NULL,
	duration_seconds  BIGINT UNSIGNED NULL,
	starts_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	expires_at        TIMESTAMP NULL DEFAULT NULL,
	issued_by         INT UNSIGNED NOT NULL,
	requires_approval TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
	approved_by       INT UNSIGNED NULL,
	approved_at       TIMESTAMP NULL DEFAULT NULL,
	revoked_at        TIMESTAMP NULL DEFAULT NULL,
	revoked_by        INT UNSIGNED NULL,
	revoke_reason     TEXT NULL,
	related_report_id BIGINT UNSIGNED NULL,
	notify_user       TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
	created_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (id),
	INDEX idx_sanctions_active (user_id, type, expires_at, revoked_at),
	INDEX idx_sanctions_scope (user_id, scope, expires_at),
	INDEX idx_sanctions_pending_approval (requires_approval, approved_at),
	INDEX idx_sanctions_related_report (related_report_id),
	INDEX idx_sanctions_issued_by (issued_by, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Settings par défaut pour le module modération
INSERT IGNORE INTO nf_settings (name, value) VALUES
	('nf_moderation_enabled', '1'),
	('nf_moderation_auto_escalation', '0'),
	('nf_moderation_warning_window_days', '30'),
	('nf_moderation_warning_threshold_mute', '3'),
	('nf_moderation_warning_threshold_ban', '5'),
	('nf_moderation_report_rate_limit_per_hour', '5'),
	('nf_moderation_report_flag_threshold_per_day', '20'),
	('nf_moderation_require_approval_ban_perm', '1'),
	('nf_moderation_require_approval_ban_temp', '0'),
	('nf_moderation_default_mute_duration_seconds', '86400'),
	('nf_moderation_default_ban_temp_duration_seconds', '604800'),
	('nf_moderation_preserve_content_snapshot', '1');
