-- Zone forum VIP : une catégorie peut être réservée aux membres VIP (statut
-- gamification). Accès filtré par le modèle forum (check_forum/topic/message +
-- listing), les admins voient tout.
ALTER TABLE nf_forum_categories ADD COLUMN vip_only TINYINT(1) NOT NULL DEFAULT 0 AFTER image_id;
