-- L'avatar d'un compte lie (Discord, GitHub, Google) tenait dans 100 caracteres.
--
-- L'adresse d'un avatar Discord en fait exactement 100 pour un identifiant a dix-neuf chiffres ; un
-- avatar ANIME (prefixe `a_`) en fait 102, et les adresses d'images de Google depassent souvent
-- cette taille. En mode SQL strict - celui de MariaDB par defaut -, l'enregistrement echouait, et la
-- connexion par ce compte avec lui (releve le 2026-10-01).

ALTER TABLE `nf_user_auth` MODIFY `avatar` varchar(255) DEFAULT NULL;
