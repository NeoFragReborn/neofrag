-- Retrait des choix de visibilité du profil : le profil montre de nouveau tout à tout le monde.

ALTER TABLE `nf_user_profile`
  DROP COLUMN `montrer_points`,
  DROP COLUMN `montrer_karma`,
  DROP COLUMN `montrer_vip`,
  DROP COLUMN `montrer_age`,
  DROP COLUMN `montrer_statut`;
