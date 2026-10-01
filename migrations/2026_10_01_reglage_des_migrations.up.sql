-- Le rattrapage des migrations suit desormais `nf_migrations_version`. L'ancien reglage,
-- `nf_schema_version` (1.2.2 et 1.2.3), ne sert plus a rien : il est retire.

DELETE FROM `nf_settings` WHERE `name` = 'nf_schema_version';
