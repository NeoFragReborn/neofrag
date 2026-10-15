-- Retour arrière de 2026_10_16_notifications_push : plus de push. Les abonnements sont oubliés (les navigateurs
-- recevront une erreur à leur prochain message, et s'en désabonneront d'eux-mêmes).

DROP TABLE IF EXISTS `nf_push_abonnements`;

ALTER TABLE `nf_notifications_preferences` DROP COLUMN `push`;
