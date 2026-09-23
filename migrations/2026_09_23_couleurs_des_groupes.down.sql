-- Retour aux noms livres auparavant, pour les seuls groupes et roles de moderation.

UPDATE `nf_groups` SET `color` = 'orange' WHERE `name` = 'moderation_junior' AND `color` = 'warning';
UPDATE `nf_groups` SET `color` = 'red'    WHERE `name` = 'moderation_senior' AND `color` = 'danger';
UPDATE `nf_roles`  SET `color` = 'orange' WHERE `name` = 'moderation_junior' AND `color` = 'warning';
UPDATE `nf_roles`  SET `color` = 'red'    WHERE `name` = 'moderation_senior' AND `color` = 'danger';
