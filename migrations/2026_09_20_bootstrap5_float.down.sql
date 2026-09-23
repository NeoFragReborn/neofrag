-- Retour arriere : on remet les noms de Bootstrap 4 dans le copyright.
--
-- Ce retour REMET UN DEFAUT — `float-end` fonctionne, `float-right` non. Il n'existe que pour que
-- la migration soit reversible comme les autres, et pour qu'un `down --step=N` qui traverse cette
-- ligne n'echoue pas au milieu d'une pile.

UPDATE `nf_settings`
   SET `value` = REPLACE(REPLACE(`value`,
           'class=&quot;float-end&quot;', 'class=&quot;float-right&quot;'),
           'class="float-end"',           'class="float-right"')
 WHERE `name` = 'nf_copyright'
   AND (`value` LIKE '%float-end%');

UPDATE `nf_settings`
   SET `value` = REPLACE(REPLACE(`value`,
           'class=&quot;float-start&quot;', 'class=&quot;float-left&quot;'),
           'class="float-start"',           'class="float-left"')
 WHERE `name` = 'nf_copyright'
   AND (`value` LIKE '%float-start%');
