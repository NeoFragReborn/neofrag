-- Soft-delete des commentaires : suppression réversible + corbeille.
-- Remplace l'ancien marqueur « content vidé / hard-delete du dernier » par deleted_at
-- (le contenu est conservé → restaurable). deleted_by = auteur de la suppression.
ALTER TABLE `nf_comment`
	ADD COLUMN `deleted_at` DATETIME NULL DEFAULT NULL,
	ADD COLUMN `deleted_by` INT(11) UNSIGNED NULL DEFAULT NULL,
	ADD KEY `idx_comment_deleted` (`deleted_at`);
