-- Table de copie défensive des pièces jointes au moment du signalement.
-- Permet aux modos de récupérer les fichiers même si l'auteur les supprime après.
CREATE TABLE IF NOT EXISTS nf_reports_attachments_snapshot (
	id INT UNSIGNED NOT NULL AUTO_INCREMENT,
	report_id BIGINT UNSIGNED NOT NULL,
	original_file_id INT UNSIGNED NULL,
	original_name VARCHAR(255) NOT NULL,
	mime_type VARCHAR(100) NOT NULL DEFAULT '',
	file_size INT UNSIGNED NOT NULL DEFAULT 0,
	backup_path VARCHAR(500) NOT NULL,
	sha256_hash VARCHAR(64) NOT NULL DEFAULT '',
	created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (id),
	INDEX idx_report (report_id),
	CONSTRAINT fk_snapshot_report FOREIGN KEY (report_id) REFERENCES nf_reports(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Settings de la copie défensive
INSERT IGNORE INTO nf_settings (name, value) VALUES
	('nf_moderation_snapshot_attachments_enabled', '1'),
	('nf_moderation_snapshot_max_size_mb', '50');
