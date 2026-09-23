-- Le menu redescend de la barre vitree vers la zone d'en-tete, et la zone « Navigation » disparait.
--
-- CE QUI N'EST PAS RESTAURE : le widget « header » (le nom du site repete sous la barre), que la
-- migration montante retirait de la zone d'en-tete. Il y a un widget « header » par theme en base,
-- et rien ne dit LEQUEL etait celui de nebula une fois la zone videe : le restaurer au juge
-- remettrait peut-etre celui d'un autre theme. On ne rend que ce qu'on sait rendre.
--
-- L'en-tete n'est rempli que s'il est reste VIDE — c'est ce que la montante y a laisse. Si
-- quelqu'un y a place quelque chose depuis, on n'y touche pas.

-- 1. Le menu de la zone « Navigation » de nebula reprend sa place dans l'en-tete.
UPDATE `nf_dispositions` `entete`
  JOIN `nf_dispositions` `barre`
    ON `barre`.`theme` = 'nebula' AND `barre`.`page` = '*' AND `barre`.`zone` = 5
   SET `entete`.`disposition` = REPLACE(`barre`.`disposition`, '"style":"row-default"', '"style":"row-dark"')
 WHERE `entete`.`theme` = 'nebula' AND `entete`.`page` = '*' AND `entete`.`zone` = 0
   AND `entete`.`disposition` = '[]';

-- 2. La zone « Navigation » de la barre n'existe plus.
DELETE FROM `nf_dispositions` WHERE `theme` = 'nebula' AND `page` = '*' AND `zone` = 5;
