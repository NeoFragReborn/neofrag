-- Retour arriere : les champs definis par l'administrateur disparaissent, AVEC les valeurs que les
-- membres y avaient mises. C'est irreversible, et c'est pourquoi l'ordre compte : les valeurs
-- d'abord, la definition ensuite (la contrainte l'exigerait de toute facon).
DROP TABLE IF EXISTS `nf_user_fields_values`;
DROP TABLE IF EXISTS `nf_user_fields`;
