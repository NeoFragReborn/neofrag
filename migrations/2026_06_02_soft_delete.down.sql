DELETE FROM nf_addon WHERE name = 'trash';
ALTER TABLE nf_news     DROP COLUMN deleted_at, DROP COLUMN deleted_by;
ALTER TABLE nf_articles DROP COLUMN deleted_at, DROP COLUMN deleted_by;
