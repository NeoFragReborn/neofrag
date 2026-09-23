-- Nebula n'affiche plus son menu DEUX fois.
--
-- LE DEFAUT. Le gabarit de nebula dessine sa propre barre vitree, avec le nom du site et une
-- navigation. L'installation du theme placait EN PLUS, dans la zone « Header » juste en dessous, un
-- widget « header » (le nom du site) et un widget « navigation » (le menu). Toute installation
-- neuve affichait donc le nom du site deux fois et le menu deux fois. Signale le
-- 2026-09-22 sur la demonstration.
--
-- CE QUI CHANGE. Le theme declare desormais une zone « Navigation » (rang 5), rendue A L'INTERIEUR
-- de la barre. Le menu y vit : il n'apparait qu'une fois, et il reste configurable depuis
-- l'administration comme n'importe quel widget. Les liens du gabarit, qui etaient ecrits en dur et
-- que personne ne pouvait corriger, ont disparu.
--
-- POURQUOI CETTE MIGRATION EXISTE. Les dispositions vivent en BASE, pas dans le code : corriger
-- l'installeur ne change rien aux sites deja installes. Sans ce fichier, la demonstration et le
-- site de production garderaient leur double menu.
--
-- PRUDENCE. Le widget deplace est celui que la zone d'en-tete de NEBULA reference — il existe un
-- widget « navigation » par theme, et prendre le premier venu deplacerait le menu d'un autre. Et
-- la zone d'en-tete n'est videe que si elle est restee EXACTEMENT telle que livree : un
-- administrateur qui y a mis autre chose garde sa page intacte.

-- 1. La zone « Navigation » recoit le widget de menu de la zone d'en-tete de nebula.
INSERT INTO `nf_dispositions` (`theme`, `page`, `zone`, `disposition`)
SELECT 'nebula', '*', 5,
       CONCAT('[{"style":"row-default","cols":[{"size":null,"widgets":[{"id":', `w`.`widget_id`, ',"style":null,"size":null}]}]}]')
  FROM `nf_widgets` `w`
  JOIN `nf_dispositions` `d`
    ON `d`.`theme` = 'nebula' AND `d`.`page` = '*' AND `d`.`zone` = 0
   AND `d`.`disposition` LIKE CONCAT('%{"id":', `w`.`widget_id`, ',%')
 WHERE `w`.`widget` = 'navigation'
   AND NOT EXISTS (SELECT 1 FROM (SELECT `theme`, `page`, `zone` FROM `nf_dispositions`) `x`
                    WHERE `x`.`theme` = 'nebula' AND `x`.`page` = '*' AND `x`.`zone` = 5)
 LIMIT 1;

-- 2. La zone d'en-tete est videe SI ET SEULEMENT SI elle est restee celle d'origine.
UPDATE `nf_dispositions` `d`
  JOIN `nf_widgets` `h` ON `h`.`widget` = 'header'
  JOIN `nf_widgets` `n` ON `n`.`widget` = 'navigation'
   SET `d`.`disposition` = '[]'
 WHERE `d`.`theme` = 'nebula' AND `d`.`page` = '*' AND `d`.`zone` = 0
   AND `d`.`disposition` = CONCAT(
        '[{"style":"row-default","cols":[{"size":null,"widgets":[{"id":', `h`.`widget_id`, ',"style":null,"size":null}]}]},',
        '{"style":"row-dark","cols":[{"size":null,"widgets":[{"id":', `n`.`widget_id`, ',"style":null,"size":null}]}]}]');
