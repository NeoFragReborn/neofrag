-- Publication programmée — généralisée à tous les types datables + parution à l'heure réelle.
--
-- 1) news / articles : colonne `announced_at` = horodatage de la PREMIÈRE annonce effective
--    (event + webhook + gamification + notifications). NULL = pas encore annoncé. Permet à
--    l'endpoint de parution (cron) de n'émettre qu'une seule fois, au moment réel de parution,
--    au lieu d'émettre à l'enregistrement (le contenu programmé pingait Discord avant d'être visible).
-- 2) pages / gallery : colonne `date` = date de publication (masque le contenu jusqu'à cette date,
--    comme news/articles). NOT NULL DEFAULT now → le contenu existant reste visible immédiatement.
-- 3) events : `publish_date` distincte de la date de TENUE (`date`). NULL = visible dès publié
--    (comportement actuel préservé) ; une date future masque l'événement jusque-là.
-- 4) nf_cron_key : token secret de l'endpoint de parution /monitoring/cron?key=… (cron externe).

ALTER TABLE nf_news     ADD COLUMN announced_at TIMESTAMP NULL DEFAULT NULL AFTER published;
ALTER TABLE nf_articles ADD COLUMN announced_at TIMESTAMP NULL DEFAULT NULL AFTER published;

ALTER TABLE nf_news     ADD KEY idx_schedule (published, announced_at, date);
ALTER TABLE nf_articles ADD KEY idx_schedule (published, announced_at, date);

-- Backfill : le contenu déjà paru est marqué annoncé pour qu'il ne soit pas re-notifié/re-webhooké
-- au premier passage du cron (sa date de parution sert d'horodatage d'annonce).
UPDATE nf_news     SET announced_at = date WHERE published = '1' AND date <= NOW();
UPDATE nf_articles SET announced_at = date WHERE published = '1' AND date <= NOW();

ALTER TABLE nf_pages   ADD COLUMN date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER published;
ALTER TABLE nf_gallery ADD COLUMN date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER published;

ALTER TABLE nf_events  ADD COLUMN publish_date TIMESTAMP NULL DEFAULT NULL AFTER published;

INSERT INTO nf_settings (name, site, lang, value, type)
SELECT 'nf_cron_key', '', '', SHA2(CONCAT(RAND(), UUID(), NOW(6)), 256), 'string'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM nf_settings WHERE name = 'nf_cron_key');
