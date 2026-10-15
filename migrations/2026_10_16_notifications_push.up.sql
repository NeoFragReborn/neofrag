-- Les notifications PUSH (entrée d11 du tableau, 2026-10-16) : un message dans le navigateur du membre, même site
-- fermé, s'il l'a demandé sur cet appareil.
--
-- Deux choses. Un CANAL de plus dans les préférences (`push`, à côté de `site` et `email`) : par défaut oui, comme
-- les deux autres — mais rien ne part tant que le membre n'a pas activé le push sur un appareil, ce que seul son
-- navigateur, avec son accord, peut faire. Et les ABONNEMENTS : un par navigateur, avec l'adresse que donne son
-- service de push et les deux clés qui chiffrent les messages pour lui seul. Ils partent avec le compte.

ALTER TABLE `nf_notifications_preferences`
  ADD COLUMN `push` tinyint(1) unsigned NOT NULL DEFAULT 1 AFTER `email`;

CREATE TABLE IF NOT EXISTS `nf_push_abonnements` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL,
  `endpoint` varchar(1000) NOT NULL,
  `empreinte` char(64) NOT NULL,
  `p256dh` varchar(120) NOT NULL,
  `auth` varchar(40) NOT NULL,
  `appareil` varchar(190) NOT NULL DEFAULT '',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `used_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `empreinte` (`empreinte`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `fk_push_abonnements_user` FOREIGN KEY (`user_id`) REFERENCES `nf_user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
