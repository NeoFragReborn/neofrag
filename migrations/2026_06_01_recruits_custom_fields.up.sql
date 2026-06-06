-- Recruits : personnalisation du formulaire de candidature.
-- Champs custom par offre (nf_recruits_fields) + réponses stockées en JSON
-- sur la candidature (colonne custom).

CREATE TABLE IF NOT EXISTS nf_recruits_fields (
	field_id   INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
	recruit_id INT(11) UNSIGNED NOT NULL,
	label      VARCHAR(200) NOT NULL,
	type       ENUM('text','textarea') NOT NULL DEFAULT 'text',
	required   TINYINT(1) NOT NULL DEFAULT 0,
	sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
	PRIMARY KEY (field_id),
	KEY idx_recruit (recruit_id),
	CONSTRAINT fk_recruits_fields_recruit FOREIGN KEY (recruit_id) REFERENCES nf_recruits(recruit_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE nf_recruits_candidacies ADD COLUMN custom LONGTEXT NULL DEFAULT NULL;
