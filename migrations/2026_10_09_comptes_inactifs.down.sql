-- Retour arrière de 2026_10_09_comptes_inactifs : la table des prévenances, le réglage et le modèle (ses traductions
-- partent avec lui, par la clé étrangère).

DROP TABLE IF EXISTS `nf_user_inactivite`;
DELETE FROM `nf_settings` WHERE `name` = 'nf_comptes_inactifs_ans';
DELETE FROM `nf_email_templates` WHERE `key` = 'user.inactivite';
