-- Le bouton des modeles d'e-mails (validation du compte, mot de passe perdu, mentions...) etait
-- blanc sur #03c1a2 : 2,3:1, loin des 4,5:1 recommandes, dans des courriels envoyes a chaque membre
-- (check-mise-en-page, sur l'apercu de l'administration, 2026-09-23). La meme teinte, assombrie :
-- #027a66, 5,3:1.
--
-- Seuls les modeles qui portent ENCORE la couleur livree sont touches : un modele que
-- l'administrateur a repris a sa facon garde ses couleurs.

UPDATE `nf_email_template_translations`
   SET `body` = REPLACE(`body`, 'background:#03c1a2', 'background:#027a66')
 WHERE `body` LIKE '%background:#03c1a2%';
