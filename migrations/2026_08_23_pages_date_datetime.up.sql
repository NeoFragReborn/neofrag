-- `nf_pages.date` (cœur, schema.sql) stocke une date de parution potentiellement FUTURE (publication
-- programmée : une date future masque la page jusqu'à son heure, cf. modules/pages/models/pages.php).
-- En TIMESTAMP, plafond au 19/01/2038 → rejet en mode SQL strict (errno 1292). Passage en DATETIME.
ALTER TABLE `nf_pages`
  MODIFY COLUMN `date` datetime NOT NULL DEFAULT current_timestamp();
