-- Newsletter : plus de pixel de suivi (2026-10-08). Chaque destinataire recevait une image invisible, propre à lui,
-- qui disait au site qui ouvrait la lettre et quand : un traceur soumis au consentement (loi Informatique et Libertés,
-- art. 82 ; CNIL, recommandation du 12 mars 2026 sur les pixels de suivi dans les courriels), que l'inscription ne
-- demandait pas. Le jeton, la date d'ouverture et le compteur s'en vont avec lui.

ALTER TABLE `nf_newsletter_queue`
  DROP KEY `idx_track`,
  DROP COLUMN `track_token`,
  DROP COLUMN `opened_at`;

ALTER TABLE `nf_newsletter_campaigns`
  DROP COLUMN `opened_to`;
