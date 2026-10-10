-- Le relevé des pages introuvables (ligne 0.41 du reste-à-faire, entrée m07 du tableau, 2026-10-10).
--
-- Les moteurs signalaient des « Introuvable (404) » que rien ne permettait de retrouver : le serveur web ne tient pas
-- de journal des requêtes, et le site ne notait rien. Chaque adresse introuvable — sans la langue ni les paramètres,
-- comme la source d'une redirection — est comptée ici (nf_noter_introuvable()), les robots à part, avec la dernière
-- page d'où l'on venait. Ni adresse IP, ni navigateur. Le Monitoring les montre et propose d'en rediriger une ; le
-- ménage du jour oublie celles qu'on n'a plus demandées depuis 90 jours, et n'en garde pas plus de 2 000.

CREATE TABLE IF NOT EXISTS `nf_pages_introuvables` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `chemin` varchar(255) NOT NULL,
  `visites` int(11) unsigned NOT NULL DEFAULT 0,
  `robots` int(11) unsigned NOT NULL DEFAULT 0,
  `provenance` varchar(255) NOT NULL DEFAULT '',
  `premiere_fois` timestamp NOT NULL DEFAULT current_timestamp(),
  `derniere_fois` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_chemin` (`chemin`),
  KEY `idx_derniere_fois` (`derniere_fois`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
