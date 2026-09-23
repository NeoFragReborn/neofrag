-- Retour a la couleur livree auparavant, pour les modeles qui portent la nouvelle.

UPDATE `nf_email_template_translations`
   SET `body` = REPLACE(`body`, 'background:#027a66', 'background:#03c1a2')
 WHERE `body` LIKE '%background:#027a66%';
