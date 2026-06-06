-- Flux RSS — module sans table (génère des flux RSS 2.0 des news et articles).
INSERT INTO nf_addon (name, type_id, data)
SELECT 'feeds', t.id, 'a:1:{s:7:"enabled";b:1;}'
FROM nf_addon_type t WHERE t.name = 'module'
  AND NOT EXISTS (SELECT 1 FROM nf_addon a JOIN nf_addon_type tt ON a.type_id=tt.id WHERE a.name='feeds' AND tt.name='module');
