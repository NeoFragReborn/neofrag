-- La médiation attend l'accord de celui qui a signalé (décision du 2026-10-09, entrée dec-mediation-accord du
-- tableau ; ligne 0.50 du reste-à-faire).
--
-- Le membre signalé voit qui l'a signalé : il est dans la conversation. Le modérateur ne fait donc plus que PROPOSER la
-- médiation ; celui qui a signalé l'accepte ou la refuse (Moderation::repondre_mediation()), et la conversation ne
-- s'ouvre qu'avec son accord. mediation_par / mediation_le : qui l'a proposée, et quand ; mediation_accord /
-- mediation_reponse_le : sa réponse ; mediation_talk_id : la conversation ouverte.

ALTER TABLE `nf_reports`
  ADD COLUMN `mediation_par` int(10) unsigned DEFAULT NULL AFTER `handled_note`,
  ADD COLUMN `mediation_le` timestamp NULL DEFAULT NULL AFTER `mediation_par`,
  ADD COLUMN `mediation_accord` enum('oui','non') DEFAULT NULL AFTER `mediation_le`,
  ADD COLUMN `mediation_reponse_le` timestamp NULL DEFAULT NULL AFTER `mediation_accord`,
  ADD COLUMN `mediation_talk_id` int(10) unsigned DEFAULT NULL AFTER `mediation_reponse_le`;
