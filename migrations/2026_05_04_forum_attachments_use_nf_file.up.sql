-- =====================================================================
-- NeoFrag — Forum Phase 5 — nf_forum_attachments use nf_file FK
-- Date : 2026-05-04
-- Décision : utiliser le système nf_file natif au lieu de stocker
-- name/path en doublon. Cleaner, réutilise le upload pipeline.
-- =====================================================================

-- Drop FK et anciennes colonnes redondantes avec nf_file
ALTER TABLE nf_forum_attachments
  DROP FOREIGN KEY nf_forum_attachments_user_fk,
  DROP INDEX idx_uploader,
  DROP COLUMN file_name,
  DROP COLUMN file_path,
  DROP COLUMN uploaded_by,
  DROP COLUMN uploaded_at;

-- Ajouter file_id avec FK vers nf_file
ALTER TABLE nf_forum_attachments
  ADD COLUMN file_id INT(11) UNSIGNED NOT NULL AFTER message_id,
  ADD INDEX idx_file (file_id),
  ADD CONSTRAINT nf_forum_attachments_file_fk
    FOREIGN KEY (file_id) REFERENCES nf_file(id)
    ON DELETE CASCADE ON UPDATE CASCADE;

-- file_size et mime_type restent (cache pour stats / query rapide)
