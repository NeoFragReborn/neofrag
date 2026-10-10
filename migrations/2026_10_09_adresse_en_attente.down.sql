-- Retour arrière de 2026_10_09_adresse_en_attente : la table d'attente et les deux modèles (leurs traductions
-- partent avec eux, par la clé étrangère).

DROP TABLE IF EXISTS `nf_user_email_change`;
DELETE FROM `nf_email_templates` WHERE `key` IN ('user.email_change', 'user.email_change_notice');
