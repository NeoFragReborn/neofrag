-- Retire les titres ajoutes par la migration, s'ils n'ont pas ete modifies depuis.

DELETE gl FROM `nf_groups_lang` gl
  JOIN `nf_groups` g ON g.`group_id` = gl.`group_id`
 WHERE (g.`name` = 'moderation_junior' AND ((gl.`lang` = 'de' AND gl.`title` = 'Moderator') OR (gl.`lang` IN ('es', 'pt') AND gl.`title` = 'Moderador') OR (gl.`lang` = 'it' AND gl.`title` = 'Moderatore')))
    OR (g.`name` = 'moderation_senior' AND ((gl.`lang` = 'de' AND gl.`title` = 'Senior-Moderator') OR (gl.`lang` IN ('es', 'pt') AND gl.`title` = 'Moderador sénior') OR (gl.`lang` = 'it' AND gl.`title` = 'Moderatore senior')));
