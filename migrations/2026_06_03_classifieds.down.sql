DELETE a FROM nf_addon a JOIN nf_addon_type t ON a.type_id = t.id WHERE a.name = 'classifieds' AND t.name = 'module';
DROP TABLE IF EXISTS nf_classifieds;
DROP TABLE IF EXISTS nf_classifieds_categories;
