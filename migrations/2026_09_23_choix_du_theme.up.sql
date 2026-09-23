-- Le site vitrine n'a qu'une apparence : le choix de theme du visiteur y est ferme.
--
-- Le 2026-09-23, un visiteur qui choisissait un theme sur la demonstration (servie depuis /demo/)
-- voyait aussi le site vitrine dans ce theme : le cookie valait pour tout le domaine. Il est
-- desormais propre a chaque site (neofrag/helpers/theme.php) ; en plus, un site dont le theme par
-- defaut est « vitrine » ferme le choix. Les autres gardent le comportement d'avant : le choix reste
-- ouvert tant que le reglage n'existe pas.
--
-- INSERT IGNORE : un choix deja fait par l'administrateur est garde.

INSERT IGNORE INTO `nf_settings` (`name`, `site`, `lang`, `value`, `type`)
SELECT 'nf_theme_visiteur', '', '', '0', 'bool'
  FROM `nf_settings`
 WHERE `name` = 'nf_default_theme' AND `site` = '' AND `lang` = '' AND `value` = 'vitrine';
