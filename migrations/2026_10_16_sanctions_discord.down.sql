-- Retour arrière de 2026_10_16_sanctions_discord : les sanctions ne disent plus d'où elles viennent.

ALTER TABLE `nf_sanctions` DROP COLUMN `origine`;
