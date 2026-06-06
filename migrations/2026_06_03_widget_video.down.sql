DELETE a FROM nf_addon a JOIN nf_addon_type t ON a.type_id = t.id WHERE a.name = 'video' AND t.name = 'widget';
