-- Les deux groupes de moderation livres n'avaient de titre qu'en francais et en anglais.
--
-- Le coeur lit le titre d'un groupe dans la langue du site (nf_groups_lang), par une JOINTURE
-- INTERNE : sur un site allemand, espagnol, italien ou portugais, ces groupes n'avaient aucune ligne
-- et DISPARAISSAIENT de la liste des groupes (releve le 2026-09-23). Le coeur retombe desormais sur
-- une autre langue ; cette migration donne en plus aux groupes livres leur titre dans les six langues.
--
-- INSERT IGNORE : un titre deja saisi par un administrateur pour l'une de ces langues est garde.

INSERT IGNORE INTO `nf_groups_lang` (`group_id`, `lang`, `title`)
SELECT g.`group_id`, t.`lang`, t.`title`
  FROM `nf_groups` g
  JOIN (
        SELECT 'moderation_junior' AS `name`, 'de' AS `lang`, 'Moderator' AS `title`
  UNION ALL SELECT 'moderation_junior', 'es', 'Moderador'
  UNION ALL SELECT 'moderation_junior', 'it', 'Moderatore'
  UNION ALL SELECT 'moderation_junior', 'pt', 'Moderador'
  UNION ALL SELECT 'moderation_senior', 'de', 'Senior-Moderator'
  UNION ALL SELECT 'moderation_senior', 'es', 'Moderador sénior'
  UNION ALL SELECT 'moderation_senior', 'it', 'Moderatore senior'
  UNION ALL SELECT 'moderation_senior', 'pt', 'Moderador sénior'
       ) t ON t.`name` = g.`name`;
