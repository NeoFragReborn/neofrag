-- Traductions des 7 templates d'e-mail en en/de/es/it/pt (FR déjà présent).
-- Le fallback était 'fr' → tout membre non francophone recevait ses e-mails en français.
-- HTML, styles inline et placeholders {{...}} conservés à l'identique ; seul le texte est traduit.
-- Idempotent : NOT EXISTS par (template_id, lang).

-- ============================ user.registration ============================
INSERT INTO `nf_email_template_translations` (`template_id`, `lang`, `subject`, `body`)
SELECT t.`template_id`, 'en', 'Confirm your account on {{site_name}}',
'<p>Hello <strong>{{username}}</strong>,</p><p>Welcome to <strong>{{site_name}}</strong>! To activate your account, click the link below:</p><p><a href="{{validation_url}}" style="display:inline-block;padding:10px 20px;background:#03c1a2;color:#fff;text-decoration:none;border-radius:4px;">Activate my account</a></p><p>If the button does not work, copy and paste this URL into your browser:<br><code>{{validation_url}}</code></p><p>If you did not request this registration, simply ignore this email.</p>'
FROM `nf_email_templates` t WHERE t.`key`='user.registration' AND NOT EXISTS (SELECT 1 FROM `nf_email_template_translations` x WHERE x.`template_id`=t.`template_id` AND x.`lang`='en');

INSERT INTO `nf_email_template_translations` (`template_id`, `lang`, `subject`, `body`)
SELECT t.`template_id`, 'de', 'Bestätige dein Konto auf {{site_name}}',
'<p>Hallo <strong>{{username}}</strong>,</p><p>Willkommen auf <strong>{{site_name}}</strong>! Um dein Konto zu aktivieren, klicke auf den folgenden Link:</p><p><a href="{{validation_url}}" style="display:inline-block;padding:10px 20px;background:#03c1a2;color:#fff;text-decoration:none;border-radius:4px;">Mein Konto aktivieren</a></p><p>Falls die Schaltfläche nicht funktioniert, kopiere diese URL in deinen Browser:<br><code>{{validation_url}}</code></p><p>Falls du diese Registrierung nicht veranlasst hast, ignoriere diese E-Mail einfach.</p>'
FROM `nf_email_templates` t WHERE t.`key`='user.registration' AND NOT EXISTS (SELECT 1 FROM `nf_email_template_translations` x WHERE x.`template_id`=t.`template_id` AND x.`lang`='de');

INSERT INTO `nf_email_template_translations` (`template_id`, `lang`, `subject`, `body`)
SELECT t.`template_id`, 'es', 'Confirma tu cuenta en {{site_name}}',
'<p>Hola <strong>{{username}}</strong>,</p><p>¡Bienvenido/a a <strong>{{site_name}}</strong>! Para activar tu cuenta, haz clic en el siguiente enlace:</p><p><a href="{{validation_url}}" style="display:inline-block;padding:10px 20px;background:#03c1a2;color:#fff;text-decoration:none;border-radius:4px;">Activar mi cuenta</a></p><p>Si el botón no funciona, copia y pega esta URL en tu navegador:<br><code>{{validation_url}}</code></p><p>Si no has solicitado este registro, simplemente ignora este correo.</p>'
FROM `nf_email_templates` t WHERE t.`key`='user.registration' AND NOT EXISTS (SELECT 1 FROM `nf_email_template_translations` x WHERE x.`template_id`=t.`template_id` AND x.`lang`='es');

INSERT INTO `nf_email_template_translations` (`template_id`, `lang`, `subject`, `body`)
SELECT t.`template_id`, 'it', 'Conferma il tuo account su {{site_name}}',
'<p>Ciao <strong>{{username}}</strong>,</p><p>Benvenuto/a su <strong>{{site_name}}</strong>! Per attivare il tuo account, clicca sul link qui sotto:</p><p><a href="{{validation_url}}" style="display:inline-block;padding:10px 20px;background:#03c1a2;color:#fff;text-decoration:none;border-radius:4px;">Attiva il mio account</a></p><p>Se il pulsante non funziona, copia e incolla questo URL nel tuo browser:<br><code>{{validation_url}}</code></p><p>Se non sei stato tu a registrarti, ignora semplicemente questa email.</p>'
FROM `nf_email_templates` t WHERE t.`key`='user.registration' AND NOT EXISTS (SELECT 1 FROM `nf_email_template_translations` x WHERE x.`template_id`=t.`template_id` AND x.`lang`='it');

INSERT INTO `nf_email_template_translations` (`template_id`, `lang`, `subject`, `body`)
SELECT t.`template_id`, 'pt', 'Confirma a tua conta em {{site_name}}',
'<p>Olá <strong>{{username}}</strong>,</p><p>Bem-vindo(a) a <strong>{{site_name}}</strong>! Para ativar a tua conta, clica no link abaixo:</p><p><a href="{{validation_url}}" style="display:inline-block;padding:10px 20px;background:#03c1a2;color:#fff;text-decoration:none;border-radius:4px;">Ativar a minha conta</a></p><p>Se o botão não funcionar, copia e cola este URL no teu navegador:<br><code>{{validation_url}}</code></p><p>Se não foste tu a fazer este registo, ignora simplesmente este email.</p>'
FROM `nf_email_templates` t WHERE t.`key`='user.registration' AND NOT EXISTS (SELECT 1 FROM `nf_email_template_translations` x WHERE x.`template_id`=t.`template_id` AND x.`lang`='pt');

-- ============================ user.lost_password ============================
INSERT INTO `nf_email_template_translations` (`template_id`, `lang`, `subject`, `body`)
SELECT t.`template_id`, 'en', 'Password reset — {{site_name}}',
'<p>Hello <strong>{{username}}</strong>,</p><p>You requested a password reset on <strong>{{site_name}}</strong>.</p><p><a href="{{reset_url}}" style="display:inline-block;padding:10px 20px;background:#03c1a2;color:#fff;text-decoration:none;border-radius:4px;">Reset my password</a></p><p>This link is valid for a limited time. If you did not make this request, ignore this email — your password will not be changed.</p>'
FROM `nf_email_templates` t WHERE t.`key`='user.lost_password' AND NOT EXISTS (SELECT 1 FROM `nf_email_template_translations` x WHERE x.`template_id`=t.`template_id` AND x.`lang`='en');

INSERT INTO `nf_email_template_translations` (`template_id`, `lang`, `subject`, `body`)
SELECT t.`template_id`, 'de', 'Passwort zurücksetzen — {{site_name}}',
'<p>Hallo <strong>{{username}}</strong>,</p><p>Du hast auf <strong>{{site_name}}</strong> das Zurücksetzen deines Passworts angefordert.</p><p><a href="{{reset_url}}" style="display:inline-block;padding:10px 20px;background:#03c1a2;color:#fff;text-decoration:none;border-radius:4px;">Mein Passwort zurücksetzen</a></p><p>Dieser Link ist nur begrenzt gültig. Falls du diese Anfrage nicht gestellt hast, ignoriere diese E-Mail — dein Passwort wird nicht geändert.</p>'
FROM `nf_email_templates` t WHERE t.`key`='user.lost_password' AND NOT EXISTS (SELECT 1 FROM `nf_email_template_translations` x WHERE x.`template_id`=t.`template_id` AND x.`lang`='de');

INSERT INTO `nf_email_template_translations` (`template_id`, `lang`, `subject`, `body`)
SELECT t.`template_id`, 'es', 'Restablecer contraseña — {{site_name}}',
'<p>Hola <strong>{{username}}</strong>,</p><p>Has solicitado restablecer tu contraseña en <strong>{{site_name}}</strong>.</p><p><a href="{{reset_url}}" style="display:inline-block;padding:10px 20px;background:#03c1a2;color:#fff;text-decoration:none;border-radius:4px;">Restablecer mi contraseña</a></p><p>Este enlace es válido durante un tiempo limitado. Si no has realizado esta solicitud, ignora este correo — tu contraseña no se modificará.</p>'
FROM `nf_email_templates` t WHERE t.`key`='user.lost_password' AND NOT EXISTS (SELECT 1 FROM `nf_email_template_translations` x WHERE x.`template_id`=t.`template_id` AND x.`lang`='es');

INSERT INTO `nf_email_template_translations` (`template_id`, `lang`, `subject`, `body`)
SELECT t.`template_id`, 'it', 'Reimpostazione della password — {{site_name}}',
'<p>Ciao <strong>{{username}}</strong>,</p><p>Hai richiesto la reimpostazione della password su <strong>{{site_name}}</strong>.</p><p><a href="{{reset_url}}" style="display:inline-block;padding:10px 20px;background:#03c1a2;color:#fff;text-decoration:none;border-radius:4px;">Reimposta la mia password</a></p><p>Questo link è valido per un periodo limitato. Se non sei stato tu a fare questa richiesta, ignora questa email — la tua password non verrà modificata.</p>'
FROM `nf_email_templates` t WHERE t.`key`='user.lost_password' AND NOT EXISTS (SELECT 1 FROM `nf_email_template_translations` x WHERE x.`template_id`=t.`template_id` AND x.`lang`='it');

INSERT INTO `nf_email_template_translations` (`template_id`, `lang`, `subject`, `body`)
SELECT t.`template_id`, 'pt', 'Redefinição de palavra-passe — {{site_name}}',
'<p>Olá <strong>{{username}}</strong>,</p><p>Solicitaste a redefinição da tua palavra-passe em <strong>{{site_name}}</strong>.</p><p><a href="{{reset_url}}" style="display:inline-block;padding:10px 20px;background:#03c1a2;color:#fff;text-decoration:none;border-radius:4px;">Redefinir a minha palavra-passe</a></p><p>Este link é válido por tempo limitado. Se não fizeste este pedido, ignora este email — a tua palavra-passe não será alterada.</p>'
FROM `nf_email_templates` t WHERE t.`key`='user.lost_password' AND NOT EXISTS (SELECT 1 FROM `nf_email_template_translations` x WHERE x.`template_id`=t.`template_id` AND x.`lang`='pt');

-- ============================ forum.mention ============================
INSERT INTO `nf_email_template_translations` (`template_id`, `lang`, `subject`, `body`)
SELECT t.`template_id`, 'en', 'You were mentioned: {{topic_title}}',
'<p>Hello <strong>{{username}}</strong>,</p><p><strong>{{mentioner}}</strong> mentioned you in the topic « {{topic_title}} » on <strong>{{site_name}}</strong>.</p><p><a href="{{topic_url}}" style="display:inline-block;padding:8px 16px;background:#03c1a2;color:#fff;text-decoration:none;border-radius:4px;">View the topic</a></p>'
FROM `nf_email_templates` t WHERE t.`key`='forum.mention' AND NOT EXISTS (SELECT 1 FROM `nf_email_template_translations` x WHERE x.`template_id`=t.`template_id` AND x.`lang`='en');

INSERT INTO `nf_email_template_translations` (`template_id`, `lang`, `subject`, `body`)
SELECT t.`template_id`, 'de', 'Du wurdest erwähnt: {{topic_title}}',
'<p>Hallo <strong>{{username}}</strong>,</p><p><strong>{{mentioner}}</strong> hat dich im Thema « {{topic_title}} » auf <strong>{{site_name}}</strong> erwähnt.</p><p><a href="{{topic_url}}" style="display:inline-block;padding:8px 16px;background:#03c1a2;color:#fff;text-decoration:none;border-radius:4px;">Thema ansehen</a></p>'
FROM `nf_email_templates` t WHERE t.`key`='forum.mention' AND NOT EXISTS (SELECT 1 FROM `nf_email_template_translations` x WHERE x.`template_id`=t.`template_id` AND x.`lang`='de');

INSERT INTO `nf_email_template_translations` (`template_id`, `lang`, `subject`, `body`)
SELECT t.`template_id`, 'es', 'Te han mencionado: {{topic_title}}',
'<p>Hola <strong>{{username}}</strong>,</p><p><strong>{{mentioner}}</strong> te ha mencionado en el tema « {{topic_title}} » en <strong>{{site_name}}</strong>.</p><p><a href="{{topic_url}}" style="display:inline-block;padding:8px 16px;background:#03c1a2;color:#fff;text-decoration:none;border-radius:4px;">Ver el tema</a></p>'
FROM `nf_email_templates` t WHERE t.`key`='forum.mention' AND NOT EXISTS (SELECT 1 FROM `nf_email_template_translations` x WHERE x.`template_id`=t.`template_id` AND x.`lang`='es');

INSERT INTO `nf_email_template_translations` (`template_id`, `lang`, `subject`, `body`)
SELECT t.`template_id`, 'it', 'Sei stato menzionato: {{topic_title}}',
'<p>Ciao <strong>{{username}}</strong>,</p><p><strong>{{mentioner}}</strong> ti ha menzionato nella discussione « {{topic_title}} » su <strong>{{site_name}}</strong>.</p><p><a href="{{topic_url}}" style="display:inline-block;padding:8px 16px;background:#03c1a2;color:#fff;text-decoration:none;border-radius:4px;">Vedi la discussione</a></p>'
FROM `nf_email_templates` t WHERE t.`key`='forum.mention' AND NOT EXISTS (SELECT 1 FROM `nf_email_template_translations` x WHERE x.`template_id`=t.`template_id` AND x.`lang`='it');

INSERT INTO `nf_email_template_translations` (`template_id`, `lang`, `subject`, `body`)
SELECT t.`template_id`, 'pt', 'Foste mencionado: {{topic_title}}',
'<p>Olá <strong>{{username}}</strong>,</p><p><strong>{{mentioner}}</strong> mencionou-te no tópico « {{topic_title}} » em <strong>{{site_name}}</strong>.</p><p><a href="{{topic_url}}" style="display:inline-block;padding:8px 16px;background:#03c1a2;color:#fff;text-decoration:none;border-radius:4px;">Ver o tópico</a></p>'
FROM `nf_email_templates` t WHERE t.`key`='forum.mention' AND NOT EXISTS (SELECT 1 FROM `nf_email_template_translations` x WHERE x.`template_id`=t.`template_id` AND x.`lang`='pt');

-- ============================ forum.subscription_reply ============================
INSERT INTO `nf_email_template_translations` (`template_id`, `lang`, `subject`, `body`)
SELECT t.`template_id`, 'en', 'New reply: {{topic_title}}',
'<p>Hello <strong>{{username}}</strong>,</p><p>You are following the topic « {{topic_title}} » on <strong>{{site_name}}</strong>. It just received a new reply from <strong>{{author}}</strong>.</p><p><a href="{{topic_url}}" style="display:inline-block;padding:8px 16px;background:#03c1a2;color:#fff;text-decoration:none;border-radius:4px;">View the reply</a></p><p style="color:#888;font-size:13px;">You can unsubscribe from the topic page.</p>'
FROM `nf_email_templates` t WHERE t.`key`='forum.subscription_reply' AND NOT EXISTS (SELECT 1 FROM `nf_email_template_translations` x WHERE x.`template_id`=t.`template_id` AND x.`lang`='en');

INSERT INTO `nf_email_template_translations` (`template_id`, `lang`, `subject`, `body`)
SELECT t.`template_id`, 'de', 'Neue Antwort: {{topic_title}}',
'<p>Hallo <strong>{{username}}</strong>,</p><p>Du folgst dem Thema « {{topic_title}} » auf <strong>{{site_name}}</strong>. Es hat soeben eine neue Antwort von <strong>{{author}}</strong> erhalten.</p><p><a href="{{topic_url}}" style="display:inline-block;padding:8px 16px;background:#03c1a2;color:#fff;text-decoration:none;border-radius:4px;">Antwort ansehen</a></p><p style="color:#888;font-size:13px;">Du kannst dich auf der Themenseite abmelden.</p>'
FROM `nf_email_templates` t WHERE t.`key`='forum.subscription_reply' AND NOT EXISTS (SELECT 1 FROM `nf_email_template_translations` x WHERE x.`template_id`=t.`template_id` AND x.`lang`='de');

INSERT INTO `nf_email_template_translations` (`template_id`, `lang`, `subject`, `body`)
SELECT t.`template_id`, 'es', 'Nueva respuesta: {{topic_title}}',
'<p>Hola <strong>{{username}}</strong>,</p><p>Sigues el tema « {{topic_title}} » en <strong>{{site_name}}</strong>. Acaba de recibir una nueva respuesta de <strong>{{author}}</strong>.</p><p><a href="{{topic_url}}" style="display:inline-block;padding:8px 16px;background:#03c1a2;color:#fff;text-decoration:none;border-radius:4px;">Ver la respuesta</a></p><p style="color:#888;font-size:13px;">Puedes darte de baja desde la página del tema.</p>'
FROM `nf_email_templates` t WHERE t.`key`='forum.subscription_reply' AND NOT EXISTS (SELECT 1 FROM `nf_email_template_translations` x WHERE x.`template_id`=t.`template_id` AND x.`lang`='es');

INSERT INTO `nf_email_template_translations` (`template_id`, `lang`, `subject`, `body`)
SELECT t.`template_id`, 'it', 'Nuova risposta: {{topic_title}}',
'<p>Ciao <strong>{{username}}</strong>,</p><p>Stai seguendo la discussione « {{topic_title}} » su <strong>{{site_name}}</strong>. Ha appena ricevuto una nuova risposta da <strong>{{author}}</strong>.</p><p><a href="{{topic_url}}" style="display:inline-block;padding:8px 16px;background:#03c1a2;color:#fff;text-decoration:none;border-radius:4px;">Vedi la risposta</a></p><p style="color:#888;font-size:13px;">Puoi annullare l''iscrizione dalla pagina della discussione.</p>'
FROM `nf_email_templates` t WHERE t.`key`='forum.subscription_reply' AND NOT EXISTS (SELECT 1 FROM `nf_email_template_translations` x WHERE x.`template_id`=t.`template_id` AND x.`lang`='it');

INSERT INTO `nf_email_template_translations` (`template_id`, `lang`, `subject`, `body`)
SELECT t.`template_id`, 'pt', 'Nova resposta: {{topic_title}}',
'<p>Olá <strong>{{username}}</strong>,</p><p>Estás a seguir o tópico « {{topic_title}} » em <strong>{{site_name}}</strong>. Acabou de receber uma nova resposta de <strong>{{author}}</strong>.</p><p><a href="{{topic_url}}" style="display:inline-block;padding:8px 16px;background:#03c1a2;color:#fff;text-decoration:none;border-radius:4px;">Ver a resposta</a></p><p style="color:#888;font-size:13px;">Podes cancelar a subscrição na página do tópico.</p>'
FROM `nf_email_templates` t WHERE t.`key`='forum.subscription_reply' AND NOT EXISTS (SELECT 1 FROM `nf_email_template_translations` x WHERE x.`template_id`=t.`template_id` AND x.`lang`='pt');

-- ============================ talks.new_message ============================
INSERT INTO `nf_email_template_translations` (`template_id`, `lang`, `subject`, `body`)
SELECT t.`template_id`, 'en', 'New message in: {{talk_name}}',
'<p>Hello <strong>{{username}}</strong>,</p><p>You received a new message from <strong>{{author}}</strong> in the conversation « {{talk_name}} » on <strong>{{site_name}}</strong>.</p><p><a href="{{talk_url}}" style="display:inline-block;padding:8px 16px;background:#03c1a2;color:#fff;text-decoration:none;border-radius:4px;">Read the message</a></p>'
FROM `nf_email_templates` t WHERE t.`key`='talks.new_message' AND NOT EXISTS (SELECT 1 FROM `nf_email_template_translations` x WHERE x.`template_id`=t.`template_id` AND x.`lang`='en');

INSERT INTO `nf_email_template_translations` (`template_id`, `lang`, `subject`, `body`)
SELECT t.`template_id`, 'de', 'Neue Nachricht in: {{talk_name}}',
'<p>Hallo <strong>{{username}}</strong>,</p><p>Du hast eine neue Nachricht von <strong>{{author}}</strong> in der Unterhaltung « {{talk_name}} » auf <strong>{{site_name}}</strong> erhalten.</p><p><a href="{{talk_url}}" style="display:inline-block;padding:8px 16px;background:#03c1a2;color:#fff;text-decoration:none;border-radius:4px;">Nachricht lesen</a></p>'
FROM `nf_email_templates` t WHERE t.`key`='talks.new_message' AND NOT EXISTS (SELECT 1 FROM `nf_email_template_translations` x WHERE x.`template_id`=t.`template_id` AND x.`lang`='de');

INSERT INTO `nf_email_template_translations` (`template_id`, `lang`, `subject`, `body`)
SELECT t.`template_id`, 'es', 'Nuevo mensaje en: {{talk_name}}',
'<p>Hola <strong>{{username}}</strong>,</p><p>Has recibido un nuevo mensaje de <strong>{{author}}</strong> en la conversación « {{talk_name}} » en <strong>{{site_name}}</strong>.</p><p><a href="{{talk_url}}" style="display:inline-block;padding:8px 16px;background:#03c1a2;color:#fff;text-decoration:none;border-radius:4px;">Leer el mensaje</a></p>'
FROM `nf_email_templates` t WHERE t.`key`='talks.new_message' AND NOT EXISTS (SELECT 1 FROM `nf_email_template_translations` x WHERE x.`template_id`=t.`template_id` AND x.`lang`='es');

INSERT INTO `nf_email_template_translations` (`template_id`, `lang`, `subject`, `body`)
SELECT t.`template_id`, 'it', 'Nuovo messaggio in: {{talk_name}}',
'<p>Ciao <strong>{{username}}</strong>,</p><p>Hai ricevuto un nuovo messaggio da <strong>{{author}}</strong> nella conversazione « {{talk_name}} » su <strong>{{site_name}}</strong>.</p><p><a href="{{talk_url}}" style="display:inline-block;padding:8px 16px;background:#03c1a2;color:#fff;text-decoration:none;border-radius:4px;">Leggi il messaggio</a></p>'
FROM `nf_email_templates` t WHERE t.`key`='talks.new_message' AND NOT EXISTS (SELECT 1 FROM `nf_email_template_translations` x WHERE x.`template_id`=t.`template_id` AND x.`lang`='it');

INSERT INTO `nf_email_template_translations` (`template_id`, `lang`, `subject`, `body`)
SELECT t.`template_id`, 'pt', 'Nova mensagem em: {{talk_name}}',
'<p>Olá <strong>{{username}}</strong>,</p><p>Recebeste uma nova mensagem de <strong>{{author}}</strong> na conversa « {{talk_name}} » em <strong>{{site_name}}</strong>.</p><p><a href="{{talk_url}}" style="display:inline-block;padding:8px 16px;background:#03c1a2;color:#fff;text-decoration:none;border-radius:4px;">Ler a mensagem</a></p>'
FROM `nf_email_templates` t WHERE t.`key`='talks.new_message' AND NOT EXISTS (SELECT 1 FROM `nf_email_template_translations` x WHERE x.`template_id`=t.`template_id` AND x.`lang`='pt');

-- ============================ moderation.sanction ============================
INSERT INTO `nf_email_template_translations` (`template_id`, `lang`, `subject`, `body`)
SELECT t.`template_id`, 'en', 'Moderation sanction on your account — {{site_name}}',
'<p>Hello <strong>{{username}}</strong>,</p><p>A sanction has been applied to your account on <strong>{{site_name}}</strong>.</p><table style="border-collapse:collapse;margin:12px 0;"><tr><td style="padding:6px 12px;background:#f5f5f5;font-weight:bold;">Type</td><td style="padding:6px 12px;">{{sanction_type}}</td></tr><tr><td style="padding:6px 12px;background:#f5f5f5;font-weight:bold;">Duration</td><td style="padding:6px 12px;">{{duration}}</td></tr><tr><td style="padding:6px 12px;background:#f5f5f5;font-weight:bold;">Reason</td><td style="padding:6px 12px;">{{reason}}</td></tr></table><p>If you believe this decision is unjustified, you can contact the moderation team via the contact page.</p>'
FROM `nf_email_templates` t WHERE t.`key`='moderation.sanction' AND NOT EXISTS (SELECT 1 FROM `nf_email_template_translations` x WHERE x.`template_id`=t.`template_id` AND x.`lang`='en');

INSERT INTO `nf_email_template_translations` (`template_id`, `lang`, `subject`, `body`)
SELECT t.`template_id`, 'de', 'Moderationsmaßnahme für dein Konto — {{site_name}}',
'<p>Hallo <strong>{{username}}</strong>,</p><p>Für dein Konto auf <strong>{{site_name}}</strong> wurde eine Maßnahme verhängt.</p><table style="border-collapse:collapse;margin:12px 0;"><tr><td style="padding:6px 12px;background:#f5f5f5;font-weight:bold;">Art</td><td style="padding:6px 12px;">{{sanction_type}}</td></tr><tr><td style="padding:6px 12px;background:#f5f5f5;font-weight:bold;">Dauer</td><td style="padding:6px 12px;">{{duration}}</td></tr><tr><td style="padding:6px 12px;background:#f5f5f5;font-weight:bold;">Grund</td><td style="padding:6px 12px;">{{reason}}</td></tr></table><p>Falls du der Meinung bist, dass diese Entscheidung ungerechtfertigt ist, kannst du das Moderationsteam über die Kontaktseite erreichen.</p>'
FROM `nf_email_templates` t WHERE t.`key`='moderation.sanction' AND NOT EXISTS (SELECT 1 FROM `nf_email_template_translations` x WHERE x.`template_id`=t.`template_id` AND x.`lang`='de');

INSERT INTO `nf_email_template_translations` (`template_id`, `lang`, `subject`, `body`)
SELECT t.`template_id`, 'es', 'Sanción de moderación en tu cuenta — {{site_name}}',
'<p>Hola <strong>{{username}}</strong>,</p><p>Se ha aplicado una sanción a tu cuenta en <strong>{{site_name}}</strong>.</p><table style="border-collapse:collapse;margin:12px 0;"><tr><td style="padding:6px 12px;background:#f5f5f5;font-weight:bold;">Tipo</td><td style="padding:6px 12px;">{{sanction_type}}</td></tr><tr><td style="padding:6px 12px;background:#f5f5f5;font-weight:bold;">Duración</td><td style="padding:6px 12px;">{{duration}}</td></tr><tr><td style="padding:6px 12px;background:#f5f5f5;font-weight:bold;">Motivo</td><td style="padding:6px 12px;">{{reason}}</td></tr></table><p>Si consideras que esta decisión es injustificada, puedes contactar con el equipo de moderación a través de la página de contacto.</p>'
FROM `nf_email_templates` t WHERE t.`key`='moderation.sanction' AND NOT EXISTS (SELECT 1 FROM `nf_email_template_translations` x WHERE x.`template_id`=t.`template_id` AND x.`lang`='es');

INSERT INTO `nf_email_template_translations` (`template_id`, `lang`, `subject`, `body`)
SELECT t.`template_id`, 'it', 'Sanzione di moderazione sul tuo account — {{site_name}}',
'<p>Ciao <strong>{{username}}</strong>,</p><p>È stata applicata una sanzione al tuo account su <strong>{{site_name}}</strong>.</p><table style="border-collapse:collapse;margin:12px 0;"><tr><td style="padding:6px 12px;background:#f5f5f5;font-weight:bold;">Tipo</td><td style="padding:6px 12px;">{{sanction_type}}</td></tr><tr><td style="padding:6px 12px;background:#f5f5f5;font-weight:bold;">Durata</td><td style="padding:6px 12px;">{{duration}}</td></tr><tr><td style="padding:6px 12px;background:#f5f5f5;font-weight:bold;">Motivo</td><td style="padding:6px 12px;">{{reason}}</td></tr></table><p>Se ritieni che questa decisione sia ingiustificata, puoi contattare il team di moderazione tramite la pagina dei contatti.</p>'
FROM `nf_email_templates` t WHERE t.`key`='moderation.sanction' AND NOT EXISTS (SELECT 1 FROM `nf_email_template_translations` x WHERE x.`template_id`=t.`template_id` AND x.`lang`='it');

INSERT INTO `nf_email_template_translations` (`template_id`, `lang`, `subject`, `body`)
SELECT t.`template_id`, 'pt', 'Sanção de moderação na tua conta — {{site_name}}',
'<p>Olá <strong>{{username}}</strong>,</p><p>Foi aplicada uma sanção à tua conta em <strong>{{site_name}}</strong>.</p><table style="border-collapse:collapse;margin:12px 0;"><tr><td style="padding:6px 12px;background:#f5f5f5;font-weight:bold;">Tipo</td><td style="padding:6px 12px;">{{sanction_type}}</td></tr><tr><td style="padding:6px 12px;background:#f5f5f5;font-weight:bold;">Duração</td><td style="padding:6px 12px;">{{duration}}</td></tr><tr><td style="padding:6px 12px;background:#f5f5f5;font-weight:bold;">Motivo</td><td style="padding:6px 12px;">{{reason}}</td></tr></table><p>Se consideras que esta decisão é injustificada, podes contactar a equipa de moderação através da página de contacto.</p>'
FROM `nf_email_templates` t WHERE t.`key`='moderation.sanction' AND NOT EXISTS (SELECT 1 FROM `nf_email_template_translations` x WHERE x.`template_id`=t.`template_id` AND x.`lang`='pt');

-- ============================ newsletter.confirmation ============================
INSERT INTO `nf_email_template_translations` (`template_id`, `lang`, `subject`, `body`)
SELECT t.`template_id`, 'en', 'Confirm your newsletter subscription — {{site_name}}',
'<p>Hi!</p><p>To complete your subscription to the <strong>{{site_name}}</strong> newsletter, click the button below:</p><p><a href="{{confirm_url}}" style="display:inline-block;padding:10px 20px;background:#03c1a2;color:#fff;text-decoration:none;border-radius:4px;">Confirm my subscription</a></p><p>If you did not request this subscription, simply ignore this email.</p>'
FROM `nf_email_templates` t WHERE t.`key`='newsletter.confirmation' AND NOT EXISTS (SELECT 1 FROM `nf_email_template_translations` x WHERE x.`template_id`=t.`template_id` AND x.`lang`='en');

INSERT INTO `nf_email_template_translations` (`template_id`, `lang`, `subject`, `body`)
SELECT t.`template_id`, 'de', 'Bestätige dein Newsletter-Abonnement — {{site_name}}',
'<p>Hallo!</p><p>Um dein Abonnement des Newsletters von <strong>{{site_name}}</strong> abzuschließen, klicke auf die Schaltfläche unten:</p><p><a href="{{confirm_url}}" style="display:inline-block;padding:10px 20px;background:#03c1a2;color:#fff;text-decoration:none;border-radius:4px;">Mein Abonnement bestätigen</a></p><p>Falls du dieses Abonnement nicht angefordert hast, ignoriere diese E-Mail einfach.</p>'
FROM `nf_email_templates` t WHERE t.`key`='newsletter.confirmation' AND NOT EXISTS (SELECT 1 FROM `nf_email_template_translations` x WHERE x.`template_id`=t.`template_id` AND x.`lang`='de');

INSERT INTO `nf_email_template_translations` (`template_id`, `lang`, `subject`, `body`)
SELECT t.`template_id`, 'es', 'Confirma tu suscripción a la newsletter — {{site_name}}',
'<p>¡Hola!</p><p>Para completar tu suscripción a la newsletter de <strong>{{site_name}}</strong>, haz clic en el botón de abajo:</p><p><a href="{{confirm_url}}" style="display:inline-block;padding:10px 20px;background:#03c1a2;color:#fff;text-decoration:none;border-radius:4px;">Confirmar mi suscripción</a></p><p>Si no has solicitado esta suscripción, simplemente ignora este correo.</p>'
FROM `nf_email_templates` t WHERE t.`key`='newsletter.confirmation' AND NOT EXISTS (SELECT 1 FROM `nf_email_template_translations` x WHERE x.`template_id`=t.`template_id` AND x.`lang`='es');

INSERT INTO `nf_email_template_translations` (`template_id`, `lang`, `subject`, `body`)
SELECT t.`template_id`, 'it', 'Conferma la tua iscrizione alla newsletter — {{site_name}}',
'<p>Ciao!</p><p>Per completare la tua iscrizione alla newsletter di <strong>{{site_name}}</strong>, clicca sul pulsante qui sotto:</p><p><a href="{{confirm_url}}" style="display:inline-block;padding:10px 20px;background:#03c1a2;color:#fff;text-decoration:none;border-radius:4px;">Conferma la mia iscrizione</a></p><p>Se non sei stato tu a richiedere questa iscrizione, ignora semplicemente questa email.</p>'
FROM `nf_email_templates` t WHERE t.`key`='newsletter.confirmation' AND NOT EXISTS (SELECT 1 FROM `nf_email_template_translations` x WHERE x.`template_id`=t.`template_id` AND x.`lang`='it');

INSERT INTO `nf_email_template_translations` (`template_id`, `lang`, `subject`, `body`)
SELECT t.`template_id`, 'pt', 'Confirma a tua subscrição da newsletter — {{site_name}}',
'<p>Olá!</p><p>Para concluir a tua subscrição da newsletter de <strong>{{site_name}}</strong>, clica no botão abaixo:</p><p><a href="{{confirm_url}}" style="display:inline-block;padding:10px 20px;background:#03c1a2;color:#fff;text-decoration:none;border-radius:4px;">Confirmar a minha subscrição</a></p><p>Se não foste tu a fazer esta subscrição, ignora simplesmente este email.</p>'
FROM `nf_email_templates` t WHERE t.`key`='newsletter.confirmation' AND NOT EXISTS (SELECT 1 FROM `nf_email_template_translations` x WHERE x.`template_id`=t.`template_id` AND x.`lang`='pt');
