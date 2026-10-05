-- Les droits des membres connectés, rétablis (2026-10-05).
--
-- La refonte des droits de mai 2026 a converti les règles par groupe en règles par rôle, sans garder deux règles du
-- NeoFrag d'origine : ce qu'un visiteur peut, un membre le peut ; ce qui n'est refusé qu'aux visiteurs reste permis
-- aux membres. Le rôle « member » n'hérite pas du rôle « visitor » : un membre connecté ne lisait donc plus le forum
-- (« Aucun forum »), ni les galeries, les pages, les types d'événements ou les dossiers ouverts aux visiteurs, et
-- n'écrivait nulle part — seuls les administrateurs, qui passent outre les droits, ne voyaient rien.
--
-- Rien ne s'ouvre ici qui ne soit déjà ouvert aux visiteurs, sauf l'écriture là où seuls les visiteurs sont refusés,
-- comme le voulait l'origine ; une règle déjà posée pour les membres n'est jamais touchée.

-- 1) Ce qu'un visiteur peut, un membre le peut.
INSERT INTO `nf_role_permissions` (`role_id`, `permission`, `scope_id`, `authorized`)
SELECT m.`role_id`, v.`permission`, v.`scope_id`, 'allow'
  FROM `nf_role_permissions` v
  JOIN `nf_roles` vr ON vr.`role_id` = v.`role_id` AND vr.`name` = 'visitor'
  JOIN `nf_roles` m ON m.`name` = 'member'
 WHERE v.`authorized` = 'allow'
   AND NOT EXISTS (SELECT 1 FROM `nf_role_permissions` x
                    WHERE x.`role_id` = m.`role_id` AND x.`permission` = v.`permission` AND x.`scope_id` = v.`scope_id`);

-- 2) Ce qui n'est refusé qu'aux visiteurs reste permis aux membres : écrire dans le forum et dans la boîte de
--    discussion, postuler à un recrutement (les modules le déclarent ainsi, `['visitors', FALSE]`).
INSERT INTO `nf_role_permissions` (`role_id`, `permission`, `scope_id`, `authorized`)
SELECT m.`role_id`, v.`permission`, v.`scope_id`, 'allow'
  FROM `nf_role_permissions` v
  JOIN `nf_roles` vr ON vr.`role_id` = v.`role_id` AND vr.`name` = 'visitor'
  JOIN `nf_roles` m ON m.`name` = 'member'
 WHERE v.`authorized` = 'never'
   AND v.`permission` IN ('forum.category_write', 'talks.write', 'recruits.recruit_postulate')
   AND NOT EXISTS (SELECT 1 FROM `nf_role_permissions` x
                    WHERE x.`role_id` = m.`role_id` AND x.`permission` = v.`permission` AND x.`scope_id` = v.`scope_id`);
