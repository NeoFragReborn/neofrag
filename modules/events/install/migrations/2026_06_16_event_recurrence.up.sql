-- Events : récurrence matérialisée. Les occurrences d'une série sont des événements normaux
-- (participants/rappels/affichage inchangés) reliés par series_id (= event_id du 1er de la série).

ALTER TABLE `nf_events`
  ADD COLUMN `series_id` int(11) unsigned DEFAULT NULL AFTER `reminder_sent_at`,
  ADD KEY `idx_series` (`series_id`);

-- Artefact legacy : `date` avait `ON UPDATE current_timestamp()` → TOUT UPDATE partiel d'un événement
-- (set_series, pose de reminder_sent_at…) réinitialisait silencieusement la date de l'événement à NOW().
-- On retire l'auto-update (la date n'est jamais censée changer hors edit() qui l'affecte explicitement).
ALTER TABLE `nf_events`
  MODIFY COLUMN `date` timestamp NOT NULL DEFAULT current_timestamp();
