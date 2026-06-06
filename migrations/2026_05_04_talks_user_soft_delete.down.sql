ALTER TABLE nf_talks_participants
	DROP INDEX idx_participants_deleted_at,
	DROP COLUMN deleted_at;
