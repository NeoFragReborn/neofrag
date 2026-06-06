-- =====================================================================
-- NeoFrag — Talks unification (Phase 1) — schema migration UP
-- Date : 2026-05-04
-- Stratégie : ADDITIF + backfill. Les talks existants (1=Admin, 2=Publique)
-- sont migrés en type='public'. Le widget legacy continue de fonctionner.
-- =====================================================================

-- ---------------------------------------------------------------------
-- 1) Étendre nf_talks pour supporter 3 types de conversations
-- ---------------------------------------------------------------------
ALTER TABLE nf_talks
  ADD COLUMN type        ENUM('direct','group','public') NOT NULL DEFAULT 'public' AFTER name,
  ADD COLUMN creator_id  INT(11) UNSIGNED DEFAULT NULL AFTER type,
  ADD COLUMN description VARCHAR(500) NOT NULL DEFAULT '' AFTER creator_id,
  ADD COLUMN created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ADD COLUMN updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  ADD COLUMN deleted_at  TIMESTAMP NULL DEFAULT NULL,
  ADD INDEX idx_type_deleted (type, deleted_at),
  ADD INDEX idx_creator (creator_id),
  ADD CONSTRAINT nf_talks_creator_fk
    FOREIGN KEY (creator_id) REFERENCES nf_user(id)
    ON DELETE SET NULL ON UPDATE CASCADE;

-- Élargir name → varchar(150) pour accepter des titres plus expressifs
ALTER TABLE nf_talks
  MODIFY COLUMN name VARCHAR(150) NOT NULL;

-- ---------------------------------------------------------------------
-- 2) Étendre nf_talks_messages : threading + edited + soft-delete + indexes
-- ---------------------------------------------------------------------
ALTER TABLE nf_talks_messages
  ADD COLUMN parent_id      INT(10) UNSIGNED DEFAULT NULL AFTER talk_id,
  ADD COLUMN edited_at      TIMESTAMP NULL DEFAULT NULL,
  ADD COLUMN edited_by      INT(11) UNSIGNED DEFAULT NULL,
  ADD COLUMN deleted_at     TIMESTAMP NULL DEFAULT NULL,
  ADD COLUMN deleted_by     INT(11) UNSIGNED DEFAULT NULL,
  ADD COLUMN deleted_reason VARCHAR(50) DEFAULT NULL,
  ADD INDEX idx_talk_date (talk_id, date),
  ADD INDEX idx_parent (parent_id),
  ADD INDEX idx_user (user_id),
  ADD INDEX idx_deleted (deleted_at);

-- FULLTEXT search sur le contenu
ALTER TABLE nf_talks_messages
  ADD FULLTEXT INDEX ft_message (message);

-- ---------------------------------------------------------------------
-- 3) Table participants — qui est dans quelle conversation
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS nf_talks_participants (
  talk_id        INT(11) UNSIGNED NOT NULL,
  user_id        INT(11) UNSIGNED NOT NULL,
  role           ENUM('member','admin') NOT NULL DEFAULT 'member',
  joined_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  last_read_at   TIMESTAMP NULL DEFAULT NULL,
  archived_at    TIMESTAMP NULL DEFAULT NULL,
  notify_email   TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
  PRIMARY KEY (talk_id, user_id),
  INDEX idx_user_archived (user_id, archived_at),
  INDEX idx_talk_role (talk_id, role),
  CONSTRAINT nf_talks_part_talk_fk
    FOREIGN KEY (talk_id) REFERENCES nf_talks(talk_id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT nf_talks_part_user_fk
    FOREIGN KEY (user_id) REFERENCES nf_user(id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_uca1400_ai_ci;

-- ---------------------------------------------------------------------
-- 4) Table attachments — pièces jointes par message
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS nf_talks_attachments (
  attachment_id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  message_id    INT(10) UNSIGNED NOT NULL,
  file_id       INT(11) UNSIGNED NOT NULL,
  file_size     INT(11) UNSIGNED NOT NULL DEFAULT 0,
  mime_type     VARCHAR(100) NOT NULL DEFAULT '',
  PRIMARY KEY (attachment_id),
  INDEX idx_message (message_id),
  INDEX idx_file (file_id),
  CONSTRAINT nf_talks_att_msg_fk
    FOREIGN KEY (message_id) REFERENCES nf_talks_messages(message_id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT nf_talks_att_file_fk
    FOREIGN KEY (file_id) REFERENCES nf_file(id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_uca1400_ai_ci;

-- ---------------------------------------------------------------------
-- 5) Backfill : marquer les talks existants comme publics + créateur admin
-- ---------------------------------------------------------------------
UPDATE nf_talks
   SET type       = 'public',
       creator_id = (SELECT id FROM nf_user WHERE admin = '1' ORDER BY id LIMIT 1)
 WHERE type IS NULL OR type = '';

-- =====================================================================
-- Fin migration UP talks unification
-- =====================================================================
