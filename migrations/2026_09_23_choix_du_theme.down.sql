-- Retire le reglage du choix de theme : le choix redevient ouvert partout.

DELETE FROM `nf_settings` WHERE `name` = 'nf_theme_visiteur';
