-- =====================================================================
-- NeoFrag — User Messages MP — Phase M2 schema migration
-- Date : 2026-05-04
-- Stratégie : ADDITIF, pas de breaking change.
-- =====================================================================

-- Soft-delete + FT sur replies
ALTER TABLE nf_users_messages_replies
  ADD COLUMN deleted_at     TIMESTAMP NULL DEFAULT NULL,
  ADD COLUMN deleted_by     INT(11) UNSIGNED DEFAULT NULL,
  ADD COLUMN deleted_reason VARCHAR(50) DEFAULT NULL,
  ADD INDEX idx_deleted (deleted_at);

ALTER TABLE nf_users_messages_replies
  ADD FULLTEXT INDEX ft_message (message);

-- FT sur titre du thread
ALTER TABLE nf_users_messages
  ADD FULLTEXT INDEX ft_title (title);

-- Table attachments
CREATE TABLE IF NOT EXISTS nf_users_messages_attachments (
  attachment_id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  reply_id      INT(11) UNSIGNED NOT NULL,
  file_id       INT(11) UNSIGNED NOT NULL,
  file_size     INT(11) UNSIGNED NOT NULL DEFAULT 0,
  mime_type     VARCHAR(100) NOT NULL DEFAULT '',
  PRIMARY KEY (attachment_id),
  INDEX idx_reply (reply_id),
  INDEX idx_file (file_id),
  CONSTRAINT nf_um_att_reply_fk
    FOREIGN KEY (reply_id) REFERENCES nf_users_messages_replies(reply_id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT nf_um_att_file_fk
    FOREIGN KEY (file_id) REFERENCES nf_file(id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_uca1400_ai_ci;
