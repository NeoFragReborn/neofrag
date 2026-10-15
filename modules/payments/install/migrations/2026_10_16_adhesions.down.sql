-- Retour de 2026_10_16_adhesions : les adhésions et leurs lots partent, le rôle « Adhérent » aussi.

DROP TABLE IF EXISTS `nf_adhesions`;
DELETE FROM `nf_payment_packs` WHERE `kind` = 'adhesion';
DELETE FROM `nf_roles` WHERE `name` = 'adherent';

ALTER TABLE `nf_payment_packs`
  MODIFY `kind` enum('points','vip') NOT NULL DEFAULT 'points';
