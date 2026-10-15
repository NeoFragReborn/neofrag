-- Retour de 2026_10_16_dons_relies : les dons venus des services reliés partent, avec leurs réglages.

DELETE FROM `nf_donations` WHERE `source` IN ('helloasso', 'kofi', 'tipeeestream');

ALTER TABLE `nf_donations`
  DROP KEY `uk_externe`,
  DROP COLUMN `external_id`,
  MODIFY `source` enum('manual','paypal') NOT NULL DEFAULT 'manual';

DELETE FROM `nf_settings` WHERE `name` LIKE 'nf\_donations\_kofi\_%' OR `name` LIKE 'nf\_donations\_helloasso\_%' OR `name` LIKE 'nf\_donations\_tipeeestream\_%'
  OR `name` IN ('nf_donations_paypal_ipn', 'nf_donations_paypal_sandbox');
