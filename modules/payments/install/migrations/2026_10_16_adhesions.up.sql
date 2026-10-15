-- Les cotisations (entrée m16 du tableau, 2026-10-16).
--
-- Un membre paie sa cotisation en ligne — ou un administrateur l'enregistre, payée en espèces — et devient
-- « adhérent » pour une durée, renouvelable, avec un rappel avant l'échéance. C'est le statut VIP à durée,
-- généralisé : un lot de paiement d'un troisième type, `adhesion`, et l'échéance de chaque adhérent.
-- Rien de ce qui existe ne change.

ALTER TABLE `nf_payment_packs`
  MODIFY `kind` enum('points','vip','adhesion') NOT NULL DEFAULT 'points';

CREATE TABLE IF NOT EXISTS `nf_adhesions` (
  `user_id` int(11) unsigned NOT NULL,
  `expires_at` datetime NOT NULL,
  `source` varchar(50) NOT NULL DEFAULT '',
  `reminded_at` datetime DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`user_id`),
  KEY `idx_expires` (`expires_at`),
  CONSTRAINT `fk_adhesion_user` FOREIGN KEY (`user_id`) REFERENCES `nf_user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
