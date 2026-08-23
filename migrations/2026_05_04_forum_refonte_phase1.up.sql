-- =====================================================================
-- NeoFrag — Forum refonte Phase 1 — Schema migration (UP)
-- Date : 2026-05-04
-- Stratégie : ADDITIF UNIQUEMENT — pas de DROP, pas de RENAME.
-- Le code legacy continue de fonctionner après cette migration.
-- =====================================================================

-- ---------------------------------------------------------------------
-- 1) Index manquant sur nf_forum_messages(topic_id, date)
--    Accélère ORDER BY date dans get_messages() et le widget forum
-- ---------------------------------------------------------------------
ALTER TABLE nf_forum_messages
  ADD INDEX idx_topic_date (topic_id, date);

-- ---------------------------------------------------------------------
-- 2) FULLTEXT indexes pour recherche (Phase 8)
--    InnoDB FT depuis MariaDB 10.0+ : OK
--    Default min_token_size = 3 (mots < 3 chars ignorés)
-- ---------------------------------------------------------------------
ALTER TABLE nf_forum_messages
  ADD FULLTEXT INDEX ft_message (message);

ALTER TABLE nf_forum_topics
  ADD FULLTEXT INDEX ft_title (title);

-- ---------------------------------------------------------------------
-- 3) Élargissement des colonnes trop courtes
-- ---------------------------------------------------------------------
ALTER TABLE nf_forum
  MODIFY COLUMN description VARCHAR(255) NOT NULL DEFAULT '';

ALTER TABLE nf_forum_topics
  MODIFY COLUMN title VARCHAR(255) NOT NULL;

ALTER TABLE nf_forum_url
  MODIFY COLUMN url VARCHAR(500) NOT NULL;

-- ---------------------------------------------------------------------
-- 4) Threading : parent_id sur messages (Phase 6)
--    Self-referential FK avec ON DELETE SET NULL pour éviter la perte
--    de fils en cas de suppression d'un message parent.
-- ---------------------------------------------------------------------
ALTER TABLE nf_forum_messages
  ADD COLUMN parent_id INT(11) UNSIGNED DEFAULT NULL AFTER topic_id,
  ADD INDEX idx_parent (parent_id),
  ADD CONSTRAINT nf_forum_messages_parent_fk
    FOREIGN KEY (parent_id) REFERENCES nf_forum_messages(message_id)
    ON DELETE SET NULL ON UPDATE CASCADE;

-- ---------------------------------------------------------------------
-- 5) Soft-delete avec flag (Phase 7 — modération)
--    Le soft-delete legacy via message=NULL reste fonctionnel.
--    Les nouvelles colonnes permettent un audit trail complet.
-- ---------------------------------------------------------------------
ALTER TABLE nf_forum_messages
  ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL,
  ADD COLUMN deleted_by INT(11) UNSIGNED DEFAULT NULL,
  ADD COLUMN deleted_reason VARCHAR(50) DEFAULT NULL,
  ADD INDEX idx_deleted_at (deleted_at);

-- ---------------------------------------------------------------------
-- 6) Booleans is_announced / is_locked (parallèle de status enum)
--    Le status enum reste en place pour le code legacy.
--    Les booleans sont la nouvelle source de vérité, backfillée.
-- ---------------------------------------------------------------------
ALTER TABLE nf_forum_topics
  ADD COLUMN is_announced TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
  ADD COLUMN is_locked    TINYINT(1) UNSIGNED NOT NULL DEFAULT 0;

UPDATE nf_forum_topics
   SET is_announced = (status IN ('-2', '1')),
       is_locked    = (status IN ('-2', '-1'));

-- ---------------------------------------------------------------------
-- 7) nf_forum_track réutilisée comme table subscriptions (Phase 3)
--    Schema d'origine : (topic_id, user_id) avec PK composite.
--    On enrichit pour supporter notifications et type forum/topic.
-- ---------------------------------------------------------------------
ALTER TABLE nf_forum_track
  ADD COLUMN created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ADD COLUMN last_notified_at TIMESTAMP NULL DEFAULT NULL,
  ADD COLUMN type             ENUM('topic', 'forum') NOT NULL DEFAULT 'topic',
  ADD INDEX idx_user_type (user_id, type);

-- ---------------------------------------------------------------------
-- 8) Table attachments (Phase 5)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS nf_forum_attachments (
  attachment_id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  message_id    INT(11) UNSIGNED NOT NULL,
  file_name     VARCHAR(255) NOT NULL,
  file_path     VARCHAR(500) NOT NULL,
  file_size     INT(11) UNSIGNED NOT NULL,
  mime_type     VARCHAR(100) NOT NULL,
  uploaded_by   INT(11) UNSIGNED DEFAULT NULL,
  uploaded_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (attachment_id),
  INDEX idx_message (message_id),
  INDEX idx_uploader (uploaded_by),
  CONSTRAINT nf_forum_attachments_message_fk
    FOREIGN KEY (message_id) REFERENCES nf_forum_messages(message_id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT nf_forum_attachments_user_fk
    FOREIGN KEY (uploaded_by) REFERENCES nf_user(id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- ---------------------------------------------------------------------
-- 9) Table mentions (Phase 4)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS nf_forum_mentions (
  mention_id        INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  message_id        INT(11) UNSIGNED NOT NULL,
  mentioned_user_id INT(11) UNSIGNED NOT NULL,
  mentioner_user_id INT(11) UNSIGNED DEFAULT NULL,
  created_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  read_at           TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (mention_id),
  INDEX idx_message (message_id),
  INDEX idx_mentioned_unread (mentioned_user_id, read_at),
  CONSTRAINT nf_forum_mentions_message_fk
    FOREIGN KEY (message_id) REFERENCES nf_forum_messages(message_id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT nf_forum_mentions_target_fk
    FOREIGN KEY (mentioned_user_id) REFERENCES nf_user(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT nf_forum_mentions_mentioner_fk
    FOREIGN KEY (mentioner_user_id) REFERENCES nf_user(id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- =====================================================================
-- Fin de la migration UP
-- =====================================================================
