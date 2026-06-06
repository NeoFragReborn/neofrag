ALTER TABLE nf_news     DROP KEY idx_schedule;
ALTER TABLE nf_articles DROP KEY idx_schedule;
ALTER TABLE nf_news     DROP COLUMN announced_at;
ALTER TABLE nf_articles DROP COLUMN announced_at;
ALTER TABLE nf_pages    DROP COLUMN date;
ALTER TABLE nf_gallery  DROP COLUMN date;
ALTER TABLE nf_events   DROP COLUMN publish_date;
DELETE FROM nf_settings WHERE name = 'nf_cron_key';
