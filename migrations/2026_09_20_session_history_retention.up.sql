-- duree de conservation de l'historique des connexions.
--
-- `nf_session_history` gardait IP, nom d'hote, referent, agent et mode d'authentification depuis
-- toujours, sans qu'aucun code ne l'efface. Ce reglage donne une duree de vie a ces lignes ; la
-- purge elle-meme vit dans neofrag/core/session.php, au meme endroit que celle des sessions.
--
-- 395 jours = 13 mois : duree couramment retenue pour des journaux de connexion, qui laisse une
-- comparaison d'une annee sur l'autre. 0 desactive la purge.
INSERT INTO `nf_settings` (`name`, `site`, `lang`, `value`, `type`)
SELECT 'nf_session_history_days', '', '', '395', 'int'
WHERE NOT EXISTS (SELECT 1 FROM `nf_settings` WHERE `name` = 'nf_session_history_days');
