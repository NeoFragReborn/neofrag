-- Widget Vidéo : lecteur HTML5 + playlist des vidéos de la bibliothèque média. Pas de table.
INSERT INTO nf_addon (name, type_id, data)
SELECT 'video', t.id, 'a:1:{s:7:"enabled";b:1;}'
FROM nf_addon_type t WHERE t.name = 'widget'
  AND NOT EXISTS (SELECT 1 FROM nf_addon a JOIN nf_addon_type tt ON a.type_id=tt.id WHERE a.name='video' AND tt.name='widget');
