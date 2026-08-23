-- Réactions multi-emoji : colonne `reaction` = clé de l'emoji choisi (like/love/haha/wow/sad/angry).
-- Les lignes existantes (= « j'aime » historiques, un seul cœur) deviennent 'love' (le cœur d'origine).
-- Le modèle reste « une réaction par utilisateur et par contenu » (UNIQUE user+type+id inchangée).
ALTER TABLE nf_reactions ADD COLUMN reaction VARCHAR(16) NOT NULL DEFAULT 'love' AFTER content_id;
