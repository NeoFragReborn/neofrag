-- Rollback talks unification

DROP TABLE IF EXISTS nf_talks_attachments;
DROP TABLE IF EXISTS nf_talks_participants;

ALTER TABLE nf_talks_messages
  DROP INDEX ft_message,
  DROP INDEX idx_deleted,
  DROP INDEX idx_user,
  DROP INDEX idx_parent,
  DROP INDEX idx_talk_date,
  DROP COLUMN deleted_reason,
  DROP COLUMN deleted_by,
  DROP COLUMN deleted_at,
  DROP COLUMN edited_by,
  DROP COLUMN edited_at,
  DROP COLUMN parent_id;

ALTER TABLE nf_talks
  DROP FOREIGN KEY nf_talks_creator_fk,
  DROP INDEX idx_creator,
  DROP INDEX idx_type_deleted,
  DROP COLUMN deleted_at,
  DROP COLUMN updated_at,
  DROP COLUMN created_at,
  DROP COLUMN description,
  DROP COLUMN creator_id,
  DROP COLUMN type;

ALTER TABLE nf_talks MODIFY COLUMN name VARCHAR(50) NOT NULL;
