-- `scheduled_at` stocke une date FUTURE (envoi programmé). Elle était en TIMESTAMP, dont la plage
-- MySQL/MariaDB s'arrête au 19/01/2038 (limite Unix 32 bits). En mode SQL strict (défaut MariaDB),
-- toute valeur au-delà est REJETÉE (errno 1292 « Incorrect datetime value ») → l'insert de la
-- campagne échoue silencieusement (le driver fait mysqli_report(OFF)). Passage en DATETIME (plage
-- jusqu'à l'an 9999). Sémantique inchangée : le code stocke/relit en heure murale via date('Y-m-d H:i:s'),
-- sans conversion de fuseau — DATETIME est même plus prévisible que TIMESTAMP sur ce point.
--
-- Bug attrapé par tests/Headless/NewsletterSchedulingTest (jamais exécuté en CI faute de base).
-- Install neuve : baseline (install.sql porte déjà datetime). Install existante : exécutée par update().
ALTER TABLE `nf_newsletter_campaigns`
  MODIFY COLUMN `scheduled_at` datetime NULL DEFAULT NULL;
