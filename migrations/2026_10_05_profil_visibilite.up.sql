-- Ce que le profil public d'un membre montre aux autres (chantier A, étape A2).
--
-- Jusqu'ici, les points, le karma et les jours de VIP d'un membre se lisaient sur son profil et dans la fiche
-- qui s'ouvre au survol de son pseudo, visiteurs compris, sans qu'il puisse les cacher. Ils lui sont désormais
-- réservés, et il choisit de les montrer. Son âge et sa présence en ligne restent montrés, comme avant : il
-- peut les cacher.

ALTER TABLE `nf_user_profile`
  ADD COLUMN `montrer_points` tinyint(1) unsigned NOT NULL DEFAULT 0,
  ADD COLUMN `montrer_karma` tinyint(1) unsigned NOT NULL DEFAULT 0,
  ADD COLUMN `montrer_vip` tinyint(1) unsigned NOT NULL DEFAULT 0,
  ADD COLUMN `montrer_age` tinyint(1) unsigned NOT NULL DEFAULT 1,
  ADD COLUMN `montrer_statut` tinyint(1) unsigned NOT NULL DEFAULT 1;
