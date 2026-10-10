-- Les traces du consentement aux services tiers (2026-10-08, helpers/consentement.php).
--
-- La table existait sans que rien ne l'écrive : trois cases figées (essentiels, mesure d'audience,
-- marketing), plus l'adresse IP et le navigateur du visiteur. Le bandeau règle désormais chaque service un
-- par un, et sa preuve n'a besoin ni de l'adresse ni du navigateur : le jeton tiré au hasard par le
-- navigateur, la liste des services acceptés, l'empreinte de ce qui était proposé, la date. Un choix
-- refait s'ajoute au précédent au lieu de l'écraser — l'historique est la preuve. La table était vide :
-- elle est refaite.

DROP TABLE IF EXISTS `nf_cookie_consent`;
CREATE TABLE `nf_cookie_consent` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `consent_token` char(16) NOT NULL,
  `user_id` int(10) unsigned DEFAULT NULL,
  `services` varchar(255) NOT NULL DEFAULT '',
  `empreinte` char(6) NOT NULL DEFAULT '',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_token` (`consent_token`),
  KEY `idx_user` (`user_id`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
