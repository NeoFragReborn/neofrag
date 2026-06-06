-- Rollback : suppression des 2 groupes modération + leurs permissions

DELETE FROM nf_access WHERE module='moderation' AND id IN (
	SELECT group_id FROM nf_groups WHERE name IN ('moderation_junior','moderation_senior')
);
DELETE FROM nf_users_groups WHERE group_id IN (
	SELECT group_id FROM nf_groups WHERE name IN ('moderation_junior','moderation_senior')
);
DELETE FROM nf_groups_lang WHERE group_id IN (
	SELECT group_id FROM nf_groups WHERE name IN ('moderation_junior','moderation_senior')
);
DELETE FROM nf_groups WHERE name IN ('moderation_junior','moderation_senior');
