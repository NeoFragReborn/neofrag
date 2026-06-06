DROP TABLE IF EXISTS nf_sanctions;
DROP TABLE IF EXISTS nf_reports;

DELETE FROM nf_settings WHERE name IN (
	'nf_moderation_enabled',
	'nf_moderation_auto_escalation',
	'nf_moderation_warning_window_days',
	'nf_moderation_warning_threshold_mute',
	'nf_moderation_warning_threshold_ban',
	'nf_moderation_report_rate_limit_per_hour',
	'nf_moderation_report_flag_threshold_per_day',
	'nf_moderation_require_approval_ban_perm',
	'nf_moderation_require_approval_ban_temp',
	'nf_moderation_default_mute_duration_seconds',
	'nf_moderation_default_ban_temp_duration_seconds',
	'nf_moderation_preserve_content_snapshot'
);
