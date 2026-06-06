-- Phase Privacy : distinction archive vs suppression user-side avec rétention 14j.
-- Avant : seules les colonnes archived_at (par participant) et deleted_at (sur nf_talks, admin) existaient.
-- Après : ajout d'un soft-delete par participant qui masque la conversation pour cet utilisateur seul,
-- en la conservant intacte pour les autres participants et pour audit modération unilatérale (14j).

ALTER TABLE nf_talks_participants
	ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL AFTER archived_at,
	ADD INDEX idx_participants_deleted_at (deleted_at);
