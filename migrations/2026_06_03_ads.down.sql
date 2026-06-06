DELETE a FROM nf_addon a JOIN nf_addon_type t ON a.type_id = t.id WHERE a.name = 'ads' AND t.name IN ('module', 'widget');
DROP TABLE IF EXISTS nf_ads;
