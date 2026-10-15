-- Les dons reliés à des services extérieurs (entrée m16 du tableau, 2026-10-16).
--
-- Un don fait sur Ko-fi, HelloAsso, TipeeeStream, ou par le bouton PayPal du site, s'enregistre de lui-même. Chaque
-- don dit d'où il vient, et porte son identifiant chez le service : la clé unique (source, external_id) arrête une
-- notification reçue deux fois. Les dons d'aujourd'hui (saisis à la main, ou PayPal) ne changent pas.

ALTER TABLE `nf_donations`
  MODIFY `source` enum('manual','paypal','helloasso','kofi','tipeeestream') NOT NULL DEFAULT 'manual',
  ADD COLUMN `external_id` varchar(100) DEFAULT NULL AFTER `paypal_txn_id`,
  ADD UNIQUE KEY `uk_externe` (`source`,`external_id`);
