-- Events : rappel automatique aux participants avant le début de l'événement (cron).
-- reminder_sent_at = marqueur d'idempotence (le rappel d'un événement ne part qu'une fois).

ALTER TABLE `nf_events`
  ADD COLUMN `reminder_sent_at` timestamp NULL DEFAULT NULL AFTER `publish_date`;
