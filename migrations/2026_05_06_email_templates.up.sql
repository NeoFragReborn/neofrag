-- =====================================================================
-- NeoFrag — Email templates : table DB + 7 templates seed (FR)
-- Date : 2026-05-06
-- Stratégie : ADDITIF, pas de breaking change.
-- Les anciens views/emails/*.tpl.php restent en place (fallback / rétro-compat).
-- =====================================================================

CREATE TABLE IF NOT EXISTS nf_email_templates (
  template_id   INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `key`         VARCHAR(100) NOT NULL,
  title         VARCHAR(255) NOT NULL,
  description   TEXT NULL,
  placeholders  TEXT NULL,
  module        VARCHAR(50) DEFAULT NULL,
  enabled       TINYINT(1) NOT NULL DEFAULT 1,
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (template_id),
  UNIQUE KEY uk_key (`key`),
  INDEX idx_module (module)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS nf_email_template_translations (
  template_id   INT(10) UNSIGNED NOT NULL,
  lang          VARCHAR(5) NOT NULL,
  subject       VARCHAR(500) NOT NULL,
  body          MEDIUMTEXT NOT NULL,
  updated_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (template_id, lang),
  CONSTRAINT fk_email_tpl_trans_tpl FOREIGN KEY (template_id) REFERENCES nf_email_templates(template_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- Seed des 7 templates initiaux (FR uniquement à l'install ; admin étend ensuite via UI)
-- =====================================================================

INSERT INTO nf_email_templates (`key`, title, description, placeholders, module) VALUES
  ('user.registration',          'Validation de compte',     'Email de validation envoyé à la création d''un compte.',                            '["{{username}}","{{site_name}}","{{validation_url}}"]', 'user'),
  ('user.lost_password',         'Réinitialisation mot de passe', 'Email contenant le lien de réinitialisation du mot de passe.',                  '["{{username}}","{{site_name}}","{{reset_url}}"]',     'user'),
  ('forum.mention',              'Forum : mention',          'Notification email quand un user est @mentionné dans un sujet/post.',               '["{{username}}","{{topic_title}}","{{topic_url}}","{{mentioner}}","{{site_name}}"]', 'forum'),
  ('forum.subscription_reply',   'Forum : nouvelle réponse', 'Notification email quand un sujet abonné reçoit une nouvelle réponse.',             '["{{username}}","{{topic_title}}","{{topic_url}}","{{author}}","{{site_name}}"]', 'forum'),
  ('talks.new_message',          'Talks : nouveau message',  'Notification email quand un user reçoit un nouveau MP/message dans un talk.',       '["{{username}}","{{talk_name}}","{{talk_url}}","{{author}}","{{site_name}}"]', 'talks'),
  ('moderation.sanction',        'Modération : sanction',    'Email envoyé à un user qui reçoit une sanction (warn/restrict/ban).',                '["{{username}}","{{site_name}}","{{sanction_type}}","{{reason}}","{{duration}}"]', 'moderation'),
  ('newsletter.confirmation',    'Newsletter : confirmation','Email de double-opt-in pour confirmer l''inscription à la newsletter.',              '["{{site_name}}","{{confirm_url}}"]', 'newsletter');

-- =====================================================================
-- Templates FR (HTML par défaut, modifiable via admin UI)
-- =====================================================================

INSERT INTO nf_email_template_translations (template_id, lang, subject, body)
SELECT template_id, 'fr',
  'Validation de votre compte sur {{site_name}}',
  CONCAT(
    '<p>Bonjour <strong>{{username}}</strong>,</p>',
    '<p>Bienvenue sur <strong>{{site_name}}</strong> ! Pour activer ton compte, clique sur le lien ci-dessous :</p>',
    '<p><a href="{{validation_url}}" style="display:inline-block;padding:10px 20px;background:#03c1a2;color:#fff;text-decoration:none;border-radius:4px;">Valider mon compte</a></p>',
    '<p>Si le bouton ne fonctionne pas, copie-colle cette URL dans ton navigateur :<br><code>{{validation_url}}</code></p>',
    '<p>Si tu n''es pas à l''origine de cette inscription, ignore simplement cet email.</p>'
  )
FROM nf_email_templates WHERE `key` = 'user.registration';

INSERT INTO nf_email_template_translations (template_id, lang, subject, body)
SELECT template_id, 'fr',
  'Réinitialisation de mot de passe — {{site_name}}',
  CONCAT(
    '<p>Bonjour <strong>{{username}}</strong>,</p>',
    '<p>Tu as demandé une réinitialisation de mot de passe sur <strong>{{site_name}}</strong>.</p>',
    '<p><a href="{{reset_url}}" style="display:inline-block;padding:10px 20px;background:#03c1a2;color:#fff;text-decoration:none;border-radius:4px;">Réinitialiser mon mot de passe</a></p>',
    '<p>Ce lien est valide pendant une durée limitée. Si tu n''es pas à l''origine de cette demande, ignore cet email — ton mot de passe ne sera pas modifié.</p>'
  )
FROM nf_email_templates WHERE `key` = 'user.lost_password';

INSERT INTO nf_email_template_translations (template_id, lang, subject, body)
SELECT template_id, 'fr',
  'Tu as été mentionné(e) : {{topic_title}}',
  CONCAT(
    '<p>Bonjour <strong>{{username}}</strong>,</p>',
    '<p><strong>{{mentioner}}</strong> t''a mentionné(e) dans le sujet « {{topic_title}} » sur <strong>{{site_name}}</strong>.</p>',
    '<p><a href="{{topic_url}}" style="display:inline-block;padding:8px 16px;background:#03c1a2;color:#fff;text-decoration:none;border-radius:4px;">Voir le sujet</a></p>'
  )
FROM nf_email_templates WHERE `key` = 'forum.mention';

INSERT INTO nf_email_template_translations (template_id, lang, subject, body)
SELECT template_id, 'fr',
  'Nouvelle réponse : {{topic_title}}',
  CONCAT(
    '<p>Bonjour <strong>{{username}}</strong>,</p>',
    '<p>Tu suis le sujet « {{topic_title}} » sur <strong>{{site_name}}</strong>. Il vient de recevoir une nouvelle réponse de <strong>{{author}}</strong>.</p>',
    '<p><a href="{{topic_url}}" style="display:inline-block;padding:8px 16px;background:#03c1a2;color:#fff;text-decoration:none;border-radius:4px;">Voir la réponse</a></p>',
    '<p style="color:#888;font-size:13px;">Tu peux te désabonner depuis la page du sujet.</p>'
  )
FROM nf_email_templates WHERE `key` = 'forum.subscription_reply';

INSERT INTO nf_email_template_translations (template_id, lang, subject, body)
SELECT template_id, 'fr',
  'Nouveau message dans : {{talk_name}}',
  CONCAT(
    '<p>Bonjour <strong>{{username}}</strong>,</p>',
    '<p>Tu as reçu un nouveau message de <strong>{{author}}</strong> dans la conversation « {{talk_name}} » sur <strong>{{site_name}}</strong>.</p>',
    '<p><a href="{{talk_url}}" style="display:inline-block;padding:8px 16px;background:#03c1a2;color:#fff;text-decoration:none;border-radius:4px;">Lire le message</a></p>'
  )
FROM nf_email_templates WHERE `key` = 'talks.new_message';

INSERT INTO nf_email_template_translations (template_id, lang, subject, body)
SELECT template_id, 'fr',
  'Sanction de modération sur ton compte — {{site_name}}',
  CONCAT(
    '<p>Bonjour <strong>{{username}}</strong>,</p>',
    '<p>Une sanction a été appliquée à ton compte sur <strong>{{site_name}}</strong>.</p>',
    '<table style="border-collapse:collapse;margin:12px 0;">',
      '<tr><td style="padding:6px 12px;background:#f5f5f5;font-weight:bold;">Type</td><td style="padding:6px 12px;">{{sanction_type}}</td></tr>',
      '<tr><td style="padding:6px 12px;background:#f5f5f5;font-weight:bold;">Durée</td><td style="padding:6px 12px;">{{duration}}</td></tr>',
      '<tr><td style="padding:6px 12px;background:#f5f5f5;font-weight:bold;">Motif</td><td style="padding:6px 12px;">{{reason}}</td></tr>',
    '</table>',
    '<p>Si tu estimes que cette décision est injustifiée, tu peux contacter l''équipe de modération via la page de contact.</p>'
  )
FROM nf_email_templates WHERE `key` = 'moderation.sanction';

INSERT INTO nf_email_template_translations (template_id, lang, subject, body)
SELECT template_id, 'fr',
  'Confirme ton inscription à la newsletter — {{site_name}}',
  CONCAT(
    '<p>Salut !</p>',
    '<p>Pour finaliser ton inscription à la newsletter de <strong>{{site_name}}</strong>, clique sur le bouton ci-dessous :</p>',
    '<p><a href="{{confirm_url}}" style="display:inline-block;padding:10px 20px;background:#03c1a2;color:#fff;text-decoration:none;border-radius:4px;">Confirmer mon inscription</a></p>',
    '<p>Si tu n''es pas à l''origine de cette inscription, ignore simplement cet email.</p>'
  )
FROM nf_email_templates WHERE `key` = 'newsletter.confirmation';
