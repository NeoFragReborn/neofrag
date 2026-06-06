-- Rollback : revenir au schema standalone
ALTER TABLE nf_forum_attachments
  DROP FOREIGN KEY nf_forum_attachments_file_fk,
  DROP INDEX idx_file,
  DROP COLUMN file_id;

ALTER TABLE nf_forum_attachments
  ADD COLUMN file_name   VARCHAR(255)      NOT NULL DEFAULT '' AFTER message_id,
  ADD COLUMN file_path   VARCHAR(500)      NOT NULL DEFAULT '' AFTER file_name,
  ADD COLUMN uploaded_by INT(11) UNSIGNED  DEFAULT NULL AFTER mime_type,
  ADD COLUMN uploaded_at TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER uploaded_by,
  ADD INDEX idx_uploader (uploaded_by),
  ADD CONSTRAINT nf_forum_attachments_user_fk
    FOREIGN KEY (uploaded_by) REFERENCES nf_user(id)
    ON DELETE SET NULL ON UPDATE CASCADE;
