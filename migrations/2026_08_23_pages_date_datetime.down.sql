-- Rollback : retour au type d'origine (TIMESTAMP). Réintroduit la limite 2038 — ne pas conserver.
ALTER TABLE `nf_pages`
  MODIFY COLUMN `date` timestamp NOT NULL DEFAULT current_timestamp();
