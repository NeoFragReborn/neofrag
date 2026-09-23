-- Les groupes et les roles de moderation etaient livres en `orange` et `red`.
--
-- Leur pastille passe par la bibliotheque de libelles, qui ne connait que les couleurs de Bootstrap
-- (`warning`, `danger`...) et les couleurs hexadecimales. Un autre nom n'ajoutait aucune classe : la
-- pastille gardait le texte blanc de `.badge`, sans fond - " Moderateur " et " Moderateur senior "
-- etaient ecrits en blanc sur la carte blanche de l'administration (check-mise-en-page, 2026-09-23).
-- badge_class() ramene desormais ces noms a une couleur lisible ; cette migration corrige aussi la
-- DONNEE, pour que les ecrans qui lisent la couleur sans passer par la pastille la comprennent.
--
-- Seuls les deux noms livres par le produit sont convertis : une couleur choisie par un
-- administrateur (au selecteur de couleur, donc en hexadecimal) n'est pas touchee.

UPDATE `nf_groups` SET `color` = 'warning' WHERE `color` = 'orange';
UPDATE `nf_groups` SET `color` = 'danger'  WHERE `color` = 'red';
UPDATE `nf_roles`  SET `color` = 'warning' WHERE `color` = 'orange';
UPDATE `nf_roles`  SET `color` = 'danger'  WHERE `color` = 'red';
