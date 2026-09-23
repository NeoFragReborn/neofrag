-- Retrait de l'interrupteur du service worker.
--
-- ATTENTION : retirer le reglage rend `/service-worker.js` dans sa forme QUI SE RETIRE, puisque le
-- reglage absent vaut « eteint ». C'est le bon sens de marche — les workers installes disparaissent
-- d'eux-memes a leur prochaine verification. Ne jamais accompagner ce retrait d'une suppression du
-- fichier servi : celle-la, au contraire, laisserait les workers en place pour toujours.

DELETE FROM `nf_settings` WHERE `name` = 'nf_pwa';
