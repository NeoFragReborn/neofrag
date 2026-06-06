-- Phase 5 bis modération — création de 2 rôles standard avec permissions associées.
-- Modérateur (junior) / Modérateur senior.
-- L'admin global (nf_user.admin = 1) a déjà toutes les permissions, pas besoin de groupe dédié.
-- Les permissions sont attachées via nf_access (module='moderation', action=...)

-- 1) Créer les 2 groupes (visible, non-auto pour permettre suppression manuelle si besoin)
INSERT INTO nf_groups (name, color, icon, hidden, auto, `order`) VALUES
	('moderation_junior',  'orange',    'fas fa-shield-alt',  '0', '0', 50),
	('moderation_senior',  'red',       'fas fa-user-shield', '0', '0', 51);

-- 2) Localisation FR + EN (titres affichés)
INSERT INTO nf_groups_lang (group_id, lang, title)
SELECT g.group_id, 'fr', t.title FROM nf_groups g
JOIN (
	SELECT 'moderation_junior' AS name, 'Modérateur' AS title UNION ALL
	SELECT 'moderation_senior', 'Modérateur senior'
) t ON t.name = g.name WHERE g.name IN ('moderation_junior','moderation_senior');

INSERT INTO nf_groups_lang (group_id, lang, title)
SELECT g.group_id, 'en', t.title FROM nf_groups g
JOIN (
	SELECT 'moderation_junior' AS name, 'Moderator' AS title UNION ALL
	SELECT 'moderation_senior', 'Senior Moderator'
) t ON t.name = g.name WHERE g.name IN ('moderation_junior','moderation_senior');

-- 3) Permissions par groupe (via nf_access)
-- Junior : view_reports, handle_reports, warn, mute, restrict, mediation
INSERT INTO nf_access (id, module, action)
SELECT g.group_id, 'moderation', a.action FROM nf_groups g
CROSS JOIN (
	SELECT 'view_reports'    AS action UNION ALL
	SELECT 'handle_reports'          UNION ALL
	SELECT 'warn'                    UNION ALL
	SELECT 'mute'                    UNION ALL
	SELECT 'restrict'                UNION ALL
	SELECT 'mediation'
) a WHERE g.name = 'moderation_junior';

-- Senior : tout le junior + ban_temp, approve, access_private
INSERT INTO nf_access (id, module, action)
SELECT g.group_id, 'moderation', a.action FROM nf_groups g
CROSS JOIN (
	SELECT 'view_reports'    AS action UNION ALL
	SELECT 'handle_reports'          UNION ALL
	SELECT 'warn'                    UNION ALL
	SELECT 'mute'                    UNION ALL
	SELECT 'restrict'                UNION ALL
	SELECT 'mediation'               UNION ALL
	SELECT 'ban_temp'                UNION ALL
	SELECT 'approve'                 UNION ALL
	SELECT 'access_private'
) a WHERE g.name = 'moderation_senior';

-- Admin global (nf_user.admin = 1) a déjà toutes les permissions sans groupe dédié.
