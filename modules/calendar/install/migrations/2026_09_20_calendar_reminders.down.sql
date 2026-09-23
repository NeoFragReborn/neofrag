-- Retour arriere : sans cette colonne, aucun rappel ne part — le code la lit avant d'envoyer.
-- Les abonnements eux-memes restent : ils vivent dans nf_subscriptions et servent a d'autres
-- contenus.
ALTER TABLE `nf_calendar_events` DROP COLUMN `reminder_sent_at`;
