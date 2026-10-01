-- Un compte externe (Discord, GitHub, Google) ne se lie qu'a UN membre.
--
-- La cle unique portait sur le triplet (membre, fournisseur, identifiant) : rien n'empechait deux
-- membres de lier le meme compte Discord, et l'API, qui retrouve un membre par son Discord (fiche
-- B11), aurait pu en trouver deux. Les doublons eventuels sont retires d'abord - le lien le plus
-- ancien est garde - pour que la cle puisse etre posee (releve le 2026-10-01).

DELETE a FROM `nf_user_auth` a
  JOIN `nf_user_auth` b ON b.`authenticator_id` = a.`authenticator_id` AND b.`key` = a.`key` AND b.`id` < a.`id`;

ALTER TABLE `nf_user_auth` ADD UNIQUE KEY `uk_authenticator_key` (`authenticator_id`, `key`);
