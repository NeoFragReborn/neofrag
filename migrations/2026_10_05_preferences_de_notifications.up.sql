-- Les préférences de notifications de chaque membre (chantier A, étape A4).
--
-- Un membre choisit, type par type, ce qu'il reçoit : sur le site (la cloche) et, pour les types qui en ont un,
-- par e-mail. Une ligne seulement pour ce qu'il a réglé ; sans ligne, il reçoit tout, comme avant. `type` est
-- celui des notifications (`nf_notifications.type`) : forum_reply, forum_mention, talks_message, comment…

CREATE TABLE IF NOT EXISTS `nf_notifications_preferences` (
  `user_id` int(11) unsigned NOT NULL,
  `type` varchar(50) NOT NULL,
  `site` tinyint(1) unsigned NOT NULL DEFAULT 1,
  `email` tinyint(1) unsigned NOT NULL DEFAULT 1,
  PRIMARY KEY (`user_id`, `type`),
  CONSTRAINT `nf_notifications_preferences_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `nf_user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
