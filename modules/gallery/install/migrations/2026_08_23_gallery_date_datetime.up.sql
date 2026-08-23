-- `date` stocke une date de parution potentiellement FUTURE (publication programmée : une date future
-- masque l'album jusqu'à son heure, cf. models/gallery.php). En TIMESTAMP, plafond au 19/01/2038 →
-- rejet en mode SQL strict (errno 1292). Passage en DATETIME (jusqu'à l'an 9999). Heure murale conservée.
-- Fresh install : baseline (install.sql porte déjà datetime). Install existante : exécutée par update().
ALTER TABLE `nf_gallery`
  MODIFY COLUMN `date` datetime NOT NULL DEFAULT current_timestamp();
