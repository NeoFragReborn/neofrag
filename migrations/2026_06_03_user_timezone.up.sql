-- Fuseau horaire par utilisateur : préférence stockée sur le profil, appliquée à
-- l'affichage des dates (date_default_timezone_set au chargement du module user).
ALTER TABLE nf_user_profile ADD COLUMN timezone VARCHAR(64) NOT NULL DEFAULT '' AFTER country;
