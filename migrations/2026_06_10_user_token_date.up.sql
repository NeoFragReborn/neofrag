-- Les tokens de reset mot de passe / validation email n'avaient pas de date :
-- impossibles à expirer, valables à vie. La colonne permet l'expiration côté checker.
ALTER TABLE `nf_user_token`
	ADD COLUMN `date` timestamp NOT NULL DEFAULT current_timestamp() AFTER `user_id`;
