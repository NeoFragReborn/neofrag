-- NeoFrag Reborn — instantané du site de DÉMO (config affichage + membres + contenu).
-- Généré par tools/dump-demo.php. Rechargé par l'auto-reset démo. NE PAS éditer à la main.
-- Ne touche pas le compte admin ni les secrets (verrouillés en mode démo).

SET FOREIGN_KEY_CHECKS = 0;
SET NAMES utf8mb4;
START TRANSACTION;

-- Config démo (idempotent)
UPDATE `nf_settings` SET `value` = 'nebula' WHERE `name` = 'nf_default_theme';
DELETE FROM `nf_addon` WHERE `name` = 'vitrine' AND `type_id` = 2;
DELETE FROM `nf_dispositions` WHERE `theme` = 'vitrine';
DELETE FROM `nf_addon` WHERE `name` = 'landing' AND `type_id` = 3;
DELETE FROM `nf_widgets` WHERE `widget` = 'landing';
DELETE FROM `nf_role_permissions` WHERE `role_id` = 3 AND `permission` = 'forum.category_read';
INSERT INTO `nf_role_permissions` (`role_id`, `permission`, `scope_id`, `authorized`) VALUES (3, 'forum.category_read', 0, 'allow');
DELETE FROM `nf_role_permissions` WHERE `role_id` = 3 AND `permission` = 'gallery.gallery_see';
INSERT INTO `nf_role_permissions` (`role_id`, `permission`, `scope_id`, `authorized`) VALUES (3, 'gallery.gallery_see', 0, 'allow');
DELETE FROM `nf_role_permissions` WHERE `role_id` = 3 AND `permission` = 'pages.access_page';
INSERT INTO `nf_role_permissions` (`role_id`, `permission`, `scope_id`, `authorized`) VALUES (3, 'pages.access_page', 0, 'allow');
DELETE FROM `nf_role_permissions` WHERE `role_id` = 3 AND `permission` = 'events.access_events_type';
INSERT INTO `nf_role_permissions` (`role_id`, `permission`, `scope_id`, `authorized`) VALUES (3, 'events.access_events_type', 0, 'allow');

-- Réglages du site (les réglages sensibles sont exclus, cf. tools/dump-demo.php)
INSERT INTO `nf_settings` (`name`, `site`, `lang`, `value`, `type`) VALUES
('blockcraft_background', '', '', '0', 'int'),
('blockcraft_background_attachment', '', '', 'scroll', 'string'),
('blockcraft_background_color', '', '', '#eef3f7', 'string'),
('blockcraft_background_position', '', '', 'center top', 'string'),
('blockcraft_background_repeat', '', '', 'repeat', 'string'),
('blockcraft_header', '', '', '0', 'int'),
('blockcraft_header_attachment', '', '', 'scroll', 'string'),
('blockcraft_header_color', '', '', '#6aa84f', 'string'),
('blockcraft_header_position', '', '', 'center top', 'string'),
('blockcraft_header_repeat', '', '', 'no-repeat', 'string'),
('blockcraft_logo', '', '', '0', 'int'),
('blockcraft_navbar_display', '', '', '0', 'bool'),
('blockcraft_social_discord', '', '', '', 'string'),
('blockcraft_social_facebook', '', '', '', 'string'),
('blockcraft_social_github', '', '', '', 'string'),
('blockcraft_social_instagram', '', '', '', 'string'),
('blockcraft_social_tiktok', '', '', '', 'string'),
('blockcraft_social_twitch', '', '', '', 'string'),
('blockcraft_social_twitter', '', '', '', 'string'),
('blockcraft_social_youtube', '', '', '', 'string'),
('blockcraft_text_color', '', '', '#2e2a25', 'string'),
('blockcraft_theme_color', '', '', '#6aa84f', 'string'),
('events_alert_mp', '', '', '1', 'string'),
('events_per_page', '', '', '10', 'int'),
('extend_background', '', '', '0', 'int'),
('extend_background_attachment', '', '', 'scroll', 'string'),
('extend_background_color', '', '', '#11171a', 'string'),
('extend_background_position', '', '', 'center top', 'string'),
('extend_background_repeat', '', '', 'repeat', 'string'),
('extend_header', '', '', '0', 'int'),
('extend_header_attachment', '', '', 'scroll', 'string'),
('extend_header_color', '', '', '#236daf', 'string'),
('extend_header_position', '', '', 'center top', 'string'),
('extend_header_repeat', '', '', 'no-repeat', 'string'),
('extend_logo', '', '', '0', 'int'),
('extend_navbar_display', '', '', '0', 'bool'),
('extend_text_color', '', '', '#c3cdd6', 'string'),
('extend_theme_color', '', '', '#236daf', 'string'),
('forge_background', '', '', '0', 'int'),
('forge_background_attachment', '', '', 'scroll', 'string'),
('forge_background_color', '', '', '#16100e', 'string'),
('forge_background_position', '', '', 'center top', 'string'),
('forge_background_repeat', '', '', 'repeat', 'string'),
('forge_header', '', '', '0', 'int'),
('forge_header_attachment', '', '', 'scroll', 'string'),
('forge_header_color', '', '', '#e2502b', 'string'),
('forge_header_position', '', '', 'center top', 'string'),
('forge_header_repeat', '', '', 'no-repeat', 'string'),
('forge_logo', '', '', '0', 'int'),
('forge_navbar_display', '', '', '0', 'bool'),
('forge_text_color', '', '', '#e8d8d0', 'string'),
('forge_theme_color', '', '', '#e2502b', 'string'),
('forum_messages_per_page', '', '', '15', 'int'),
('forum_topics_per_page', '', '', '20', 'int'),
('granite_background', '', '', '0', 'int'),
('granite_background_attachment', '', '', 'scroll', 'string'),
('granite_background_color', '', '', '#f3f5f7', 'string'),
('granite_background_position', '', '', 'center top', 'string'),
('granite_background_repeat', '', '', 'repeat', 'string'),
('granite_header', '', '', '0', 'int'),
('granite_header_attachment', '', '', 'scroll', 'string'),
('granite_header_color', '', '', '#0e7c86', 'string'),
('granite_header_position', '', '', 'center top', 'string'),
('granite_header_repeat', '', '', 'no-repeat', 'string'),
('granite_logo', '', '', '0', 'int'),
('granite_navbar_display', '', '', '0', 'bool'),
('granite_social_discord', '', '', '', 'string'),
('granite_social_facebook', '', '', '', 'string'),
('granite_social_github', '', '', '', 'string'),
('granite_social_instagram', '', '', '', 'string'),
('granite_social_tiktok', '', '', '', 'string'),
('granite_social_twitch', '', '', '', 'string'),
('granite_social_twitter', '', '', '', 'string'),
('granite_social_youtube', '', '', '', 'string'),
('granite_text_color', '', '', '#1f2933', 'string'),
('granite_theme_color', '', '', '#0e7c86', 'string'),
('images_per_page', '', '', '24', 'int'),
('news_per_page', '', '', '5', 'int'),
('nf_analytics', '', '', '', 'string'),
('nf_captcha_private_key', '', '', '', 'string'),
('nf_captcha_public_key', '', '', '', 'string'),
('nf_contact', '', '', 'noreply@neofrag.com', 'string'),
('nf_cookie_expire', '', '', '1 hour', 'string'),
('nf_cookie_name', '', '', 'session', 'string'),
('nf_copyright', '', '', 'Copyright {copyright} {year} {name}, tous droits r&eacute;serv&eacute;s &lt;div class=&quot;float-end&quot;&gt;Propuls&eacute; par {neofrag}&lt;/div&gt;', 'string'),
('nf_session_history_days', '', '', '395', 'int'),
('nf_default_page', '', '', 'news', 'string'),
('nf_default_theme', '', '', 'nebula', 'string'),
('nf_description', '', '', 'NeoFrag Reborn', 'string'),
('nf_email_transport', '', '', 'none:1780867039', 'string'),
('nf_favicon', '', '', NULL, 'int'),
('nf_http_authentication', '', '', '0', 'bool'),
('nf_http_authentication_name', '', '', '', 'string'),
('nf_humans_txt', '', '', '/* TEAM */\r\n	NeoFrag CMS for gamers\r\n	Contact: contact [at] neofrag.fr\r\n	Twitter: @NeoFragCMS\r\n	From: France\r\n\r\n	Developper: Micha&euml;l BILCOT\r\n	Contact: michael.bilcot [at] neofrag.fr\r\n	Twitter: @NeoFragCMS\r\n	From: Paris, France\r\n\r\n	Designer: J&eacute;r&eacute;my VALENTIN\r\n	Contact: jeremy.valentin [at] neofrag.fr\r\n	Twitter: @NeoFragCMS\r\n	From: Caen, France', 'string'),
('nf_maintenance', '', '', '0', 'bool'),
('nf_maintenance_background', '', '', '0', 'int'),
('nf_maintenance_background_color', '', '', '', 'string'),
('nf_maintenance_background_position', '', '', '', 'string'),
('nf_maintenance_background_repeat', '', '', '', 'string'),
('nf_maintenance_content', '', '', '', 'string'),
('nf_maintenance_logo', '', '', '0', 'int'),
('nf_maintenance_opening', '', '', '', 'string'),
('nf_maintenance_text_color', '', '', '', 'string'),
('nf_maintenance_title', '', '', '', 'string'),
('nf_moderation_auto_escalation', '', '', '1', 'string'),
('nf_moderation_default_ban_temp_duration_seconds', '', '', '604800', 'string'),
('nf_moderation_default_mute_duration_seconds', '', '', '86400', 'string'),
('nf_moderation_enabled', '', '', '1', 'string'),
('nf_moderation_preserve_content_snapshot', '', '', '1', 'string'),
('nf_moderation_report_flag_threshold_per_day', '', '', '20', 'string'),
('nf_moderation_report_rate_limit_per_hour', '', '', '5', 'string'),
('nf_moderation_require_approval_ban_perm', '', '', '1', 'string'),
('nf_moderation_require_approval_ban_temp', '', '', '1', 'string'),
('nf_moderation_snapshot_attachments_enabled', '', '', '1', 'string'),
('nf_moderation_snapshot_max_size_mb', '', '', '50', 'string'),
('nf_moderation_warning_threshold_ban', '', '', '5', 'string'),
('nf_moderation_warning_threshold_mute', '', '', '3', 'string'),
('nf_moderation_warning_window_days', '', '', '30', 'string'),
('nf_monitoring_last_check', '', '', '1781308707', 'int'),
('nf_name', '', '', 'NeoFrag Reborn', 'string'),
('nf_registration_charte', '', '', '', 'string'),
('nf_registration_status', '', '', '0', 'int'),
('nf_robots_txt', '', '', 'User-agent: *\r\nDisallow:', 'string'),
('nf_social_behance', '', '', '', 'string'),
('nf_social_deviantart', '', '', '', 'string'),
('nf_social_dribble', '', '', '', 'string'),
('nf_social_facebook', '', '', '', 'string'),
('nf_social_flickr', '', '', '', 'string'),
('nf_social_github', '', '', '', 'string'),
('nf_social_google', '', '', '', 'string'),
('nf_social_instagram', '', '', '', 'string'),
('nf_social_steam', '', '', '', 'string'),
('nf_social_twitch', '', '', '', 'string'),
('nf_social_twitter', '', '', '', 'string'),
('nf_social_youtube', '', '', '', 'string'),
('nf_team_biographie', '', '', 'Communauté de joueurs fondée autour des jeux compétitifs. On y trouve des équipes CS2 et Valorant, un forum actif, des tournois maison et une galerie de nos meilleurs moments. Cette démonstration est réinitialisée régulièrement.', 'string'),
('nf_team_creation', '', '', '1705276800', 'string'),
('nf_team_logo', '', '', NULL, 'int'),
('nf_team_name', '', '', 'NeoFrag Reborn', 'string'),
('nf_team_type', '', '', 'Communauté gaming', 'string'),
('nf_theme_color', '', '', '#2b373a', 'string'),
('nf_theme_epoch', '', '', '4', 'int'),
('nf_update_callback', '', '', '', 'string'),
('nf_version_css', '', '', '1789561426', 'int'),
('nf_welcome', '', '', '0', 'bool'),
('nf_welcome_content', '', '', '', 'string'),
('nf_welcome_title', '', '', '', 'string'),
('nf_welcome_user_id', '', '', '', 'int'),
('partners_logo_display', '', '', 'logo_dark', 'string'),
('recruits_alert', '', '', '1', 'bool'),
('recruits_hide_unavailable', '', '', '1', 'bool'),
('recruits_per_page', '', '', '5', 'int'),
('recruits_send_mail', '', '', '1', 'bool'),
('recruits_send_mp', '', '', '1', 'bool')
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`);

-- Membres démo — remis en état (upsert), jamais supprimés : une session vivante
-- s'appuie sur la ligne `nf_user`, et la retirer bloquait le site 30 s (cf. tools/dump-demo.php).
DELETE FROM `nf_user_profile` WHERE `id` NOT IN (1, 271, 329, 330, 331, 332, 333, 334, 335, 336, 337);
DELETE FROM `nf_user` WHERE `id` NOT IN (1, 271, 329, 330, 331, 332, 333, 334, 335, 336, 337);
DELETE FROM `nf_session` WHERE `user_id` IS NOT NULL AND `user_id` NOT IN (1, 271, 329, 330, 331, 332, 333, 334, 335, 336, 337);
DELETE FROM `nf_user_points`;
DELETE FROM `nf_karma`;
INSERT INTO `nf_user` (`id`, `username`, `password`, `salt`, `email`, `registration_date`, `last_activity_date`, `admin`, `language`, `data`, `deleted`, `totp_secret`, `totp_enabled`) VALUES
('271', 'demo', '$argon2id$v=19$m=65536,t=4,p=1$Q3RPYXZlN0VkNTh1QmpWRg$NV4Qs4Z9PCDbMVZjuDIz/2JtXgeaNNb9jPjRgQHLn00', '', 'demo@demo.local', '2026-07-18 00:13:40', '2026-09-16 11:40:41', '1', NULL, '', '0', NULL, '0'),
('329', 'ShadowFox', '$argon2id$v=19$m=65536,t=4,p=1$V1J5ZEdKekRtdjVMNEYuYw$33xbenfEEv8jJYjSqQ//kAwspjLzRZzBS/fjqGYoW7k', '', 'shadowfox@demo.local', '2026-07-18 11:46:28', '2026-09-16 11:46:28', '0', NULL, '', '0', NULL, '0'),
('330', 'NovaStrike', '$argon2id$v=19$m=65536,t=4,p=1$V1J5ZEdKekRtdjVMNEYuYw$33xbenfEEv8jJYjSqQ//kAwspjLzRZzBS/fjqGYoW7k', '', 'novastrike@demo.local', '2026-07-22 11:46:28', '2026-09-16 10:46:28', '0', NULL, '', '0', NULL, '0'),
('331', 'VortexQc', '$argon2id$v=19$m=65536,t=4,p=1$V1J5ZEdKekRtdjVMNEYuYw$33xbenfEEv8jJYjSqQ//kAwspjLzRZzBS/fjqGYoW7k', '', 'vortexqc@demo.local', '2026-07-26 11:46:28', '2026-09-16 09:46:28', '0', NULL, '', '0', NULL, '0'),
('332', 'LunaByte', '$argon2id$v=19$m=65536,t=4,p=1$V1J5ZEdKekRtdjVMNEYuYw$33xbenfEEv8jJYjSqQ//kAwspjLzRZzBS/fjqGYoW7k', '', 'lunabyte@demo.local', '2026-07-30 11:46:28', '2026-09-16 08:46:28', '0', NULL, '', '0', NULL, '0'),
('333', 'Rekkles_FR', '$argon2id$v=19$m=65536,t=4,p=1$V1J5ZEdKekRtdjVMNEYuYw$33xbenfEEv8jJYjSqQ//kAwspjLzRZzBS/fjqGYoW7k', '', 'rekkles_fr@demo.local', '2026-08-03 11:46:28', '2026-09-16 07:46:28', '0', NULL, '', '0', NULL, '0'),
('334', 'PixelWitch', '$argon2id$v=19$m=65536,t=4,p=1$V1J5ZEdKekRtdjVMNEYuYw$33xbenfEEv8jJYjSqQ//kAwspjLzRZzBS/fjqGYoW7k', '', 'pixelwitch@demo.local', '2026-08-07 11:46:28', '2026-09-16 06:46:28', '0', NULL, '', '0', NULL, '0'),
('335', 'Zenith', '$argon2id$v=19$m=65536,t=4,p=1$V1J5ZEdKekRtdjVMNEYuYw$33xbenfEEv8jJYjSqQ//kAwspjLzRZzBS/fjqGYoW7k', '', 'zenith@demo.local', '2026-08-11 11:46:28', '2026-09-16 05:46:28', '0', NULL, '', '0', NULL, '0'),
('336', 'Kira', '$argon2id$v=19$m=65536,t=4,p=1$V1J5ZEdKekRtdjVMNEYuYw$33xbenfEEv8jJYjSqQ//kAwspjLzRZzBS/fjqGYoW7k', '', 'kira@demo.local', '2026-08-15 11:46:28', '2026-09-16 04:46:28', '0', NULL, '', '0', NULL, '0'),
('337', 'OldSchool', '$argon2id$v=19$m=65536,t=4,p=1$V1J5ZEdKekRtdjVMNEYuYw$33xbenfEEv8jJYjSqQ//kAwspjLzRZzBS/fjqGYoW7k', '', 'oldschool@demo.local', '2026-08-19 11:46:28', '2026-09-16 03:46:28', '0', NULL, '', '0', NULL, '0')
ON DUPLICATE KEY UPDATE `username` = VALUES(`username`), `password` = VALUES(`password`), `salt` = VALUES(`salt`), `email` = VALUES(`email`), `registration_date` = VALUES(`registration_date`), `last_activity_date` = VALUES(`last_activity_date`), `admin` = VALUES(`admin`), `language` = VALUES(`language`), `data` = VALUES(`data`), `deleted` = VALUES(`deleted`), `totp_secret` = VALUES(`totp_secret`), `totp_enabled` = VALUES(`totp_enabled`);
INSERT INTO `nf_user_profile` (`id`, `first_name`, `last_name`, `avatar`, `cover`, `signature`, `date_of_birth`, `sex`, `country`, `timezone`, `location`, `quote`, `website`, `linkedin`, `github`, `instagram`, `twitch`) VALUES
('271', 'Alex', 'Martin', NULL, NULL, '', NULL, 'male', 'fr', 'Europe/Paris', '', 'Ici pour tester NeoFrag Reborn !', '', '', '', '', ''),
('329', 'Lucas', 'Bernard', NULL, NULL, '', NULL, 'male', 'fr', 'Europe/Paris', '', 'GG WP à tous.', '', '', '', '', ''),
('330', 'Emma', 'Dubois', NULL, NULL, '', NULL, 'female', 'be', 'Europe/Paris', '', 'La précision avant tout.', '', '', '', '', ''),
('331', 'Hugo', 'Lefebvre', NULL, NULL, '', NULL, 'male', 'ca', 'Europe/Paris', '', 'Aim first, think later.', '', '', '', '', ''),
('332', 'Chloé', 'Moreau', NULL, NULL, '', NULL, 'female', 'ch', 'Europe/Paris', '', 'Support main, toujours là.', '', '', '', '', ''),
('333', 'Nathan', 'Garcia', NULL, NULL, '', NULL, 'male', 'fr', 'Europe/Paris', '', 'On grind la ranked.', '', '', '', '', ''),
('334', 'Léa', 'Roux', NULL, NULL, '', NULL, 'female', 'fr', 'Europe/Paris', '', 'Montage & highlights.', '', '', '', '', ''),
('335', 'Théo', 'Fournier', NULL, NULL, '', NULL, 'male', 'fr', 'Europe/Paris', '', 'IGL de la team.', '', '', '', '', ''),
('336', 'Manon', 'Girard', NULL, NULL, '', NULL, 'female', 'fr', 'Europe/Paris', '', 'Clutch or kick.', '', '', '', '', ''),
('337', 'Pierre', 'Lambert', NULL, NULL, '', NULL, 'male', 'fr', 'Europe/Paris', '', 'Je joue depuis Quake.', '', '', '', '', '')
ON DUPLICATE KEY UPDATE `first_name` = VALUES(`first_name`), `last_name` = VALUES(`last_name`), `avatar` = VALUES(`avatar`), `cover` = VALUES(`cover`), `signature` = VALUES(`signature`), `date_of_birth` = VALUES(`date_of_birth`), `sex` = VALUES(`sex`), `country` = VALUES(`country`), `timezone` = VALUES(`timezone`), `location` = VALUES(`location`), `quote` = VALUES(`quote`), `website` = VALUES(`website`), `linkedin` = VALUES(`linkedin`), `github` = VALUES(`github`), `instagram` = VALUES(`instagram`), `twitch` = VALUES(`twitch`);
INSERT INTO `nf_user_points` (`user_id`, `total`, `earned`, `spent`, `updated_at`) VALUES
('329', '50', '60', '10', '2026-09-16 11:46:28'),
('330', '85', '95', '10', '2026-09-16 11:46:28'),
('331', '120', '130', '10', '2026-09-16 11:46:28'),
('332', '155', '165', '10', '2026-09-16 11:46:28'),
('333', '190', '200', '10', '2026-09-16 11:46:28'),
('334', '225', '235', '10', '2026-09-16 11:46:28'),
('335', '260', '270', '10', '2026-09-16 11:46:28'),
('336', '295', '305', '10', '2026-09-16 11:46:28'),
('337', '330', '340', '10', '2026-09-16 11:46:28');
INSERT INTO `nf_karma` (`user_id`, `score`, `reactions_received`, `content_count`, `updated_at`) VALUES
('329', '0', '0', '0', '2026-09-16 11:46:28'),
('330', '7', '3', '1', '2026-09-16 11:46:28'),
('331', '14', '6', '2', '2026-09-16 11:46:28'),
('332', '21', '9', '3', '2026-09-16 11:46:28'),
('333', '28', '12', '4', '2026-09-16 11:46:28'),
('334', '35', '15', '5', '2026-09-16 11:46:28'),
('335', '42', '18', '6', '2026-09-16 11:46:28'),
('336', '49', '21', '7', '2026-09-16 11:46:28'),
('337', '56', '24', '8', '2026-09-16 11:46:28');

-- Contenu
DELETE FROM `nf_news_categories`;
INSERT INTO `nf_news_categories` (`category_id`, `image_id`, `icon_id`, `name`) VALUES
('1', NULL, NULL, 'annonces'),
('2', NULL, NULL, 'competition'),
('3', NULL, NULL, 'communaute');
DELETE FROM `nf_news_categories_lang`;
INSERT INTO `nf_news_categories_lang` (`category_id`, `lang`, `title`) VALUES
('1', 'fr', 'Annonces'),
('2', 'fr', 'Compétition'),
('3', 'fr', 'Communauté');
DELETE FROM `nf_news`;
INSERT INTO `nf_news` (`news_id`, `category_id`, `user_id`, `image_id`, `date`, `published`, `announced_at`, `views`, `vote`, `deleted_at`, `deleted_by`) VALUES
('1', '1', '271', NULL, '2026-08-27 11:46:28', '1', '2026-08-27 11:46:28', '30', '1', NULL, NULL),
('2', '2', '329', NULL, '2026-08-30 11:46:28', '1', '2026-08-30 11:46:28', '47', '1', NULL, NULL),
('3', '3', '330', NULL, '2026-09-02 11:46:28', '1', '2026-09-02 11:46:28', '64', '1', NULL, NULL),
('4', '1', '331', NULL, '2026-09-05 11:46:28', '1', '2026-09-05 11:46:28', '81', '1', NULL, NULL),
('5', '2', '332', NULL, '2026-09-08 11:46:28', '1', '2026-09-08 11:46:28', '98', '1', NULL, NULL),
('6', '3', '333', NULL, '2026-09-11 11:46:28', '1', '2026-09-11 11:46:28', '115', '1', NULL, NULL);
DELETE FROM `nf_news_lang`;
INSERT INTO `nf_news_lang` (`news_id`, `lang`, `title`, `introduction`, `content`, `tags`) VALUES
('1', 'fr', 'Bienvenue sur NeoFrag Reborn', 'Le nouveau site de la communauté est en ligne !', '<p>Nous sommes ravis de vous accueillir sur notre nouvelle plateforme propulsée par <strong>NeoFrag Reborn</strong>. Forum, actualités, galeries, tournois : tout y est. Inscrivez-vous et rejoignez l\'aventure !</p>', ''),
('2', 'fr', 'Victoire en finale du tournoi régional', 'Notre équipe principale s\'impose 3-1 en grande finale.', '<p>Après un parcours sans faute, nos joueurs ont décroché le trophée du tournoi régional. Félicitations à toute l\'équipe pour cette performance !</p><p>Prochain objectif : les qualifications nationales.</p>', ''),
('3', 'fr', 'Soirée communautaire ce vendredi', 'Rejoignez-nous pour une soirée détente sur le serveur.', '<p>Ce vendredi à 21h, on se retrouve tous pour des parties fun et conviviales. Débutants bienvenus !</p>', ''),
('4', 'fr', 'Nouveau partenariat matériel', 'Des réductions exclusives pour nos membres.', '<p>Grâce à notre nouveau partenaire, profitez de réductions sur le matériel gaming. Détails dans l\'espace membre.</p>', ''),
('5', 'fr', 'Calendrier des matchs du mois', 'Tous les rendez-vous compétitifs à ne pas manquer.', '<p>Le calendrier des prochains matchs est disponible. Venez supporter vos équipes !</p>', ''),
('6', 'fr', 'Concours de montage vidéo', 'Montrez vos plus beaux highlights et gagnez des points.', '<p>Participez à notre concours de montage : les meilleures vidéos seront récompensées en points boutique.</p>', '');
DELETE FROM `nf_articles_categories`;
INSERT INTO `nf_articles_categories` (`category_id`, `image_id`, `icon_id`, `name`) VALUES
('1', NULL, NULL, 'guides'),
('2', NULL, NULL, 'tests');
DELETE FROM `nf_articles_categories_lang`;
INSERT INTO `nf_articles_categories_lang` (`category_id`, `lang`, `title`) VALUES
('1', 'fr', 'Guides'),
('2', 'fr', 'Tests');
DELETE FROM `nf_articles`;
INSERT INTO `nf_articles` (`article_id`, `category_id`, `user_id`, `image_id`, `date`, `published`, `announced_at`, `views`, `deleted_at`, `deleted_by`) VALUES
('1', '1', '331', NULL, '2026-09-01 11:46:28', '1', '2026-09-01 11:46:28', '40', NULL, NULL),
('2', '1', '332', NULL, '2026-09-04 11:46:28', '1', '2026-09-04 11:46:28', '62', NULL, NULL),
('3', '2', '333', NULL, '2026-09-07 11:46:28', '1', '2026-09-07 11:46:28', '84', NULL, NULL),
('4', '2', '334', NULL, '2026-09-10 11:46:28', '1', '2026-09-10 11:46:28', '106', NULL, NULL);
DELETE FROM `nf_articles_lang`;
INSERT INTO `nf_articles_lang` (`article_id`, `lang`, `title`, `excerpt`, `content`, `tags`) VALUES
('1', 'fr', 'Bien débuter en compétitif', 'Nos conseils pour progresser rapidement.', '<h2>Les bases</h2><p>Maîtrisez d\'abord votre visée et votre placement. La régularité prime sur les coups d\'éclat.</p><h2>L\'esprit d\'équipe</h2><p>La communication est la clé de la victoire.</p>', ''),
('2', 'fr', 'Optimiser sa configuration', 'Réglages et matériel pour un setup au top.', '<p>Un bon setup ne fait pas tout, mais il aide. Voici nos recommandations réglages et périphériques.</p>', ''),
('3', 'fr', 'Notre avis sur le dernier patch', 'Ce qui change pour la méta compétitive.', '<p>Le dernier patch rebat les cartes. Analyse des nerfs, buffs et de leur impact sur la méta.</p>', ''),
('4', 'fr', 'Casque gaming : le comparatif', 'On a testé pour vous les modèles du moment.', '<p>Confort, son, micro : notre comparatif complet pour choisir le bon casque.</p>', '');
DELETE FROM `nf_forum_categories`;
INSERT INTO `nf_forum_categories` (`category_id`, `title`, `order`, `image_id`, `vip_only`) VALUES
('1', 'Communauté', '0', NULL, '0'),
('2', 'Jeux & Compétition', '1', NULL, '0');
DELETE FROM `nf_forum`;
INSERT INTO `nf_forum` (`forum_id`, `parent_id`, `is_subforum`, `title`, `description`, `order`, `count_topics`, `count_messages`, `last_message_id`) VALUES
('1', '1', '0', 'Présentations', 'Présentez-vous à la communauté', '0', '2', '6', '6'),
('2', '1', '0', 'Discussions générales', 'Pour parler de tout et de rien', '1', '1', '3', '9'),
('3', '2', '0', 'Stratégies', 'Partagez vos tactiques', '0', '1', '4', '13'),
('4', '2', '0', 'Recherche d\'équipe', 'Trouvez des coéquipiers', '1', '1', '2', '15');
DELETE FROM `nf_forum_url`;
INSERT INTO `nf_forum_url` (`forum_id`, `url`, `redirects`) VALUES
('1', '', '0'),
('2', '', '0'),
('3', '', '0'),
('4', '', '0');
DELETE FROM `nf_forum_topics`;
INSERT INTO `nf_forum_topics` (`topic_id`, `forum_id`, `message_id`, `title`, `status`, `views`, `count_messages`, `last_message_id`, `is_announced`, `is_locked`) VALUES
('1', '1', '1', 'Salut tout le monde !', '0', '10', '4', '4', '0', '0'),
('2', '1', '5', 'Présentation rapide', '0', '19', '2', '6', '0', '0'),
('3', '2', '7', 'Votre setup du moment ?', '0', '28', '3', '9', '0', '0'),
('4', '3', '10', 'Gérer la pression en finale', '0', '37', '4', '13', '0', '0'),
('5', '4', '14', 'Cherche support pour ranked', '0', '46', '2', '15', '0', '0');
DELETE FROM `nf_forum_messages`;
INSERT INTO `nf_forum_messages` (`message_id`, `topic_id`, `parent_id`, `user_id`, `message`, `date`, `deleted_at`, `deleted_by`, `deleted_reason`) VALUES
('1', '1', NULL, '271', 'Nouveau ici, hâte de jouer avec vous.', '2026-09-04 11:46:28', NULL, NULL, NULL),
('2', '1', NULL, '329', 'Bienvenue à toi !', '2026-09-04 12:46:28', NULL, NULL, NULL),
('3', '1', NULL, '330', 'Salut, on se voit en jeu !', '2026-09-04 13:46:28', NULL, NULL, NULL),
('4', '1', NULL, '331', 'Welcome !', '2026-09-04 14:46:28', NULL, NULL, NULL),
('5', '2', NULL, '329', 'Joueur depuis des années, ravi de rejoindre.', '2026-09-05 11:46:28', NULL, NULL, NULL),
('6', '2', NULL, '330', 'Bienvenue parmi nous !', '2026-09-05 12:46:28', NULL, NULL, NULL),
('7', '3', NULL, '330', 'Montrez vos installations !', '2026-09-06 11:46:28', NULL, NULL, NULL),
('8', '3', NULL, '331', 'Clavier méca + souris légère, le combo.', '2026-09-06 12:46:28', NULL, NULL, NULL),
('9', '3', NULL, '332', 'Double écran obligatoire pour moi.', '2026-09-06 13:46:28', NULL, NULL, NULL),
('10', '4', NULL, '331', 'Comment vous restez calmes dans les moments clés ?', '2026-09-07 11:46:28', NULL, NULL, NULL),
('11', '4', NULL, '332', 'Respiration et routine avant match.', '2026-09-07 12:46:28', NULL, NULL, NULL),
('12', '4', NULL, '333', 'On parle peu mais on parle utile.', '2026-09-07 13:46:28', NULL, NULL, NULL),
('13', '4', NULL, '334', 'Le mental, c\'est 50% du jeu.', '2026-09-07 14:46:28', NULL, NULL, NULL),
('14', '5', NULL, '332', 'Niveau diamant, dispo le soir.', '2026-09-08 11:46:28', NULL, NULL, NULL),
('15', '5', NULL, '333', 'Intéressé, je t\'ajoute !', '2026-09-08 12:46:28', NULL, NULL, NULL);
DELETE FROM `nf_gallery_categories`;
INSERT INTO `nf_gallery_categories` (`category_id`, `image_id`, `icon_id`, `name`) VALUES
('1', NULL, NULL, 'evenements'),
('2', NULL, NULL, 'highlights');
DELETE FROM `nf_gallery_categories_lang`;
INSERT INTO `nf_gallery_categories_lang` (`category_id`, `lang`, `title`) VALUES
('1', 'fr', 'Événements'),
('2', 'fr', 'Highlights');
DELETE FROM `nf_gallery`;
INSERT INTO `nf_gallery` (`gallery_id`, `category_id`, `image_id`, `name`, `published`, `date`, `deleted_at`, `deleted_by`) VALUES
('1', '1', '1', 'lan-d-ete-2025', '1', '2026-09-06 11:46:28', NULL, NULL),
('2', '1', '13', 'finale-regionale', '1', '2026-09-08 11:46:28', NULL, NULL),
('3', '2', '22', 'best-of-du-mois', '1', '2026-09-10 11:46:29', NULL, NULL);
DELETE FROM `nf_gallery_lang`;
INSERT INTO `nf_gallery_lang` (`gallery_id`, `lang`, `title`, `description`) VALUES
('1', 'fr', 'LAN d\'été 2025', 'Les meilleurs moments de notre LAN annuelle.'),
('2', 'fr', 'Finale régionale', 'Retour en images sur notre victoire.'),
('3', 'fr', 'Best of du mois', 'Compilation des plus belles actions.');
DELETE FROM `nf_gallery_images`;
INSERT INTO `nf_gallery_images` (`image_id`, `thumbnail_file_id`, `original_file_id`, `file_id`, `gallery_id`, `title`, `description`, `date`, `views`) VALUES
('1', '2', '3', '1', '1', 'Installation des postes', '', '2026-09-06 11:46:28', '12'),
('2', '5', '6', '4', '1', 'La finale sur grand écran', '', '2026-09-06 11:56:28', '19'),
('3', '8', '9', '7', '1', 'Remise des trophées', '', '2026-09-06 12:06:28', '26'),
('4', '11', '12', '10', '1', 'Photo de groupe', '', '2026-09-06 12:16:28', '33'),
('5', '14', '15', '13', '2', 'Le dernier round', '', '2026-09-08 11:46:29', '15'),
('6', '17', '18', '16', '2', 'Le clutch de Zenith', '', '2026-09-08 11:56:29', '22'),
('7', '20', '21', '19', '2', 'Célébration', '', '2026-09-08 12:06:29', '29'),
('8', '23', '24', '22', '3', 'Ace sur Mirage', '', '2026-09-10 11:46:29', '18'),
('9', '26', '27', '25', '3', 'Triple kill en overtime', '', '2026-09-10 11:56:29', '25'),
('10', '29', '30', '28', '3', 'Le but impossible', '', '2026-09-10 12:06:29', '32');
DELETE FROM `nf_games`;
INSERT INTO `nf_games` (`game_id`, `parent_id`, `image_id`, `icon_id`, `name`) VALUES
('1', NULL, NULL, NULL, 'counter-strike-2'),
('2', NULL, NULL, NULL, 'valorant'),
('3', NULL, NULL, NULL, 'league-of-legends'),
('4', NULL, NULL, NULL, 'rocket-league');
DELETE FROM `nf_games_lang`;
INSERT INTO `nf_games_lang` (`game_id`, `lang`, `title`) VALUES
('1', 'fr', 'Counter-Strike 2'),
('2', 'fr', 'Valorant'),
('3', 'fr', 'League of Legends'),
('4', 'fr', 'Rocket League');
DELETE FROM `nf_teams`;
INSERT INTO `nf_teams` (`team_id`, `game_id`, `image_id`, `icon_id`, `name`, `order`) VALUES
('1', '1', NULL, NULL, 'equipe-principale-cs2', '0'),
('2', '2', NULL, NULL, 'roster-valorant', '1'),
('3', '3', NULL, NULL, 'equipe-lol', '2');
DELETE FROM `nf_teams_lang`;
INSERT INTO `nf_teams_lang` (`team_id`, `lang`, `title`, `description`) VALUES
('1', 'fr', 'Équipe principale CS2', 'Notre roster compétitif sur CS2.'),
('2', 'fr', 'Roster Valorant', 'Cinq joueurs, un objectif : le top.'),
('3', 'fr', 'Équipe LoL', 'La faille n\'a qu\'à bien se tenir.');
DELETE FROM `nf_teams_users`;
INSERT INTO `nf_teams_users` (`team_id`, `user_id`, `role_id`) VALUES
('1', '329', '1'),
('2', '330', '1'),
('3', '331', '1'),
('1', '330', '2'),
('1', '331', '2'),
('1', '332', '2'),
('2', '331', '2'),
('2', '332', '2'),
('2', '333', '2'),
('3', '332', '2'),
('3', '333', '2'),
('3', '334', '2'),
('1', '333', '3'),
('2', '334', '3'),
('3', '335', '3');
DELETE FROM `nf_events_types`;
INSERT INTO `nf_events_types` (`type_id`, `type`, `title`, `color`, `icon`) VALUES
('1', '1', 'Tournoi', '#e74c3c', 'fas fa-trophy'),
('2', '1', 'Entraînement', '#3498db', 'fas fa-dumbbell');
DELETE FROM `nf_events`;
INSERT INTO `nf_events` (`event_id`, `type_id`, `user_id`, `image_id`, `title`, `description`, `private_description`, `location`, `date`, `date_end`, `published`, `publish_date`, `reminder_sent_at`, `series_id`) VALUES
('1', '1', '271', NULL, 'Tournoi régional CS2', 'Qualifications ouvertes à tous les niveaux.', '', 'En ligne', '2026-09-21 11:46:29', NULL, '1', '2026-09-11 11:46:29', NULL, NULL),
('2', '1', '271', NULL, 'Coupe Valorant communautaire', 'Format double élimination, BO3.', '', 'Discord', '2026-09-28 11:46:29', NULL, '1', '2026-09-11 11:46:29', NULL, NULL),
('3', '2', '271', NULL, 'Entraînement hebdo LoL', 'Scrims et review de games.', '', 'Faille de l\'invocateur', '2026-09-14 11:46:29', NULL, '1', '2026-09-11 11:46:29', NULL, NULL);
DELETE FROM `nf_awards`;
INSERT INTO `nf_awards` (`award_id`, `team_id`, `game_id`, `image_id`, `name`, `location`, `date`, `description`, `platform`, `ranking`, `participants`) VALUES
('1', '1', '1', NULL, 'Tournoi régional 2025', 'Lyon', '2026-08-07', '', 'PC', '1', '16'),
('2', '2', '2', NULL, 'Coupe d\'hiver', 'En ligne', '2026-08-07', '', 'PC', '2', '24'),
('3', '3', '3', NULL, 'Ligue communautaire', 'En ligne', '2026-08-07', '', 'PC', '3', '12');
DELETE FROM `nf_comment`;
INSERT INTO `nf_comment` (`id`, `parent_id`, `user_id`, `module_id`, `module`, `content`, `date`, `deleted_at`, `deleted_by`) VALUES
('1', NULL, '271', '1', 'news', 'Super nouvelle, merci !', '2026-09-16 10:46:29', NULL, NULL),
('2', NULL, '330', '1', 'news', 'Hâte d\'y être !', '2026-09-16 09:46:29', NULL, NULL),
('3', NULL, '330', '2', 'news', 'GG à l\'équipe !', '2026-09-16 08:46:29', NULL, NULL),
('4', NULL, '332', '2', 'news', 'On compte sur vous !', '2026-09-16 07:46:29', NULL, NULL),
('5', NULL, '334', '2', 'news', 'Excellent, vivement la suite.', '2026-09-16 06:46:29', NULL, NULL),
('6', NULL, '333', '3', 'news', 'Super nouvelle, merci !', '2026-09-16 05:46:29', NULL, NULL),
('7', NULL, '334', '4', 'news', 'Hâte d\'y être !', '2026-09-16 04:46:29', NULL, NULL),
('8', NULL, '336', '4', 'news', 'GG à l\'équipe !', '2026-09-16 03:46:29', NULL, NULL);
DELETE FROM `nf_reactions`;
INSERT INTO `nf_reactions` (`id`, `user_id`, `content_type`, `content_id`, `reaction`, `created_at`) VALUES
('1', '271', 'news', '1', 'love', '2026-09-16 11:16:29'),
('2', '330', 'news', '1', 'love', '2026-09-16 10:46:29'),
('3', '332', 'news', '1', 'love', '2026-09-16 10:16:29'),
('4', '331', 'news', '2', 'love', '2026-09-16 09:46:29'),
('5', '333', 'news', '2', 'love', '2026-09-16 09:16:29'),
('6', '335', 'news', '2', 'love', '2026-09-16 08:46:29'),
('7', '337', 'news', '2', 'love', '2026-09-16 08:16:29'),
('8', '335', 'news', '3', 'love', '2026-09-16 07:46:29'),
('9', '337', 'news', '3', 'love', '2026-09-16 07:16:29'),
('10', '329', 'news', '3', 'love', '2026-09-16 06:46:29'),
('11', '331', 'news', '3', 'love', '2026-09-16 06:16:29'),
('12', '333', 'news', '3', 'love', '2026-09-16 05:46:29'),
('13', '330', 'news', '4', 'love', '2026-09-16 05:16:29'),
('14', '332', 'news', '4', 'love', '2026-09-16 04:46:29');
DELETE FROM `nf_faq_categories`;
INSERT INTO `nf_faq_categories` (`id`, `title`, `sort_order`) VALUES
('1', 'Général', '0');
DELETE FROM `nf_faq_questions`;
INSERT INTO `nf_faq_questions` (`id`, `category_id`, `question`, `answer`, `sort_order`, `published`, `created_at`, `updated_at`) VALUES
('1', '1', 'Comment rejoindre la communauté ?', 'Inscrivez-vous gratuitement, puis présentez-vous sur le forum.', '0', '1', '2026-09-16 11:46:29', '2026-09-16 11:46:29'),
('2', '1', 'Le site est-il gratuit ?', 'Oui, NeoFrag Reborn est 100% gratuit et open source.', '1', '1', '2026-09-16 11:46:29', '2026-09-16 11:46:29'),
('3', '1', 'Comment gagner des points ?', 'En participant : poster, commenter, réagir et contribuer.', '2', '1', '2026-09-16 11:46:29', '2026-09-16 11:46:29');
DELETE FROM `nf_downloads_categories`;
INSERT INTO `nf_downloads_categories` (`id`, `title`, `description`, `sort_order`) VALUES
('1', 'Ressources', 'Configs, fonds d\'écran et outils.', '0');
DELETE FROM `nf_downloads`;
INSERT INTO `nf_downloads` (`id`, `category_id`, `title`, `description`, `file_url`, `file_size_bytes`, `file_type`, `version`, `downloads_count`, `published`, `created_at`) VALUES
('1', '1', 'Pack de fonds d\'écran', 'Une sélection de wallpapers aux couleurs de la team.', 'https://example.com/wallpapers.zip', '15728640', 'zip', '1.0', '20', '1', '2026-09-16 11:46:29'),
('2', '1', 'Config CS2 recommandée', 'Notre fichier de configuration partagé.', 'https://example.com/cs2-config.cfg', '8192', 'cfg', '2.3', '51', '1', '2026-09-16 11:46:29');
DELETE FROM `nf_links_categories`;
INSERT INTO `nf_links_categories` (`id`, `title`, `sort_order`) VALUES
('1', 'Liens utiles', '0');
DELETE FROM `nf_links`;
INSERT INTO `nf_links` (`id`, `category_id`, `title`, `url`, `description`, `clicks`, `published`, `sort_order`, `created_at`) VALUES
('1', '1', 'Notre Discord', 'https://discord.gg/example', 'Rejoignez le serveur vocal de la communauté.', '0', '1', '0', '2026-09-16 11:46:29'),
('2', '1', 'Chaîne Twitch', 'https://twitch.tv/example', 'Suivez nos lives et tournois.', '13', '1', '1', '2026-09-16 11:46:29'),
('3', '1', 'NeoFrag Reborn', 'https://neofr.ag', 'Le CMS qui propulse ce site.', '26', '1', '2', '2026-09-16 11:46:29');
DELETE FROM `nf_partners`;
INSERT INTO `nf_partners` (`partner_id`, `name`, `logo_light`, `logo_dark`, `website`, `facebook`, `twitter`, `code`, `count`, `order`) VALUES
('1', 'gamergear', NULL, NULL, 'https://example.com', '', '', '', '0', '0'),
('2', 'energydrink', NULL, NULL, 'https://example.com', '', '', '', '9', '1'),
('3', 'hostpro', NULL, NULL, 'https://example.com', '', '', '', '18', '2');
DELETE FROM `nf_partners_lang`;
INSERT INTO `nf_partners_lang` (`partner_id`, `lang`, `title`, `description`) VALUES
('1', 'fr', 'GamerGear', 'Matériel gaming partenaire officiel.'),
('2', 'fr', 'EnergyDrink', 'Le carburant de nos joueurs.'),
('3', 'fr', 'HostPro', 'Serveurs de jeu haute performance.');
DELETE FROM `nf_guestbook`;
INSERT INTO `nf_guestbook` (`id`, `user_id`, `name`, `message`, `ip_address`, `status`, `created_at`) VALUES
('1', '271', 'Alex', 'Super communauté, accueil au top !', '', 'approved', '2026-09-15 11:46:29'),
('2', '329', 'Sam', 'Le site est vraiment propre, bravo.', '', 'approved', '2026-09-14 11:46:29'),
('3', '330', 'Jordan', 'Hâte de participer au prochain tournoi.', '', 'approved', '2026-09-13 11:46:29');
DELETE FROM `nf_surveys`;
INSERT INTO `nf_surveys` (`id`, `title`, `description`, `user_id`, `multiple_choice`, `show_results`, `closed_at`, `published`, `created_at`) VALUES
('1', 'Quel jeu pour le prochain tournoi ?', 'Votez pour le jeu de notre prochain événement.', '271', '0', 'always', NULL, '1', '2026-09-16 11:46:29');
DELETE FROM `nf_surveys_options`;
INSERT INTO `nf_surveys_options` (`id`, `survey_id`, `label`, `sort_order`) VALUES
('1', '1', 'Counter-Strike 2', '0'),
('2', '1', 'Valorant', '1'),
('3', '1', 'League of Legends', '2'),
('4', '1', 'Rocket League', '3');
DELETE FROM `nf_surveys_votes`;
INSERT INTO `nf_surveys_votes` (`id`, `survey_id`, `option_id`, `user_id`, `ip_hash`, `created_at`) VALUES
('1', '1', '1', '271', '', '2026-09-16 11:46:29'),
('2', '1', '2', '329', '', '2026-09-16 10:46:29'),
('3', '1', '3', '330', '', '2026-09-16 09:46:29'),
('4', '1', '4', '331', '', '2026-09-16 08:46:29'),
('5', '1', '1', '332', '', '2026-09-16 07:46:29'),
('6', '1', '2', '333', '', '2026-09-16 06:46:29'),
('7', '1', '3', '334', '', '2026-09-16 05:46:29'),
('8', '1', '4', '335', '', '2026-09-16 04:46:29'),
('9', '1', '1', '336', '', '2026-09-16 03:46:29'),
('10', '1', '2', '337', '', '2026-09-16 02:46:29');
DELETE FROM `nf_classifieds`;
INSERT INTO `nf_classifieds` (`id`, `category_id`, `user_id`, `ad_type`, `title`, `description`, `price`, `contact`, `image`, `status`, `views`, `created_at`, `updated_at`) VALUES
('1', '1', '271', 'offer', 'Vends clavier mécanique', 'Switch rouges, très bon état, peu servi.', '60.00', 'discord: alex#0001', '', 'published', '5', '2026-09-14 11:46:29', '2026-09-14 11:46:29'),
('2', '1', '329', 'request', 'Cherche casque gaming', 'Budget 50€, micro indispensable.', '0.00', 'mp sur le forum', '', 'published', '13', '2026-09-13 11:46:29', '2026-09-13 11:46:29');
DELETE FROM `nf_recruits`;
INSERT INTO `nf_recruits` (`recruit_id`, `title`, `introduction`, `description`, `requierments`, `date`, `user_id`, `size`, `role`, `icon`, `date_end`, `closed`, `team_id`, `image_id`) VALUES
('1', 'Recrutement CS2 - Joueur AWP', 'Notre équipe CS2 cherche un sniper.', 'Tu maîtrises l\'AWP et tu cherches une équipe sérieuse ? Postule !', 'Niveau Faceit 7+, dispo 3 soirs/semaine, micro obligatoire.', '2026-09-13 11:46:29', '271', '1', 'AWPer', 'fas fa-crosshairs', '2026-10-06', '0', NULL, NULL),
('2', 'Recrutement Valorant - Support', 'On cherche un joueur support/initiateur.', 'Rejoins notre roster Valorant en construction.', 'Immortal+, bonne communication, esprit d\'équipe.', '2026-09-12 11:46:29', '271', '1', 'Initiateur', 'fas fa-shield', '2026-10-06', '0', NULL, NULL);
DELETE FROM `nf_recruits_fields`;
INSERT INTO `nf_recruits_fields` (`field_id`, `recruit_id`, `label`, `type`, `required`, `sort_order`) VALUES
('1', '1', 'Quel est ton pseudo en jeu ?', 'text', '1', '0'),
('2', '1', 'Quelles sont tes disponibilités en soirée ?', 'text', '1', '1'),
('3', '1', 'Décris une situation où tu as pris le lead.', 'textarea', '0', '2'),
('4', '2', 'Quel est ton pseudo en jeu ?', 'text', '1', '0'),
('5', '2', 'Quelles sont tes disponibilités en soirée ?', 'text', '1', '1'),
('6', '2', 'Décris une situation où tu as pris le lead.', 'textarea', '0', '2');
DELETE FROM `nf_calendar_events`;
INSERT INTO `nf_calendar_events` (`id`, `title`, `description`, `location`, `start_at`, `end_at`, `all_day`, `user_id`, `color`, `published`, `created_at`) VALUES
('1', 'Entraînement CS2', 'Scrims du soir', 'Serveur communautaire', '2026-09-18 11:46:29', '2026-09-18 13:46:29', '0', '271', '#1abc9c', '1', '2026-09-16 11:46:29'),
('2', 'Soirée détente', 'Parties fun ouvertes à tous', 'Discord', '2026-09-21 11:46:29', '2026-09-21 13:46:29', '0', '271', '#1abc9c', '1', '2026-09-16 11:46:29'),
('3', 'Maintenance serveur', 'Indisponibilité prévue', '', '2026-09-25 11:46:29', '2026-09-25 13:46:29', '1', '271', '#1abc9c', '1', '2026-09-16 11:46:29');
DELETE FROM `nf_bug_tickets`;
INSERT INTO `nf_bug_tickets` (`id`, `title`, `description`, `type`, `priority`, `status`, `user_id`, `assigned_to`, `created_at`, `updated_at`) VALUES
('1', 'Bouton de connexion mal aligné sur mobile', 'Sur petit écran, le bouton dépasse légèrement.', 'bug', 'normal', 'resolved', '329', NULL, '2026-09-16 11:46:29', '2026-09-16 11:46:29'),
('2', 'Ajouter un mode sombre au profil', 'Ce serait agréable d\'avoir le thème sombre partout.', 'feature', 'low', 'open', '330', NULL, '2026-09-16 11:46:29', '2026-09-16 11:46:29'),
('3', 'Comment changer mon avatar ?', 'Je ne trouve pas l\'option dans les réglages.', 'question', 'normal', 'closed', '331', NULL, '2026-09-16 11:46:29', '2026-09-16 11:46:29');
DELETE FROM `nf_bug_comments`;
INSERT INTO `nf_bug_comments` (`id`, `ticket_id`, `user_id`, `content`, `is_status_change`, `created_at`) VALUES
('1', '1', '271', 'Merci pour le retour, on regarde ça.', '0', '2026-09-16 10:46:29'),
('2', '2', '271', 'Merci pour le retour, on regarde ça.', '0', '2026-09-16 09:46:29'),
('3', '3', '271', 'Merci pour le retour, on regarde ça.', '0', '2026-09-16 08:46:29');
DELETE FROM `nf_donations_campaigns`;
INSERT INTO `nf_donations_campaigns` (`id`, `name`, `title`, `description`, `goal_amount`, `currency`, `paypal_email`, `paypal_button_id`, `deadline`, `status`, `created_at`, `updated_at`) VALUES
('1', 'serveur-2025', 'Financement du serveur 2025', 'Aidez-nous à financer l\'hébergement de nos serveurs de jeu.', '500.00', 'EUR', '', '', '2026-11-15', 'active', '2026-09-16 11:46:29', '2026-09-16 11:46:29');
DELETE FROM `nf_donations`;
INSERT INTO `nf_donations` (`id`, `campaign_id`, `user_id`, `donor_name`, `amount`, `currency`, `message`, `is_anonymous`, `is_public`, `source`, `paypal_txn_id`, `status`, `created_at`) VALUES
('1', '1', '271', 'Alex', '20.00', 'EUR', 'Bon courage à toute la team !', '0', '1', 'manual', NULL, 'completed', '2026-09-15 11:46:29'),
('2', '1', '329', 'Anonyme', '10.00', 'EUR', '', '1', '1', 'manual', NULL, 'completed', '2026-09-14 11:46:29'),
('3', '1', '330', 'Sam', '50.00', 'EUR', 'Continuez comme ça.', '0', '1', 'manual', NULL, 'completed', '2026-09-13 11:46:29');
DELETE FROM `nf_ads`;
INSERT INTO `nf_ads` (`id`, `title`, `placement`, `format`, `image_url`, `url`, `html`, `active`, `starts_at`, `ends_at`, `position`, `impressions`, `clicks`, `created_at`) VALUES
('1', 'Bannière partenaire', 'sidebar', 'html', '', 'https://example.com', '<div style=\"padding:1rem;text-align:center\">Espace partenaire</div>', '1', NULL, NULL, '0', '120', '8', '2026-09-16 11:46:29');
DELETE FROM `nf_newsletter_subscribers`;
INSERT INTO `nf_newsletter_subscribers` (`id`, `email`, `token`, `confirmed`, `user_id`, `created_at`, `confirmed_at`) VALUES
('1', 'fan1@demo.local', '53588cb45242ff932aa0d660a0f8b466', '1', '271', '2026-09-15 11:46:29', '2026-09-15 11:46:29'),
('2', 'fan2@demo.local', '60ddc1119f43967ce735873791118e72', '1', '329', '2026-09-14 11:46:29', '2026-09-14 11:46:29'),
('3', 'fan3@demo.local', 'b76d2d02e09519e7acd8319038a7b0cb', '1', '330', '2026-09-13 11:46:29', '2026-09-13 11:46:29'),
('4', 'fan4@demo.local', '6503cf0906b80c7fe634101873615593', '1', '331', '2026-09-12 11:46:29', '2026-09-12 11:46:29');
DELETE FROM `nf_events_matches`;
INSERT INTO `nf_events_matches` (`event_id`, `team_id`, `opponent_id`, `mode_id`, `webtv`, `website`) VALUES
('1', '1', '1', '1', 'https://twitch.tv/exemple', ''),
('2', '2', '2', '4', '', ''),
('3', '3', '3', '6', '', '');
DELETE FROM `nf_events_matches_opponents`;
INSERT INTO `nf_events_matches_opponents` (`opponent_id`, `image_id`, `title`, `website`, `country`) VALUES
('1', NULL, 'Team Nocturne', 'https://example.org/nocturne', 'fr'),
('2', NULL, 'Aurora Esports', 'https://example.org/aurora', 'be'),
('3', NULL, 'Crimson Owls', 'https://example.org/owls', 'ca'),
('4', NULL, 'Skyline Gaming', '', 'ch');
DELETE FROM `nf_events_matches_rounds`;
INSERT INTO `nf_events_matches_rounds` (`round_id`, `event_id`, `map_id`, `score1`, `score2`) VALUES
('1', '1', '1', '13', '9'),
('2', '1', '2', '11', '13'),
('3', '1', '3', '13', '7'),
('4', '2', '6', '13', '11'),
('5', '2', '7', '9', '13');
DELETE FROM `nf_events_participants`;
INSERT INTO `nf_events_participants` (`event_id`, `user_id`, `status`) VALUES
('1', '271', '1'),
('1', '329', '2'),
('1', '330', '1'),
('1', '332', '1'),
('1', '333', '1'),
('1', '334', '2'),
('1', '336', '1'),
('1', '337', '1'),
('2', '271', '1'),
('2', '329', '2'),
('2', '330', '1'),
('2', '332', '1'),
('2', '333', '1'),
('2', '334', '2'),
('2', '336', '1'),
('2', '337', '1'),
('3', '271', '1'),
('3', '329', '2'),
('3', '330', '1'),
('3', '332', '1'),
('3', '333', '1'),
('3', '334', '2'),
('3', '336', '1'),
('3', '337', '1');
DELETE FROM `nf_pages`;
INSERT INTO `nf_pages` (`page_id`, `name`, `published`, `date`, `layout`) VALUES
('1', 'a-propos', '1', '2026-08-17 11:46:29', 'default'),
('2', 'reglement', '1', '2026-08-22 11:46:29', 'default'),
('3', 'nous-rejoindre', '1', '2026-08-27 11:46:29', 'default'),
('4', 'partenaires', '1', '2026-09-01 11:46:29', 'default');
DELETE FROM `nf_pages_lang`;
INSERT INTO `nf_pages_lang` (`page_id`, `lang`, `title`, `subtitle`, `content`) VALUES
('1', 'fr', 'À propos de la communauté', 'Qui sommes-nous', '<p>Fondée en 2019 autour de Counter-Strike, notre communauté réunit aujourd\'hui une centaine de joueurs sur quatre jeux. On y vient pour le niveau, on y reste pour l\'ambiance.</p><p>Trois équipes compétitives, des entraînements hebdomadaires, une LAN annuelle — et un serveur Discord ouvert à tous.</p>'),
('2', 'fr', 'Règlement intérieur', 'Ce qu\'on attend de chacun', '<p>Respect avant tout : pas d\'insultes, pas de propos discriminatoires, pas de triche. Un manquement se règle d\'abord par un avertissement, ensuite par une exclusion.</p><ul><li>Micro conseillé en entraînement, obligatoire en match officiel.</li><li>Prévenir en cas d\'absence, au moins 24 h à l\'avance.</li><li>Le staff tranche les litiges ; ses décisions sont publiques.</li></ul>'),
('3', 'fr', 'Nous rejoindre', 'Comment postuler', '<p>Les recrutements ouverts sont listés dans la rubrique dédiée. Tu peux aussi te présenter sur le forum : on regarde toutes les candidatures spontanées.</p>'),
('4', 'fr', 'Nos partenaires', 'Ils nous soutiennent', '<p>Trois partenaires nous accompagnent sur l\'hébergement, le matériel et l\'habillage vidéo. Leur présence finance nos déplacements en LAN.</p>');
DELETE FROM `nf_pages_instances`;
-- nf_pages_instances : aucune donnée.
DELETE FROM `nf_menus`;
INSERT INTO `nf_menus` (`menu_id`, `name`, `title`) VALUES
('1', 'communaute', 'La communauté');
DELETE FROM `nf_menus_items`;
INSERT INTO `nf_menus_items` (`item_id`, `menu_id`, `parent_id`, `title`, `url`, `icon`, `target`, `position`) VALUES
('1', '1', NULL, 'À propos', 'pages/a-propos', 'fas fa-circle-info', '', '0'),
('2', '1', NULL, 'Règlement', 'pages/reglement', 'fas fa-gavel', '', '1'),
('3', '1', NULL, 'Nous rejoindre', 'recruits', 'fas fa-user-plus', '', '2'),
('4', '1', NULL, 'Nos partenaires', 'pages/partenaires', 'fas fa-handshake', '', '3'),
('5', '1', NULL, 'Discord', 'https://discord.gg/exemple', 'fab fa-discord', '_blank', '4');
DELETE FROM `nf_custom_emojis`;
INSERT INTO `nf_custom_emojis` (`id`, `name`, `image_id`, `created_at`) VALUES
('1', 'gg', '34', '2026-08-27 11:46:29'),
('2', 'clutch', '35', '2026-08-27 11:46:29'),
('3', 'ace', '36', '2026-08-27 11:46:29'),
('4', 'salt', '37', '2026-08-27 11:46:29'),
('5', 'pog', '38', '2026-08-27 11:46:29');
DELETE FROM `nf_slider_slides`;
INSERT INTO `nf_slider_slides` (`id`, `image_url`, `title`, `caption`, `link`, `sort_order`, `active`, `created_at`, `updated_at`) VALUES
('1', 'upload/demo/demo-slide-0.jpg', 'Bienvenue sur la démo', 'Toutes les rubriques sont peuplées : navigue librement.', 'news', '0', '1', '2026-09-09 11:46:29', '2026-09-09 11:46:29'),
('2', 'upload/demo/demo-slide-1.jpg', 'LAN d\'été 2025', 'Retrouve les photos dans la galerie.', 'gallery', '1', '1', '2026-09-09 11:46:29', '2026-09-09 11:46:29'),
('3', 'upload/demo/demo-slide-2.jpg', 'Recrutement ouvert', 'Deux postes à pourvoir sur CS2 et Valorant.', 'recruits', '2', '1', '2026-09-09 11:46:29', '2026-09-09 11:46:29');
DELETE FROM `nf_talks`;
INSERT INTO `nf_talks` (`talk_id`, `name`, `type`, `audience`, `creator_id`, `description`, `created_at`, `updated_at`, `deleted_at`) VALUES
('1', 'Salon général', 'public', 'all', '271', 'Le salon ouvert à tous les membres.', '2026-08-22 11:46:29', '2026-09-16 10:46:29', NULL),
('2', 'Staff', 'public', 'staff', '271', 'Coordination interne du staff.', '2026-08-27 11:46:29', '2026-09-16 09:46:29', NULL),
('3', 'Scrims CS2', 'group', 'all', '271', 'Organisation des entraînements CS2.', '2026-09-01 11:46:29', '2026-09-16 08:46:29', NULL);
DELETE FROM `nf_talks_messages`;
INSERT INTO `nf_talks_messages` (`message_id`, `talk_id`, `parent_id`, `user_id`, `message`, `date`, `edited_at`, `edited_by`, `deleted_at`, `deleted_by`, `deleted_reason`) VALUES
('1', '1', NULL, '271', 'Bienvenue à tous sur le salon général. Présentez-vous ici !', '2026-09-16 07:46:29', NULL, NULL, NULL, NULL, NULL),
('2', '1', NULL, '329', 'Salut tout le monde, ravi de rejoindre la communauté 👋', '2026-09-16 08:46:29', NULL, NULL, NULL, NULL, NULL),
('3', '1', NULL, '330', 'Bienvenue ! Si tu joues CS2, viens sur les scrims du mardi.', '2026-09-16 09:46:29', NULL, NULL, NULL, NULL, NULL),
('4', '1', NULL, '329', 'Merci, je serai là.', '2026-09-16 10:46:29', NULL, NULL, NULL, NULL, NULL),
('5', '2', NULL, '271', 'On cale la LAN d\'hiver sur le week-end du 12 ?', '2026-09-16 08:46:29', NULL, NULL, NULL, NULL, NULL),
('6', '2', NULL, '331', 'Ça me va. Je réserve la salle demain.', '2026-09-16 09:46:29', NULL, NULL, NULL, NULL, NULL),
('7', '3', NULL, '330', 'Scrim ce soir 21 h contre Aurora, tout le monde est dispo ?', '2026-09-16 06:46:29', NULL, NULL, NULL, NULL, NULL),
('8', '3', NULL, '332', 'Présent.', '2026-09-16 07:46:29', NULL, NULL, NULL, NULL, NULL),
('9', '3', NULL, '333', 'Je serai 10 min en retard.', '2026-09-16 08:46:29', NULL, NULL, NULL, NULL, NULL);
DELETE FROM `nf_talks_participants`;
INSERT INTO `nf_talks_participants` (`talk_id`, `user_id`, `role`, `joined_at`, `last_read_at`, `archived_at`, `deleted_at`, `notify_email`) VALUES
('1', '271', 'admin', '2026-08-22 11:46:29', '2026-09-16 10:46:29', NULL, NULL, '1'),
('1', '329', 'member', '2026-08-22 11:46:29', '2026-09-16 09:46:29', NULL, NULL, '1'),
('1', '330', 'member', '2026-08-22 11:46:29', '2026-09-16 08:46:29', NULL, NULL, '1'),
('1', '331', 'member', '2026-08-22 11:46:29', '2026-09-16 07:46:29', NULL, NULL, '1'),
('1', '332', 'member', '2026-08-22 11:46:29', '2026-09-16 06:46:29', NULL, NULL, '1'),
('1', '333', 'member', '2026-08-22 11:46:29', '2026-09-16 05:46:29', NULL, NULL, '1'),
('1', '334', 'member', '2026-08-22 11:46:29', '2026-09-16 04:46:29', NULL, NULL, '1'),
('1', '335', 'member', '2026-08-22 11:46:29', '2026-09-16 03:46:29', NULL, NULL, '1'),
('1', '336', 'member', '2026-08-22 11:46:29', '2026-09-16 02:46:29', NULL, NULL, '1'),
('1', '337', 'member', '2026-08-22 11:46:29', '2026-09-16 01:46:29', NULL, NULL, '1'),
('2', '271', 'admin', '2026-08-27 11:46:29', '2026-09-16 10:46:29', NULL, NULL, '1'),
('2', '329', 'member', '2026-08-27 11:46:29', '2026-09-16 09:46:29', NULL, NULL, '1'),
('2', '330', 'member', '2026-08-27 11:46:29', '2026-09-16 08:46:29', NULL, NULL, '1'),
('2', '331', 'member', '2026-08-27 11:46:29', '2026-09-16 07:46:29', NULL, NULL, '1'),
('2', '332', 'member', '2026-08-27 11:46:29', '2026-09-16 06:46:29', NULL, NULL, '1'),
('2', '333', 'member', '2026-08-27 11:46:29', '2026-09-16 05:46:29', NULL, NULL, '1'),
('2', '334', 'member', '2026-08-27 11:46:29', '2026-09-16 04:46:29', NULL, NULL, '1'),
('2', '335', 'member', '2026-08-27 11:46:29', '2026-09-16 03:46:29', NULL, NULL, '1'),
('2', '336', 'member', '2026-08-27 11:46:29', '2026-09-16 02:46:29', NULL, NULL, '1'),
('2', '337', 'member', '2026-08-27 11:46:29', '2026-09-16 01:46:29', NULL, NULL, '1'),
('3', '271', 'admin', '2026-09-01 11:46:29', '2026-09-16 10:46:29', NULL, NULL, '1'),
('3', '329', 'member', '2026-09-01 11:46:29', '2026-09-16 09:46:29', NULL, NULL, '1'),
('3', '330', 'member', '2026-09-01 11:46:29', '2026-09-16 08:46:29', NULL, NULL, '1'),
('3', '331', 'member', '2026-09-01 11:46:29', '2026-09-16 07:46:29', NULL, NULL, '1'),
('3', '332', 'member', '2026-09-01 11:46:29', '2026-09-16 06:46:29', NULL, NULL, '1'),
('3', '333', 'member', '2026-09-01 11:46:29', '2026-09-16 05:46:29', NULL, NULL, '1');
DELETE FROM `nf_talks_attachments`;
-- nf_talks_attachments : aucune donnée.
DELETE FROM `nf_revisions`;
INSERT INTO `nf_revisions` (`id`, `content_type`, `content_id`, `lang`, `user_id`, `summary`, `data`, `created_at`) VALUES
('1', 'news', '1', 'fr', '271', 'Correction d\'une faute de frappe', '{\"title\":\"Notre équipe CS2 se qualifie\"}', '2026-09-14 11:46:29');
DELETE FROM `nf_wiki_pages`;
INSERT INTO `nf_wiki_pages` (`id`, `slug`, `title`, `content`, `parent_id`, `sort_order`, `published`, `user_id`, `views`, `created_at`, `updated_at`) VALUES
('24', 'guide-utilisateur', 'Guide utilisateur', '<h1>Guide utilisateur</h1>\n<p>Tout pour installer et piloter ton site <strong>NeoFrag Reborn</strong>.</p>\n', NULL, '1', '1', NULL, '2', '2026-09-15 14:28:39', '2026-09-16 00:07:38'),
('25', 'installation', 'Installation', '<h1>Installation</h1>\n<p>NeoFrag Reborn s\'installe sur un hébergement web classique, <strong>mutualisé compris</strong> : ni Docker, ni\nComposer, ni accès shell ne sont nécessaires — le paquet embarque ses dépendances.</p>\n<h2>Prérequis</h2>\n<ul>\n<li><strong>PHP 8.2+</strong> avec les extensions <code>mysqli</code>, <code>gd</code>, <code>intl</code>, <code>mbstring</code>, <code>zip</code>, <code>curl</code>.</li>\n<li><strong>MySQL 5.7+</strong> ou <strong>MariaDB 10.5+</strong>.</li>\n<li><strong>Apache</strong> avec <code>mod_rewrite</code> (un <code>.htaccess</code> est livré) ; ou <strong>nginx</strong> (<code>nginx.conf</code>) ; ou <strong>Caddy</strong>\n(<code>Caddyfile</code>).</li>\n<li>Une base de données <strong>vide</strong> et ses identifiants.</li>\n<li>Un vrai <strong>nom de domaine</strong> pointant sur le serveur : l\'assistant en déduit l\'adresse de contact du site\n(<code>noreply@ton-domaine</code>) — installé par son adresse IP, un site n\'aurait pas d\'adresse d\'expéditeur\nvalide.</li>\n</ul>\n<h2>L\'assistant d\'installation</h2>\n<p>L\'assistant se déroule en <strong>cinq étapes</strong> : <em>Prérequis</em> → <em>Profil du site</em> → <em>Base de données</em> →\n<em>Administrateur</em> → <em>Terminé</em>.</p>\n<ol>\n<li>\n<p><strong>Téléverse</strong> les fichiers de NeoFrag Reborn à la racine web (FTP, ou décompression du paquet).</p>\n</li>\n<li>\n<p>Ouvre ton domaine : l\'assistant se lance et affiche les <strong>prérequis</strong> avec la valeur constatée\n(version de PHP, extensions présentes).</p>\n</li>\n<li>\n<p>Choisis le <strong>profil du site</strong> :</p>\n<table>\n<thead>\n<tr>\n<th>Profil</th>\n<th>Ce qu\'il installe</th>\n</tr>\n</thead>\n<tbody>\n<tr>\n<td><strong>Complet</strong></td>\n<td>le cœur et tous les modules du paquet</td>\n</tr>\n<tr>\n<td><strong>Gaming / eSport</strong></td>\n<td>le cœur et l\'identité gaming : forum, équipes, jeux, événements, recrutement, palmarès…</td>\n</tr>\n<tr>\n<td><strong>Communauté</strong></td>\n<td>le cœur, les actualités, le forum, la galerie</td>\n</tr>\n<tr>\n<td><strong>Cœur seul</strong></td>\n<td>comptes, permissions, pages, paramètres — rien de plus</td>\n</tr>\n</tbody>\n</table>\n<p>Les modules restent décochables un par un ; ceux qu\'un module réclame sont ajoutés automatiquement,\net l\'écran final le dit. Sans JavaScript, le formulaire fonctionne quand même.</p>\n</li>\n<li>\n<p>Renseigne la <strong>connexion à la base de données</strong> : l\'assistant importe le schéma, installe les modules,\nwidgets et thèmes choisis, et pose les dispositions par défaut.</p>\n</li>\n<li>\n<p>Crée le <strong>compte administrateur</strong>. C\'est fini.</p>\n</li>\n</ol>\n<p>Les modules non installés s\'ajoutent plus tard depuis <strong>Administration → Thèmes &amp; Addons</strong>, et ceux\nqui ne sont pas dans le paquet depuis le <a href=\"marketplace\">marketplace</a>.</p>\n<h2>Installation en ligne de commande</h2>\n<p>Pour un déploiement <strong>scriptable</strong> (serveur, provisioning), <code>install/cli.php</code> fait la même chose que\nl\'assistant, sans navigateur. Il installe le profil <strong>Complet</strong>.</p>\n<pre><code class=\"language-bash\"># Mot de passe administrateur via variable d\'environnement (invisible dans la liste des processus) :\nexport NF_ADMIN_PASS=\'mon-mot-de-passe-fort\'\nphp install/cli.php \\\n  --db-host=localhost --db-name=neofrag --db-user=neofrag --db-pass=secret \\\n  --admin-user=admin --admin-email=admin@site.tld --admin-pass-env=NF_ADMIN_PASS \\\n  --site-name=&quot;Ma communauté&quot; --site-url=https://site.tld --yes\n</code></pre>\n<p>Sans arguments, il passe en <strong>mode interactif</strong> (mot de passe masqué). Options : <code>--db-port</code>,\n<code>--create-db</code> (crée la base), <code>--dry-run</code> (valide la configuration et teste la connexion sans rien\nécrire), <code>--force</code> (réinstalle), <code>--no-lock</code> (ne pose pas le verrou), <code>--help</code>.</p>\n<h2>Sécuriser l\'accès après l\'installation</h2>\n<p>À la fin, l\'assistant pose un <strong>verrou</strong> (<code>install/db.txt</code>) : revisiter <code>/install/</code> n\'affiche plus rien\nd\'exploitable. Par précaution, <strong>supprime ou renomme le dossier <code>install/</code></strong> — il n\'est plus nécessaire.\nActive <strong>HTTPS</strong> si l\'hébergeur ne l\'a pas fait. Sous Caddy, le <code>Caddyfile</code> livré refuse déjà l\'accès à\n<code>config/</code>, <code>logs/</code>, <code>install/</code>, <code>tools/</code>, <code>tests/</code>, <code>docs/</code> et aux fichiers sensibles.</p>\n<h2>Premiers réglages</h2>\n<ol>\n<li><strong>Administration → Paramètres</strong> : nom du site, description, favicon, <strong>adresse de contact</strong> (vérifie\nqu\'elle est valide et qu\'un serveur de messagerie peut envoyer — sans cela, le formulaire de contact\net les e-mails d\'inscription n\'aboutissent pas ; un serveur SMTP se règle dans <em>Paramètres → E-mail</em>,\net <em>E-mails → Envoi de test</em> le prouve), page d\'accueil.</li>\n<li><strong>Administration → Thèmes &amp; Addons</strong> : ton thème, tes modules.</li>\n<li><strong>Administration → Éditeur en direct</strong> : compose tes pages (place tes widgets dans les régions).</li>\n<li><strong>Administration → Utilisateurs / Permissions</strong> : crée tes rôles et règle les accès.</li>\n<li><strong>Tâches planifiées</strong> : les publications programmées, les newsletters et les rappels d\'événements\npartent par le <strong>cron</strong> du site (Monitoring en donne l\'adresse et la clé). Ajoute une tâche toutes\nles cinq minutes chez ton hébergeur ; sans elle, rien de programmé ne part.</li>\n</ol>\n<h2>Dépannage</h2>\n<ul>\n<li><strong>« Connexion à la base impossible »</strong> : vérifie l\'hôte (souvent <code>localhost</code>, parfois une adresse\ndédiée sur les mutualisés), le port (<code>3306</code>), le nom de la base — <strong>elle doit exister et être vide</strong> —\net les identifiants.</li>\n<li><strong>« Extension PHP manquante »</strong> : active l\'extension signalée depuis le panel de l\'hébergeur ; en ligne\nde commande, <code>php -m</code>.</li>\n<li><strong>Dossier <code>config/</code> non inscriptible</strong> : l\'assistant doit y écrire <code>db.php</code> et les secrets. Donne les\ndroits d\'écriture à <code>config/</code>, <code>cache/</code>, <code>logs/</code>, <code>upload/</code>, <code>backups/</code>.</li>\n<li><strong>Installation interrompue</strong> : MySQL ne sait pas annuler un <code>CREATE TABLE</code> à moitié joué — <strong>recrée\nune base vierge</strong> avant de relancer.</li>\n<li><strong>Adresses en 404 ou AJAX cassés sous nginx ou Plesk</strong> : le <code>.htaccess</code> n\'est lu que par Apache ; voir la\nnote « nginx / Plesk » du guide de déploiement.</li>\n<li><strong>Aucun e-mail ne part</strong> : regarde <code>logs/php.log</code> (<code>[email] échec envoi …</code>) ; l\'adresse de contact\ndoit être valide et un transport doit exister (serveur SMTP configuré, ou agent de messagerie local).</li>\n</ul>\n<h2>Développement</h2>\n<p>Pour développer ou tester, toute machine avec PHP 8.2+ et MariaDB convient ; une pile Docker\n(<code>docker-compose.yml</code>) est fournie. Détails (base, migrations, tests, contrôles, déploiement) :\n<code>docs/development.md</code>.</p>\n', '24', '1', '1', NULL, '2', '2026-09-15 14:28:39', '2026-09-16 00:07:38'),
('26', 'concepts', 'Concepts', '<h1>Concepts</h1>\n<p>Comprendre ces cinq notions suffit à maîtriser NeoFrag Reborn.</p>\n<h2>Modules</h2>\n<p>Un <strong>module</strong> est une fonctionnalité complète : le forum, les actualités, la galerie, les membres, la\nboutique… Chaque module apporte ses <strong>pages publiques</strong>, son <strong>interface d\'administration</strong>, ses\n<strong>données</strong> (tables) et ses <strong>permissions</strong>.</p>\n<p>Le paquet livre <strong>54 modules</strong>, mais tu ne les installes pas tous : à l\'installation, un <strong>profil de\nsite</strong> — <em>Complet</em>, <em>Gaming / eSport</em>, <em>Communauté</em> ou <em>Cœur seul</em> — pré-coche ce qui correspond, et tu\npeux décocher module par module. Ce qu\'un module réclame est ajouté automatiquement (le palmarès a\nbesoin des équipes). Les modules du <strong>cœur</strong> (comptes, permissions, paramètres, pages, outils…) sont\ntoujours là et ne se désinstallent pas.</p>\n<p>Ensuite, depuis <strong>Administration → Thèmes &amp; Addons</strong>, tu installes, actives, désactives ou désinstalles\nchaque module. Un module désactivé disparaît du site mais conserve ses données. Les adresses d\'un module\nabsent répondent par un <strong>404 propre</strong>, jamais par une erreur.</p>\n<h2>Widgets</h2>\n<p>Un <strong>widget</strong> est un petit bloc réutilisable : le menu de navigation, l\'espace membre, les derniers\ncommentaires, un compte à rebours, un lecteur Twitch… Un même widget peut être placé plusieurs fois, à\ndes endroits différents, avec des réglages différents. Le paquet en livre <strong>38</strong>.</p>\n<p>Les widgets se posent dans les <strong>régions</strong> du thème via l\'<strong>éditeur en direct</strong> : un assistant en\nquatre étapes (widget, type, titre, configuration), du glisser-déposer pour les déplacer.</p>\n<h2>Thèmes</h2>\n<p>Un <strong>thème</strong> donne l\'identité visuelle du site : couleurs, typographies, mise en page, navigation,\npied de page, et le <strong>mode clair ou sombre</strong>. Il déclare des <strong>zones</strong>, leur donne des <strong>noms de\nrégions</strong> stables (<code>header</code>, <code>content</code>, <code>footer</code>…), et pose ses <strong>dispositions</strong> par défaut.</p>\n<p>Le paquet livre <strong>Nebula</strong>, un thème communautaire généraliste (navy et turquoise, glassmorphism),\npensé pour une équipe, une guilde ou une communauté. Quatre autres thèmes — <strong>Granite</strong>, <strong>Forge</strong>,\n<strong>Blockcraft</strong>, <strong>Extend</strong> — s\'installent depuis le <a href=\"marketplace\">marketplace</a>. Le thème actif se\nchoisit dans <strong>Administration → Thèmes &amp; Addons</strong> ; si plusieurs thèmes publics sont installés, les\nvisiteurs peuvent en changer via le sélecteur en pied de page.</p>\n<p>Tous les thèmes parlent le <strong>même vocabulaire de couleurs</strong> (<code>--nf-accent</code>, <code>--nf-surface</code>…) : un widget\nprend automatiquement la charte du thème actif.</p>\n<h2>Zones, régions et dispositions</h2>\n<p>Un thème découpe la page en <strong>zones</strong> : généralement <em>Header</em>, <em>Avant-contenu</em>, <em>Contenu</em>,\n<em>Post-contenu</em>, <em>Footer</em>, chacune connue sous un <strong>nom de région</strong> que les gabarits emploient.</p>\n<p>Une <strong>disposition</strong> décrit, pour une page donnée (ou un motif de pages), quels widgets occupent quelle\nzone et dans quelle grille. Les motifs vont du plus général au plus précis :</p>\n<table>\n<thead>\n<tr>\n<th>Motif</th>\n<th>S\'applique à</th>\n</tr>\n</thead>\n<tbody>\n<tr>\n<td><code>*</code></td>\n<td>toutes les pages</td>\n</tr>\n<tr>\n<td><code>/</code></td>\n<td>la page d\'accueil uniquement</td>\n</tr>\n<tr>\n<td><code>forum/*</code></td>\n<td>toutes les pages du forum</td>\n</tr>\n</tbody>\n</table>\n<p>Le motif le plus précis l\'emporte : une disposition <code>*</code> met le contenu sur huit colonnes plus une\ncolonne latérale, une disposition <code>marketplace*</code> peut passer la même page en pleine largeur.</p>\n<p>Tu modifies les dispositions à la souris avec l\'<strong>éditeur en direct</strong>. Une page peut aussi porter des\n<strong>blocs</strong> : des instances de modules ordonnées et configurées, insérées par le shortcode <code>[block:…]</code>.</p>\n<h2>Addons et marketplace</h2>\n<p><strong>Addon</strong> est le terme générique pour tout ce qui s\'installe : modules, widgets, thèmes, connecteurs\nd\'authentification (Discord, GitHub, Google) et packs de langue (six langues livrées).</p>\n<p>Le <strong><a href=\"marketplace\">marketplace</a></strong> est le catalogue du projet : il <strong>détecte les mises à jour</strong> des\naddons installés et permet d\'<strong>ajouter</strong> ceux qui ne sont pas dans le paquet, avec vérification\nd\'intégrité SHA-256. Le <strong>cœur du CMS</strong> se met à jour en un clic depuis <strong>Monitoring</strong>, avec sauvegarde\navant écriture et retour arrière automatique en cas d\'échec.</p>\n<p>Techniquement, un addon n\'est qu\'un <strong>dossier de fichiers PHP</strong> : tu peux le versionner, le partager, et\nle réinstaller sur n\'importe quel site NeoFrag Reborn. C\'est ce qui rend le CMS extensible — voir les\nguides développeur.</p>\n<h2>En pratique : monter une communauté</h2>\n<p>Pour une équipe de jeu : à l\'installation, choisis le profil <strong>Gaming / eSport</strong> (forum, équipes,\névénements, recrutement, palmarès…) et le thème Nebula est en place ; puis, avec l\'éditeur en direct,\nplace le widget <strong>navigation</strong> dans la région <code>header</code> et un widget de contenu (derniers sujets,\nprochains matchs…) dans la colonne latérale de l\'accueil. Tout se règle dans l\'administration, <strong>sans\nune ligne de code</strong>.</p>\n<h2>Surcharge sans forker</h2>\n<p>Tout fichier livré (vue, classe, asset) peut être <strong>surchargé</strong> sans modifier le code d\'origine. Le\nframework résout dans cet ordre (le premier trouvé gagne) :</p>\n<ol>\n<li><code>overrides/{type}/{fichier}</code> — surcharge globale</li>\n<li><code>themes/{thème actif}/overrides/{type}/{fichier}</code> — surcharge par thème</li>\n<li>l\'original livré</li>\n</ol>\n<p>Tu adaptes ainsi un module ou un widget à ton site sans jamais toucher au cœur, et sans casser les mises\nà jour.</p>\n', '24', '2', '1', NULL, '2', '2026-09-15 14:28:39', '2026-09-16 00:07:38'),
('27', 'admin', 'Administration', '<h1>Le panel d\'administration</h1>\n<p>L\'administration se trouve sous <strong>/admin</strong>. Elle adopte la charte NeoFrag Reborn\n(dark navy + teal, clair/sombre au choix) et s\'organise autour d\'une <strong>barre latérale</strong>.</p>\n<h2>Repères</h2>\n<ul>\n<li><strong>Barre latérale</strong> : les modules sont regroupés par catégories claires — <em>Contenu</em>,\n<em>Communauté</em>, <em>Connaissance</em> (wiki, FAQ), <em>Média</em>, <em>Gaming</em>, <em>Monétisation</em> — plus <em>Système</em> et\n<em>Monitoring</em>. Les sections se déplient en accordéon ; une catégorie vide est masquée.</li>\n<li><strong>Épingles</strong> : survole un module dans la sidebar et clique l\'épingle pour l\'ajouter à\n<em>Épinglé</em> (raccourcis en haut, mémorisés dans ton navigateur).</li>\n<li><strong>Recherche rapide</strong> : <code>Ctrl/Cmd + K</code> ouvre la palette de commandes pour sauter à\nn\'importe quel module ou action.</li>\n<li><strong>En-tête</strong> : fil d\'ariane + actions contextuelles (Permissions, Configuration, Aide),\n« Voir le site » et bascule clair/sombre.</li>\n</ul>\n<h2>Tâches courantes</h2>\n<table>\n<thead>\n<tr>\n<th>Je veux…</th>\n<th>J\'y vais</th>\n</tr>\n</thead>\n<tbody>\n<tr>\n<td>Publier une actu / un article / une page</td>\n<td><em>Contenu</em> → le module concerné</td>\n</tr>\n<tr>\n<td>Gérer le forum, les commentaires, la modération</td>\n<td><em>Communauté</em></td>\n</tr>\n<tr>\n<td>Gérer membres, groupes, sessions</td>\n<td><em>Système → Utilisateurs</em></td>\n</tr>\n<tr>\n<td>Régler qui peut faire quoi</td>\n<td><em>Système → Permissions (matrice)</em> — grille rôle × action (vert = autorisé, gris = défaut, rouge = jamais)</td>\n</tr>\n<tr>\n<td>Changer le thème / installer un addon</td>\n<td><em>Système → Thèmes &amp; Addons</em></td>\n</tr>\n<tr>\n<td>Réglages du site (nom, accueil, inscriptions, sécurité, copyright)</td>\n<td><em>Système → Paramètres</em></td>\n</tr>\n<tr>\n<td>Composer les pages à la souris</td>\n<td><em>Système → Live Editor</em></td>\n</tr>\n<tr>\n<td>Sauvegardes, mises à jour, état du site, journal d\'audit</td>\n<td><em>Monitoring</em></td>\n</tr>\n</tbody>\n</table>\n<blockquote>\n<p><strong>Monitoring</strong> réunit la <strong>santé du site</strong> (vérifications d\'intégrité des fichiers, espace disque, infos\nserveur, état des tâches cron), les <strong>sauvegardes</strong> (créer / télécharger / restaurer) et le <strong>journal\nd\'audit</strong> des actions sensibles (réglages, addons, comptes).</p>\n</blockquote>\n<h2>Permissions</h2>\n<p>NeoFrag Reborn fonctionne par <strong>rôles</strong>. La <strong>matrice de permissions</strong> (par module)\nrègle, pour chaque rôle, l\'accès et les actions. Tu assignes les rôles aux membres et\naux groupes depuis <em>Système</em>.</p>\n<p><strong>Exemple — créer un rôle « Modérateur »</strong> :</p>\n<ol>\n<li>Dans <em>Système → Permissions</em>, <strong>crée un rôle</strong> « Modérateur ».</li>\n<li>Sur sa colonne de la <strong>matrice</strong>, coche les actions voulues (modérer le forum, gérer les\ncommentaires…) → <strong>vert = autorisé</strong>.</li>\n<li>Dans <em>Système → Utilisateurs</em>, édite un membre et <strong>assigne-lui ce rôle</strong> : il hérite aussitôt de ces accès.</li>\n</ol>\n<h2>Réglages essentiels</h2>\n<p>Dans <strong>Paramètres</strong> : titre et description du site, favicon, email de contact, page\nd\'accueil, gestion des inscriptions, sécurité anti-bots (captcha), maintenance,\ncopyright, réseaux sociaux. Ces réglages alimentent les thèmes et les widgets.</p>\n', '24', '3', '1', NULL, '2', '2026-09-15 14:28:39', '2026-09-16 00:07:39'),
('28', 'marketplace', 'Marketplace', '<h1>Le marketplace</h1>\n<p>Le <strong>marketplace</strong> sert à <strong>mettre à jour</strong> tes addons et à <strong>ajouter des addons tiers</strong> non livrés\ndans le paquet. NeoFrag Reborn s\'installe déjà <strong>complet</strong> (tous les modules/widgets/thèmes du paquet,\nmodèle « tout bundlé ») ; le marketplace intervient <em>après</em> l\'installation, depuis l\'administration.</p>\n<p>Le catalogue et les archives sont servis depuis <strong>neofrag-reborn.xyz</strong>. Chaque archive est vérifiée\npar <strong>empreinte SHA-256</strong> au téléchargement (intégrité), via HTTPS.</p>\n<h2>Installer un addon tiers</h2>\n<p>Deux chemins :</p>\n<h3>1. En un clic depuis l\'administration</h3>\n<p><strong>Admin → Thèmes &amp; Addons → Marketplace</strong>. La fenêtre liste les addons disponibles non installés ;\ncoche-les et clique <strong>Installer</strong> — NeoFrag télécharge, vérifie l\'empreinte SHA-256, extrait et\nenregistre l\'addon (ainsi que son widget apparié, le cas échéant).</p>\n<h3>2. Manuellement (archive ZIP)</h3>\n<p>La page <strong>Marketplace</strong> (front) présente chaque addon en fiche avec un bouton <strong>Télécharger</strong>. Tu peux\naussi : télécharger le <code>.zip</code>, puis <strong>Admin → Thèmes &amp; Addons → Ajouter</strong> et envoyer l\'archive (l\'upload\nest validé : les archives au contenu non sûr sont refusées).</p>\n<h2>Mettre à jour ou retirer</h2>\n<ul>\n<li><strong>Mettre à jour</strong> : <strong>Admin → Thèmes &amp; Addons → Mises à jour</strong> compare la version installée de chaque\naddon à celle du catalogue et liste celles à mettre à jour ; applique-les en un clic (téléchargement +\nSHA-256, fichiers remplacés, <strong>migrations de schéma en attente appliquées</strong>). Sinon, envoyer un nouveau\n<code>.zip</code> via <em>Ajouter</em> met aussi à jour (une version supérieure remplace l\'ancienne).</li>\n<li><strong>Retirer</strong> : depuis la fiche de l\'addon dans <strong>Thèmes &amp; Addons</strong>, <strong>Supprimer</strong> (l\'addon doit être\ndésactivé). Ses tables et données sont retirées.</li>\n</ul>\n<blockquote>\n<p>Les <strong>connecteurs d\'authentification</strong> (Discord, GitHub, Google) font partie du <strong>cœur</strong> : ils sont\ndéjà présents, à configurer dans les réglages — ils ne passent pas par le marketplace.</p>\n</blockquote>\n<blockquote>\n<p><strong>Marketplace injoignable ?</strong> Si l\'écran « Mises à jour » affiche « Marketplace injoignable », vérifie\nque ton serveur peut sortir en <strong>HTTPS</strong> vers <code>neofrag-reborn.xyz</code>. Le catalogue est fixé à cette origine ;\nun administrateur peut la surcharger (vers un hôte autorisé, en HTTPS) via le réglage <code>nf_marketplace_url</code>.</p>\n</blockquote>\n<h2>Sécurité</h2>\n<p>Le marketplace ne télécharge que depuis une <strong>origine fixe</strong> en <strong>HTTPS</strong> (jamais une URL saisie par\nl\'utilisateur), vérifie le <strong>SHA-256</strong> de chaque archive contre le catalogue, et <strong>refuse toute archive\npiégée</strong> (anti-zip-slip : chemins absolus, <code>..</code>, symlinks). Aucun code distant n\'est exécuté lors de\nl\'ajout/mise à jour d\'un addon (extraction + <code>install.sql</code>/migrations SQL idempotents uniquement).</p>\n<h2>Pour les auteurs d\'addons</h2>\n<p>Le catalogue est généré depuis le dépôt par <code>tools/package-addons.php</code>, qui zippe chaque addon\n(dossier <code>&lt;name&gt;/</code> à la racine de l\'archive) et produit <code>marketplace/catalog.json</code> :</p>\n<pre><code class=\"language-json\">{\n  &quot;schema&quot;: 1, &quot;base_version&quot;: &quot;1.0.0&quot;,\n  &quot;addons&quot;: [{\n    &quot;type&quot;: &quot;module&quot;, &quot;name&quot;: &quot;wiki&quot;, &quot;tier&quot;: 2, &quot;category&quot;: &quot;contenu&quot;,\n    &quot;title&quot;: &quot;Wiki&quot;, &quot;version&quot;: &quot;1.0&quot;, &quot;file&quot;: &quot;modules/wiki.zip&quot;,\n    &quot;size&quot;: 15114, &quot;sha256&quot;: &quot;…&quot;, &quot;provides_widgets&quot;: []\n  }]\n}\n</code></pre>\n<p><code>catalog.json</code> et les <code>.zip</code> doivent être publiés <strong>ensemble</strong> (jeu cohérent du même run : les empreintes\nSHA-256 dépendent du run). Pour proposer ton addon, suis les guides\n<a href=\"create-a-module\">créer un module</a>, <a href=\"create-a-widget\">un widget</a> ou\n<a href=\"create-a-theme\">un thème</a>, puis zippe son dossier.</p>\n<h2>Héberger le catalogue (opérateur)</h2>\n<p>Le marketplace est un <strong>jeu de fichiers statiques</strong> servi sur le domaine de la marketplace : <code>catalog.json</code></p>\n<ul>\n<li>les <code>.zip</code> (rangés sous <code>modules/</code>, <code>widgets/</code>, <code>themes/</code>).</li>\n</ul>\n<ol>\n<li><code>php tools/package-addons.php</code> → (re)génère <code>marketplace/catalog.json</code> + les zips à jour.</li>\n<li>Uploade le contenu de <code>marketplace/</code> à la racine du site → <code>https://&lt;host&gt;/marketplace/catalog.json</code>.</li>\n<li>Le CMS pointe vers ce catalogue via <strong><code>nf_marketplace_url</code></strong> (défaut : <code>https://neofrag-reborn.xyz/marketplace</code>,\n<strong>sans <code>www</code></strong>). Origines autorisées (anti-SSRF, HTTPS:443) : <code>neofrag-reborn.xyz</code> et <code>www.neofrag-reborn.xyz</code> ;\npour un autre domaine, adapte <code>nf_marketplace_url</code> <strong>et</strong> <code>MARKETPLACE_HOSTS</code> dans <code>install/lib/installer.php</code>.</li>\n</ol>\n<p>À refaire <strong>à chaque changement d\'addon</strong> (version ou fichiers) : les SHA-256 du catalogue doivent\ncorrespondre aux zips publiés (même run).</p>\n<h2>Mise à jour du cœur (NeoFrag lui-même)</h2>\n<p>Le catalogue porte <code>base_version</code> (la version du CMS pour laquelle il a été bâti). <strong>Admin → Thèmes &amp; Addons\n→ Mises à jour</strong> la compare à la version installée et <strong>signale</strong> une nouvelle version du cœur le cas échéant.</p>\n<p>Deux chemins pour l\'appliquer.</p>\n<h3>En un clic depuis le Monitoring</h3>\n<p><strong>Admin → Monitoring → Mettre à jour</strong>. Le site prend d\'abord une <strong>sauvegarde</strong>, puis télécharge le paquet\nde mise à jour, vérifie son empreinte, superpose les fichiers, applique les migrations en attente et\nrecompile les feuilles de style.</p>\n<p>Ce bouton était désactivé sur ce fork (<code>NEOFRAG_ALLOW_AUTOUPDATE</code>) parce que l\'ancien mécanisme téléchargeait\nla release <strong>upstream</strong> (<code>neofrag.download</code>) et l\'étalait par-dessus, ce qui aurait écrasé le code Reborn\ndivergé. Un interrupteur global empêchait toutefois aussi les mises à jour légitimes. Il est remplacé par\nquatre garanties de nature :</p>\n<ol>\n<li>l\'<strong>origine</strong> vient de la même allow-list que le marketplace — <code>neofrag.download</code> n\'y est pas, et une\nvaleur injectée en base ne peut pas l\'y faire entrer ;</li>\n<li><code>version.json</code> ne fournit qu\'un <strong>nom de fichier</strong>, jamais une URL : ni hôte, ni chemin, donc ni\nredirection ni remontée de répertoire ;</li>\n<li>l\'empreinte <strong>SHA-256</strong> est vérifiée <strong>avant</strong> qu\'un seul fichier du site ne soit touché ;</li>\n<li>l\'archive est contrôlée <strong>entrée par entrée</strong> (anti-zip-slip, symlinks refusés).</li>\n</ol>\n<p><code>config/</code> et <code>install/</code> ne sont jamais réécrits quand ils existent déjà : la configuration d\'un site en\nservice est préservée.</p>\n<h3>À la main</h3>\n<p>Télécharge la release et remplace les fichiers (hors <code>config/</code>, <code>upload/</code>, <code>backups/</code>), puis visite le\nsite — les migrations s\'appliquent.</p>\n<h3>Publier une mise à jour (opérateur)</h3>\n<p><code>php tools/build-release.php</code> produit, en plus des paquets d\'installation, <strong>trois fichiers à publier\nensemble</strong> sur l\'origine de mise à jour (<code>https://neofrag-reborn.xyz/update/</code> par défaut, surchargeable\nvia <code>nf_monitoring_check_url</code> vers un hôte autorisé) :</p>\n<table>\n<thead>\n<tr>\n<th>Fichier</th>\n<th>Rôle</th>\n</tr>\n</thead>\n<tbody>\n<tr>\n<td><code>neofrag-reborn-update-&lt;v&gt;.zip</code></td>\n<td>le paquet, <strong>à plat</strong> (aucun dossier racine)</td>\n</tr>\n<tr>\n<td><code>version.json</code></td>\n<td>version publiée, nom du zip, son SHA-256, sa taille</td>\n</tr>\n<tr>\n<td><code>checksum.json</code></td>\n<td>une empreinte MD5 par fichier livré, pour le contrôle d\'intégrité du Monitoring</td>\n</tr>\n</tbody>\n</table>\n<blockquote>\n<p>Le paquet de mise à jour est <strong>plat</strong>, contrairement aux paquets d\'installation qui rangent tout sous\n<code>neofrag-reborn/</code>. C\'est essentiel : l\'updater écrit chaque entrée à son propre chemin, donc un paquet\nà dossier racine créerait un sous-dossier <code>neofrag-reborn/</code> au lieu de remplacer quoi que ce soit — la\nmise à jour « réussirait » sans rien mettre à jour.</p>\n</blockquote>\n<blockquote>\n<p>Les trois fichiers forment un <strong>jeu cohérent d\'un même run</strong> : le SHA-256 de <code>version.json</code> et les\nempreintes de <code>checksum.json</code> ne valent que pour ce zip précis. Publier l\'un sans les autres fait\néchouer la vérification côté site.</p>\n</blockquote>\n', '24', '4', '1', NULL, '2', '2026-09-15 14:28:39', '2026-09-16 00:07:39'),
('29', 'guide-developpeur', 'Guide développeur', '<h1>Guide développeur</h1>\n<p>Étends NeoFrag Reborn : crée tes <strong>thèmes</strong>, <strong>widgets</strong> et <strong>modules</strong>, et maîtrise le framework.</p>\n', NULL, '2', '1', NULL, '2', '2026-09-15 14:28:39', '2026-09-16 00:07:38'),
('30', 'create-a-theme', 'Créer un thème', '<h1>Créer un thème</h1>\n<p>Un <strong>thème</strong> donne au site son identité visuelle complète : la mise en page (navigation, régions, pied\nde page), la charte (couleurs, typographies) et les <strong>dispositions</strong> par défaut (quels widgets, où).</p>\n<p>Nous esquissons un thème <code>aurora</code>. Le plus simple pour démarrer est de <strong>cloner <code>themes/nebula/</code></strong> (le\nthème public livré) puis d\'adapter. Tout ce qui suit est vérifié contre le code de NeoFrag Reborn 1.1.0.</p>\n<h2>Structure des fichiers</h2>\n<pre><code>themes/aurora/\n├── aurora.php                # classe : zones, régions, assets, dispositions\n├── views/\n│   ├── body.tpl.php          # le squelette de page (rend les régions)\n│   └── live_editor/          # row.tpl.php, widget.tpl.php : styles proposés dans l\'éditeur en direct\n├── css/\n│   ├── style.css             # la charte — définit TOUT le vocabulaire --nf-*\n│   └── sass/                 # optionnel : SCSS compilé côté serveur\n├── js/\n│   ├── theme.js              # mode clair/sombre du thème\n│   └── aurora.js             # interactions du thème\n├── images/\n│   └── thumbnail.jpg         # vignette 480 × 270 pour la carte d\'addon\n└── install/\n    └── migrations/           # évolutions des dispositions livrées (optionnel)\n</code></pre>\n<h2>1. La classe — <code>aurora.php</code></h2>\n<pre><code class=\"language-php\">&lt;?php\nnamespace NF\\Themes\\Aurora;\n\nuse NF\\NeoFrag\\Addons\\Theme;\n\nclass Aurora extends Theme\n{\n    protected function __info()\n    {\n        return [\n            \'title\'       =&gt; \'Aurora\',\n            \'description\' =&gt; $this-&gt;lang(\'Thème communautaire sombre et néon.\'),\n            \'author\'      =&gt; \'Ton Nom\',\n            \'license\'     =&gt; \'Creative Commons CC BY-NC-SA 4.0\',\n            \'version\'     =&gt; \'1.0.0\',\n\n            // Déclarations de découplage — OBLIGATOIRES.\n            \'core\'        =&gt; FALSE,\n            \'presets\'     =&gt; [],\n            \'requires\'    =&gt; [],\n\n            // Les zones, dans l\'ordre, par leur TITRE (c\'est ce titre qu\'emploie install()) …\n            \'zones\'       =&gt; [\'Header\', \'Avant-contenu\', \'Contenu\', \'Post-contenu\', \'Footer\'],\n            // … et les RÉGIONS nommées que les gabarits rendent. OBLIGATOIRE pour un thème public.\n            \'regions\'     =&gt; [\n                \'header\'         =&gt; \'Header\',\n                \'before_content\' =&gt; \'Avant-contenu\',\n                \'content\'        =&gt; \'Contenu\',\n                \'after_content\'  =&gt; \'Post-contenu\',\n                \'footer\'         =&gt; \'Footer\',\n            ],\n        ];\n    }\n\n    public function __init()\n    {\n        // Les assets de chaque page, dans l\'ordre d\'inclusion. Ceux du cœur d\'abord : Bootstrap 5, le\n        // socle du produit (css/nf-bs5-bridge.css : ses composants et les couleurs de Bootstrap tirées\n        // de la palette du thème), FontAwesome ; puis la charte du thème ; puis les scripts.\n        $this-&gt;css(\'bootstrap.min\')-&gt;css(\'nf-bs5-bridge\')\n             -&gt;css(\'icons/fontawesome.min\')\n             -&gt;css(\'style\')\n             -&gt;js(\'bootstrap.bundle.min\')\n             -&gt;js(\'modal\')-&gt;js(\'notify\')-&gt;js(\'confirm\')\n             -&gt;js(\'theme\')-&gt;js(\'aurora\');\n    }\n\n    public function styles_row()    { return $this-&gt;view(\'live_editor/row\'); }\n    public function styles_widget() { return $this-&gt;view(\'live_editor/widget\'); }\n\n    public function install($dispositions = [])\n    {\n        $dispositions = $this-&gt;array();\n        // … dispositions par défaut (voir §3) …\n        return parent::install($dispositions);\n    }\n}\n</code></pre>\n<ul>\n<li><code>zones</code> déclare les zones dans l\'ordre ; <code>regions</code> leur donne un <strong>nom stable</strong> que les gabarits\nemploient. <code>tools/check-addon-contracts.php</code> <strong>exige</strong> la clé <code>regions</code> sur tout thème public : c\'est\nla fondation du chantier page-builder, et un gabarit qui rendrait <code>zone(2)</code> dépendrait de l\'ordre.</li>\n<li><strong>Pas de jQuery</strong> : il n\'est plus chargé par le cœur, et <code>tools/check-js-sources.php</code> refuse tout\nscript qui l\'appelle. Bootstrap 5 se charge par <code>bootstrap.bundle.min</code> (Popper inclus).</li>\n<li><code>styles_row()</code> / <code>styles_widget()</code> rendent les listes de styles de lignes et de panneaux proposées\ndans l\'éditeur en direct ; copie <code>themes/nebula/views/live_editor/</code> pour commencer.</li>\n</ul>\n<h2>2. Le squelette — <code>views/body.tpl.php</code></h2>\n<p>Le <code>body.tpl.php</code> est le HTML de la page. Il <strong>rend les régions</strong> par leur nom :</p>\n<pre><code class=\"language-php\">&lt;nav class=&quot;au-nav&quot;&gt;\n    &lt;a href=&quot;&lt;?php echo url(\'\') ?&gt;&quot;&gt;&lt;?php echo htmlspecialchars($this-&gt;config-&gt;nf_name) ?&gt;&lt;/a&gt;\n    &lt;?php echo $this-&gt;output-&gt;region(\'header\') ?&gt;\n&lt;/nav&gt;\n\n&lt;main&gt;\n    &lt;?php if ($zone = $this-&gt;output-&gt;region(\'before_content\')): ?&gt;&lt;section class=&quot;au-banner&quot;&gt;&lt;?php echo $zone ?&gt;&lt;/section&gt;&lt;?php endif ?&gt;\n    &lt;?php if ($zone = $this-&gt;output-&gt;region(\'content\')): ?&gt;&lt;div class=&quot;container&quot;&gt;&lt;?php echo $zone ?&gt;&lt;/div&gt;&lt;?php endif ?&gt;\n    &lt;?php if ($zone = $this-&gt;output-&gt;region(\'after_content\')): ?&gt;&lt;div class=&quot;container&quot;&gt;&lt;?php echo $zone ?&gt;&lt;/div&gt;&lt;?php endif ?&gt;\n&lt;/main&gt;\n\n&lt;footer&gt;\n    &lt;?php echo $this-&gt;output-&gt;region(\'footer\') ?&gt;\n&lt;/footer&gt;\n</code></pre>\n<p>Le garde <code>if ($zone = …)</code> dit la vérité : une région déclarée mais <strong>sans widget</strong> rend une chaîne vide\n(depuis le 2026-09-17 ; avant, des blancs faisaient dessiner un bandeau décoré autour de rien). Tu peux\ndonc envelopper chaque région d\'un conteneur stylé sans risque.</p>\n<blockquote>\n<p><strong>Détecter l\'accueil</strong> : <code>((string) $this-&gt;url-&gt;request === \'\')</code>.</p>\n</blockquote>\n<p>Tu es libre : soit tu rends les régions telles quelles (navigation pilotée par les widgets de la région\n<code>header</code>), soit tu <strong>codes une barre de navigation ou un pied de page sur mesure</strong> dans le gabarit, comme\nNebula, pour une identité unique. Si tu écris un pied de page, <strong>ne pose pas aussi</strong> un widget de\ncopyright dans la région <code>footer</code> de tes dispositions : le thème Extend l\'affichait deux fois.</p>\n<p>Si tu proposes aux visiteurs de changer de thème, écris <code>&lt;?php echo nf_selecteur_theme() ?&gt;</code> dans ton pied\nde page : le cœur rend le menu des thèmes installés (hors <code>admin</code>) et retient le choix dans un cookie propre\nau site. Il ne s\'affiche que s\'il y a plus d\'un thème public, et que l\'administrateur n\'a pas fermé le\nchoix (<strong>Préférences générales → Choix du thème</strong>).</p>\n<h2>3. Les dispositions par défaut — <code>install()</code></h2>\n<p><code>install()</code> décrit, par motif de page et par <strong>titre de zone</strong>, la grille de widgets posée à\nl\'installation du thème :</p>\n<pre><code class=\"language-php\">// Toutes les pages : module principal (8 colonnes) + colonne latérale (4 colonnes).\n$dispositions-&gt;set(\'*\', \'Contenu\', $this-&gt;array([\n    $this-&gt;row(\n        $this-&gt;col(\n            $this-&gt;widget($this-&gt;db-&gt;insert(\'nf_widgets\', [\'widget\' =&gt; \'module\', \'type\' =&gt; \'index\']))\n        )-&gt;size(\'col-12 col-lg-8\'),\n        $this-&gt;col(\n            $this-&gt;widget($this-&gt;db-&gt;insert(\'nf_widgets\', [\'widget\' =&gt; \'user\', \'type\' =&gt; \'index\']))-&gt;style(\'panel-color\')\n        )-&gt;size(\'col-12 col-lg-4\')\n    )-&gt;style(\'row-default\')\n]));\n\n// Accueil uniquement : un carrousel pleine largeur avant le contenu.\n$dispositions-&gt;set(\'/\', \'Avant-contenu\', $this-&gt;array([\n    $this-&gt;row($this-&gt;col(\n        $this-&gt;widget($this-&gt;db-&gt;insert(\'nf_widgets\', [\'widget\' =&gt; \'slider\', \'type\' =&gt; \'index\']))\n    ))-&gt;style(\'row-default\')\n]));\n</code></pre>\n<ul>\n<li><code>set($page, $zone, [...lignes])</code> : <code>$page</code> est un motif (<code>*</code>, <code>/</code>, <code>forum/*</code>…), le plus précis l\'emporte.</li>\n<li><code>$this-&gt;widget($id)</code> reçoit l\'<strong>id</strong> d\'une ligne <code>nf_widgets</code> fraîchement insérée (widget, type,\nréglages en JSON). Un widget posé <strong>sans réglages</strong> doit s\'en sortir : c\'est le rôle de son checker.</li>\n<li><code>-&gt;size(\'col-12 col-lg-8\')</code> — grille Bootstrap 5, <strong>toujours avec un point de rupture</strong> : <code>col-8</code> seul\ns\'applique dès 0 px et écrase la page sur téléphone. <code>-&gt;style(\'row-default\' | \'row-dark\')</code> et\n<code>-&gt;style(\'panel-default\' | \'panel-color\' | \'panel-header\')</code> stylent lignes et panneaux.</li>\n<li>Ne pose dans les dispositions livrées que des widgets <strong>du cœur</strong> : le paquet s\'installe à la carte,\nun widget optionnel peut ne pas être là.</li>\n</ul>\n<p>Une installation existante ne rejoue jamais <code>install()</code> : pour corriger une disposition livrée chez ceux\nqui ont déjà le thème, écris une <strong>migration de thème</strong> (<code>install/migrations/AAAA_MM_JJ_nom.up.sql</code>,\nsuivie dans <code>nf_addon_migrations</code>, jouée à la mise à jour). Exemple réel :\n<code>themes/extend/install/migrations/</code>.</p>\n<h2>4. La charte — <code>css/style.css</code></h2>\n<p><strong>Chaque thème définit la totalité du vocabulaire <code>--nf-*</code></strong>, dans <code>:root</code> (et dans sa variante claire\nou sombre). Les feuilles des modules et des widgets n\'emploient que ce vocabulaire ; une variable qu\'un\nthème oublie retombe sur un repli — des <strong>blocs blancs</strong> en thème sombre sont apparus ainsi.\n<code>tools/check-css-variables.php</code> refuse toute variable employée par un module ou un widget que <strong>tous</strong>\nles thèmes ne définissent pas. Le vocabulaire complet — vingt-six jetons — avec des valeurs <strong>d\'exemple</strong>\n(Nebula, lui, fait pointer chacun vers sa palette privée <code>--fg-*</code>, ce qui lui permet d\'avoir un mode\nclair et un mode sombre en ne changeant que celle-ci) :</p>\n<pre><code class=\"language-css\">:root {\n    /* fonds et surfaces */\n    --nf-bg: #070a10;            --nf-bg-elevated: #0d1220;\n    --nf-surface: rgba(255,255,255,.035);  --nf-surface-2: rgba(255,255,255,.06);  --nf-surface-3: rgba(255,255,255,.09);\n    --nf-hover: rgba(255,255,255,.06);\n    /* traits */\n    --nf-border: rgba(255,255,255,.09);    --nf-border-strong: rgba(255,255,255,.18);\n    /* textes */\n    --nf-text: #e7eef6;  --nf-text-strong: #ffffff;  --nf-text-soft: #b7c2d0;  --nf-text-muted: #8593a6;\n    --nf-muted: #8593a6; --nf-muted-2: #66748a;\n    /* accent — et la couleur qui se pose DESSUS */\n    --nf-accent: #2dd4bf;  --nf-accent-soft: rgba(45,212,191,.15);  --nf-accent-strong: #14b8a6;  --nf-accent-text: #99f6e4;\n    --nf-on-accent: #041014;\n    /* états */\n    --nf-success: #22c55e;  --nf-info: #38bdf8;  --nf-warning: #f59e0b;  --nf-danger: #ef4444;\n    --nf-danger-soft: rgba(239,68,68,.18);\n    /* formes */\n    --nf-radius: 14px;  --nf-radius-sm: 8px;\n}\n</code></pre>\n<p>Deux pièges mesurés sur les thèmes livrés :</p>\n<ul>\n<li><strong><code>--nf-on-accent</code> se recalcule quand l\'accent change.</strong> Si ta seconde palette (mode clair, variante)\nremplace <code>--nf-accent</code>, redéfinis aussi la couleur posée dessus, sinon le texte des boutons garde\ncelle de l\'autre accent — du blanc sur du turquoise, 1,86:1. <code>tools/check-contraste.php</code> mesure le\ncontraste WCAG de tous les thèmes dans les deux modes.</li>\n<li><strong>Pas de <code>.row { margin: -15px }</code></strong> : c\'est la gouttière de Bootstrap 4 ; Bootstrap 5 emploie −12 px\nvia <code>--bs-gutter-x</code>, et les 3 px d\'écart font déborder la page. <code>tools/check-responsive.php</code> mesure\nle débordement horizontal à 390, 768 et 1400 px ; <code>tools/check-classes-bs4.php</code> refuse les classes\ndisparues de Bootstrap <strong>3 et 4</strong> (<code>card-columns</code>, <code>float-right</code>, <code>panel-body</code>, <code>badge-danger</code>…),\ndans les gabarits comme dans les feuilles de style — redéfinir <code>.badge-danger</code> dans ton thème ne\nla fait pas revivre, le contrôle la refuse —, les attributs <code>data-*</code> restés sans le préfixe <code>bs</code> —\nun <code>data-target</code> ne fait plus rien, en silence — et les classes que le code <strong>fabrique</strong> par\nconcaténation. Un thème doit employer les noms de Bootstrap 5 : <code>float-end</code>, <code>text-start</code>, <code>g-0</code>,\n<code>ms-2</code>, <code>bg-danger-subtle text-danger-emphasis</code>. Pour teinter ces couleurs à ta charte, redéfinis\nles variables de Bootstrap (<code>--bs-danger-text-emphasis</code>…) plutôt que les classes.</li>\n</ul>\n<p>Le CSS peut être un <strong>gabarit PHP</strong> ; le <code>?v=</code> est basé sur la date du fichier, toute édition invalide\nle cache. Un dossier <code>css/sass/</code> est compilé côté serveur (scssphp) à l\'installation et depuis\nAdministration → Outils.</p>\n<p><strong>Mode clair / sombre.</strong> Chaque thème gère le sien dans <code>js/theme.js</code> (attribut <code>data-theme</code> sur\n<code>&lt;html&gt;</code>, préférence mémorisée côté navigateur sous <strong>sa propre clé</strong>, pas une clé commune). Un thème\n« sombre seulement » force <code>document.documentElement.setAttribute(\'data-theme\', \'dark\')</code>.</p>\n<h2>5. La vignette — <code>images/thumbnail.jpg</code></h2>\n<p>La carte du thème dans l\'administration et le catalogue attend <code>images/thumbnail.jpg</code> en <strong>480 × 270</strong>.\nFais-en une <strong>vraie capture</strong> de l\'accueil, pas une maquette : <code>tools/capture-vignettes.php</code> le fait pour\ntous les thèmes installés et refuse deux vignettes identiques.</p>\n<h2>6. Installer, activer, éprouver</h2>\n<ol>\n<li>Dépose <code>themes/aurora/</code>.</li>\n<li><strong>Administration → Thèmes &amp; Addons → Scanner le disque</strong> → coche <code>aurora</code> → installe.</li>\n<li><strong>Activer</strong> sur sa fiche (définit le thème par défaut). Si le thème a été enregistré sans dispositions,\nl\'activation lance <code>install()</code> d\'elle-même. Le bouton <strong>Réinstaller par défaut</strong> ré-exécute <code>install()</code>.</li>\n<li>Avant de livrer, fais passer : <code>check-addon-declarations</code>, <code>check-addon-contracts</code> (la clé <code>regions</code>),\n<code>check-css-variables</code>, <code>check-classes-bs4</code>, <code>check-js-sources</code>, puis <code>check-responsive</code>,\n<code>check-contraste</code> et <code>check-js-console</code> sur une installation où le thème est actif. Regarde la page\nrendue : un bandeau vide, un copyright en double ou un commentaire illisible ne se voient pas dans le\ncode.</li>\n</ol>\n<h2>Bonnes pratiques</h2>\n<ul>\n<li><strong>Polices</strong> : Google Fonts est autorisé par la politique de sécurité (feuilles) ; préfère un <code>&lt;link&gt;</code> à\nun <code>@import</code> en tête de CSS, qui bloque le rendu.</li>\n<li><strong>Aucun script depuis un CDN</strong> : la CSP stricte (<code>script-src \'self\' \'nonce-…\'</code>) le refuserait, et le\nprojet ne dépend d\'aucun tiers. Tout JS vit dans le thème ou le cœur.</li>\n<li><strong>Crédite</strong> le projet d\'origine (NeoFrag, Michaël BILCOT &amp; Jérémy VALENTIN, LGPLv3) si tu pars d\'un\nthème existant.</li>\n</ul>\n', '29', '1', '1', NULL, '2', '2026-09-15 14:28:39', '2026-09-16 00:07:39'),
('31', 'create-a-widget', 'Créer un widget', '<h1>Créer un widget</h1>\n<p>Un <strong>widget</strong> est un bloc réutilisable plaçable dans n\'importe quelle zone d\'un thème, avec l\'éditeur\nen direct. C\'est l\'addon le plus simple à écrire : une classe, un contrôleur, une vue.</p>\n<p>Nous allons créer un widget <code>hello</code> qui affiche un message de bienvenue paramétrable. Tout est vérifié\ncontre le code de NeoFrag Reborn 1.1.0 ; <code>widgets/html</code> et <code>widgets/about</code> sont de bons exemples réels.</p>\n<h2>Structure des fichiers</h2>\n<pre><code>widgets/hello/\n├── hello.php                 # la classe du widget (métadonnées, déclarations, types)\n├── controllers/\n│   ├── index.php             # le contrôleur : prépare et rend la vue\n│   ├── checker.php           # valide et complète les réglages (recommandé dès qu\'il y a des réglages)\n│   └── admin.php             # le formulaire de réglages (optionnel)\n├── views/\n│   ├── index.tpl.php         # le gabarit HTML\n│   └── admin.tpl.php         # gabarit des réglages (optionnel)\n├── css/\n│   └── hello.css             # styles (optionnel)\n└── js/\n    └── hello.js              # optionnel — vanilla, jamais jQuery\n</code></pre>\n<h2>1. La classe — <code>hello.php</code></h2>\n<pre><code class=\"language-php\">&lt;?php\nnamespace NF\\Widgets\\Hello;\n\nuse NF\\NeoFrag\\Addons\\Widget;\n\nclass Hello extends Widget\n{\n    protected function __info()\n    {\n        return [\n            \'title\'       =&gt; $this-&gt;lang(\'Bienvenue\'),\n            \'description\' =&gt; $this-&gt;lang(\'Affiche un message de bienvenue personnalisable.\'),\n            \'icon\'        =&gt; \'fas fa-hand-spock\',   // OBLIGATOIRE, et doit exister dans le FontAwesome embarqué\n            \'author\'      =&gt; \'Ton Nom\',\n            \'license\'     =&gt; \'LGPLv3\',\n            \'version\'     =&gt; \'1.0.0\',\n\n            // Déclarations de découplage — OBLIGATOIRES (la CI refuse un addon muet).\n            \'core\'        =&gt; FALSE,\n            \'presets\'     =&gt; [\'gaming\', \'communaute\'],\n            \'requires\'    =&gt; [],                     // ex. [\'teams\'] si le widget lit nf_teams\n\n            // Optionnel : plusieurs types = plusieurs méthodes du contrôleur, choisies à la pose.\n            \'types\'       =&gt; [\n                \'index\' =&gt; $this-&gt;lang(\'Message\'),\n            ],\n        ];\n    }\n}\n</code></pre>\n<ul>\n<li>Le <code>namespace</code> <strong>doit</strong> suivre le dossier : <code>NF\\Widgets\\&lt;Name&gt;</code>.</li>\n<li><code>icon</code> est obligatoire : l\'assistant d\'ajout de widget de l\'éditeur en direct présente les widgets par\n<strong>cartes à icône</strong>, et <code>tools/check-addon-declarations.php</code> refuse un nom absent du FontAwesome\nembarqué (des icônes ont été renommées entre la version 5 et la 6).</li>\n<li><code>core</code>, <code>presets</code>, <code>requires</code> : mêmes règles que pour un module — voir\n<a href=\"create-a-module\">Créer un module</a>, « Les trois déclarations de découplage ».</li>\n<li>Les <strong>réglages</strong> ne se déclarent pas dans cette classe mais dans <code>controllers/admin.php</code> (§5).</li>\n</ul>\n<h2>2. Le contrôleur — <code>controllers/index.php</code></h2>\n<pre><code class=\"language-php\">&lt;?php\nnamespace NF\\Widgets\\Hello\\Controllers;\n\nuse NF\\NeoFrag\\Loadables\\Controllers\\Widget as Controller_Widget;\n\nclass Index extends Controller_Widget\n{\n    public function index($settings = [])\n    {\n        return $this-&gt;css(\'hello\')-&gt;view(\'index\', [\n            \'message\' =&gt; $settings[\'message\'],\n        ]);\n    }\n}\n</code></pre>\n<p>Le contrôleur reçoit les <code>$settings</code> — <strong>déjà validés et complétés par le checker</strong> (§6) —, prépare les\ndonnées, charge son CSS et rend sa vue. Le chaînage <code>-&gt;css(\'hello\')-&gt;view(\'index\', $data)</code> est le\nmotif standard. Un widget à plusieurs <code>types</code> a une méthode par type.</p>\n<h2>3. La vue — <code>views/index.tpl.php</code></h2>\n<pre><code class=\"language-php\">&lt;div class=&quot;hello-widget&quot;&gt;\n    &lt;i class=&quot;fas fa-hand-spock&quot;&gt;&lt;/i&gt;\n    &lt;span&gt;&lt;?php echo htmlspecialchars($message) ?&gt;&lt;/span&gt;\n&lt;/div&gt;\n</code></pre>\n<p><strong>Échappe toujours</strong> ce qui vient d\'un réglage ou de la base (<code>htmlspecialchars</code>). Un titre écrit par un\nmembre posé en <code>innerHTML</code> côté JS deviendrait une injection stockée, servie à tous.</p>\n<h2>4. Le CSS — <code>css/hello.css</code> (optionnel)</h2>\n<pre><code class=\"language-css\">.hello-widget { display: flex; align-items: center; gap: 10px; padding: 14px 16px; color: var(--nf-text); }\n.hello-widget i { color: var(--nf-accent); }\n</code></pre>\n<p><strong>N\'emploie que le vocabulaire partagé <code>--nf-*</code></strong>, celui que <strong>tous</strong> les thèmes définissent :\n<code>--nf-bg</code>, <code>--nf-bg-elevated</code>, <code>--nf-surface</code>, <code>--nf-surface-2</code>, <code>--nf-surface-3</code>, <code>--nf-hover</code>,\n<code>--nf-border</code>, <code>--nf-border-strong</code>, <code>--nf-text</code>, <code>--nf-text-strong</code>, <code>--nf-text-soft</code>,\n<code>--nf-text-muted</code>, <code>--nf-muted</code>, <code>--nf-muted-2</code>, <code>--nf-accent</code>, <code>--nf-accent-soft</code>,\n<code>--nf-accent-strong</code>, <code>--nf-accent-text</code>, <code>--nf-on-accent</code>, <code>--nf-success</code>, <code>--nf-info</code>,\n<code>--nf-warning</code>, <code>--nf-danger</code>, <code>--nf-danger-soft</code>, <code>--nf-radius</code>, <code>--nf-radius-sm</code>.</p>\n<p>Une variable qu\'un thème ne définirait pas retomberait sur son repli — c\'est ainsi que des <strong>blocs\nblancs</strong> sont apparus en thème sombre. <code>tools/check-css-variables.php</code> refuse toute variable employée\npar un widget ou un module que tous les thèmes ne définissent pas. Ne pose pas de valeur de repli :\nelle masquerait le défaut au lieu de le révéler.</p>\n<p>Grille : <code>col-12 col-lg-6</code>, jamais <code>col-6</code> seul (Bootstrap 5 l\'applique dès 0 px).</p>\n<h2>5. Les réglages — <code>controllers/admin.php</code> (optionnel)</h2>\n<p>Pour qu\'un widget soit <strong>configurable</strong>, ajoute <code>controllers/admin.php</code> : il rend le formulaire de\nréglages, et ses champs alimentent les <code>$settings</code> que reçoit le contrôleur <code>index</code>. La classe étend\n<code>Controller</code> (pas <code>Widget</code>) :</p>\n<pre><code class=\"language-php\">&lt;?php\nnamespace NF\\Widgets\\Hello\\Controllers;\n\nuse NF\\NeoFrag\\Loadables\\Controller;\n\nclass Admin extends Controller\n{\n    public function index($settings = [])\n    {\n        $settings = (array) $settings + [\'message\' =&gt; \'\'];\n\n        return $this-&gt;view(\'admin\', [\n            \'message\' =&gt; $settings[\'message\'] ?: $this-&gt;lang(\'Bienvenue sur le site !\'),\n        ]);\n    }\n}\n</code></pre>\n<p>La vue <code>views/admin.tpl.php</code> rend les champs ; chaque champ est nommé <code>settings[&lt;clé&gt;]</code>. Chaque\nligne porte <code>nf-field</code>, la classe de champ du produit : elle donne l\'espacement que chaque thème règle\npour tous les formulaires.</p>\n<pre><code class=\"language-php\">&lt;div class=&quot;nf-field row&quot;&gt;\n    &lt;label for=&quot;settings-message&quot; class=&quot;col-12 col-lg-4 col-form-label&quot;&gt;&lt;?php echo $this-&gt;lang(\'Message\') ?&gt;&lt;/label&gt;\n    &lt;div class=&quot;col-12 col-lg-8&quot;&gt;\n        &lt;input class=&quot;form-control&quot; type=&quot;text&quot; name=&quot;settings[message]&quot; id=&quot;settings-message&quot;\n               value=&quot;&lt;?php echo htmlspecialchars($message) ?&gt;&quot;&gt;\n    &lt;/div&gt;\n&lt;/div&gt;\n</code></pre>\n<p>Dans l\'éditeur en direct, l\'assistant d\'ajout affiche l\'étape « Configuration » <strong>si et seulement si</strong>\nce contrôleur rend quelque chose : un widget sans réglages n\'a simplement pas de <code>controllers/admin.php</code>.</p>\n<h2>6. Valider et compléter les réglages — <code>controllers/checker.php</code></h2>\n<p><strong>Un widget peut arriver sans réglages</strong> : posé par l\'<code>install()</code> d\'un thème, ajouté dans l\'éditeur en\ndirect avant qu\'on ouvre son formulaire, ou restauré depuis une disposition ancienne. Son checker doit\nrendre des réglages utilisables même quand on ne lui en donne aucun. Douze widgets ne le faisaient pas ;\nl\'un employait une clé absente comme diviseur — division par zéro, widget mort.</p>\n<p>La méthode <code>index($settings)</code> reçoit les réglages <strong>bruts</strong> et <strong>retourne</strong> un tableau <strong>validé et\ncomplété de ses défauts</strong>, qui devient les <code>$settings</code> du contrôleur :</p>\n<pre><code class=\"language-php\">&lt;?php\nnamespace NF\\Widgets\\Hello\\Controllers;\n\nuse NF\\NeoFrag\\Loadables\\Controller;\n\nclass Checker extends Controller\n{\n    public function index($settings = [])\n    {\n        // Le repli en tête : `+` ne remplit que les clés absentes, les valeurs fournies restent intactes.\n        $settings = (array) $settings + [\'message\' =&gt; \'\', \'align\' =&gt; \'\'];\n\n        return [\n            \'message\' =&gt; $settings[\'message\'] !== \'\' ? $settings[\'message\'] : $this-&gt;lang(\'Bienvenue !\'),\n            \'align\'   =&gt; in_array($settings[\'align\'], [\'text-start\', \'text-center\', \'text-end\'], TRUE)\n                       ? $settings[\'align\'] : \'text-start\',\n        ];\n    }\n}\n</code></pre>\n<p><code>tools/check-widget-reglages.php</code> refuse toute lecture <code>$settings[\'clé\']</code> qui n\'est ni protégée sur sa\nligne (<code>isset</code>, <code>??</code>, <code>empty</code>, <code>array_key_exists</code>) ni couverte par ce repli en tête de méthode. Exemple\nréel complet : <code>widgets/about/controllers/checker.php</code>.</p>\n<h2>7. Du JavaScript dans un widget</h2>\n<ul>\n<li>Charge-le par <code>-&gt;js(\'hello\')</code> dans le contrôleur ; le fichier est <code>js/hello.js</code>.</li>\n<li><strong>Vanilla, sans jQuery</strong> : <code>NF.ready(fn)</code>, <code>NF.data(el, \'clé\')</code>, <code>NF.ajax({url, method, data, dataType})</code>,\n<code>NF.post(url, data)</code>, <code>NF.setHtml(el, html)</code> (les <code>&lt;script&gt;</code> insérés sont ré-exécutés avec le bon\nnonce). <code>tools/check-js-sources.php</code> refuse <code>$(…)</code>.</li>\n<li>Les adresses AJAX se lisent sur le balisage (<code>data-url=&quot;…&quot;</code>) plutôt qu\'en PHP interpolé dans le <code>.js</code>,\nce qui rend le fichier éprouvable tel quel dans le harnais (<code>tests/Browser/*.test.html</code>).</li>\n<li>Un script qui plante avant d\'attacher ses écouteurs ne casse rien de visible :\n<code>tools/check-js-console.php</code> ouvre les pages dans un vrai navigateur et le voit.</li>\n</ul>\n<h2>8. Installer, placer, distribuer</h2>\n<ol>\n<li>Dépose <code>widgets/hello/</code> sur ton site.</li>\n<li><strong>Administration → Thèmes &amp; Addons → Scanner le disque</strong>, coche <code>hello</code>, installe.</li>\n<li>Dans l\'<strong>éditeur en direct</strong>, ajoute-le dans une zone : l\'assistant propose le widget, son type, un\ntitre, puis sa configuration s\'il en a une. Un widget peut être posé plusieurs fois, avec des réglages\ndifférents.</li>\n<li>Pour le distribuer : zippe le dossier (<code>widgets/hello/</code> à la racine de l\'archive) — il s\'installe via\n<strong>Ajouter (ZIP)</strong> — ou laisse <code>tools/package-addons.php</code> l\'inscrire au catalogue du marketplace.</li>\n</ol>\n<p><code>tools/check-widget-contract.php</code> interroge réellement chaque couple widget/type pour vérifier qu\'aucun\nne casse à la pose.</p>\n', '29', '2', '1', NULL, '2', '2026-09-15 14:28:39', '2026-09-16 00:07:39'),
('32', 'create-a-module', 'Créer un module', '<h1>Créer un module</h1>\n<p>Un <strong>module</strong> est une fonctionnalité complète : ses pages publiques, ses routes, son administration,\nses données et ses permissions. C\'est l\'addon le plus riche.</p>\n<p>Nous allons créer un module <code>notes</code> qui affiche une liste de notes publiques. Tout ce qui suit est\nvérifié contre le code de NeoFrag Reborn 1.1.0 ; les modules livrés (<code>modules/contact</code>, <code>modules/news</code>)\nsont les meilleurs exemples à lire ensuite.</p>\n<h2>Structure des fichiers</h2>\n<pre><code>modules/notes/\n├── notes.php                 # classe : métadonnées, déclarations, routes, permissions\n├── controllers/\n│   ├── checker.php           # valide la route et charge les données\n│   ├── index.php             # rend les pages publiques\n│   └── admin.php             # interface d\'administration (optionnel)\n├── install/\n│   ├── install.sql           # tables du module (joué à l\'installation, idempotent)\n│   ├── uninstall.sql         # suppression des tables\n│   └── migrations/           # évolutions du schéma entre deux versions (optionnel)\n├── views/\n│   └── index.tpl.php\n├── css/                      # optionnel\n├── js/                       # optionnel — vanilla, jamais jQuery\n└── langs/\n    └── fr.php                # traductions (optionnel)\n</code></pre>\n<h2>1. La classe — <code>notes.php</code></h2>\n<pre><code class=\"language-php\">&lt;?php\nnamespace NF\\Modules\\Notes;\n\nuse NF\\NeoFrag\\Addons\\Module;\n\nclass Notes extends Module\n{\n    protected function __info()\n    {\n        return [\n            \'title\'       =&gt; $this-&gt;lang(\'Notes\'),\n            \'description\' =&gt; $this-&gt;lang(\'Petites notes publiques.\'),\n            \'icon\'        =&gt; \'fas fa-note-sticky\',   // doit exister dans le FontAwesome embarqué\n            \'author\'      =&gt; \'Ton Nom\',\n            \'license\'     =&gt; \'LGPLv3\',\n            \'version\'     =&gt; \'1.0.0\',\n            \'admin\'       =&gt; TRUE,                   // expose une page d\'administration\n\n            // Déclarations de découplage — OBLIGATOIRES (la CI refuse un addon muet).\n            \'core\'        =&gt; FALSE,                  // TRUE = livré toujours et non désinstallable\n            \'presets\'     =&gt; [\'communaute\'],         // profils d\'installation qui le pré-cochent\n            \'requires\'    =&gt; [],                     // addons dont il a BESOIN (sans eux, il casse)\n\n            \'routes\'      =&gt; [\n                \'\'                 =&gt; \'index\',       // /notes\n                \'{id}/{url_title}\' =&gt; \'_show\',       // /notes/42/ma-note\n            ],\n        ];\n    }\n}\n</code></pre>\n<h3>Les trois déclarations de découplage</h3>\n<p>Depuis le 2026-09-15, le paquet s\'installe <strong>à la carte</strong> : l\'installeur propose des profils\n(<em>Complet</em>, <em>Gaming / eSport</em>, <em>Communauté</em>, <em>Cœur seul</em>) composés <strong>à partir de ces déclarations</strong>.\nAucune liste n\'est écrite à la main ailleurs.</p>\n<ul>\n<li><code>core</code> — <code>TRUE</code> pour un module d\'infrastructure ou de CMS livré toujours et non désinstallable ;\n<code>FALSE</code> pour tout ce qui est optionnel. Un module du cœur ne peut <strong>jamais</strong> dépendre d\'un\noptionnel (règle 3 de <code>tools/check-addon-declarations.php</code>).</li>\n<li><code>presets</code> — les profils qui le pré-cochent : <code>\'gaming\'</code> et/ou <code>\'communaute\'</code>. Vide : il n\'apparaît\nque dans le profil <em>Complet</em>.</li>\n<li><code>requires</code> — les addons dont il a besoin <strong>pour ne pas casser</strong> : une table lue, une classe nommée.\nL\'installeur les ajoute d\'office quand on coche ton module. Une dépendance <strong>molle</strong> (un service qui\npeut manquer) ne se déclare pas ici : elle se <strong>garde</strong> dans le code (voir §5).</li>\n</ul>\n<p>Ces trois clés sont vérifiées statiquement en CI, ainsi que l\'existence de l\'icône déclarée (règle 7 :\nFontAwesome a renommé des icônes entre la 5 et la 6). Un addon qu\'on ne veut pas au catalogue du\nmarketplace ajoute <code>\'distributed\' =&gt; FALSE</code>.</p>\n<h3>Les routes</h3>\n<p>Une route mappe un <strong>motif d\'URL</strong> vers une <strong>méthode de contrôleur</strong>. Le motif est relatif au nom du\nmodule (<code>\'\'</code> = la page d\'accueil du module, ici <code>/notes</code>). Les routes d\'administration commencent par\n<code>admin</code> (<code>\'admin{pages}\' =&gt; \'index\'</code> pour une liste paginée sous <code>/admin/notes</code>).</p>\n<blockquote>\n<p>Les placeholders sont un ensemble <strong>fixe</strong> : <code>{id}</code> (entier), <code>{key_id}</code>, <code>{url_title}</code> (slug),\n<code>{url_title*}</code> (slug avec des <code>/</code>), <code>{page}</code> et <code>{pages}</code> (pagination). Un placeholder inconnu\nproduit un 404 silencieux. Pour une fiche, le motif idiomatique est <code>{id}/{url_title}</code> : l\'<code>id</code>\nidentifie la fiche, le slug est cosmétique.</p>\n</blockquote>\n<h2>2. Le checker — <code>controllers/checker.php</code></h2>\n<p>Le <strong>checker</strong> s\'exécute avant le contrôleur : il valide la route et charge les données. Renvoyer\n<code>FALSE</code> ou rien déclenche un 404. Ce qu\'il <strong>retourne devient les arguments</strong> de la méthode de même\nnom du contrôleur.</p>\n<pre><code class=\"language-php\">&lt;?php\nnamespace NF\\Modules\\Notes\\Controllers;\n\nuse NF\\NeoFrag\\Loadables\\Controllers\\Module_Checker;\n\nclass Checker extends Module_Checker\n{\n    public function index()\n    {\n        $notes = $this-&gt;db-&gt;select(\'id\', \'title\', \'body\')\n                          -&gt;from(\'nf_notes\')\n                          -&gt;order_by(\'id DESC\')\n                          -&gt;get();\n\n        return [$notes];   // → index($notes)\n    }\n\n    public function _show($id, $url_title)\n    {\n        if ($note = $this-&gt;db-&gt;from(\'nf_notes\')-&gt;where(\'id\', $id)-&gt;row())\n        {\n            return [$note];   // → _show($note)\n        }\n        // rien : 404\n    }\n}\n</code></pre>\n<p>Pour un point d\'entrée qui reçoit un <strong>POST</strong> (AJAX, formulaire écrit à la main), le checker lit les\nchamps avec <code>post_check(\'titre\', \'corps\', \'options?\')</code> : chaque nom est <strong>obligatoire</strong>, sauf s\'il porte\nle suffixe <code>?</code>, auquel cas il vaut <code>NULL</code> s\'il manque. Un champ obligatoire absent fait échouer le\nchecker : réponse <strong>404</strong> en production (le motif exact — quel champ, ce qui est arrivé à la place —\nest <strong>journalisé</strong>) et <strong>400 avec le motif</strong> en mode debug. Ce comportement vient de dix widgets qu\'on\nne pouvait plus ajouter dans l\'éditeur en direct parce qu\'un champ facultatif était exigé.</p>\n<h2>3. Le contrôleur public — <code>controllers/index.php</code></h2>\n<pre><code class=\"language-php\">&lt;?php\nnamespace NF\\Modules\\Notes\\Controllers;\n\nuse NF\\NeoFrag\\Loadables\\Controllers\\Module as Controller_Module;\n\nclass Index extends Controller_Module\n{\n    public function index($notes)\n    {\n        $this-&gt;title($this-&gt;lang(\'Notes\'))\n             -&gt;icon(\'fas fa-note-sticky\')\n             -&gt;breadcrumb();\n\n        return $this-&gt;css(\'notes\')-&gt;view(\'index\', [\'notes\' =&gt; $notes]);\n    }\n}\n</code></pre>\n<p><code>title()</code>, <code>icon()</code>, <code>breadcrumb()</code> renseignent l\'en-tête de page. Le contrôleur rend ensuite sa vue,\nou un panneau via <code>$this-&gt;panel()-&gt;title()-&gt;body($html)</code> pour du HTML construit en PHP.</p>\n<blockquote>\n<p><strong>Ne passe jamais du contenu de la base par <code>$this-&gt;title()</code> puis par la traduction.</strong> Un titre écrit\npar l\'administrateur n\'a pas de traduction : <code>lang()</code> le cherche en vain et journalise un\navertissement à chaque visite dans une autre langue. Emballe ce qui vient de la base dans\n<code>$this-&gt;no_translate(...)</code>.</p>\n</blockquote>\n<h2>4. La vue — <code>views/index.tpl.php</code></h2>\n<pre><code class=\"language-php\">&lt;div class=&quot;notes&quot;&gt;\n    &lt;?php if (empty($notes)): ?&gt;\n        &lt;div class=&quot;alert alert-info&quot;&gt;&lt;?php echo $this-&gt;lang(\'Aucune note.\') ?&gt;&lt;/div&gt;\n    &lt;?php else: foreach ($notes as $n): ?&gt;\n        &lt;article class=&quot;note&quot;&gt;\n            &lt;h3&gt;&lt;?php echo htmlspecialchars($n[\'title\']) ?&gt;&lt;/h3&gt;\n            &lt;p&gt;&lt;?php echo htmlspecialchars($n[\'body\']) ?&gt;&lt;/p&gt;\n        &lt;/article&gt;\n    &lt;?php endforeach; endif ?&gt;\n&lt;/div&gt;\n</code></pre>\n<p>Les variables passées à <code>view()</code> sont disponibles directement. <strong>Échappe toujours</strong> ce qui vient de la\nbase (<code>htmlspecialchars</code>) ; pour du HTML riche saisi par un membre, <code>sanitize_html()</code> (HTMLPurifier).</p>\n<p><strong>Le front est Bootstrap 5, sans jQuery, sous une CSP stricte.</strong></p>\n<ul>\n<li>Les classes de grille s\'écrivent <code>col-12 col-lg-8</code>, jamais <code>col-8</code> seul (qui s\'applique dès 0 px et\ncasse le téléphone) ; <code>tools/check-classes-bs4.php</code> refuse les classes Bootstrap 4 disparues.</li>\n<li>Un <code>&lt;script&gt;</code> inline dans une vue reçoit automatiquement le <strong>nonce</strong> de la réponse (filtre\nd\'<code>index.php</code>) ; un fichier JS se charge par <code>-&gt;js(\'nom\')</code> (dossier <code>js/</code> du module).</li>\n<li>Écris le JavaScript avec les primitives du cœur — <code>NF.ready</code>, <code>NF.data(el, \'clé\')</code>,\n<code>NF.ajax({url, method, data, dataType})</code>, <code>NF.post(url, data)</code>, <code>NF.setHtml</code>, <code>NF.insertHtml</code>,\n<code>NF.replaceHtml</code> — jamais <code>$(…)</code> : jQuery n\'est plus chargé, et <code>tools/check-js-sources.php</code>\nrefuse tout appel. Les adresses dont le script a besoin se posent en <code>data-*</code> sur le balisage plutôt\nqu\'en PHP interpolé dans le <code>.js</code>.</li>\n</ul>\n<h2>5. Les données</h2>\n<p>Un module <strong>livre ses tables</strong> dans <code>install/install.sql</code> (et leur suppression dans <code>uninstall.sql</code>).\nCe SQL est joué <strong>à l\'installation du module</strong> (assistant d\'installation, scan de l\'administration,\nZIP du marketplace), idempotent grâce à <code>CREATE TABLE IF NOT EXISTS</code> :</p>\n<pre><code class=\"language-sql\">-- modules/notes/install/install.sql\nCREATE TABLE IF NOT EXISTS nf_notes (\n    id    INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,\n    title VARCHAR(255) NOT NULL,\n    body  TEXT NOT NULL\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;\n</code></pre>\n<blockquote>\n<p><strong>Pas de tables propres ? Pas de <code>install.sql</code>.</strong> Un module qui réutilise des tables du cœur (par\nexemple <code>nf_file</code> via <code>model2(\'file\')</code>) s\'installe sans toucher au schéma.</p>\n</blockquote>\n<h3>Faire évoluer le schéma — migrations par addon</h3>\n<p><code>install.sql</code> crée les tables manquantes mais ne <strong>modifie pas</strong> une table déjà présente chez les\nutilisateurs. Pour un changement de schéma entre deux versions de ton module :</p>\n<pre><code>modules/notes/install/migrations/2026_10_01_add_pinned.up.sql\n</code></pre>\n<pre><code class=\"language-sql\">ALTER TABLE nf_notes ADD COLUMN pinned TINYINT(1) NOT NULL DEFAULT 0;\n</code></pre>\n<ul>\n<li>Nom = <strong>préfixe daté</strong> (<code>AAAA_MM_JJ_description</code>), exécution dans l\'ordre.</li>\n<li>Suivi dans <code>nf_addon_migrations</code> : chaque migration ne s\'exécute <strong>qu\'une fois</strong>.</li>\n<li>À l\'installation neuve (<code>install.sql</code> porte déjà le schéma à jour), les migrations sont\n<strong>baselinées</strong> — marquées sans être jouées. À la mise à jour (marketplace → « Mises à jour »),\nseules les nouvelles sont <strong>exécutées</strong> (<code>Addon::update()</code>).</li>\n<li>Règle d\'or : une migration pour une vraie évolution d\'un schéma <strong>déjà livré</strong> ; une nouvelle table\nva dans <code>install.sql</code>, jamais dans une migration.</li>\n<li>Le site de démonstration <strong>ne joue aucune migration</strong> (<code>Addon::migrate()</code> sort quand <code>nf_demo()</code>\nest vrai) : son état vient de son instantané <code>install/demo.sql</code>.</li>\n</ul>\n<p>Le dossier <code>migrations/</code> <strong>à la racine</strong> du projet est réservé aux évolutions transverses du cœur.</p>\n<h3>Dépendre d\'un autre module sans casser</h3>\n<p>Le paquet s\'installe à la carte : le module dont tu as besoin peut <strong>ne pas être là</strong>. Quatre façons\nde dépendre, et ce qui se passe s\'il manque (<code>tools/check-addon-coupling.php</code> les relève au tokenizer) :</p>\n<table>\n<thead>\n<tr>\n<th>Moyen</th>\n<th>Si l\'addon manque</th>\n<th>À faire</th>\n</tr>\n</thead>\n<tbody>\n<tr>\n<td>lire <strong>sa table</strong> (<code>from(\'nf_teams\')</code>)</td>\n<td><strong>fatal</strong> — « Table doesn\'t exist »</td>\n<td>le déclarer dans <code>requires</code>, ou garder par <code>$this-&gt;db-&gt;table_exists(\'nf_teams\')</code> et <strong>annoter</strong></td>\n</tr>\n<tr>\n<td>nommer <strong>sa classe</strong></td>\n<td><strong>fatal</strong> — « Class not found »</td>\n<td>idem</td>\n</tr>\n<tr>\n<td>appeler <strong>son service</strong> (<code>$this-&gt;module(\'teams\')</code>, <code>model2(\'team\')</code>)</td>\n<td>tolérant : rend <code>NULL</code></td>\n<td>tester le retour : <code>if ($teams = $this-&gt;module(\'teams\'))</code></td>\n</tr>\n<tr>\n<td>écrire <strong>son URL</strong> (<code>url(\'teams/…\')</code>)</td>\n<td>cosmétique : lien mort</td>\n<td>acceptable</td>\n</tr>\n</tbody>\n</table>\n<p>Un couplage fatal <strong>ni déclaré ni annoté fait échouer la CI</strong>. L\'annotation se pose à l\'endroit exact,\nen commentaire : <code>// couplage(teams): purge à la suppression d\'un jeu — gardé par table_exists()</code>, ou\nen tête de fichier <code>couplage(teams): …</code> quand les références y sont étalées.</p>\n<h3>Types de contenu : réactions, abonnements, révisions, corbeille</h3>\n<p>Si ton module publie du <strong>contenu</strong> (comme les actualités, les articles, les sujets), déclare-le : les\nmodules transverses — réactions, notifications, révisions, corbeille, gamification — le collectent au\nlieu de porter chacun leur propre liste de tables.</p>\n<pre><code class=\"language-php\">public function declare_content_types()\n{\n    return [\n        \'note\' =&gt; [\n            \'table\' =&gt; \'nf_notes\', \'pk\' =&gt; \'id\', \'author\' =&gt; \'user_id\',\n            \'reactable\' =&gt; TRUE, \'subscribable\' =&gt; TRUE, \'revisable\' =&gt; FALSE,\n        ],\n    ];\n}\n</code></pre>\n<p>Exemple réel : <code>modules/news/news.php</code>.</p>\n<h3>Les carrefours : statistiques, activité, tableau de bord, recherche</h3>\n<p>Un module se branche sur une page qui <strong>agrège</strong> en posant un contrôleur du nom du carrefour. Le\ncarrefour appelle la méthode du même nom <strong>sans rien vérifier</strong> : renommée, rendue privée ou dotée d\'un\nparamètre obligatoire de plus, l\'erreur n\'apparaît qu\'à l\'ouverture de la page — c\'est pourquoi\n<code>tools/check-addon-contracts.php</code> fige ces contrats en CI.</p>\n<table>\n<thead>\n<tr>\n<th>Contrôleur</th>\n<th>Méthode attendue</th>\n<th>Appelé par</th>\n</tr>\n</thead>\n<tbody>\n<tr>\n<td><code>controllers/statistics.php</code></td>\n<td><code>statistics()</code></td>\n<td>la page Statistiques de l\'administration</td>\n</tr>\n<tr>\n<td><code>controllers/activity.php</code></td>\n<td><code>activity($user_id, $limit)</code></td>\n<td>le profil d\'un membre</td>\n</tr>\n<tr>\n<td><code>controllers/dashboard.php</code></td>\n<td><code>dashboard()</code></td>\n<td>le tableau de bord de l\'administration</td>\n</tr>\n<tr>\n<td><code>controllers/block.php</code></td>\n<td><code>block()</code></td>\n<td>les blocs <code>[block:…]</code> des pages</td>\n</tr>\n<tr>\n<td><code>controllers/search.php</code></td>\n<td><code>search()</code> <strong>et</strong> <code>suggest()</code></td>\n<td>la recherche globale et la suggestion instantanée — un module qui n\'a que <code>search()</code> est <strong>ignoré en silence</strong></td>\n</tr>\n</tbody>\n</table>\n<h2>6. Les permissions (optionnel)</h2>\n<pre><code class=\"language-php\">public function permissions()\n{\n    return [\n        \'default\' =&gt; [\n            \'access\' =&gt; [\n                [\n                    \'title\'  =&gt; $this-&gt;lang(\'Notes\'),\n                    \'icon\'   =&gt; \'fas fa-note-sticky\',\n                    \'access\' =&gt; [\n                        \'add_note\'    =&gt; [\'title\' =&gt; $this-&gt;lang(\'Ajouter\'),   \'icon\' =&gt; \'fas fa-plus\', \'admin\' =&gt; TRUE],\n                        \'delete_note\' =&gt; [\'title\' =&gt; $this-&gt;lang(\'Supprimer\'), \'icon\' =&gt; \'far fa-trash-alt\', \'admin\' =&gt; TRUE],\n                    ],\n                ],\n            ],\n        ],\n    ];\n}\n</code></pre>\n<p>Les permissions deviennent éditables dans <strong>Administration → Permissions</strong> (matrice rôle × action).\nDans le code : <code>$this-&gt;access(\'notes\', \'add_note\')</code>. Exemple réel : <code>modules/news/news.php</code>.</p>\n<h2>7. L\'administration (optionnel)</h2>\n<p>Si <code>\'admin\' =&gt; TRUE</code>, ajoute <code>controllers/admin.php</code> (classe <code>Admin extends Controller_Module</code>). Sa\nméthode <code>index()</code> est servie sous <code>/admin/notes</code>.</p>\n<pre><code class=\"language-php\">&lt;?php\nnamespace NF\\Modules\\Notes\\Controllers;\n\nuse NF\\NeoFrag\\Loadables\\Controllers\\Module as Controller_Module;\n\nclass Admin extends Controller_Module\n{\n    public function index()\n    {\n        $this-&gt;title($this-&gt;lang(\'Notes\'))-&gt;icon(\'fas fa-note-sticky\');\n\n        $this-&gt;form()\n             -&gt;add_rules([\n                 \'title\' =&gt; [\'label\' =&gt; $this-&gt;lang(\'Titre\'),   \'type\' =&gt; \'text\',   \'rules\' =&gt; \'required\'],\n                 \'body\'  =&gt; [\'label\' =&gt; $this-&gt;lang(\'Contenu\'), \'type\' =&gt; \'editor\'],\n             ])\n             -&gt;add_submit($this-&gt;lang(\'Ajouter\'));\n\n        if ($this-&gt;form()-&gt;is_valid($post))\n        {\n            $this-&gt;db-&gt;insert(\'nf_notes\', [\'title\' =&gt; $post[\'title\'], \'body\' =&gt; $post[\'body\']]);\n            notify($this-&gt;lang(\'Note ajoutée.\'));\n            redirect(\'admin/notes\');\n        }\n\n        return $this-&gt;admin_card(\'fas fa-note-sticky\', $this-&gt;lang(\'Notes\'), $this-&gt;form()-&gt;display());\n    }\n}\n</code></pre>\n<p>C\'est <strong><code>form()</code></strong> (<code>add_rules()</code> / <code>is_valid()</code> / <code>display()</code>) qui est l\'API de formulaire des écrans\nd\'administration ; <code>form2()</code> (cf. <a href=\"framework\">Le framework</a>) sert aux formulaires publics, riches ou\nde confirmation seule. Le trait <code>Admin_Helpers</code>, disponible sur tout contrôleur de module, habille le\ncontenu : <code>admin_card()</code>, <code>admin_back()</code>, <code>admin_split()</code>, <code>admin_action_bar()</code>, <code>admin_empty()</code>,\n<code>admin_stats()</code>, <code>sort_select()</code>. Toute sous-page doit offrir un retour (<code>admin_back()</code> ou le fil\nd\'Ariane) : <code>tools/check-admin-back.php</code> le vérifie sur les 99 pages d\'administration.</p>\n<h3>Actions mutantes : exiger un jeton CSRF</h3>\n<p>Toute action qui <strong>modifie</strong> quelque chose déclenchée par un <strong>lien GET</strong> ou un <strong>POST écrit à la main</strong>\n(hors <code>form()</code> / <code>form2()</code>, qui portent leur propre jeton) doit être protégée : <code>SameSite=Lax</code> ne\nsuffit pas.</p>\n<pre><code class=\"language-php\">// Génération du lien : jeton en query.\n$html .= \'&lt;a href=&quot;\'.$this-&gt;csrf_url(\'admin/notes/delete/\'.$id).\'&quot;&gt;\'.icon(\'far fa-trash-alt\').\'&lt;/a&gt;\';\n\n// Contrôleur : vérifie le jeton AVANT de muter, sinon redirige.\npublic function _delete($note)\n{\n    $this-&gt;check_csrf(\'admin/notes\');\n    $this-&gt;db-&gt;where(\'id\', $note[\'id\'])-&gt;delete(\'nf_notes\');\n    notify($this-&gt;lang(\'Note supprimée.\'));\n    redirect(\'admin/notes\');\n}\n</code></pre>\n<p>Pour un POST manuel, le jeton va en champ caché : <code>&lt;input type=&quot;hidden&quot; name=&quot;_&quot; value=&quot;&lt;?php echo $this-&gt;csrf_token() ?&gt;&quot;&gt;</code>.</p>\n<h3>Tables d\'administration</h3>\n<p><code>table2()</code> rend des listes paginées, triables par clic sur l\'en-tête et filtrables (exemple :\n<code>modules/user/controllers/admin.php</code>). Le tri est géré par <code>js/table2.js</code>, en vanilla.</p>\n<h2>8. Éprouver le module</h2>\n<p>Le projet a un filet, et un module neuf doit y entrer :</p>\n<ul>\n<li><strong>Tests</strong> — <code>tests/Unit</code> (sans base), <code>tests/Headless</code> (le framework booté, de vrais modèles contre la\nbase de test, chaque test dans une transaction annulée — <code>HeadlessTestCase</code>), <code>tests/Integration</code>\n(miroir SQL), <code>tests/Browser/*.test.html</code> (contrat d\'un script dans un vrai navigateur, via\n<code>tools/check-js.php</code>). <code>vendor/bin/phpunit --fail-on-skipped</code>.</li>\n<li><strong>Contrôles</strong> à faire passer avant de livrer : <code>check-addon-declarations</code>, <code>check-addon-coupling</code>,\n<code>check-addon-contracts</code>, <code>check-js-sources</code>, <code>check-langs --toutes</code>,\n<code>check-strict-types</code> (le compteur ne doit jamais baisser — déclare <code>declare(strict_types=1)</code> dans tes\nfichiers neufs), puis <code>check-install-profiles</code> (installe chaque profil pour de vrai et frappe les\nroutes des modules absents, qui doivent rendre un <strong>404 propre</strong>, jamais un 500) et\n<code>check-js-console</code> (ouvre les pages d\'administration dans un navigateur et refuse toute erreur JS).</li>\n<li>PHPStan : <code>vendor/bin/phpstan analyse</code>.</li>\n</ul>\n<p>La liste complète est dans <a href=\"../../tools/README.md\"><code>tools/README.md</code></a> ; <code>php tools/check-all.php --navigateur</code>\nles joue tous.</p>\n<h2>9. Installer et distribuer</h2>\n<ol>\n<li>Dépose <code>modules/notes/</code>.</li>\n<li><strong>Administration → Thèmes &amp; Addons → Scanner le disque</strong> → coche <code>notes</code> → installe (l\'installation\njoue <code>install/install.sql</code>, baseline les migrations, pose les permissions).</li>\n<li>Pour le distribuer : zippe le dossier (<code>modules/notes/</code> à la racine de l\'archive), il s\'installe via\n<strong>Ajouter (ZIP)</strong>. Pour le publier au catalogue du marketplace du projet, il doit avoir\n<code>\'core\' =&gt; FALSE</code> et pas de <code>\'distributed\' =&gt; FALSE</code> ; <code>tools/package-addons.php</code> zippe et\ninscrit tous les addons optionnels dans <code>marketplace/catalog.json</code> avec leur empreinte SHA-256.</li>\n</ol>\n', '29', '3', '1', NULL, '2', '2026-09-15 14:28:39', '2026-09-16 00:07:40'),
('33', 'framework', 'Le framework', '<h1>Le framework</h1>\n<p>Référence des briques que tu manipules en écrivant des addons. Pour l\'architecture interne complète,\nvoir <code>docs/architecture.md</code>. Tout ce qui suit est vérifié contre le code de\nNeoFrag Reborn 1.1.0.</p>\n<h2>Le service locator — <code>NeoFrag()</code></h2>\n<p>Tout le framework est accessible via le singleton global <code>NeoFrag()</code> et, dans une classe d\'addon, via\n<code>$this</code> (qui y délègue). Les services s\'obtiennent par méthodes magiques :</p>\n<pre><code class=\"language-php\">$this-&gt;db        // accès base de données\n$this-&gt;config    // configuration du site (nf_name, nf_default_theme…)\n$this-&gt;user      // membre courant\n$this-&gt;url       // requête / segments / base\n$this-&gt;lang(...) // traduction\n$this-&gt;events    // événements internes (fire / on)\n$this-&gt;module(\'forum\');        // un module — NULL s\'il n\'est pas installé\nNeoFrag()-&gt;model2(\'addon\');    // un modèle\n</code></pre>\n<blockquote>\n<p><code>__call</code> <strong>avale les appels inconnus</strong> : <code>$this-&gt;methode_inexistante()</code> ne casse rien. Pour éprouver\nun contrôle, injecte un défaut que la magie ne rattrape pas (une fonction globale absente).</p>\n</blockquote>\n<h2>Routing</h2>\n<p>Un module déclare ses routes dans <code>__info().routes</code> : <code>motif =&gt; méthode</code>.</p>\n<pre><code class=\"language-php\">\'routes\' =&gt; [\n    \'\'                 =&gt; \'index\',   // page d\'accueil du module\n    \'{id}/{url_title}\' =&gt; \'_show\',   // /module/42/slug\n    \'admin{pages}\'     =&gt; \'index\',   // administration paginée\n],\n</code></pre>\n<ul>\n<li>Placeholders <strong>fixes</strong> : <code>{id}</code> (entier), <code>{key_id}</code>, <code>{url_title}</code> (slug), <code>{url_title*}</code>, <code>{page}</code>\net <code>{pages}</code> (pagination). Un placeholder inconnu → 404 silencieux.</li>\n<li>Cycle : le <strong>checker</strong> (<code>controllers/checker.php</code>) valide la route et charge les données ; ce qu\'il\n<strong>retourne</strong> devient les arguments de la méthode homonyme du <strong>contrôleur</strong> (<code>controllers/index.php</code>).</li>\n<li>Le routeur d\'aujourd\'hui est <code>route → module → page → 404</code>. Les <strong>régions nommées</strong> des thèmes\n(<code>region(\'content\')</code>) sont la fondation du futur routage centré sur les pages (outlines, routes\nréservées), pas encore écrit.</li>\n</ul>\n<blockquote>\n<p>En test ou avec <code>curl</code>, le CMS traite les agents non-navigateur comme des robots et <strong>n\'ouvre pas la\nsession</strong> : envoie un <strong>User-Agent de navigateur</strong>.</p>\n</blockquote>\n<h3>Un refus de checker se lit dans le journal</h3>\n<p>Quand un checker échoue — champ POST manquant, cible inexistante — le site répond <strong>404</strong> ; le motif\n(route, checker, méthode, champ absent et ce qui est arrivé à la place) est <strong>toujours journalisé</strong>\n(<code>[checker] …</code>). En mode debug, le motif est rendu au client et le statut est <strong>400</strong> : « ta requête est\nmal formée » plutôt que « cette adresse n\'existe pas ». <code>post_check(\'a\', \'b?\')</code> : le suffixe <code>?</code>\nrend un champ facultatif (<code>NULL</code> s\'il manque).</p>\n<h2>Base de données</h2>\n<p>Query builder fluide ; les tables portent le préfixe <code>nf_</code>.</p>\n<pre><code class=\"language-php\">$rows = $this-&gt;db\n    -&gt;select(\'id\', \'title\', \'created\')\n    -&gt;from(\'nf_news\')\n    -&gt;where(\'published\', \'1\')\n    -&gt;where(\'author_id\', $user_id)\n    -&gt;order_by(\'created DESC\')\n    -&gt;limit(10)\n    -&gt;get();                 // tableau de lignes ; -&gt;row() pour une seule\n\n$id = $this-&gt;db-&gt;insert(\'nf_news\', [\'title\' =&gt; $t, \'body\' =&gt; $b]);  // renvoie l\'id\n$this-&gt;db-&gt;where(\'id\', $id)-&gt;update(\'nf_news\', [\'title\' =&gt; $t2]);\n$this-&gt;db-&gt;where(\'id\', $id)-&gt;delete(\'nf_news\');\n\n$this-&gt;db-&gt;transaction(); … $this-&gt;db-&gt;commit();   // ou -&gt;rollback()\n</code></pre>\n<ul>\n<li><strong><code>table_exists(\'nf_teams\')</code></strong> — vrai si la table existe. Le paquet s\'installe à la carte : un module\ndu cœur qui lit la table d\'un module optionnel <strong>doit</strong> se garder ainsi (ou déclarer la dépendance\ndans <code>requires</code>). Résultat mis en cache pour la requête.</li>\n<li><strong><code>import($sql)</code></strong> — exécute du SQL multi-instructions (DDL d\'installation) hors du pipeline des\nrequêtes préparées.</li>\n<li>Une <strong>jointure qui ne sert qu\'à compter ou à décorer doit être <code>LEFT</code></strong> : sept listes faisaient\ndisparaître tout contenu sans enfant (un forum sans message, un album sans image) par une jointure\nstricte.</li>\n<li><code>MATCH … AGAINST</code> (FULLTEXT) <strong>ne voit pas</strong> une ligne insérée dans une transaction non validée :\nInnoDB n\'indexe qu\'au commit. Un test de recherche FULLTEXT ne peut pas s\'envelopper dans la\ntransaction annulée du socle de test.</li>\n</ul>\n<p>Pour les entités gérées (addons, fichiers…), passe par les <strong>modèles</strong> : <code>NeoFrag()-&gt;model2(\'addon\')</code>,\n<code>NeoFrag()-&gt;model2(\'file\', $id)-&gt;delete()</code> ; pour itérer un ensemble typé,\n<code>NeoFrag()-&gt;collection(\'addon\')-&gt;get()</code>.</p>\n<h2>Formulaires — <code>form()</code> &amp; <code>form2()</code></h2>\n<p>Deux API coexistent (décision actée : la v1 est gelée, pas migrée en masse) :</p>\n<ul>\n<li><strong><code>form()</code></strong> — l\'API des <strong>écrans d\'administration</strong> : champs déclarés en tableau via\n<code>add_rules([...])</code>, traitée par <code>is_valid($post)</code>, rendue par <code>-&gt;display()</code>. Exemple complet dans\n<a href=\"create-a-module\">Créer un module</a> (§7).</li>\n<li><strong><code>form2()</code></strong> — fluide, chaque champ est un objet <code>form_*()</code>. Pour les formulaires <strong>publics</strong> ou\n<strong>riches</strong>, et la <strong>seule</strong> qui valide un formulaire de <strong>confirmation seule</strong> (sans champ) — une\nconfirmation de suppression n\'accepte <strong>que</strong> le champ <code>delete</code> : une case ajoutée la ferait échouer\nen silence.</li>\n</ul>\n<pre><code class=\"language-php\">return $this-&gt;form2()\n    -&gt;rule($this-&gt;form_text(\'title\')-&gt;title($this-&gt;lang(\'Titre\'))-&gt;required())\n    -&gt;rule($this-&gt;form_textarea(\'body\')-&gt;title($this-&gt;lang(\'Contenu\')))\n    -&gt;captcha()\n    -&gt;success(function ($data) {\n        $this-&gt;db-&gt;insert(\'nf_news\', $data);\n        notify($this-&gt;lang(\'Enregistré\'));\n    })\n    -&gt;submit($this-&gt;lang(\'Publier\'));\n</code></pre>\n<p>Les deux portent leur propre jeton CSRF. Une action déclenchée par un <strong>lien</strong> ou un <strong>POST écrit à la\nmain</strong> doit le vérifier elle-même : <code>csrf_url()</code>, <code>check_csrf()</code>, <code>csrf_token()</code> (trait <code>Admin_Helpers</code>).</p>\n<h2>Tables — <code>table2()</code></h2>\n<p>Rend des listes paginées, <strong>triables par clic sur l\'en-tête</strong> (Maj + clic : tri multi-colonnes ;\nCtrl + clic : retirer) et filtrables, à partir d\'une requête. Exemple : la liste des membres,\n<code>modules/user/controllers/admin.php</code>. Le tri est servi par <code>js/table2.js</code> en vanilla.</p>\n<h2>Traductions</h2>\n<p><code>$this-&gt;lang(\'Clé\')</code> renvoie la traduction. Les fichiers <code>langs/fr.php</code> mappent le <strong>crc32b de la\nclé source</strong> vers la traduction :</p>\n<pre><code class=\"language-php\">return [\n    hash(\'crc32b\', \'Bienvenue\') =&gt; \'Bienvenue\',   // ou directement \'467f39a3\' =&gt; \'…\'\n];\n</code></pre>\n<ul>\n<li><code>$this-&gt;lang(\'%d élément(s)\', $n)</code> accepte des arguments (style <code>sprintf</code>).</li>\n<li>Le <strong>français est la langue source</strong> ; six langues sont livrées et <code>tools/check-langs.php --toutes</code>\nrefuse une clé manquante dans l\'une d\'elles. <code>--fix</code> ajoute les clés manquantes au <strong>français seul</strong>.</li>\n<li><strong>Ne traduis jamais du contenu de la base</strong> (un titre écrit par l\'administrateur) : <code>lang()</code> le\ncherche en vain et journalise un avertissement à chaque visite. Emballe-le dans\n<code>$this-&gt;no_translate(...)</code>.</li>\n</ul>\n<h2>Le front : Bootstrap 5, vanilla, CSP stricte</h2>\n<ul>\n<li><strong>Bootstrap 5.3</strong>, <strong>sans jQuery</strong>. Les classes de grille portent toujours un point de rupture\n(<code>col-12 col-lg-8</code>) ; <code>tools/check-classes-bs4.php</code> refuse les classes de Bootstrap 4.</li>\n<li><strong>CSP stricte à nonce</strong> : chaque réponse HTML porte un nonce aléatoire, posé sur tous les <code>&lt;script&gt;</code>\ninline par le filtre d\'<code>index.php</code>, et <code>script-src</code> n\'autorise que <code>\'self\'</code>, ce nonce, et les origines\nde reCAPTCHA (plus Google Analytics <strong>si</strong> un identifiant est configuré). <strong>Aucun script depuis un\nCDN.</strong> Le JS inséré dynamiquement passe par <code>NF.setHtml</code> / <code>NF.insertHtml</code> / <code>NF.replaceHtml</code>, qui\nré-exécutent les <code>&lt;script&gt;</code> ajoutés avec le bon nonce, y compris dans l\'iframe de l\'éditeur en direct.</li>\n<li><strong><code>window.NF</code></strong>, défini dans le gabarit principal, remplace les quelques primitives dont on avait\nbesoin — et rien de plus (ce n\'est pas un mini-jQuery) :</li>\n</ul>\n<table>\n<thead>\n<tr>\n<th>Primitive</th>\n<th>Rôle</th>\n</tr>\n</thead>\n<tbody>\n<tr>\n<td><code>NF.ready(fn)</code></td>\n<td>exécute <code>fn</code> quand le DOM est prêt (ou tout de suite s\'il l\'est déjà)</td>\n</tr>\n<tr>\n<td><code>NF.data(el, \'ma-cle\')</code></td>\n<td>lit <code>data-ma-cle</code> avec la coercition de jQuery (nombre, booléen, JSON)</td>\n</tr>\n<tr>\n<td><code>NF.ajax({url, method, data, dataType, headers, signal})</code></td>\n<td><code>fetch</code> avec <code>X-Requested-With</code> sur la même origine, corps <code>form-urlencoded</code>, tableaux en <code>clé[]</code> ; <strong>rejette</strong> sur un statut d\'erreur ; <code>dataType: \'text\'</code> sinon JSON</td>\n</tr>\n<tr>\n<td><code>NF.post(url, data)</code></td>\n<td>raccourci POST</td>\n</tr>\n<tr>\n<td><code>NF.setHtml(el, html)</code></td>\n<td><code>innerHTML</code> + exécution des scripts</td>\n</tr>\n<tr>\n<td><code>NF.insertHtml(cible, position, html)</code></td>\n<td><code>insertAdjacentHTML</code> + exécution des seuls scripts ajoutés</td>\n</tr>\n<tr>\n<td><code>NF.replaceHtml(el, html)</code></td>\n<td>remplace l\'élément, scripts exécutés</td>\n</tr>\n<tr>\n<td><code>NF.runScripts(root)</code>, <code>NF.loadScript(src)</code></td>\n<td>ré-exécuter, charger avec le nonce</td>\n</tr>\n</tbody>\n</table>\n<ul>\n<li>Les modales (<code>js/modal.js</code>), les notifications (<code>js/notify.js</code>) et les confirmations (<code>js/confirm.js</code>)\nsont chargées par les thèmes.</li>\n<li>Deux contrôles gardent ce front : <code>check-js-sources</code> (syntaxe, PHP interpolé toléré, et aucun <code>$(</code>),\n<code>check-js-console</code> (les vraies pages, dans un vrai navigateur : aucune erreur, aucune violation CSP).\nLe harnais <code>tests/Browser/*.test.html</code> + <code>tools/check-js.php</code> éprouve le <strong>contrat</strong> d\'un script sur\nle vrai fichier, avec des doubles de <code>fetch</code>.</li>\n</ul>\n<h2>Sécurité : ce qui existe déjà</h2>\n<p>Avant d\'écrire le tien : <code>Rate_Limit</code> (par clé, ex. <code>contact:ip:&lt;ip&gt;</code>), <code>Audit_Log</code> (actions\nsensibles), <code>File_Jail</code> (chemins d\'upload), <code>Moderation</code>, TOTP pour la double authentification,\n<code>sanitize_html()</code> (HTMLPurifier) pour le HTML riche, <code>is_dangerous_upload()</code>, et le mode démo\n(<code>nf_demo()</code>) qui verrouille l\'écriture des modules sensibles.</p>\n<h2>Événements</h2>\n<p><code>$this-&gt;events-&gt;fire(\'forum.post.created\', $message_id, $topic_id, $user_id)</code> côté émetteur ;\n<code>$this-&gt;events-&gt;on(\'forum.post.created\', function (...) { … })</code> côté abonné. Les écouteurs restent\nactifs pour toute la requête.</p>\n<h2>Helpers utiles</h2>\n<ul>\n<li><code>url(\'forum/42\')</code> — construit une URL routée (préfixe de langue inclus).</li>\n<li><code>notify(\'Message\')</code> — notification à l\'écran après redirection.</li>\n<li><code>htmlspecialchars(...)</code> / <code>sanitize_html(...)</code> — échappement / nettoyage anti-XSS.</li>\n<li><code>nf_demo()</code> — vrai sur le site de démonstration.</li>\n</ul>\n<h2>Migrations</h2>\n<p>Le schéma du <strong>cœur</strong> évolue par migrations versionnées, suivies dans <code>nf_migrations</code> :</p>\n<pre><code class=\"language-bash\">php tools/migrate.php status                 # appliqué / en attente\nphp tools/migrate.php up [--pretend]          # applique (simulation avec --pretend)\nphp tools/migrate.php down [--step=N]         # annule\nphp tools/migrate.php baseline --until=NAME   # adopter une base existante\n</code></pre>\n<p>Fichiers : <code>migrations/AAAA_MM_JJ_nom.up.sql</code> (+ <code>.down.sql</code>). Le DDL MySQL est auto-commit →\n<strong>sauvegarde avant <code>up</code> en production</strong>. Les <strong>addons</strong> ont leurs propres migrations\n(<code>&lt;addon&gt;/install/migrations/</code>, suivies dans <code>nf_addon_migrations</code>) — voir\n<a href=\"create-a-module\">Créer un module</a>. Le site de démonstration <strong>ne joue aucune migration</strong> : son état\nvient de son instantané.</p>\n<h2>Surcharge à trois niveaux</h2>\n<p>Résolution des vues, classes et fichiers (premier trouvé gagne) :</p>\n<ol>\n<li><code>overrides/{type}/{fichier}</code> — global</li>\n<li><code>themes/{thème actif}/overrides/{type}/{fichier}</code> — par thème</li>\n<li>l\'original livré</li>\n</ol>\n<p>Tu personnalises sans forker et sans casser les mises à jour. Attention : deux fichiers homonymes à deux\nniveaux, et <code>path()</code> sert l\'un ou l\'autre selon l\'appelant — un assistant s\'est affiché sans style à\ncause d\'un doublon (<code>tools/check-assets.php</code> le refuse désormais).</p>\n<h2>Environnement de développement</h2>\n<p>Le projet tourne sur <strong>tout PHP 8.2+ avec MySQL ou MariaDB</strong> (Apache + <code>mod_rewrite</code>, ou nginx, ou\nCaddy). La voie de référence du projet est une <strong>installation d\'épreuve sur un serveur</strong>, où tourne\nla batterie complète : <code>php tools/check-all.php --navigateur</code>, <code>vendor/bin/phpunit --fail-on-skipped</code>\net <code>composer stan</code>. L\'environnement, la base de test et la boucle de travail sont décrits dans\n<code>docs/development.md</code> ; chaque outil est décrit dans\n<a href=\"../../tools/README.md\"><code>tools/README.md</code></a>.</p>\n', '29', '4', '1', NULL, '2', '2026-09-15 14:28:39', '2026-09-16 00:07:40');
DELETE FROM `nf_wiki_revisions`;
INSERT INTO `nf_wiki_revisions` (`id`, `page_id`, `content`, `title`, `user_id`, `comment`, `created_at`) VALUES
('1', '24', '<h1>Guide utilisateur</h1>\n<p>Tout pour installer et piloter ton site <strong>NeoFrag Reborn</strong>.</p>\n', 'Guide utilisateur', '271', 'Première rédaction', '2026-09-11 11:46:29'),
('2', '25', '<h1>Installation</h1>\n<p>NeoFrag Reborn s\'installe sur un hébergement web classique, <strong>mutualisé compris</strong>.</p>\n<h2>Prérequis</h2>\n<ul>\n<li><strong>PHP 8.2+</strong> avec les extensions <code>mysqli</code>, <code>gd</code>, <code>intl</code>, <code>mbstring</code>, <code>zip</code>, <code>curl</code>.</li>\n<li><strong>MySQL 5.7+</strong> ou <strong>MariaDB 10.5+</strong>.</li>\n<li><strong>Apache</strong> avec <code>mod_rewrite</code> (un <code>nginx.conf</code> est fourni en alternative).</li>\n<li>Une base de données vide + ses identifiants (panel de l\'hébergeur).</li>\n</ul>\n<h2>Mise en ligne</h2>\n<p>L\'assistant d\'installation se déroule en <strong>4 étapes</strong> : Prérequis → Base de données →\nAdministrateur → Terminé.</p>\n<ol>\n<li><strong>Téléverse</strong> les fichiers de NeoFrag Reborn à la racine web (FTP ou Git).</li>\n<li>Visite ton domaine : l\'<strong>assistant d\'installation</strong> se lance automatiquement et vérifie les prérequis.</li>\n<li>Renseigne la <strong>connexion base de données</strong> (une base vide) — l\'assistant importe le schéma, les migrations\n<strong>et installe tous les modules, widgets et thèmes</strong> livrés dans le paquet (modèle « tout bundlé »).</li>\n<li>Crée le <strong>compte administrateur</strong>. C\'est fini — supprime/replie l\'accès à l\'installeur si l\'hébergeur ne le fait pas.</li>\n</ol>\n<h2>Installation en ligne de commande (CLI)</h2>\n<p>Pour un déploiement <strong>scriptable et reproductible</strong> (VPS, provisioning), une alternative à l\'assistant\nweb : <code>install/cli.php</code>. Elle fait exactement la même chose (même lib, même modèle « tout bundlé »),\nsans navigateur.</p>\n<pre><code class=\"language-bash\"># Mot de passe admin via variable d\'environnement (invisible dans la liste des process) :\nexport NF_ADMIN_PASS=\'mon-mot-de-passe-fort\'\nphp install/cli.php \\\n  --db-name=neofrag --db-user=neofrag --db-pass=secret \\\n  --admin-user=admin --admin-email=admin@site.tld --admin-pass-env=NF_ADMIN_PASS \\\n  --site-name=&quot;Ma communauté&quot; --site-url=https://site.tld --yes\n</code></pre>\n<p>Sans arguments, elle passe en <strong>mode interactif</strong> (elle demande ce qui manque, mot de passe masqué).\nOptions utiles : <code>--create-db</code> (crée la base), <code>--demo</code> (contenu de démo), <code>--dry-run</code> (valide la config\net teste la connexion sans rien écrire), <code>--force</code> (réinstalle), <code>--no-lock</code> (ne pose pas le verrou).\nAide complète : <code>php install/cli.php --help</code>.</p>\n<blockquote>\n<p>Comme pour l\'assistant web, <strong>supprime (ou renomme) le dossier <code>install/</code></strong> après coup en production.</p>\n</blockquote>\n<h2>Modules : tout est déjà là</h2>\n<p>NeoFrag Reborn s\'installe <strong>complet</strong> : tous les modules (actualités, forum, galerie, équipes, événements,\nwiki, boutique…), widgets et thèmes du paquet sont installés et activés d\'emblée — il n\'y a <strong>pas de choix\nde profil</strong> à l\'installation (modèle WordPress).</p>\n<p>Tu <strong>actives/désactives</strong> ensuite chaque addon depuis <strong>Admin → Thèmes &amp; Addons</strong>. Les <strong>mises à jour</strong> des\naddons (et l\'ajout d\'addons tiers) se font depuis le <a href=\"marketplace\">marketplace</a>, avec intégrité vérifiée\npar empreinte SHA-256.</p>\n<blockquote>\n<p>La page d\'accueil rend toujours quelque chose (jamais d\'écran vide) : les actualités par défaut.</p>\n</blockquote>\n<h2>Premiers réglages</h2>\n<ol>\n<li><strong>Admin → Paramètres</strong> : nom du site, description, favicon, page d\'accueil.</li>\n<li><strong>Admin → Thèmes &amp; Addons</strong> : choisis ton thème (Nebula pour une communauté) et\n<strong>active ou désactive</strong> les modules selon tes besoins — tout est déjà installé (modèle « tout bundlé »).</li>\n<li><strong>Admin → Live Editor</strong> : compose tes pages (place tes widgets dans les zones).</li>\n<li><strong>Admin → Utilisateurs / Permissions</strong> : crée tes rôles et règle les accès.</li>\n</ol>\n<h2>Sécuriser l\'accès après l\'installation</h2>\n<p>À la fin, l\'assistant pose un <strong>verrou</strong> (<code>install/db.txt</code>) : revisiter <code>/install/</code> n\'affiche plus rien\nd\'exploitable. Par précaution, <strong>supprime (ou renomme) le dossier <code>install/</code></strong> de ton serveur — il n\'est\nplus nécessaire au fonctionnement du site. Pense aussi à activer <strong>HTTPS</strong> (Let\'s Encrypt) si l\'hébergeur\nne l\'a pas fait.</p>\n<h2>Dépannage</h2>\n<ul>\n<li><strong>« Connexion à la base impossible »</strong> : vérifie l\'hôte (souvent <code>localhost</code>, parfois une adresse dédiée\nsur les mutualisés), le port (<code>3306</code>), le nom de la base — <strong>elle doit exister et être vide</strong> — et les\nidentifiants (panel de l\'hébergeur).</li>\n<li><strong>« Extension PHP manquante »</strong> (étape Prérequis) : active l\'extension signalée (<code>mysqli</code>, <code>gd</code>, <code>intl</code>,\n<code>mbstring</code>, <code>zip</code>, <code>curl</code>) depuis le panel de l\'hébergeur, ou demande au support. En ligne de commande : <code>php -m</code>.</li>\n<li><strong>Dossier <code>config/</code> non inscriptible</strong> : l\'assistant doit y écrire <code>db.php</code> + les secrets. Donne les droits\nd\'écriture à <code>config/</code> (et à <code>cache/</code>, <code>logs/</code>, <code>upload/</code>, <code>backups/</code>).</li>\n<li><strong>Installation interrompue en cours de route</strong> : MySQL ne sait pas annuler un <code>CREATE TABLE</code> à moitié joué\n→ <strong>vide ou recrée une base vierge</strong> avant de relancer <code>/install/</code> (ne réessaie pas sur une base déjà entamée).</li>\n<li><strong>URLs en 404 / AJAX cassés sous nginx ou Plesk</strong> : le <code>.htaccess</code> n\'est lu que par Apache. Voir la note\n« nginx / Plesk » du guide de déploiement.</li>\n</ul>\n<h2>Développement local</h2>\n<p>Pour développer ou tester, une stack <strong>Docker</strong> est fournie (Apache + PHP 8.3, MariaDB,\nphpMyAdmin, Mailpit) :</p>\n<pre><code class=\"language-bash\">docker compose up -d        # http://localhost:8080\n</code></pre>\n<p>Détails (bootstrap d\'une base, migrations, tests, déploiement) :\n<code>docs/development.md</code>.</p>\n', 'Installation', '271', 'Première rédaction', '2026-09-11 11:46:29');
DELETE FROM `nf_dispositions`;
INSERT INTO `nf_dispositions` (`disposition_id`, `theme`, `page`, `zone`, `disposition`) VALUES
('82', 'nebula', '*', '0', '[]'),
('134', 'nebula', '*', '5', '[{\"style\":\"row-default\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":133,\"style\":null,\"size\":null}]}]}]'),
('83', 'nebula', '*', '2', '[{\"style\":\"row-default\",\"cols\":[{\"size\":\"col-md-8\",\"widgets\":[{\"id\":135,\"style\":null,\"size\":null}]},{\"size\":\"col-md-4\",\"widgets\":[{\"id\":136,\"style\":\"panel-color\",\"size\":null},{\"id\":137,\"style\":\"panel-default\",\"size\":null},{\"id\":138,\"style\":\"panel-default\",\"size\":null}]}]}]'),
('84', 'nebula', '*', '1', '[]'),
('85', 'nebula', '*', '3', '[]'),
('86', 'nebula', '*', '4', '[]'),
('87', 'nebula', '/', '1', '[{\"style\":\"row-default\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":134,\"style\":null,\"size\":null}]}]}]'),
('88', 'nebula', 'forum/*', '2', '[{\"style\":\"row-default\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":139,\"style\":null,\"size\":null}]}]},{\"style\":\"row-default\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":140,\"style\":null,\"size\":null}]}]}]'),
('89', 'nebula', 'forum/*', '3', '[{\"style\":\"row-default\",\"cols\":[{\"size\":\"col-md-4\",\"widgets\":[{\"id\":145,\"style\":\"panel-header\",\"size\":null}]},{\"size\":\"col-md-8\",\"widgets\":[{\"id\":146,\"style\":\"panel-header\",\"size\":null}]}]}]'),
('90', 'nebula', 'news/_news/*', '2', '[{\"style\":\"row-default\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":141,\"style\":null,\"size\":null}]}]},{\"style\":\"row-default\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":142,\"style\":null,\"size\":null}]}]}]'),
('91', 'nebula', 'user/*', '2', '[{\"style\":\"row-default\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":143,\"style\":null,\"size\":null}]}]},{\"style\":\"row-default\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":144,\"style\":null,\"size\":null}]}]}]'),
('92', 'blockcraft', '*', '0', '[{\"style\":\"row-default\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":9002,\"style\":null,\"size\":null}]}]},{\"style\":\"row-dark\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":9003,\"style\":null,\"size\":null}]}]}]'),
('93', 'blockcraft', '*', '2', '[{\"style\":\"row-default\",\"cols\":[{\"size\":\"col-md-8\",\"widgets\":[{\"id\":9005,\"style\":null,\"size\":null}]},{\"size\":\"col-md-4\",\"widgets\":[{\"id\":9006,\"style\":\"panel-color\",\"size\":null},{\"id\":9007,\"style\":\"panel-default\",\"size\":null},{\"id\":9008,\"style\":\"panel-default\",\"size\":null},{\"id\":9009,\"style\":\"panel-header\",\"size\":null}]}]}]'),
('94', 'blockcraft', '*', '1', '[]'),
('95', 'blockcraft', '*', '3', '[]'),
('96', 'blockcraft', '*', '4', '[]'),
('97', 'blockcraft', '/', '1', '[{\"style\":\"row-default\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":9004,\"style\":null,\"size\":null}]}]}]'),
('98', 'blockcraft', 'forum/*', '2', '[{\"style\":\"row-default\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":9010,\"style\":null,\"size\":null}]}]},{\"style\":\"row-default\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":9011,\"style\":null,\"size\":null}]}]}]'),
('99', 'blockcraft', 'forum/*', '3', '[{\"style\":\"row-default\",\"cols\":[{\"size\":\"col-md-4\",\"widgets\":[{\"id\":9016,\"style\":\"panel-header\",\"size\":null}]},{\"size\":\"col-md-8\",\"widgets\":[{\"id\":9017,\"style\":\"panel-header\",\"size\":null}]}]}]'),
('100', 'blockcraft', 'news/_news/*', '2', '[{\"style\":\"row-default\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":9012,\"style\":null,\"size\":null}]}]},{\"style\":\"row-default\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":9013,\"style\":null,\"size\":null}]}]}]'),
('101', 'blockcraft', 'user/*', '2', '[{\"style\":\"row-default\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":9014,\"style\":null,\"size\":null}]}]},{\"style\":\"row-default\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":9015,\"style\":null,\"size\":null}]}]}]'),
('102', 'extend', '*', '0', '[{\"style\":\"row-dark\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":9018,\"style\":null,\"size\":null}]}]}]'),
('103', 'extend', '*', '3', '[{\"style\":\"row-default\",\"cols\":[{\"size\":\"col-md-8\",\"widgets\":[{\"id\":9023,\"style\":null,\"size\":null}]},{\"size\":\"col-md-4\",\"widgets\":[{\"id\":9024,\"style\":null,\"size\":null}]}]},{\"style\":\"row-default\",\"cols\":[{\"size\":\"col-md-8\",\"widgets\":[{\"id\":9025,\"style\":null,\"size\":null}]},{\"size\":\"col-md-4\",\"widgets\":[{\"id\":9026,\"style\":\"panel-color\",\"size\":null},{\"id\":9027,\"style\":\"panel-default\",\"size\":null},{\"id\":9028,\"style\":\"panel-default\",\"size\":null},{\"id\":9029,\"style\":\"panel-header\",\"size\":null}]}]}]'),
('104', 'extend', '*', '4', '[{\"style\":\"row-default\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":9036,\"style\":\"panel-default\",\"size\":null}]}]}]'),
('105', 'extend', '*', '5', '[]'),
('106', 'extend', '*', '1', '[]'),
('107', 'extend', '*', '2', '[]'),
('108', 'extend', '/', '1', '[{\"style\":\"row-default\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":9019,\"style\":null,\"size\":null}]}]}]'),
('109', 'extend', '/', '2', '[{\"style\":\"row-default\",\"cols\":[{\"size\":\"col-md-4\",\"widgets\":[{\"id\":9020,\"style\":\"panel-default\",\"size\":null}]},{\"size\":\"col-md-4\",\"widgets\":[{\"id\":9021,\"style\":\"panel-default\",\"size\":null}]},{\"size\":\"col-md-4\",\"widgets\":[{\"id\":9022,\"style\":\"panel-default\",\"size\":null}]}]}]'),
('110', 'extend', 'forum/*', '3', '[{\"style\":\"row-default\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":9030,\"style\":null,\"size\":null}]}]},{\"style\":\"row-default\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":9031,\"style\":null,\"size\":null}]}]}]'),
('111', 'extend', 'forum/*', '4', '[{\"style\":\"row-default\",\"cols\":[{\"size\":\"col-md-4\",\"widgets\":[{\"id\":9037,\"style\":\"panel-header\",\"size\":null}]},{\"size\":\"col-md-8\",\"widgets\":[{\"id\":9038,\"style\":\"panel-header\",\"size\":null}]}]}]'),
('112', 'extend', 'news/_news/*', '3', '[{\"style\":\"row-default\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":9032,\"style\":null,\"size\":null}]}]},{\"style\":\"row-default\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":9033,\"style\":null,\"size\":null}]}]}]'),
('113', 'extend', 'user/*', '3', '[{\"style\":\"row-default\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":9034,\"style\":null,\"size\":null}]}]},{\"style\":\"row-default\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":9035,\"style\":null,\"size\":null}]}]}]'),
('114', 'forge', '*', '0', '[{\"style\":\"row-default\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":9040,\"style\":null,\"size\":null}]}]},{\"style\":\"row-dark\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":9041,\"style\":null,\"size\":null}]}]}]'),
('115', 'forge', '*', '2', '[{\"style\":\"row-default\",\"cols\":[{\"size\":\"col-md-8\",\"widgets\":[{\"id\":9043,\"style\":null,\"size\":null}]},{\"size\":\"col-md-4\",\"widgets\":[{\"id\":9044,\"style\":\"panel-color\",\"size\":null},{\"id\":9045,\"style\":\"panel-default\",\"size\":null},{\"id\":9046,\"style\":\"panel-default\",\"size\":null},{\"id\":9047,\"style\":\"panel-header\",\"size\":null}]}]}]'),
('116', 'forge', '*', '1', '[]'),
('117', 'forge', '*', '3', '[]'),
('118', 'forge', '*', '4', '[]'),
('119', 'forge', '/', '1', '[{\"style\":\"row-default\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":9042,\"style\":null,\"size\":null}]}]}]'),
('120', 'forge', 'forum/*', '2', '[{\"style\":\"row-default\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":9048,\"style\":null,\"size\":null}]}]},{\"style\":\"row-default\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":9049,\"style\":null,\"size\":null}]}]}]'),
('121', 'forge', 'forum/*', '3', '[{\"style\":\"row-default\",\"cols\":[{\"size\":\"col-md-4\",\"widgets\":[{\"id\":9054,\"style\":\"panel-header\",\"size\":null}]},{\"size\":\"col-md-8\",\"widgets\":[{\"id\":9055,\"style\":\"panel-header\",\"size\":null}]}]}]'),
('122', 'forge', 'news/_news/*', '2', '[{\"style\":\"row-default\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":9050,\"style\":null,\"size\":null}]}]},{\"style\":\"row-default\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":9051,\"style\":null,\"size\":null}]}]}]'),
('123', 'forge', 'user/*', '2', '[{\"style\":\"row-default\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":9052,\"style\":null,\"size\":null}]}]},{\"style\":\"row-default\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":9053,\"style\":null,\"size\":null}]}]}]'),
('124', 'granite', '*', '0', '[{\"style\":\"row-default\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":9056,\"style\":null,\"size\":null}]}]},{\"style\":\"row-dark\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":9057,\"style\":null,\"size\":null}]}]}]'),
('125', 'granite', '*', '2', '[{\"style\":\"row-default\",\"cols\":[{\"size\":\"col-md-8\",\"widgets\":[{\"id\":9059,\"style\":null,\"size\":null}]},{\"size\":\"col-md-4\",\"widgets\":[{\"id\":9060,\"style\":\"panel-color\",\"size\":null},{\"id\":9061,\"style\":\"panel-default\",\"size\":null},{\"id\":9062,\"style\":\"panel-default\",\"size\":null},{\"id\":9063,\"style\":\"panel-header\",\"size\":null}]}]}]'),
('126', 'granite', '*', '1', '[]'),
('127', 'granite', '*', '3', '[]'),
('128', 'granite', '*', '4', '[]'),
('129', 'granite', '/', '1', '[{\"style\":\"row-default\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":9058,\"style\":null,\"size\":null}]}]}]'),
('130', 'granite', 'forum/*', '2', '[{\"style\":\"row-default\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":9064,\"style\":null,\"size\":null}]}]},{\"style\":\"row-default\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":9065,\"style\":null,\"size\":null}]}]}]'),
('131', 'granite', 'forum/*', '3', '[{\"style\":\"row-default\",\"cols\":[{\"size\":\"col-md-4\",\"widgets\":[{\"id\":9070,\"style\":\"panel-header\",\"size\":null}]},{\"size\":\"col-md-8\",\"widgets\":[{\"id\":9071,\"style\":\"panel-header\",\"size\":null}]}]}]'),
('132', 'granite', 'news/_news/*', '2', '[{\"style\":\"row-default\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":9066,\"style\":null,\"size\":null}]}]},{\"style\":\"row-default\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":9067,\"style\":null,\"size\":null}]}]}]'),
('133', 'granite', 'user/*', '2', '[{\"style\":\"row-default\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":9068,\"style\":null,\"size\":null}]}]},{\"style\":\"row-default\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":9069,\"style\":null,\"size\":null}]}]}]');
DELETE FROM `nf_widgets`;
INSERT INTO `nf_widgets` (`widget_id`, `widget`, `type`, `title`, `settings`) VALUES
('133', 'navigation', 'index', NULL, '{\"links\":[{\"title\":\"Accueil\",\"url\":\"\"},{\"title\":\"Actualit&eacute;s\",\"url\":\"news\"},{\"title\":\"Forum\",\"url\":\"forum\"},{\"title\":\"Galerie\",\"url\":\"gallery\"},{\"title\":\"Membres\",\"url\":\"members\"},{\"title\":\"Contact\",\"url\":\"contact\"}]}'),
('134', 'slider', 'index', NULL, NULL),
('135', 'module', 'index', NULL, NULL),
('136', 'user', 'index', NULL, NULL),
('137', 'members', 'online', NULL, NULL),
('138', 'news', 'categories', NULL, NULL),
('139', 'breadcrumb', 'index', NULL, NULL),
('140', 'module', 'index', NULL, NULL),
('141', 'breadcrumb', 'index', NULL, NULL),
('142', 'module', 'index', NULL, NULL),
('143', 'breadcrumb', 'index', NULL, NULL),
('144', 'module', 'index', NULL, NULL),
('145', 'forum', 'statistics', NULL, NULL),
('146', 'forum', 'activity', NULL, NULL),
('9001', 'module', 'index', NULL, NULL),
('9002', 'header', 'index', NULL, 'a:6:{s:7:\"display\";s:4:\"logo\";s:5:\"align\";s:10:\"text-start\";s:5:\"title\";s:0:\"\";s:11:\"description\";s:0:\"\";s:11:\"color_title\";s:7:\"#ffffff\";s:17:\"color_description\";s:7:\"#e9efe4\";}'),
('9003', 'navigation', 'index', NULL, 'a:1:{s:5:\"links\";a:7:{i:0;a:2:{s:5:\"title\";s:7:\"Accueil\";s:3:\"url\";s:0:\"\";}i:1;a:2:{s:5:\"title\";s:17:\"Actualit&eacute;s\";s:3:\"url\";s:4:\"news\";}i:2;a:2:{s:5:\"title\";s:5:\"Forum\";s:3:\"url\";s:5:\"forum\";}i:3;a:2:{s:5:\"title\";s:14:\"&Eacute;quipes\";s:3:\"url\";s:5:\"teams\";}i:4;a:2:{s:5:\"title\";s:7:\"Galerie\";s:3:\"url\";s:7:\"gallery\";}i:5;a:2:{s:5:\"title\";s:7:\"Membres\";s:3:\"url\";s:7:\"members\";}i:6;a:2:{s:5:\"title\";s:7:\"Contact\";s:3:\"url\";s:7:\"contact\";}}}'),
('9004', 'slider', 'index', NULL, NULL),
('9005', 'module', 'index', NULL, NULL),
('9006', 'user', 'index', NULL, NULL),
('9007', 'members', 'online', NULL, NULL),
('9008', 'news', 'categories', NULL, NULL),
('9009', 'talks', 'index', NULL, 'a:1:{s:7:\"talk_id\";i:2;}'),
('9010', 'breadcrumb', 'index', NULL, NULL),
('9011', 'module', 'index', NULL, NULL),
('9012', 'breadcrumb', 'index', NULL, NULL),
('9013', 'module', 'index', NULL, NULL),
('9014', 'breadcrumb', 'index', NULL, NULL),
('9015', 'module', 'index', NULL, NULL),
('9016', 'forum', 'statistics', NULL, NULL),
('9017', 'forum', 'activity', NULL, NULL),
('9018', 'navigation', 'index', NULL, 'a:1:{s:5:\"links\";a:7:{i:0;a:2:{s:5:\"title\";s:7:\"Accueil\";s:3:\"url\";s:0:\"\";}i:1;a:2:{s:5:\"title\";s:17:\"Actualit&eacute;s\";s:3:\"url\";s:4:\"news\";}i:2;a:2:{s:5:\"title\";s:5:\"Forum\";s:3:\"url\";s:5:\"forum\";}i:3;a:2:{s:5:\"title\";s:14:\"&Eacute;quipes\";s:3:\"url\";s:5:\"teams\";}i:4;a:2:{s:5:\"title\";s:7:\"Galerie\";s:3:\"url\";s:7:\"gallery\";}i:5;a:2:{s:5:\"title\";s:7:\"Membres\";s:3:\"url\";s:7:\"members\";}i:6;a:2:{s:5:\"title\";s:7:\"Contact\";s:3:\"url\";s:7:\"contact\";}}}'),
('9019', 'slider', 'index', NULL, NULL),
('9020', 'events', 'matches', NULL, NULL),
('9021', 'news', 'index', NULL, NULL),
('9022', 'events', 'upcoming', NULL, NULL),
('9023', 'breadcrumb', 'index', NULL, NULL),
('9024', 'search', 'index', NULL, NULL),
('9025', 'module', 'index', NULL, NULL),
('9026', 'user', 'index', NULL, NULL),
('9027', 'members', 'online', NULL, NULL),
('9028', 'news', 'categories', NULL, NULL),
('9029', 'talks', 'index', NULL, 'a:1:{s:7:\"talk_id\";i:2;}'),
('9030', 'breadcrumb', 'index', NULL, NULL),
('9031', 'module', 'index', NULL, NULL),
('9032', 'breadcrumb', 'index', NULL, NULL),
('9033', 'module', 'index', NULL, NULL),
('9034', 'breadcrumb', 'index', NULL, NULL),
('9035', 'module', 'index', NULL, NULL),
('9036', 'partners', 'index', NULL, NULL),
('9037', 'forum', 'statistics', NULL, NULL),
('9038', 'forum', 'activity', NULL, NULL),
('9040', 'header', 'index', NULL, 'a:6:{s:7:\"display\";s:4:\"logo\";s:5:\"align\";s:10:\"text-start\";s:5:\"title\";s:0:\"\";s:11:\"description\";s:0:\"\";s:11:\"color_title\";s:7:\"#ffffff\";s:17:\"color_description\";s:7:\"#f3d9cf\";}'),
('9041', 'navigation', 'index', NULL, 'a:1:{s:5:\"links\";a:7:{i:0;a:2:{s:5:\"title\";s:7:\"Accueil\";s:3:\"url\";s:0:\"\";}i:1;a:2:{s:5:\"title\";s:17:\"Actualit&eacute;s\";s:3:\"url\";s:4:\"news\";}i:2;a:2:{s:5:\"title\";s:5:\"Forum\";s:3:\"url\";s:5:\"forum\";}i:3;a:2:{s:5:\"title\";s:14:\"&Eacute;quipes\";s:3:\"url\";s:5:\"teams\";}i:4;a:2:{s:5:\"title\";s:7:\"Galerie\";s:3:\"url\";s:7:\"gallery\";}i:5;a:2:{s:5:\"title\";s:7:\"Membres\";s:3:\"url\";s:7:\"members\";}i:6;a:2:{s:5:\"title\";s:7:\"Contact\";s:3:\"url\";s:7:\"contact\";}}}'),
('9042', 'slider', 'index', NULL, NULL),
('9043', 'module', 'index', NULL, NULL),
('9044', 'user', 'index', NULL, NULL),
('9045', 'members', 'online', NULL, NULL),
('9046', 'news', 'categories', NULL, NULL),
('9047', 'talks', 'index', NULL, 'a:1:{s:7:\"talk_id\";i:2;}'),
('9048', 'breadcrumb', 'index', NULL, NULL),
('9049', 'module', 'index', NULL, NULL),
('9050', 'breadcrumb', 'index', NULL, NULL),
('9051', 'module', 'index', NULL, NULL),
('9052', 'breadcrumb', 'index', NULL, NULL),
('9053', 'module', 'index', NULL, NULL),
('9054', 'forum', 'statistics', NULL, NULL),
('9055', 'forum', 'activity', NULL, NULL),
('9056', 'header', 'index', NULL, 'a:6:{s:7:\"display\";s:4:\"logo\";s:5:\"align\";s:10:\"text-start\";s:5:\"title\";s:0:\"\";s:11:\"description\";s:0:\"\";s:11:\"color_title\";s:7:\"#ffffff\";s:17:\"color_description\";s:7:\"#d6e4e6\";}'),
('9057', 'navigation', 'index', NULL, 'a:1:{s:5:\"links\";a:7:{i:0;a:2:{s:5:\"title\";s:7:\"Accueil\";s:3:\"url\";s:0:\"\";}i:1;a:2:{s:5:\"title\";s:17:\"Actualit&eacute;s\";s:3:\"url\";s:4:\"news\";}i:2;a:2:{s:5:\"title\";s:5:\"Forum\";s:3:\"url\";s:5:\"forum\";}i:3;a:2:{s:5:\"title\";s:14:\"&Eacute;quipes\";s:3:\"url\";s:5:\"teams\";}i:4;a:2:{s:5:\"title\";s:7:\"Galerie\";s:3:\"url\";s:7:\"gallery\";}i:5;a:2:{s:5:\"title\";s:7:\"Membres\";s:3:\"url\";s:7:\"members\";}i:6;a:2:{s:5:\"title\";s:7:\"Contact\";s:3:\"url\";s:7:\"contact\";}}}'),
('9058', 'slider', 'index', NULL, NULL),
('9059', 'module', 'index', NULL, NULL),
('9060', 'user', 'index', NULL, NULL),
('9061', 'members', 'online', NULL, NULL),
('9062', 'news', 'categories', NULL, NULL),
('9063', 'talks', 'index', NULL, 'a:1:{s:7:\"talk_id\";i:2;}'),
('9064', 'breadcrumb', 'index', NULL, NULL),
('9065', 'module', 'index', NULL, NULL),
('9066', 'breadcrumb', 'index', NULL, NULL),
('9067', 'module', 'index', NULL, NULL),
('9068', 'breadcrumb', 'index', NULL, NULL),
('9069', 'module', 'index', NULL, NULL),
('9070', 'forum', 'statistics', NULL, NULL),
('9071', 'forum', 'activity', NULL, NULL);

COMMIT;
SET FOREIGN_KEY_CHECKS = 1;

-- ────────────────────────────────────────────────────────────────────────────────────────────
-- CONTENU D'EXEMPLE des cinq modules installés sur la démonstration le 2026-09-22.
--
-- Pourquoi il est ici : la démonstration sert aussi de plateau de photo pour les vignettes du
-- marketplace. Un module installé mais VIDE ne montre qu'un titre et un cadre — une vignette qui
-- n'apprend rien. Ce contenu est volontairement court et dans l'esprit du reste de la démo : une
-- communauté de joueurs.
-- ────────────────────────────────────────────────────────────────────────────────────────────

DELETE FROM `nf_glossary_terms`;
DELETE FROM `nf_glossary_categories`;

INSERT INTO `nf_glossary_categories` (`id`, `title`, `sort_order`) VALUES
(1, 'Vocabulaire de jeu', 0),
(2, 'Communauté', 1);

INSERT INTO `nf_glossary_terms` (`category_id`, `term`, `initial`, `definition`, `synonyms`, `published`) VALUES
(1, 'Clutch', 'C', 'Situation où un joueur reste seul face à plusieurs adversaires et remporte tout de même la manche.', 'clutcher', 1),
(1, 'Eco round', 'E', 'Manche jouée volontairement sans acheter d''équipement, pour économiser de quoi s''équiper à la suivante.', 'éco', 1),
(1, 'Peek', 'P', 'Action de se découvrir brièvement pour observer ou tirer, puis de se remettre à couvert.', 'peeker, jiggle peek', 1),
(2, 'Roster', 'R', 'Liste des joueurs qui composent une équipe à un moment donné.', 'effectif', 1),
(2, 'Scrim', 'S', 'Match d''entraînement organisé entre deux équipes, sans enjeu de classement.', 'scrimmage', 1),
(2, 'Shoutcast', 'S', 'Commentaire en direct d''une partie, assuré par un ou deux casteurs.', 'cast, casteur', 1);

DELETE FROM `nf_quotes`;
DELETE FROM `nf_quotes_categories`;

INSERT INTO `nf_quotes_categories` (`id`, `title`, `sort_order`) VALUES
(1, 'Sur le jeu', 0),
(2, 'Sur l''équipe', 1);

INSERT INTO `nf_quotes` (`category_id`, `quote`, `author`, `source`, `sort_order`, `published`) VALUES
(1, 'On ne perd pas une manche parce qu''on vise mal, on la perd parce qu''on a arrêté de se parler.', 'Rekkles_FR', 'Débrief du tournoi régional', 0, 1),
(1, 'Le meilleur réglage de souris, c''est celui qu''on garde trois mois.', 'NovaStrike', 'Guide du débutant', 1, 1),
(2, 'Une équipe, c''est cinq joueurs qui font la même erreur en même temps — et qui la corrigent ensemble.', 'ShadowFox', 'Interview d''avant-saison', 0, 1),
(2, 'On recrute des gens, pas des statistiques.', 'LunaByte', 'Annonce de recrutement', 1, 1);

DELETE FROM `nf_recipes`;
DELETE FROM `nf_recipes_categories`;

INSERT INTO `nf_recipes_categories` (`id`, `title`, `sort_order`) VALUES
(1, 'Soirées LAN', 0),
(2, 'Boissons', 1);

INSERT INTO `nf_recipes` (`category_id`, `title`, `intro`, `ingredients`, `steps`, `servings`, `prep_minutes`, `cook_minutes`, `sort_order`, `published`) VALUES
(1, 'Wraps froids de la LAN', 'Se tiennent d''une main, ne graissent pas le clavier.', '6 galettes de blé\n300 g de poulet rôti\n1 avocat\n2 tomates\n4 cuillères de fromage frais\nQuelques feuilles de salade', 'Émincer le poulet et les tomates.\nÉtaler le fromage frais sur chaque galette.\nGarnir, rouler serré, couper en deux.\nRéserver au frais jusqu''au coup d''envoi.', 6, 20, 0, 0, 1),
(1, 'Pop-corn au paprika fumé', 'Le bruit en moins : on n''en mange pas pendant les phases silencieuses.', '100 g de maïs à éclater\n2 cuillères d''huile\n1 cuillère de paprika fumé\nSel', 'Chauffer l''huile dans une grande casserole.\nVerser le maïs, couvrir, secouer jusqu''à la fin des éclatements.\nSaupoudrer de paprika et de sel, mélanger.', 4, 5, 10, 1, 1),
(2, 'Thé glacé menthe-citron', 'De quoi tenir une soirée sans finir électrique.', '1 l d''eau\n3 sachets de thé vert\n1 citron\nUne dizaine de feuilles de menthe\nMiel selon le goût', 'Infuser le thé cinq minutes, laisser refroidir.\nAjouter le jus de citron et la menthe froissée.\nSucrer au miel, servir bien frais.', 4, 10, 5, 0, 1);

DELETE FROM `nf_places`;
DELETE FROM `nf_places_categories`;

INSERT INTO `nf_places_categories` (`id`, `title`, `sort_order`) VALUES
(1, 'Salles et LAN', 0),
(2, 'Points de rencontre', 1);

INSERT INTO `nf_places` (`category_id`, `title`, `description`, `address`, `latitude`, `longitude`, `sort_order`, `published`) VALUES
(1, 'Salle de la LAN d''été', 'Deux cents postes, fibre dédiée, buvette ouverte toute la nuit.', '12 rue des Halles, 69002 Lyon', 45.760000, 4.832000, 0, 1),
(1, 'Gymnase du tournoi régional', 'Le tournoi d''automne s''y tient chaque année depuis trois saisons.', 'Avenue du Stade, 33000 Bordeaux', 44.837800, -0.579100, 1, 1),
(2, 'Bar associatif Le Respawn', 'Rencontre mensuelle de la communauté, premier jeudi du mois.', '5 place Saint-Pierre, 31000 Toulouse', 43.604700, 1.443700, 0, 1),
(2, 'Espace jeu de la médiathèque', 'Initiations le samedi après-midi, ouvert à tous.', '2 quai de Seine, 75019 Paris', 48.883000, 2.373000, 1, 1);

DELETE FROM `nf_webradio_shows`;

INSERT INTO `nf_webradio_shows` (`title`, `host`, `description`, `day`, `start_time`, `end_time`, `published`) VALUES
('Le réveil du serveur', 'LunaByte', 'Les actualités de la communauté et la playlist du matin.', 1, '08:00', '10:00', 1),
('Débrief des scrims', 'ShadowFox', 'Retour sur les entraînements de la semaine, avec les joueurs.', 3, '20:00', '21:30', 1),
('Nuit blanche', 'NovaStrike', 'Musique et discussions jusqu''au bout de la nuit, pendant les LAN.', 6, '22:00', '02:00', 1);

INSERT INTO `nf_settings` (`name`, `site`, `lang`, `value`, `type`)
SELECT 'webradio_stream', '', '', 'https://example.com/stream/neofrag.mp3', 'string'
 WHERE NOT EXISTS (SELECT 1 FROM `nf_settings` WHERE `name` = 'webradio_stream');

UPDATE `nf_settings` SET `value` = 'https://example.com/stream/neofrag.mp3' WHERE `name` = 'webradio_stream';
