-- Retour arrière de 2026_10_09_jetons_par_usage : la colonne de l'usage.

ALTER TABLE `nf_user_token`
	DROP COLUMN `type`;
