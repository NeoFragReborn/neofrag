-- Les classes directionnelles de Bootstrap 4 restees dans les reglages, apres le passage a
-- Bootstrap 5.
--
-- Bootstrap 5 a RENOMME les utilitaires directionnels : `float-right` est devenu `float-end`,
-- `text-left` est devenu `text-start`. Une classe renommee ne provoque aucune erreur — elle n'est
-- simplement plus definie, et l'element garde sa mise en page par defaut.
--
-- La valeur par defaut de `nf_copyright` porte `<div class="float-right">Propulse par {neofrag}</div>`.
-- Elle vient de loin : `neofrag/install/alpha.0.2.php` l'ecrivait avec `pull-right` (Bootstrap 3),
-- et `neofrag/install/alpha_0_2_2.php` l'avait migree vers `float-right` au passage a Bootstrap 4.
-- Le pas suivant n'a jamais ete fait. Resultat, sur toute installation existante : la mention
-- « Propulse par NeoFrag » ne flotte pas a droite du pied de page, elle tombe a la ligne, a gauche.
--
-- Verifie le 2026-09-20 : les trois installations du projet — production, demonstration et essai —
-- portaient la valeur fautive.
--
-- On ne touche qu'aux REGLAGES, jamais au contenu redige par les membres : une actualite ou un
-- message de forum qui contient `float-right` appartient a son auteur, et le reecrire en base
-- serait modifier ses textes sans le lui demander.

UPDATE `nf_settings`
   SET `value` = REPLACE(REPLACE(`value`,
           'class=&quot;float-right&quot;', 'class=&quot;float-end&quot;'),
           'class="float-right"',           'class="float-end"')
 WHERE `name` = 'nf_copyright'
   AND (`value` LIKE '%float-right%');

UPDATE `nf_settings`
   SET `value` = REPLACE(REPLACE(`value`,
           'class=&quot;float-left&quot;', 'class=&quot;float-start&quot;'),
           'class="float-left"',           'class="float-start"')
 WHERE `name` = 'nf_copyright'
   AND (`value` LIKE '%float-left%');
