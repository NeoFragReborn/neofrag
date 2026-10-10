-- Un lien envoyé par e-mail ne vaut que pour son usage (audit de sécurité du 2026-10-09).
--
-- Le lien « mot de passe oublié » (valable une heure, pour choisir un nouveau mot de passe) ouvrait aussi
-- `user/validation/`, le lien d'inscription : valable deux jours, il connecte sans rien demander. La colonne dit
-- à quoi sert chaque lien. Les liens déjà envoyés n'avaient pas d'usage écrit : ils tombent. Un membre qui
-- attendait sa validation en reçoit un nouveau à sa prochaine tentative de connexion.

ALTER TABLE `nf_user_token`
	ADD COLUMN `type` enum('mot_de_passe','validation') NOT NULL DEFAULT 'mot_de_passe' AFTER `user_id`;

DELETE FROM `nf_user_token`;
