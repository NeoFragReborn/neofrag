-- Retour arriere : le reglage disparait, et la purge cesse — le code la saute quand il vaut 0 ou rien.
DELETE FROM `nf_settings` WHERE `name` = 'nf_session_history_days';
