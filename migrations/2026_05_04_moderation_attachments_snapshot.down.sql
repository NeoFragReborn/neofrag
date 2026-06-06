DROP TABLE IF EXISTS nf_reports_attachments_snapshot;
DELETE FROM nf_settings WHERE name IN (
	'nf_moderation_snapshot_attachments_enabled',
	'nf_moderation_snapshot_max_size_mb'
);
