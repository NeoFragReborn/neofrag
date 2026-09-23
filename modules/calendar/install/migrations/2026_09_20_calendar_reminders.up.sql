-- rappels du calendrier.
--
-- Le module `events` sait rappeler ses evenements a leurs PARTICIPANTS depuis longtemps. Le
-- calendrier generique n'a pas de participants : il lui fallait d'abord un moyen de suivre un
-- evenement. C'est le mecanisme d'abonnement du module `notifications`, deja employe par les
-- actualites et les articles, qui le fournit — le calendrier declare simplement son type de contenu
-- comme abonnable.
--
-- Cette colonne est ce qui rend le rappel IDEMPOTENT : elle est posee atomiquement avant l'envoi,
-- si bien que deux passages du cron qui se chevauchent n'envoient jamais deux fois. Meme motif que
-- `nf_events.reminder_sent_at`.
ALTER TABLE `nf_calendar_events`
  ADD COLUMN `reminder_sent_at` datetime DEFAULT NULL AFTER `created_at`;
