-- Gabarit de page : 'default' (cadre/panel) ou 'blank' (contenu pleine largeur, sans chrome).
ALTER TABLE `nf_pages`
	ADD COLUMN `layout` VARCHAR(20) NOT NULL DEFAULT 'default' AFTER `published`;
