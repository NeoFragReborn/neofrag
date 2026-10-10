-- Retour arrière de 2026_10_09_mediation_accord : les colonnes de la proposition de médiation.

ALTER TABLE `nf_reports`
  DROP COLUMN `mediation_talk_id`,
  DROP COLUMN `mediation_reponse_le`,
  DROP COLUMN `mediation_accord`,
  DROP COLUMN `mediation_le`,
  DROP COLUMN `mediation_par`;
