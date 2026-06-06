-- =====================================================================
-- NeoFrag — Forum refonte Phase 1 — Schema migration (DOWN / rollback)
-- À exécuter pour annuler la migration UP correspondante.
-- =====================================================================

-- 9) Mentions
DROP TABLE IF EXISTS nf_forum_mentions;

-- 8) Attachments
DROP TABLE IF EXISTS nf_forum_attachments;

-- 7) nf_forum_track : retirer colonnes ajoutées
ALTER TABLE nf_forum_track
  DROP INDEX idx_user_type,
  DROP COLUMN type,
  DROP COLUMN last_notified_at,
  DROP COLUMN created_at;

-- 6) Booleans is_announced / is_locked
ALTER TABLE nf_forum_topics
  DROP COLUMN is_locked,
  DROP COLUMN is_announced;

-- 5) Soft-delete colonnes
ALTER TABLE nf_forum_messages
  DROP INDEX idx_deleted_at,
  DROP COLUMN deleted_reason,
  DROP COLUMN deleted_by,
  DROP COLUMN deleted_at;

-- 4) Threading parent_id
ALTER TABLE nf_forum_messages
  DROP FOREIGN KEY nf_forum_messages_parent_fk,
  DROP INDEX idx_parent,
  DROP COLUMN parent_id;

-- 3) Restaurer varchar(100) — ATTENTION : si des rows ont été insérées avec
--    title/url/description > 100 chars, ce DOWN va échouer.
ALTER TABLE nf_forum_url
  MODIFY COLUMN url VARCHAR(100) NOT NULL;

ALTER TABLE nf_forum_topics
  MODIFY COLUMN title VARCHAR(100) NOT NULL;

ALTER TABLE nf_forum
  MODIFY COLUMN description VARCHAR(100) NOT NULL DEFAULT '';

-- 2) FULLTEXT indexes
ALTER TABLE nf_forum_topics
  DROP INDEX ft_title;

ALTER TABLE nf_forum_messages
  DROP INDEX ft_message;

-- 1) Index topic_date
ALTER TABLE nf_forum_messages
  DROP INDEX idx_topic_date;

-- =====================================================================
-- Fin du rollback DOWN
-- =====================================================================
