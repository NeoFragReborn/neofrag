-- Corbeille : extension du soft-delete aux galeries.
ALTER TABLE nf_gallery
	ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL,
	ADD COLUMN deleted_by INT(11) UNSIGNED NULL DEFAULT NULL,
	ADD KEY idx_deleted_at (deleted_at);
