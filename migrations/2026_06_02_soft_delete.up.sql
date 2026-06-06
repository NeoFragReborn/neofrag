-- Corbeille (soft-delete) : au lieu de supprimer une ligne, on horodate deleted_at.
-- Le contenu disparaît des listes/affichage (WHERE deleted_at IS NULL) mais reste
-- restaurable depuis la corbeille admin. Purge = vraie suppression.
-- v1 : news + articles. Étendre ensuite (forum, commentaires, galerie) sur le même modèle.

ALTER TABLE nf_news
	ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL AFTER vote,
	ADD COLUMN deleted_by INT(11) UNSIGNED NULL DEFAULT NULL AFTER deleted_at,
	ADD KEY idx_deleted_at (deleted_at);

ALTER TABLE nf_articles
	ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL,
	ADD COLUMN deleted_by INT(11) UNSIGNED NULL DEFAULT NULL,
	ADD KEY idx_deleted_at (deleted_at);

-- Module Corbeille (admin) : vue centralisée du contenu soft-deleted.
INSERT INTO nf_addon (name, type_id, data)
SELECT 'trash', t.id, 'a:1:{s:7:"enabled";b:1;}'
FROM nf_addon_type t
WHERE t.name = 'module'
  AND NOT EXISTS (SELECT 1 FROM nf_addon WHERE name = 'trash');
