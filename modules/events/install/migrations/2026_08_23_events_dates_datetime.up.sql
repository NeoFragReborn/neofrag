-- Les dates d'événement et de masquage sont toutes FUTURES (`date`/`date_end` = quand l'événement a
-- lieu ; `publish_date` = programmation de la publication). En TIMESTAMP, plafond au 19/01/2038 →
-- un événement au-delà est rejeté en mode SQL strict (errno 1292). Passage en DATETIME (jusqu'à 9999).
-- `reminder_sent_at` reste TIMESTAMP (marqueur « rappel envoyé le », toujours ~présent).
-- NB : la migration 2026_06_16_event_recurrence remettait `date` en timestamp ; celle-ci (postérieure)
-- corrige après elle. Fresh install : baseline. Install existante : exécutée par update().
ALTER TABLE `nf_events`
  MODIFY COLUMN `date` datetime NOT NULL DEFAULT current_timestamp(),
  MODIFY COLUMN `date_end` datetime NULL DEFAULT NULL,
  MODIFY COLUMN `publish_date` datetime NULL DEFAULT NULL;
