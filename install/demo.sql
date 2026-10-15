-- NeoFrag Reborn — instantané du site de DÉMO (config affichage + membres + contenu), rechargé par l'auto-reset.
-- Généré par tools/dump-demo.php depuis la base vive. NE PAS éditer à la main.
-- Régénérer : php tools/dump-demo.php

-- Ne touche pas le compte admin ni les secrets (verrouillés en mode démo).
-- nf-demo-present: 2026-09-16

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
DELETE FROM `nf_role_permissions` WHERE `role_id` = 2 AND `permission` = 'forum.category_read';
INSERT INTO `nf_role_permissions` (`role_id`, `permission`, `scope_id`, `authorized`) VALUES (2, 'forum.category_read', 0, 'allow');
DELETE FROM `nf_role_permissions` WHERE `role_id` = 3 AND `permission` = 'gallery.gallery_see';
INSERT INTO `nf_role_permissions` (`role_id`, `permission`, `scope_id`, `authorized`) VALUES (3, 'gallery.gallery_see', 0, 'allow');
DELETE FROM `nf_role_permissions` WHERE `role_id` = 2 AND `permission` = 'gallery.gallery_see';
INSERT INTO `nf_role_permissions` (`role_id`, `permission`, `scope_id`, `authorized`) VALUES (2, 'gallery.gallery_see', 0, 'allow');
DELETE FROM `nf_role_permissions` WHERE `role_id` = 3 AND `permission` = 'pages.access_page';
INSERT INTO `nf_role_permissions` (`role_id`, `permission`, `scope_id`, `authorized`) VALUES (3, 'pages.access_page', 0, 'allow');
DELETE FROM `nf_role_permissions` WHERE `role_id` = 2 AND `permission` = 'pages.access_page';
INSERT INTO `nf_role_permissions` (`role_id`, `permission`, `scope_id`, `authorized`) VALUES (2, 'pages.access_page', 0, 'allow');
DELETE FROM `nf_role_permissions` WHERE `role_id` = 3 AND `permission` = 'events.access_events_type';
INSERT INTO `nf_role_permissions` (`role_id`, `permission`, `scope_id`, `authorized`) VALUES (3, 'events.access_events_type', 0, 'allow');
DELETE FROM `nf_role_permissions` WHERE `role_id` = 2 AND `permission` = 'events.access_events_type';
INSERT INTO `nf_role_permissions` (`role_id`, `permission`, `scope_id`, `authorized`) VALUES (2, 'events.access_events_type', 0, 'allow');
DELETE FROM `nf_role_permissions` WHERE `role_id` = 2 AND `permission` = 'forum.category_write';
INSERT INTO `nf_role_permissions` (`role_id`, `permission`, `scope_id`, `authorized`) VALUES (2, 'forum.category_write', 0, 'allow');
DELETE FROM `nf_role_permissions` WHERE `role_id` = 2 AND `permission` = 'talks.write';
INSERT INTO `nf_role_permissions` (`role_id`, `permission`, `scope_id`, `authorized`) VALUES (2, 'talks.write', 0, 'allow');
DELETE FROM `nf_role_permissions` WHERE `role_id` = 2 AND `permission` = 'recruits.recruit_postulate';
INSERT INTO `nf_role_permissions` (`role_id`, `permission`, `scope_id`, `authorized`) VALUES (2, 'recruits.recruit_postulate', 0, 'allow');

-- Réglages du site (les réglages sensibles sont exclus, cf. tools/dump-demo.php)
INSERT INTO `nf_settings` (`name`, `site`, `lang`, `value`, `type`) VALUES
('blockcraft_background', '', '', '0', 'int'),
('blockcraft_background_attachment', '', '', 'scroll', 'string'),
('blockcraft_background_color', '', '', '#eef3e6', 'string'),
('blockcraft_background_position', '', '', 'center top', 'string'),
('blockcraft_background_repeat', '', '', 'repeat', 'string'),
('blockcraft_header', '', '', '0', 'int'),
('blockcraft_header_attachment', '', '', 'scroll', 'string'),
('blockcraft_header_color', '', '', '#6aa84f', 'string'),
('blockcraft_header_position', '', '', 'center top', 'string'),
('blockcraft_header_repeat', '', '', 'no-repeat', 'string'),
('blockcraft_logo', '', '', '0', 'int'),
('blockcraft_adresse', '', '', 'play.example.org', 'string'),
('blockcraft_navbar_display', '', '', '0', 'bool'),
('blockcraft_social_discord', '', '', '', 'string'),
('blockcraft_social_facebook', '', '', '', 'string'),
('blockcraft_social_github', '', '', '', 'string'),
('blockcraft_social_instagram', '', '', '', 'string'),
('blockcraft_social_tiktok', '', '', '', 'string'),
('blockcraft_social_twitch', '', '', '', 'string'),
('blockcraft_social_twitter', '', '', '', 'string'),
('blockcraft_social_youtube', '', '', '', 'string'),
('blockcraft_text_color', '', '', '#1e2916', 'string'),
('blockcraft_theme_color', '', '', '#3c7a27', 'string'),
('events_alert_mp', '', '', '1', 'string'),
('events_per_page', '', '', '10', 'string'),
('extend_background', '', '', '0', 'int'),
('extend_background_attachment', '', '', 'scroll', 'string'),
('extend_background_color', '', '', '#0a111c', 'string'),
('extend_background_position', '', '', 'center top', 'string'),
('extend_background_repeat', '', '', 'repeat', 'string'),
('extend_header', '', '', '0', 'int'),
('extend_header_position', '', '', 'center center', 'string'),
('extend_logo', '', '', '0', 'int'),
('extend_text_color', '', '', '#c3d0de', 'string'),
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
('nf_captcha_provider', '', '', 'altcha', 'string'),
('nf_captcha_public_key', '', '', '', 'string'),
('nf_contact', '', '', 'noreply@neofrag.com', 'string'),
('nf_cookie_expire', '', '', '1 hour', 'string'),
('nf_cookie_name', '', '', 'session', 'string'),
('nf_copyright', '', '', 'Copyright {copyright} {year} {name}, tous droits r&eacute;serv&eacute;s &lt;div class=&quot;float-end&quot;&gt;Propuls&eacute; par {neofrag}&lt;/div&gt;', 'string'),
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
('nf_pwa', '', '', '0', 'bool'),
('nf_registration_charte', '', '', '', 'string'),
('nf_registration_status', '', '', '0', 'int'),
('nf_robots_txt', '', '', 'User-agent: *\r\nDisallow:', 'string'),
('nf_session_history_days', '', '', '395', 'int'),
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
('nf_timezone', '', '', 'Europe/Paris', 'string'),
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
('recruits_send_mp', '', '', '1', 'bool'),
('webradio_stream', '', '', 'https://example.com/stream/neofrag.mp3', 'string')
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
('1', 'de', 'Ankündigungen'),
('1', 'en', 'Announcements'),
('1', 'es', 'Anuncios'),
('1', 'fr', 'Annonces'),
('1', 'it', 'Annunci'),
('1', 'pt', 'Anúncios'),
('2', 'de', 'Wettkampf'),
('2', 'en', 'Competition'),
('2', 'es', 'Competición'),
('2', 'fr', 'Compétition'),
('2', 'it', 'Competizione'),
('2', 'pt', 'Competição'),
('3', 'de', 'Gemeinschaft'),
('3', 'en', 'Community'),
('3', 'es', 'Comunidad'),
('3', 'fr', 'Communauté'),
('3', 'it', 'Comunità'),
('3', 'pt', 'Comunidade');
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
('1', 'de', 'Willkommen bei NeoFrag Reborn', 'Die neue Website der Gemeinschaft ist online!', '<p>Wir freuen uns, euch auf unserer neuen Plattform zu begrüßen, betrieben mit <strong>NeoFrag Reborn</strong>. Forum, Neuigkeiten, Galerien, Turniere: alles ist da. Meldet euch an und seid dabei!</p>', ''),
('1', 'en', 'Welcome to NeoFrag Reborn', 'The community\'s new website is live!', '<p>We are delighted to welcome you to our new platform, powered by <strong>NeoFrag Reborn</strong>. Forum, news, galleries, tournaments: it\'s all here. Sign up and join the adventure!</p>', ''),
('1', 'es', 'Bienvenido a NeoFrag Reborn', '¡La nueva web de la comunidad ya está en línea!', '<p>Nos alegra darte la bienvenida a nuestra nueva plataforma, impulsada por <strong>NeoFrag Reborn</strong>. Foro, noticias, galerías, torneos: está todo. ¡Regístrate y únete a la aventura!</p>', ''),
('1', 'fr', 'Bienvenue sur NeoFrag Reborn', 'Le nouveau site de la communauté est en ligne !', '<p>Nous sommes ravis de vous accueillir sur notre nouvelle plateforme propulsée par <strong>NeoFrag Reborn</strong>. Forum, actualités, galeries, tournois : tout y est. Inscrivez-vous et rejoignez l\'aventure !</p>', ''),
('1', 'it', 'Benvenuto su NeoFrag Reborn', 'Il nuovo sito della comunità è online!', '<p>Siamo felici di accoglierti sulla nostra nuova piattaforma, basata su <strong>NeoFrag Reborn</strong>. Forum, notizie, gallerie, tornei: c’è tutto. Iscriviti e unisciti all’avventura!</p>', ''),
('1', 'pt', 'Bem-vindo ao NeoFrag Reborn', 'O novo site da comunidade está online!', '<p>Temos muito gosto em receber-te na nossa nova plataforma, assente no <strong>NeoFrag Reborn</strong>. Fórum, notícias, galerias, torneios: está tudo cá. Regista-te e junta-te à aventura!</p>', ''),
('2', 'de', 'Sieg im Finale des Regionalturniers', 'Unser Hauptteam gewinnt das große Finale mit 3:1.', '<p>Nach einem makellosen Turnierverlauf haben unsere Spieler die Trophäe des Regionalturniers geholt. Glückwunsch an das ganze Team zu dieser Leistung!</p><p>Nächstes Ziel: die nationale Qualifikation.</p>', ''),
('2', 'en', 'Victory in the regional tournament final', 'Our main team wins the grand final 3-1.', '<p>After a flawless run, our players lifted the regional tournament trophy. Congratulations to the whole team on this performance!</p><p>Next goal: the national qualifiers.</p>', ''),
('2', 'es', 'Victoria en la final del torneo regional', 'Nuestro equipo principal se impone 3-1 en la gran final.', '<p>Tras un recorrido impecable, nuestros jugadores levantaron el trofeo del torneo regional. ¡Enhorabuena a todo el equipo por esta actuación!</p><p>Próximo objetivo: la clasificación nacional.</p>', ''),
('2', 'fr', 'Victoire en finale du tournoi régional', 'Notre équipe principale s\'impose 3-1 en grande finale.', '<p>Après un parcours sans faute, nos joueurs ont décroché le trophée du tournoi régional. Félicitations à toute l\'équipe pour cette performance !</p><p>Prochain objectif : les qualifications nationales.</p>', ''),
('2', 'it', 'Vittoria nella finale del torneo regionale', 'La nostra squadra principale si impone 3-1 in finale.', '<p>Dopo un percorso senza errori, i nostri giocatori hanno alzato il trofeo del torneo regionale. Complimenti a tutta la squadra per questa prestazione!</p><p>Prossimo obiettivo: le qualificazioni nazionali.</p>', ''),
('2', 'pt', 'Vitória na final do torneio regional', 'A nossa equipa principal vence a grande final por 3-1.', '<p>Depois de um percurso impecável, os nossos jogadores levantaram o troféu do torneio regional. Parabéns a toda a equipa por esta exibição!</p><p>Próximo objetivo: o apuramento nacional.</p>', ''),
('3', 'de', 'Community-Abend an diesem Freitag', 'Komm zu einem entspannten Abend auf dem Server.', '<p>Diesen Freitag um 21 Uhr treffen wir uns alle zu lockeren, geselligen Runden. Anfänger sind willkommen!</p>', ''),
('3', 'en', 'Community night this Friday', 'Join us for a relaxed evening on the server.', '<p>This Friday at 9 pm, we all meet up for fun, friendly games. Beginners welcome!</p>', ''),
('3', 'es', 'Noche comunitaria este viernes', 'Acompáñanos a una velada distendida en el servidor.', '<p>Este viernes a las 21:00 nos juntamos todos para echar unas partidas divertidas y distendidas. ¡Principiantes bienvenidos!</p>', ''),
('3', 'fr', 'Soirée communautaire ce vendredi', 'Rejoignez-nous pour une soirée détente sur le serveur.', '<p>Ce vendredi à 21h, on se retrouve tous pour des parties fun et conviviales. Débutants bienvenus !</p>', ''),
('3', 'it', 'Serata della comunità questo venerdì', 'Raggiungici per una serata in relax sul server.', '<p>Questo venerdì alle 21 ci troviamo tutti per partite divertenti e in allegria. Principianti benvenuti!</p>', ''),
('3', 'pt', 'Noite da comunidade esta sexta-feira', 'Junta-te a nós para um serão descontraído no servidor.', '<p>Esta sexta-feira às 21h juntamo-nos todos para umas partidas divertidas e descontraídas. Principiantes bem-vindos!</p>', ''),
('4', 'de', 'Neue Hardware-Partnerschaft', 'Exklusive Rabatte für unsere Mitglieder.', '<p>Dank unseres neuen Partners gibt es Rabatte auf Gaming-Hardware. Einzelheiten im Mitgliederbereich.</p>', ''),
('4', 'en', 'New hardware partnership', 'Exclusive discounts for our members.', '<p>Thanks to our new partner, enjoy discounts on gaming gear. Details in the members\' area.</p>', ''),
('4', 'es', 'Nuevo acuerdo de material', 'Descuentos exclusivos para nuestros miembros.', '<p>Gracias a nuestro nuevo socio, disfruta de descuentos en material gaming. Los detalles, en el área de miembros.</p>', ''),
('4', 'fr', 'Nouveau partenariat matériel', 'Des réductions exclusives pour nos membres.', '<p>Grâce à notre nouveau partenaire, profitez de réductions sur le matériel gaming. Détails dans l\'espace membre.</p>', ''),
('4', 'it', 'Nuova partnership hardware', 'Sconti esclusivi per i nostri membri.', '<p>Grazie al nostro nuovo partner, approfitta degli sconti sull’hardware da gioco. I dettagli nell’area membri.</p>', ''),
('4', 'pt', 'Nova parceria de material', 'Descontos exclusivos para os nossos membros.', '<p>Graças ao nosso novo parceiro, aproveita descontos em material gaming. Os detalhes na área de membros.</p>', ''),
('5', 'de', 'Spielplan des Monats', 'Alle Wettkampftermine, die man nicht verpassen darf.', '<p>Der Spielplan der nächsten Partien ist da. Kommt vorbei und feuert eure Teams an!</p>', ''),
('5', 'en', 'This month\'s match schedule', 'All the competitive dates not to miss.', '<p>The schedule of upcoming matches is out. Come and cheer on your teams!</p>', ''),
('5', 'es', 'Calendario de partidos del mes', 'Todas las citas competitivas que no te puedes perder.', '<p>Ya está disponible el calendario de los próximos partidos. ¡Ven a animar a tus equipos!</p>', ''),
('5', 'fr', 'Calendrier des matchs du mois', 'Tous les rendez-vous compétitifs à ne pas manquer.', '<p>Le calendrier des prochains matchs est disponible. Venez supporter vos équipes !</p>', ''),
('5', 'it', 'Calendario delle partite del mese', 'Tutti gli appuntamenti competitivi da non perdere.', '<p>Il calendario delle prossime partite è disponibile. Vieni a tifare per le tue squadre!</p>', ''),
('5', 'pt', 'Calendário dos jogos do mês', 'Todos os encontros competitivos a não perder.', '<p>O calendário dos próximos jogos já está disponível. Vem apoiar as tuas equipas!</p>', ''),
('6', 'de', 'Videoschnitt-Wettbewerb', 'Zeig deine besten Highlights und sammle Punkte.', '<p>Mach mit bei unserem Schnittwettbewerb: die besten Videos werden mit Shop-Punkten belohnt.</p>', ''),
('6', 'en', 'Video editing contest', 'Show off your best highlights and earn points.', '<p>Enter our editing contest: the best videos will be rewarded with shop points.</p>', ''),
('6', 'es', 'Concurso de edición de vídeo', 'Enseña tus mejores jugadas y gana puntos.', '<p>Participa en nuestro concurso de edición: los mejores vídeos se premiarán con puntos de la tienda.</p>', ''),
('6', 'fr', 'Concours de montage vidéo', 'Montrez vos plus beaux highlights et gagnez des points.', '<p>Participez à notre concours de montage : les meilleures vidéos seront récompensées en points boutique.</p>', ''),
('6', 'it', 'Concorso di montaggio video', 'Mostra i tuoi highlight migliori e guadagna punti.', '<p>Partecipa al nostro concorso di montaggio: i video migliori saranno premiati con punti negozio.</p>', ''),
('6', 'pt', 'Concurso de edição de vídeo', 'Mostra os teus melhores momentos e ganha pontos.', '<p>Participa no nosso concurso de edição: os melhores vídeos serão recompensados com pontos da loja.</p>', '');
DELETE FROM `nf_articles_categories`;
INSERT INTO `nf_articles_categories` (`category_id`, `image_id`, `icon_id`, `name`) VALUES
('1', NULL, NULL, 'guides'),
('2', NULL, NULL, 'tests');
DELETE FROM `nf_articles_categories_lang`;
INSERT INTO `nf_articles_categories_lang` (`category_id`, `lang`, `title`) VALUES
('1', 'de', 'Ratgeber'),
('1', 'en', 'Guides'),
('1', 'es', 'Guías'),
('1', 'fr', 'Guides'),
('1', 'it', 'Guide'),
('1', 'pt', 'Guias'),
('2', 'de', 'Tests'),
('2', 'en', 'Reviews'),
('2', 'es', 'Análisis'),
('2', 'fr', 'Tests'),
('2', 'it', 'Recensioni'),
('2', 'pt', 'Análises');
DELETE FROM `nf_articles`;
INSERT INTO `nf_articles` (`article_id`, `category_id`, `series_id`, `series_order`, `user_id`, `image_id`, `date`, `published`, `featured`, `announced_at`, `views`, `deleted_at`, `deleted_by`) VALUES
('1', '1', NULL, '0', '331', NULL, '2026-09-01 11:46:28', '1', '0', '2026-09-01 11:46:28', '40', NULL, NULL),
('2', '1', NULL, '0', '332', NULL, '2026-09-04 11:46:28', '1', '0', '2026-09-04 11:46:28', '62', NULL, NULL),
('3', '2', NULL, '0', '333', NULL, '2026-09-07 11:46:28', '1', '0', '2026-09-07 11:46:28', '84', NULL, NULL),
('4', '2', NULL, '0', '334', NULL, '2026-09-10 11:46:28', '1', '0', '2026-09-10 11:46:28', '106', NULL, NULL);
DELETE FROM `nf_articles_lang`;
INSERT INTO `nf_articles_lang` (`article_id`, `lang`, `title`, `excerpt`, `content`, `tags`) VALUES
('1', 'de', 'Gut starten im Wettkampf', 'Unsere Tipps, um schnell besser zu werden.', '<h2>Die Grundlagen</h2><p>Beherrsche zuerst Zielen und Positionierung. Beständigkeit schlägt spektakuläre Aktionen.</p><h2>Teamgeist</h2><p>Kommunikation ist der Schlüssel zum Sieg.</p>', ''),
('1', 'en', 'Getting started in competitive play', 'Our tips to improve quickly.', '<h2>The basics</h2><p>Master your aim and positioning first. Consistency beats flashy plays.</p><h2>Team spirit</h2><p>Communication is the key to victory.</p>', ''),
('1', 'es', 'Empezar bien en competitivo', 'Nuestros consejos para progresar rápido.', '<h2>Lo básico</h2><p>Domina primero la puntería y la colocación. La constancia vale más que las jugadas vistosas.</p><h2>Espíritu de equipo</h2><p>La comunicación es la clave de la victoria.</p>', ''),
('1', 'fr', 'Bien débuter en compétitif', 'Nos conseils pour progresser rapidement.', '<h2>Les bases</h2><p>Maîtrisez d\'abord votre visée et votre placement. La régularité prime sur les coups d\'éclat.</p><h2>L\'esprit d\'équipe</h2><p>La communication est la clé de la victoire.</p>', ''),
('1', 'it', 'Iniziare bene nel competitivo', 'I nostri consigli per migliorare in fretta.', '<h2>Le basi</h2><p>Padroneggia prima la mira e il posizionamento. La costanza vale più delle giocate appariscenti.</p><h2>Spirito di squadra</h2><p>La comunicazione è la chiave della vittoria.</p>', ''),
('1', 'pt', 'Começar bem no competitivo', 'Os nossos conselhos para evoluir depressa.', '<h2>As bases</h2><p>Domina primeiro a mira e o posicionamento. A regularidade vale mais do que as jogadas vistosas.</p><h2>Espírito de equipa</h2><p>A comunicação é a chave da vitória.</p>', ''),
('2', 'de', 'Das eigene Setup optimieren', 'Einstellungen und Hardware für ein Top-Setup.', '<p>Ein gutes Setup ist nicht alles, aber es hilft. Hier unsere Empfehlungen zu Einstellungen und Peripherie.</p>', ''),
('2', 'en', 'Optimising your setup', 'Settings and gear for a top-notch setup.', '<p>A good setup isn\'t everything, but it helps. Here are our recommended settings and peripherals.</p>', ''),
('2', 'es', 'Optimizar tu configuración', 'Ajustes y material para un setup de primera.', '<p>Un buen setup no lo es todo, pero ayuda. Estas son nuestras recomendaciones de ajustes y periféricos.</p>', ''),
('2', 'fr', 'Optimiser sa configuration', 'Réglages et matériel pour un setup au top.', '<p>Un bon setup ne fait pas tout, mais il aide. Voici nos recommandations réglages et périphériques.</p>', ''),
('2', 'it', 'Ottimizzare la propria configurazione', 'Impostazioni e hardware per un setup al top.', '<p>Un buon setup non fa tutto, ma aiuta. Ecco i nostri consigli su impostazioni e periferiche.</p>', ''),
('2', 'pt', 'Otimizar a tua configuração', 'Definições e material para um setup de topo.', '<p>Um bom setup não faz tudo, mas ajuda. Aqui ficam as nossas recomendações de definições e periféricos.</p>', ''),
('3', 'de', 'Unsere Meinung zum letzten Patch', 'Was sich für die Wettkampf-Meta ändert.', '<p>Der letzte Patch mischt die Karten neu. Eine Analyse der Nerfs, Buffs und ihrer Wirkung auf die Meta.</p>', ''),
('3', 'en', 'Our take on the latest patch', 'What changes for the competitive meta.', '<p>The latest patch reshuffles the deck. An analysis of the nerfs, buffs and their impact on the meta.</p>', ''),
('3', 'es', 'Nuestra opinión sobre el último parche', 'Lo que cambia para el meta competitivo.', '<p>El último parche vuelve a repartir las cartas. Análisis de los nerfs, los buffs y su impacto en el meta.</p>', ''),
('3', 'fr', 'Notre avis sur le dernier patch', 'Ce qui change pour la méta compétitive.', '<p>Le dernier patch rebat les cartes. Analyse des nerfs, buffs et de leur impact sur la méta.</p>', ''),
('3', 'it', 'Il nostro parere sull’ultima patch', 'Cosa cambia per il meta competitivo.', '<p>L’ultima patch rimescola le carte. Analisi di nerf, buff e del loro impatto sul meta.</p>', ''),
('3', 'pt', 'A nossa opinião sobre o último patch', 'O que muda para o meta competitivo.', '<p>O último patch baralha as cartas. Análise dos nerfs, dos buffs e do seu impacto no meta.</p>', ''),
('4', 'de', 'Gaming-Headsets: der Vergleich', 'Wir haben die aktuellen Modelle für euch getestet.', '<p>Komfort, Klang, Mikrofon: unser ausführlicher Vergleich, damit ihr das richtige Headset findet.</p>', ''),
('4', 'en', 'Gaming headsets: the comparison', 'We tested the current models for you.', '<p>Comfort, sound, microphone: our full comparison to help you pick the right headset.</p>', ''),
('4', 'es', 'Auriculares gaming: el comparativo', 'Hemos probado por ti los modelos del momento.', '<p>Comodidad, sonido, micrófono: nuestro comparativo completo para elegir bien los auriculares.</p>', ''),
('4', 'fr', 'Casque gaming : le comparatif', 'On a testé pour vous les modèles du moment.', '<p>Confort, son, micro : notre comparatif complet pour choisir le bon casque.</p>', ''),
('4', 'it', 'Cuffie da gaming: il confronto', 'Abbiamo provato per voi i modelli del momento.', '<p>Comfort, audio, microfono: il nostro confronto completo per scegliere le cuffie giuste.</p>', ''),
('4', 'pt', 'Auscultadores gaming: o comparativo', 'Testámos por ti os modelos do momento.', '<p>Conforto, som, microfone: o nosso comparativo completo para escolheres os auscultadores certos.</p>', '');
DELETE FROM `nf_forum_categories`;
INSERT INTO `nf_forum_categories` (`category_id`, `title`, `order`, `image_id`, `vip_only`) VALUES
('1', 'Communauté', '0', NULL, '0'),
('2', 'Jeux & Compétition', '1', NULL, '0');
DELETE FROM `nf_forum_categories_lang`;
INSERT INTO `nf_forum_categories_lang` (`category_id`, `lang`, `title`) VALUES
('1', 'de', 'Gemeinschaft'),
('1', 'en', 'Community'),
('1', 'es', 'Comunidad'),
('1', 'fr', 'Communauté'),
('1', 'it', 'Comunità'),
('1', 'pt', 'Comunidade'),
('2', 'de', 'Spiele & Wettkampf'),
('2', 'en', 'Games & competition'),
('2', 'es', 'Juegos y competición'),
('2', 'fr', 'Jeux & Compétition'),
('2', 'it', 'Giochi e competizione'),
('2', 'pt', 'Jogos e competição');
DELETE FROM `nf_forum`;
INSERT INTO `nf_forum` (`forum_id`, `parent_id`, `is_subforum`, `title`, `description`, `icon`, `order`, `count_topics`, `count_messages`, `last_message_id`) VALUES
('1', '1', '0', 'Présentations', 'Présentez-vous à la communauté', '', '0', '2', '4', '6'),
('2', '1', '0', 'Discussions générales', 'Pour parler de tout et de rien', '', '1', '1', '2', '9'),
('3', '2', '0', 'Stratégies', 'Partagez vos tactiques', '', '0', '1', '3', '13'),
('4', '2', '0', 'Recherche d\'équipe', 'Trouvez des coéquipiers', '', '1', '1', '1', '15');
DELETE FROM `nf_forum_lang`;
INSERT INTO `nf_forum_lang` (`forum_id`, `lang`, `title`, `description`) VALUES
('1', 'de', 'Vorstellungen', 'Stell dich der Gemeinschaft vor'),
('1', 'en', 'Introductions', 'Introduce yourself to the community'),
('1', 'es', 'Presentaciones', 'Preséntate a la comunidad'),
('1', 'fr', 'Présentations', 'Présentez-vous à la communauté'),
('1', 'it', 'Presentazioni', 'Presentati alla comunità'),
('1', 'pt', 'Apresentações', 'Apresenta-te à comunidade'),
('2', 'de', 'Allgemeine Diskussionen', 'Für alles und nichts'),
('2', 'en', 'General discussion', 'To talk about anything and everything'),
('2', 'es', 'Charla general', 'Para hablar de todo un poco'),
('2', 'fr', 'Discussions générales', 'Pour parler de tout et de rien'),
('2', 'it', 'Discussioni generali', 'Per parlare di tutto e di niente'),
('2', 'pt', 'Conversa geral', 'Para falar de tudo um pouco'),
('3', 'de', 'Strategien', 'Teilt eure Taktiken'),
('3', 'en', 'Strategies', 'Share your tactics'),
('3', 'es', 'Estrategias', 'Comparte tus tácticas'),
('3', 'fr', 'Stratégies', 'Partagez vos tactiques'),
('3', 'it', 'Strategie', 'Condividi le tue tattiche'),
('3', 'pt', 'Estratégias', 'Partilha as tuas táticas'),
('4', 'de', 'Teamsuche', 'Findet Mitspieler'),
('4', 'en', 'Looking for a team', 'Find teammates'),
('4', 'es', 'Busco equipo', 'Encuentra compañeros'),
('4', 'fr', 'Recherche d\'équipe', 'Trouvez des coéquipiers'),
('4', 'it', 'Cerco squadra', 'Trova compagni di squadra'),
('4', 'pt', 'Procuro equipa', 'Encontra colegas de equipa');
DELETE FROM `nf_forum_url`;
INSERT INTO `nf_forum_url` (`forum_id`, `url`, `redirects`) VALUES
('1', '', '0'),
('2', '', '0'),
('3', '', '0'),
('4', '', '0');
DELETE FROM `nf_forum_topics`;
INSERT INTO `nf_forum_topics` (`topic_id`, `forum_id`, `message_id`, `title`, `status`, `prefix_id`, `solution_message_id`, `views`, `count_messages`, `last_message_id`, `is_announced`, `is_locked`) VALUES
('1', '1', '1', 'Salut tout le monde !', '0', NULL, NULL, '10', '3', '4', '0', '0'),
('2', '1', '5', 'Présentation rapide', '0', NULL, NULL, '19', '1', '6', '0', '0'),
('3', '2', '7', 'Votre setup du moment ?', '0', NULL, NULL, '28', '2', '9', '0', '0'),
('4', '3', '10', 'Gérer la pression en finale', '0', NULL, NULL, '37', '3', '13', '0', '0'),
('5', '4', '14', 'Cherche support pour ranked', '0', NULL, NULL, '46', '1', '15', '0', '0');
DELETE FROM `nf_forum_messages`;
INSERT INTO `nf_forum_messages` (`message_id`, `topic_id`, `parent_id`, `user_id`, `identity_id`, `message`, `date`, `deleted_at`, `deleted_by`, `deleted_reason`) VALUES
('1', '1', NULL, '271', NULL, 'Nouveau ici, hâte de jouer avec vous.', '2026-09-04 11:46:28', NULL, NULL, NULL),
('2', '1', NULL, '329', NULL, 'Bienvenue à toi !', '2026-09-04 12:46:28', NULL, NULL, NULL),
('3', '1', NULL, '330', NULL, 'Salut, on se voit en jeu !', '2026-09-04 13:46:28', NULL, NULL, NULL),
('4', '1', NULL, '331', NULL, 'Welcome !', '2026-09-04 14:46:28', NULL, NULL, NULL),
('5', '2', NULL, '329', NULL, 'Joueur depuis des années, ravi de rejoindre.', '2026-09-05 11:46:28', NULL, NULL, NULL),
('6', '2', NULL, '330', NULL, 'Bienvenue parmi nous !', '2026-09-05 12:46:28', NULL, NULL, NULL),
('7', '3', NULL, '330', NULL, 'Montrez vos installations !', '2026-09-06 11:46:28', NULL, NULL, NULL),
('8', '3', NULL, '331', NULL, 'Clavier méca + souris légère, le combo.', '2026-09-06 12:46:28', NULL, NULL, NULL),
('9', '3', NULL, '332', NULL, 'Double écran obligatoire pour moi.', '2026-09-06 13:46:28', NULL, NULL, NULL),
('10', '4', NULL, '331', NULL, 'Comment vous restez calmes dans les moments clés ?', '2026-09-07 11:46:28', NULL, NULL, NULL),
('11', '4', NULL, '332', NULL, 'Respiration et routine avant match.', '2026-09-07 12:46:28', NULL, NULL, NULL),
('12', '4', NULL, '333', NULL, 'On parle peu mais on parle utile.', '2026-09-07 13:46:28', NULL, NULL, NULL),
('13', '4', NULL, '334', NULL, 'Le mental, c\'est 50% du jeu.', '2026-09-07 14:46:28', NULL, NULL, NULL),
('14', '5', NULL, '332', NULL, 'Niveau diamant, dispo le soir.', '2026-09-08 11:46:28', NULL, NULL, NULL),
('15', '5', NULL, '333', NULL, 'Intéressé, je t\'ajoute !', '2026-09-08 12:46:28', NULL, NULL, NULL);
DELETE FROM `nf_gallery_categories`;
INSERT INTO `nf_gallery_categories` (`category_id`, `image_id`, `icon_id`, `name`) VALUES
('1', NULL, NULL, 'evenements'),
('2', NULL, NULL, 'highlights');
DELETE FROM `nf_gallery_categories_lang`;
INSERT INTO `nf_gallery_categories_lang` (`category_id`, `lang`, `title`) VALUES
('1', 'de', 'Veranstaltungen'),
('1', 'en', 'Events'),
('1', 'es', 'Eventos'),
('1', 'fr', 'Événements'),
('1', 'it', 'Eventi'),
('1', 'pt', 'Eventos'),
('2', 'de', 'Highlights'),
('2', 'en', 'Highlights'),
('2', 'es', 'Highlights'),
('2', 'fr', 'Highlights'),
('2', 'it', 'Highlights'),
('2', 'pt', 'Highlights');
DELETE FROM `nf_gallery`;
INSERT INTO `nf_gallery` (`gallery_id`, `category_id`, `image_id`, `name`, `published`, `date`, `deleted_at`, `deleted_by`) VALUES
('1', '1', '1', 'lan-d-ete-2025', '1', '2026-09-06 11:46:28', NULL, NULL),
('2', '1', '13', 'finale-regionale', '1', '2026-09-08 11:46:28', NULL, NULL),
('3', '2', '22', 'best-of-du-mois', '1', '2026-09-10 11:46:29', NULL, NULL);
DELETE FROM `nf_gallery_lang`;
INSERT INTO `nf_gallery_lang` (`gallery_id`, `lang`, `title`, `description`) VALUES
('1', 'de', 'Sommer-LAN 2025', 'Die besten Momente unserer jährlichen LAN.'),
('1', 'en', 'Summer LAN 2025', 'The best moments of our annual LAN.'),
('1', 'es', 'LAN de verano 2025', 'Los mejores momentos de nuestra LAN anual.'),
('1', 'fr', 'LAN d\'été 2025', 'Les meilleurs moments de notre LAN annuelle.'),
('1', 'it', 'LAN estiva 2025', 'I momenti migliori della nostra LAN annuale.'),
('1', 'pt', 'LAN de verão 2025', 'Os melhores momentos da nossa LAN anual.'),
('2', 'de', 'Regionalfinale', 'Unser Sieg in Bildern.'),
('2', 'en', 'Regional final', 'Our victory in pictures.'),
('2', 'es', 'Final regional', 'Nuestra victoria en imágenes.'),
('2', 'fr', 'Finale régionale', 'Retour en images sur notre victoire.'),
('2', 'it', 'Finale regionale', 'La nostra vittoria per immagini.'),
('2', 'pt', 'Final regional', 'A nossa vitória em imagens.'),
('3', 'de', 'Best of des Monats', 'Eine Sammlung der schönsten Spielzüge.'),
('3', 'en', 'Best of the month', 'A compilation of the finest plays.'),
('3', 'es', 'Lo mejor del mes', 'Una recopilación de las mejores jugadas.'),
('3', 'fr', 'Best of du mois', 'Compilation des plus belles actions.'),
('3', 'it', 'Il meglio del mese', 'Una raccolta delle azioni più belle.'),
('3', 'pt', 'O melhor do mês', 'Uma compilação das melhores jogadas.');
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
('1', 'de', 'Counter-Strike 2'),
('1', 'en', 'Counter-Strike 2'),
('1', 'es', 'Counter-Strike 2'),
('1', 'fr', 'Counter-Strike 2'),
('1', 'it', 'Counter-Strike 2'),
('1', 'pt', 'Counter-Strike 2'),
('2', 'de', 'Valorant'),
('2', 'en', 'Valorant'),
('2', 'es', 'Valorant'),
('2', 'fr', 'Valorant'),
('2', 'it', 'Valorant'),
('2', 'pt', 'Valorant'),
('3', 'de', 'League of Legends'),
('3', 'en', 'League of Legends'),
('3', 'es', 'League of Legends'),
('3', 'fr', 'League of Legends'),
('3', 'it', 'League of Legends'),
('3', 'pt', 'League of Legends'),
('4', 'de', 'Rocket League'),
('4', 'en', 'Rocket League'),
('4', 'es', 'Rocket League'),
('4', 'fr', 'Rocket League'),
('4', 'it', 'Rocket League'),
('4', 'pt', 'Rocket League');
DELETE FROM `nf_teams`;
INSERT INTO `nf_teams` (`team_id`, `game_id`, `image_id`, `icon_id`, `name`, `order`) VALUES
('1', '1', NULL, NULL, 'equipe-principale-cs2', '0'),
('2', '2', NULL, NULL, 'roster-valorant', '1'),
('3', '3', NULL, NULL, 'equipe-lol', '2');
DELETE FROM `nf_teams_lang`;
INSERT INTO `nf_teams_lang` (`team_id`, `lang`, `title`, `description`) VALUES
('1', 'de', 'CS2-Hauptteam', 'Unser Wettkampf-Roster in CS2.'),
('1', 'en', 'Main CS2 team', 'Our competitive CS2 roster.'),
('1', 'es', 'Equipo principal de CS2', 'Nuestro róster competitivo en CS2.'),
('1', 'fr', 'Équipe principale CS2', 'Notre roster compétitif sur CS2.'),
('1', 'it', 'Squadra principale CS2', 'Il nostro roster competitivo su CS2.'),
('1', 'pt', 'Equipa principal de CS2', 'O nosso plantel competitivo em CS2.'),
('2', 'de', 'Valorant-Roster', 'Fünf Spieler, ein Ziel: ganz nach oben.'),
('2', 'en', 'Valorant roster', 'Five players, one goal: the top.'),
('2', 'es', 'Róster de Valorant', 'Cinco jugadores, un objetivo: lo más alto.'),
('2', 'fr', 'Roster Valorant', 'Cinq joueurs, un objectif : le top.'),
('2', 'it', 'Roster Valorant', 'Cinque giocatori, un obiettivo: la vetta.'),
('2', 'pt', 'Plantel de Valorant', 'Cinco jogadores, um objetivo: o topo.'),
('3', 'de', 'LoL-Team', 'Die Kluft kann sich warm anziehen.'),
('3', 'en', 'LoL team', 'The Rift had better watch out.'),
('3', 'es', 'Equipo de LoL', 'La Grieta que se ande con cuidado.'),
('3', 'fr', 'Équipe LoL', 'La faille n\'a qu\'à bien se tenir.'),
('3', 'it', 'Squadra LoL', 'La Landa può tremare.'),
('3', 'pt', 'Equipa de LoL', 'A Fenda que se cuide.');
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
INSERT INTO `nf_events_types` (`type_id`, `type`, `color`, `icon`) VALUES
('1', '1', '#e74c3c', 'fas fa-trophy'),
('2', '1', '#3498db', 'fas fa-dumbbell');
DELETE FROM `nf_events_types_lang`;
INSERT INTO `nf_events_types_lang` (`type_id`, `lang`, `title`) VALUES
('1', 'fr', 'Tournoi'),
('1', 'en', 'Tournament'),
('1', 'de', 'Turnier'),
('1', 'es', 'Torneo'),
('1', 'it', 'Torneo'),
('1', 'pt', 'Torneio'),
('2', 'fr', 'Entraînement'),
('2', 'en', 'Practice'),
('2', 'de', 'Training'),
('2', 'es', 'Entrenamiento'),
('2', 'it', 'Allenamento'),
('2', 'pt', 'Treino');
DELETE FROM `nf_events`;
INSERT INTO `nf_events` (`event_id`, `type_id`, `user_id`, `image_id`, `date`, `date_end`, `published`, `publish_date`, `reminder_sent_at`, `series_id`) VALUES
('1', '1', '271', NULL, '2026-09-21 11:46:29', NULL, '1', '2026-09-11 11:46:29', NULL, NULL),
('2', '1', '271', NULL, '2026-09-28 11:46:29', NULL, '1', '2026-09-11 11:46:29', NULL, NULL),
('3', '2', '271', NULL, '2026-09-14 11:46:29', NULL, '1', '2026-09-11 11:46:29', NULL, NULL);
DELETE FROM `nf_events_lang`;
INSERT INTO `nf_events_lang` (`event_id`, `lang`, `title`, `description`, `private_description`, `location`) VALUES
('1', 'fr', 'Tournoi régional CS2', 'Qualifications ouvertes à tous les niveaux.', '', 'En ligne'),
('1', 'en', 'CS2 regional tournament', 'Qualifiers open to all levels.', '', 'Online'),
('1', 'de', 'CS2-Regionalturnier', 'Qualifikation für alle Spielstärken offen.', '', 'Online'),
('1', 'es', 'Torneo regional de CS2', 'Clasificatorios abiertos a todos los niveles.', '', 'En línea'),
('1', 'it', 'Torneo regionale CS2', 'Qualificazioni aperte a tutti i livelli.', '', 'Online'),
('1', 'pt', 'Torneio regional de CS2', 'Apuramentos abertos a todos os níveis.', '', 'Online'),
('2', 'fr', 'Coupe Valorant communautaire', 'Format double élimination, BO3.', '', 'Discord'),
('2', 'en', 'Community Valorant cup', 'Double elimination, best of three.', '', 'Discord'),
('2', 'de', 'Community-Valorant-Pokal', 'Doppel-K.-o.-System, Best of Three.', '', 'Discord'),
('2', 'es', 'Copa Valorant de la comunidad', 'Doble eliminación, al mejor de tres.', '', 'Discord'),
('2', 'it', 'Coppa Valorant della comunità', 'Doppia eliminazione, al meglio delle tre.', '', 'Discord'),
('2', 'pt', 'Taça Valorant da comunidade', 'Dupla eliminação, à melhor de três.', '', 'Discord'),
('3', 'fr', 'Entraînement hebdo LoL', 'Scrims et review de games.', '', 'Faille de l\'invocateur'),
('3', 'en', 'Weekly LoL practice', 'Scrims and game reviews.', '', 'Summoner\'s Rift'),
('3', 'de', 'Wöchentliches LoL-Training', 'Scrims und Spielanalysen.', '', 'Die Kluft der Beschwörer'),
('3', 'es', 'Entrenamiento semanal de LoL', 'Scrims y análisis de partidas.', '', 'Grieta del Invocador'),
('3', 'it', 'Allenamento settimanale LoL', 'Scrim e analisi delle partite.', '', 'Landa degli Evocatori'),
('3', 'pt', 'Treino semanal de LoL', 'Scrims e análise de jogos.', '', 'Fenda do Invocador');
DELETE FROM `nf_awards`;
INSERT INTO `nf_awards` (`award_id`, `team_id`, `game_id`, `image_id`, `date`, `platform`, `ranking`, `participants`) VALUES
('1', '1', '1', NULL, '2026-08-07', 'PC', '1', '16'),
('2', '2', '2', NULL, '2026-08-07', 'PC', '2', '24'),
('3', '3', '3', NULL, '2026-08-07', 'PC', '3', '12');
DELETE FROM `nf_awards_lang`;
INSERT INTO `nf_awards_lang` (`award_id`, `lang`, `name`, `location`, `description`) VALUES
('1', 'fr', 'Tournoi régional 2025', 'Lyon', ''),
('1', 'en', 'Regional tournament 2025', 'Lyon', ''),
('1', 'de', 'Regionalturnier 2025', 'Lyon', ''),
('1', 'es', 'Torneo regional 2025', 'Lyon', ''),
('1', 'it', 'Torneo regionale 2025', 'Lione', ''),
('1', 'pt', 'Torneio regional 2025', 'Lião', ''),
('2', 'fr', 'Coupe d\'hiver', 'En ligne', ''),
('2', 'en', 'Winter cup', 'Online', ''),
('2', 'de', 'Winterpokal', 'Online', ''),
('2', 'es', 'Copa de invierno', 'En línea', ''),
('2', 'it', 'Coppa invernale', 'Online', ''),
('2', 'pt', 'Taça de inverno', 'Online', ''),
('3', 'fr', 'Ligue communautaire', 'En ligne', ''),
('3', 'en', 'Community league', 'Online', ''),
('3', 'de', 'Community-Liga', 'Online', ''),
('3', 'es', 'Liga comunitaria', 'En línea', ''),
('3', 'it', 'Lega della comunità', 'Online', ''),
('3', 'pt', 'Liga da comunidade', 'Online', '');
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
INSERT INTO `nf_faq_categories` (`id`, `sort_order`) VALUES
('1', '0');
DELETE FROM `nf_faq_categories_lang`;
INSERT INTO `nf_faq_categories_lang` (`category_id`, `lang`, `title`) VALUES
('1', 'fr', 'Général'),
('1', 'en', 'General'),
('1', 'de', 'Allgemein'),
('1', 'es', 'General'),
('1', 'it', 'Generale'),
('1', 'pt', 'Geral');
DELETE FROM `nf_faq_questions`;
INSERT INTO `nf_faq_questions` (`id`, `category_id`, `sort_order`, `published`, `created_at`, `updated_at`) VALUES
('1', '1', '0', '1', '2026-09-16 11:46:29', '2026-09-16 11:46:29'),
('2', '1', '1', '1', '2026-09-16 11:46:29', '2026-09-16 11:46:29'),
('3', '1', '2', '1', '2026-09-16 11:46:29', '2026-09-16 11:46:29');
DELETE FROM `nf_faq_questions_lang`;
INSERT INTO `nf_faq_questions_lang` (`question_id`, `lang`, `question`, `answer`) VALUES
('1', 'fr', 'Comment rejoindre la communauté ?', 'Inscrivez-vous gratuitement, puis présentez-vous sur le forum.'),
('1', 'en', 'How do I join the community?', 'Sign up for free, then introduce yourself on the forum.'),
('1', 'de', 'Wie trete ich der Community bei?', 'Melde dich kostenlos an und stelle dich im Forum vor.'),
('1', 'es', '¿Cómo me uno a la comunidad?', 'Regístrate gratis y luego preséntate en el foro.'),
('1', 'it', 'Come posso unirmi alla comunità ?', 'Iscriviti gratuitamente, poi presentati sul forum.'),
('1', 'pt', 'Como me junto à comunidade?', 'Regista-te gratuitamente e apresenta-te no fórum.'),
('2', 'fr', 'Le site est-il gratuit ?', 'Oui, NeoFrag Reborn est 100% gratuit et open source.'),
('2', 'en', 'Is the site free?', 'Yes, NeoFrag Reborn is 100% free and open source.'),
('2', 'de', 'Ist die Seite kostenlos?', 'Ja, NeoFrag Reborn ist zu 100 % kostenlos und quelloffen.'),
('2', 'es', '¿El sitio es gratuito?', 'Sí, NeoFrag Reborn es 100% gratuito y de código abierto.'),
('2', 'it', 'Il sito è gratuito ?', 'Sì, NeoFrag Reborn è 100% gratuito e open source.'),
('2', 'pt', 'O site é gratuito?', 'Sim, o NeoFrag Reborn é 100% gratuito e de código aberto.'),
('3', 'fr', 'Comment gagner des points ?', 'En participant : poster, commenter, réagir et contribuer.'),
('3', 'en', 'How do I earn points?', 'By taking part: posting, commenting, reacting and contributing.'),
('3', 'de', 'Wie verdiene ich Punkte?', 'Durch Mitmachen: posten, kommentieren, reagieren und beitragen.'),
('3', 'es', '¿Cómo gano puntos?', 'Participando: publicar, comentar, reaccionar y contribuir.'),
('3', 'it', 'Come si guadagnano punti ?', 'Partecipando: pubblicare, commentare, reagire e contribuire.'),
('3', 'pt', 'Como ganho pontos?', 'Participando: publicar, comentar, reagir e contribuir.');
DELETE FROM `nf_downloads_categories`;
INSERT INTO `nf_downloads_categories` (`id`, `sort_order`) VALUES
('1', '0');
DELETE FROM `nf_downloads_categories_lang`;
INSERT INTO `nf_downloads_categories_lang` (`category_id`, `lang`, `title`, `description`) VALUES
('1', 'fr', 'Ressources', 'Configs, fonds d\'écran et outils.'),
('1', 'en', 'Resources', 'Configs, wallpapers and tools.'),
('1', 'de', 'Ressourcen', 'Konfigurationen, Hintergrundbilder und Werkzeuge.'),
('1', 'es', 'Recursos', 'Configuraciones, fondos de pantalla y herramientas.'),
('1', 'it', 'Risorse', 'Configurazioni, sfondi e strumenti.'),
('1', 'pt', 'Recursos', 'Configurações, fundos de ecrã e ferramentas.');
DELETE FROM `nf_downloads`;
INSERT INTO `nf_downloads` (`id`, `category_id`, `file_url`, `file_size_bytes`, `file_type`, `version`, `downloads_count`, `published`, `created_at`) VALUES
('1', '1', 'https://example.com/wallpapers.zip', '15728640', 'zip', '1.0', '20', '1', '2026-09-16 11:46:29'),
('2', '1', 'https://example.com/cs2-config.cfg', '8192', 'cfg', '2.3', '51', '1', '2026-09-16 11:46:29');
DELETE FROM `nf_downloads_lang`;
INSERT INTO `nf_downloads_lang` (`download_id`, `lang`, `title`, `description`) VALUES
('1', 'fr', 'Pack de fonds d\'écran', 'Une sélection de wallpapers aux couleurs de la team.'),
('1', 'en', 'Wallpaper pack', 'A selection of wallpapers in the team\'s colours.'),
('1', 'de', 'Hintergrundbild-Paket', 'Eine Auswahl an Wallpapern in den Farben des Teams.'),
('1', 'es', 'Pack de fondos de pantalla', 'Una selección de fondos con los colores del equipo.'),
('1', 'it', 'Pacchetto di sfondi', 'Una selezione di sfondi con i colori del team.'),
('1', 'pt', 'Pacote de fundos de ecrã', 'Uma selecção de fundos com as cores da equipa.'),
('2', 'fr', 'Config CS2 recommandée', 'Notre fichier de configuration partagé.'),
('2', 'en', 'Recommended CS2 config', 'Our shared configuration file.'),
('2', 'de', 'Empfohlene CS2-Konfiguration', 'Unsere gemeinsame Konfigurationsdatei.'),
('2', 'es', 'Configuración de CS2 recomendada', 'Nuestro archivo de configuración compartido.'),
('2', 'it', 'Configurazione CS2 consigliata', 'Il nostro file di configurazione condiviso.'),
('2', 'pt', 'Configuração de CS2 recomendada', 'O nosso ficheiro de configuração partilhado.');
DELETE FROM `nf_links_categories`;
INSERT INTO `nf_links_categories` (`id`, `sort_order`) VALUES
('1', '0');
DELETE FROM `nf_links_categories_lang`;
INSERT INTO `nf_links_categories_lang` (`category_id`, `lang`, `title`) VALUES
('1', 'fr', 'Liens utiles'),
('1', 'en', 'Useful links'),
('1', 'de', 'Nützliche Links'),
('1', 'es', 'Enlaces útiles'),
('1', 'it', 'Link utili'),
('1', 'pt', 'Ligações úteis');
DELETE FROM `nf_links`;
INSERT INTO `nf_links` (`id`, `category_id`, `url`, `clicks`, `published`, `sort_order`, `created_at`) VALUES
('1', '1', 'https://discord.gg/example', '0', '1', '0', '2026-09-16 11:46:29'),
('2', '1', 'https://twitch.tv/example', '13', '1', '1', '2026-09-16 11:46:29'),
('3', '1', 'https://neofr.ag', '26', '1', '2', '2026-09-16 11:46:29');
DELETE FROM `nf_links_lang`;
INSERT INTO `nf_links_lang` (`link_id`, `lang`, `title`, `description`) VALUES
('1', 'fr', 'Notre Discord', 'Rejoignez le serveur vocal de la communauté.'),
('1', 'en', 'Our Discord', 'Join the community\'s voice server.'),
('1', 'de', 'Unser Discord', 'Tritt dem Sprachserver der Community bei.'),
('1', 'es', 'Nuestro Discord', 'Únete al servidor de voz de la comunidad.'),
('1', 'it', 'Il nostro Discord', 'Unisciti al server vocale della comunità.'),
('1', 'pt', 'O nosso Discord', 'Junta-te ao servidor de voz da comunidade.'),
('2', 'fr', 'Chaîne Twitch', 'Suivez nos lives et tournois.'),
('2', 'en', 'Twitch channel', 'Follow our streams and tournaments.'),
('2', 'de', 'Twitch-Kanal', 'Verfolge unsere Streams und Turniere.'),
('2', 'es', 'Canal de Twitch', 'Sigue nuestros directos y torneos.'),
('2', 'it', 'Canale Twitch', 'Segui le nostre dirette e i tornei.'),
('2', 'pt', 'Canal de Twitch', 'Segue as nossas transmissões e torneios.'),
('3', 'fr', 'NeoFrag Reborn', 'Le CMS qui propulse ce site.'),
('3', 'en', 'NeoFrag Reborn', 'The CMS that powers this site.'),
('3', 'de', 'NeoFrag Reborn', 'Das CMS, das diese Seite antreibt.'),
('3', 'es', 'NeoFrag Reborn', 'El CMS que impulsa este sitio.'),
('3', 'it', 'NeoFrag Reborn', 'Il CMS che fa funzionare questo sito.'),
('3', 'pt', 'NeoFrag Reborn', 'O CMS que move este site.');
DELETE FROM `nf_partners`;
INSERT INTO `nf_partners` (`partner_id`, `name`, `logo_light`, `logo_dark`, `website`, `facebook`, `twitter`, `code`, `count`, `order`) VALUES
('1', 'gamergear', NULL, NULL, 'https://example.com', '', '', '', '0', '0'),
('2', 'energydrink', NULL, NULL, 'https://example.com', '', '', '', '9', '1'),
('3', 'hostpro', NULL, NULL, 'https://example.com', '', '', '', '18', '2');
DELETE FROM `nf_partners_lang`;
INSERT INTO `nf_partners_lang` (`partner_id`, `lang`, `title`, `description`) VALUES
('1', 'de', 'GamerGear', 'Offizieller Partner für Gaming-Hardware.'),
('1', 'en', 'GamerGear', 'Official gaming hardware partner.'),
('1', 'es', 'GamerGear', 'Socio oficial de material gaming.'),
('1', 'fr', 'GamerGear', 'Matériel gaming partenaire officiel.'),
('1', 'it', 'GamerGear', 'Partner ufficiale per l’hardware da gioco.'),
('1', 'pt', 'GamerGear', 'Parceiro oficial de material gaming.'),
('2', 'de', 'EnergyDrink', 'Der Treibstoff unserer Spieler.'),
('2', 'en', 'EnergyDrink', 'The fuel of our players.'),
('2', 'es', 'EnergyDrink', 'El combustible de nuestros jugadores.'),
('2', 'fr', 'EnergyDrink', 'Le carburant de nos joueurs.'),
('2', 'it', 'EnergyDrink', 'Il carburante dei nostri giocatori.'),
('2', 'pt', 'EnergyDrink', 'O combustível dos nossos jogadores.'),
('3', 'de', 'HostPro', 'Hochleistungs-Gameserver.'),
('3', 'en', 'HostPro', 'High-performance game servers.'),
('3', 'es', 'HostPro', 'Servidores de juego de alto rendimiento.'),
('3', 'fr', 'HostPro', 'Serveurs de jeu haute performance.'),
('3', 'it', 'HostPro', 'Server di gioco ad alte prestazioni.'),
('3', 'pt', 'HostPro', 'Servidores de jogo de alto desempenho.');
DELETE FROM `nf_guestbook`;
INSERT INTO `nf_guestbook` (`id`, `user_id`, `name`, `message`, `ip_address`, `status`, `created_at`) VALUES
('1', '271', 'Alex', 'Super communauté, accueil au top !', '', 'approved', '2026-09-15 11:46:29'),
('2', '329', 'Sam', 'Le site est vraiment propre, bravo.', '', 'approved', '2026-09-14 11:46:29'),
('3', '330', 'Jordan', 'Hâte de participer au prochain tournoi.', '', 'approved', '2026-09-13 11:46:29');
DELETE FROM `nf_surveys`;
INSERT INTO `nf_surveys` (`id`, `user_id`, `multiple_choice`, `show_results`, `closed_at`, `published`, `created_at`) VALUES
('1', '271', '0', 'always', NULL, '1', '2026-09-16 11:46:29');
DELETE FROM `nf_surveys_lang`;
INSERT INTO `nf_surveys_lang` (`survey_id`, `lang`, `title`, `description`) VALUES
('1', 'fr', 'Quel jeu pour le prochain tournoi ?', 'Votez pour le jeu de notre prochain événement.'),
('1', 'en', 'Which game for the next tournament?', 'Vote for the game of our next event.'),
('1', 'de', 'Welches Spiel für das nächste Turnier?', 'Stimme für das Spiel unseres nächsten Events ab.'),
('1', 'es', '¿Qué juego para el próximo torneo?', 'Vota por el juego de nuestro próximo evento.'),
('1', 'it', 'Quale gioco per il prossimo torneo?', 'Vota il gioco del nostro prossimo evento.'),
('1', 'pt', 'Que jogo para o próximo torneio?', 'Vota no jogo do nosso próximo evento.');
DELETE FROM `nf_surveys_options`;
INSERT INTO `nf_surveys_options` (`id`, `survey_id`, `sort_order`) VALUES
('1', '1', '0'),
('2', '1', '1'),
('3', '1', '2'),
('4', '1', '3');
DELETE FROM `nf_surveys_options_lang`;
INSERT INTO `nf_surveys_options_lang` (`option_id`, `lang`, `label`) VALUES
('1', 'fr', 'Counter-Strike 2'),
('1', 'en', 'Counter-Strike 2'),
('1', 'de', 'Counter-Strike 2'),
('1', 'es', 'Counter-Strike 2'),
('1', 'it', 'Counter-Strike 2'),
('1', 'pt', 'Counter-Strike 2'),
('2', 'fr', 'Valorant'),
('2', 'en', 'Valorant'),
('2', 'de', 'Valorant'),
('2', 'es', 'Valorant'),
('2', 'it', 'Valorant'),
('2', 'pt', 'Valorant'),
('3', 'fr', 'League of Legends'),
('3', 'en', 'League of Legends'),
('3', 'de', 'League of Legends'),
('3', 'es', 'League of Legends'),
('3', 'it', 'League of Legends'),
('3', 'pt', 'League of Legends'),
('4', 'fr', 'Rocket League'),
('4', 'en', 'Rocket League'),
('4', 'de', 'Rocket League'),
('4', 'es', 'Rocket League'),
('4', 'it', 'Rocket League'),
('4', 'pt', 'Rocket League');
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
INSERT INTO `nf_recruits` (`recruit_id`, `date`, `user_id`, `size`, `role`, `icon`, `date_end`, `closed`, `team_id`, `image_id`) VALUES
('1', '2026-09-13 11:46:29', '271', '1', 'AWPer', 'fas fa-crosshairs', '2026-10-06', '0', NULL, NULL),
('2', '2026-09-12 11:46:29', '271', '1', 'Initiateur', 'fas fa-shield', '2026-10-06', '0', NULL, NULL);
DELETE FROM `nf_recruits_lang`;
INSERT INTO `nf_recruits_lang` (`recruit_id`, `lang`, `title`, `introduction`, `description`, `requierments`) VALUES
('1', 'fr', 'Recrutement CS2 - Joueur AWP', 'Notre équipe CS2 cherche un sniper.', 'Tu maîtrises l\'AWP et tu cherches une équipe sérieuse ? Postule !', 'Niveau Faceit 7+, dispo 3 soirs/semaine, micro obligatoire.'),
('1', 'en', 'CS2 recruitment - AWP player', 'Our CS2 team is looking for a sniper.', 'You master the AWP and want a serious team? Apply!', 'Faceit level 7+, available 3 evenings a week, microphone required.'),
('1', 'de', 'CS2-Rekrutierung - AWP-Spieler', 'Unser CS2-Team sucht einen Sniper.', 'Du beherrschst die AWP und suchst ein ernsthaftes Team? Bewirb dich!', 'Faceit-Level 7+, an 3 Abenden pro Woche verfügbar, Mikrofon Pflicht.'),
('1', 'es', 'Reclutamiento CS2 - Jugador AWP', 'Nuestro equipo de CS2 busca un francotirador.', '¿Dominas la AWP y buscas un equipo serio? ¡Postúlate!', 'Nivel Faceit 7+, disponible 3 noches por semana, micrófono obligatorio.'),
('1', 'it', 'Reclutamento CS2 - Giocatore AWP', 'La nostra squadra di CS2 cerca un cecchino.', 'Padroneggi l’AWP e cerchi una squadra seria? Candidati!', 'Livello Faceit 7+, disponibile 3 sere a settimana, microfono obbligatorio.'),
('1', 'pt', 'Recrutamento CS2 - Jogador AWP', 'A nossa equipa de CS2 procura um sniper.', 'Dominas a AWP e procuras uma equipa séria? Candidata-te!', 'Nível Faceit 7+, disponível 3 noites por semana, microfone obrigatório.'),
('2', 'fr', 'Recrutement Valorant - Support', 'On cherche un joueur support/initiateur.', 'Rejoins notre roster Valorant en construction.', 'Immortal+, bonne communication, esprit d\'équipe.'),
('2', 'en', 'Valorant recruitment - Support', 'We are looking for a support/initiator player.', 'Join our Valorant roster under construction.', 'Immortal+, good communication, team spirit.'),
('2', 'de', 'Valorant-Rekrutierung - Support', 'Wir suchen einen Support-/Initiator-Spieler.', 'Komm in unser Valorant-Roster im Aufbau.', 'Immortal+, gute Kommunikation, Teamgeist.'),
('2', 'es', 'Reclutamiento Valorant - Apoyo', 'Buscamos un jugador de apoyo/iniciador.', 'Únete a nuestro roster de Valorant en construcción.', 'Immortal+, buena comunicación, espíritu de equipo.'),
('2', 'it', 'Reclutamento Valorant - Supporto', 'Cerchiamo un giocatore di supporto/iniziatore.', 'Unisciti al nostro roster di Valorant in costruzione.', 'Immortal+, buona comunicazione, spirito di squadra.'),
('2', 'pt', 'Recrutamento Valorant - Suporte', 'Procuramos um jogador de suporte/iniciador.', 'Junta-te ao nosso roster de Valorant em construção.', 'Immortal+, boa comunicação, espírito de equipa.');
DELETE FROM `nf_recruits_fields`;
INSERT INTO `nf_recruits_fields` (`field_id`, `recruit_id`, `type`, `required`, `sort_order`) VALUES
('1', '1', 'text', '1', '0'),
('2', '1', 'text', '1', '1'),
('3', '1', 'textarea', '0', '2'),
('4', '2', 'text', '1', '0'),
('5', '2', 'text', '1', '1'),
('6', '2', 'textarea', '0', '2');
DELETE FROM `nf_recruits_fields_lang`;
INSERT INTO `nf_recruits_fields_lang` (`field_id`, `lang`, `label`) VALUES
('1', 'fr', 'Quel est ton pseudo en jeu ?'),
('1', 'en', 'What is your in-game name?'),
('1', 'de', 'Wie lautet dein Spielername?'),
('1', 'es', '¿Cuál es tu nombre en el juego?'),
('1', 'it', 'Qual è il tuo nickname in gioco?'),
('1', 'pt', 'Qual é o teu nome no jogo?'),
('2', 'fr', 'Quelles sont tes disponibilités en soirée ?'),
('2', 'en', 'When are you available in the evenings?'),
('2', 'de', 'Wann bist du abends verfügbar?'),
('2', 'es', '¿Qué disponibilidad tienes por las noches?'),
('2', 'it', 'Quali sono le tue disponibilità serali?'),
('2', 'pt', 'Qual é a tua disponibilidade à noite?'),
('3', 'fr', 'Décris une situation où tu as pris le lead.'),
('3', 'en', 'Describe a situation where you took the lead.'),
('3', 'de', 'Beschreibe eine Situation, in der du die Führung übernommen hast.'),
('3', 'es', 'Describe una situación en la que tomaste la iniciativa.'),
('3', 'it', 'Descrivi una situazione in cui hai preso il comando.'),
('3', 'pt', 'Descreve uma situação em que assumiste a liderança.'),
('4', 'fr', 'Quel est ton pseudo en jeu ?'),
('4', 'en', 'What is your in-game name?'),
('4', 'de', 'Wie lautet dein Spielername?'),
('4', 'es', '¿Cuál es tu nombre en el juego?'),
('4', 'it', 'Qual è il tuo nickname in gioco?'),
('4', 'pt', 'Qual é o teu nome no jogo?'),
('5', 'fr', 'Quelles sont tes disponibilités en soirée ?'),
('5', 'en', 'When are you available in the evenings?'),
('5', 'de', 'Wann bist du abends verfügbar?'),
('5', 'es', '¿Qué disponibilidad tienes por las noches?'),
('5', 'it', 'Quali sono le tue disponibilità serali?'),
('5', 'pt', 'Qual é a tua disponibilidade à noite?'),
('6', 'fr', 'Décris une situation où tu as pris le lead.'),
('6', 'en', 'Describe a situation where you took the lead.'),
('6', 'de', 'Beschreibe eine Situation, in der du die Führung übernommen hast.'),
('6', 'es', 'Describe una situación en la que tomaste la iniciativa.'),
('6', 'it', 'Descrivi una situazione in cui hai preso il comando.'),
('6', 'pt', 'Descreve uma situação em que assumiste a liderança.');
DELETE FROM `nf_calendar_events`;
INSERT INTO `nf_calendar_events` (`id`, `title`, `description`, `location`, `start_at`, `end_at`, `all_day`, `user_id`, `color`, `published`, `created_at`, `reminder_sent_at`) VALUES
('1', 'Entraînement CS2', 'Scrims du soir', 'Serveur communautaire', '2026-09-18 11:46:29', '2026-09-18 13:46:29', '0', '271', '#1abc9c', '1', '2026-09-16 11:46:29', NULL),
('2', 'Soirée détente', 'Parties fun ouvertes à tous', 'Discord', '2026-09-21 11:46:29', '2026-09-21 13:46:29', '0', '271', '#1abc9c', '1', '2026-09-16 11:46:29', NULL),
('3', 'Maintenance serveur', 'Indisponibilité prévue', '', '2026-09-25 11:46:29', '2026-09-25 13:46:29', '1', '271', '#1abc9c', '1', '2026-09-16 11:46:29', NULL);
DELETE FROM `nf_bug_tickets`;
INSERT INTO `nf_bug_tickets` (`id`, `title`, `description`, `type`, `priority`, `status`, `duplicate_of`, `user_id`, `assigned_to`, `created_at`, `updated_at`) VALUES
('1', 'Bouton de connexion mal aligné sur mobile', 'Sur petit écran, le bouton dépasse légèrement.', 'bug', 'normal', 'resolved', NULL, '329', NULL, '2026-09-16 11:46:29', '2026-09-16 11:46:29'),
('2', 'Ajouter un mode sombre au profil', 'Ce serait agréable d\'avoir le thème sombre partout.', 'feature', 'low', 'open', NULL, '330', NULL, '2026-09-16 11:46:29', '2026-09-16 11:46:29'),
('3', 'Comment changer mon avatar ?', 'Je ne trouve pas l\'option dans les réglages.', 'question', 'normal', 'closed', NULL, '331', NULL, '2026-09-16 11:46:29', '2026-09-16 11:46:29');
DELETE FROM `nf_bug_comments`;
INSERT INTO `nf_bug_comments` (`id`, `ticket_id`, `user_id`, `content`, `is_status_change`, `author_provider`, `author_external_id`, `author_name`, `created_at`) VALUES
('1', '1', '271', 'Merci pour le retour, on regarde ça.', '0', NULL, NULL, NULL, '2026-09-16 10:46:29'),
('2', '2', '271', 'Merci pour le retour, on regarde ça.', '0', NULL, NULL, NULL, '2026-09-16 09:46:29'),
('3', '3', '271', 'Merci pour le retour, on regarde ça.', '0', NULL, NULL, NULL, '2026-09-16 08:46:29');
DELETE FROM `nf_donations_campaigns`;
INSERT INTO `nf_donations_campaigns` (`id`, `name`, `goal_amount`, `currency`, `paypal_email`, `paypal_button_id`, `deadline`, `status`, `created_at`, `updated_at`) VALUES
('1', 'serveur-2025', '500.00', 'EUR', '', '', '2026-11-15', 'active', '2026-09-16 11:46:29', '2026-09-16 11:46:29');
DELETE FROM `nf_donations_campaigns_lang`;
INSERT INTO `nf_donations_campaigns_lang` (`campaign_id`, `lang`, `title`, `description`) VALUES
('1', 'fr', 'Financement du serveur 2025', 'Aidez-nous à financer l\'hébergement de nos serveurs de jeu.'),
('1', 'en', 'Server funding 2025', 'Help us fund the hosting of our game servers.'),
('1', 'de', 'Serverfinanzierung 2025', 'Helft uns, das Hosting unserer Spieleserver zu finanzieren.'),
('1', 'es', 'Financiación del servidor 2025', 'Ayúdanos a financiar el alojamiento de nuestros servidores de juego.'),
('1', 'it', 'Finanziamento del server 2025', 'Aiutaci a finanziare l’hosting dei nostri server di gioco.'),
('1', 'pt', 'Financiamento do servidor 2025', 'Ajuda-nos a financiar o alojamento dos nossos servidores de jogo.');
DELETE FROM `nf_donations`;
INSERT INTO `nf_donations` (`id`, `campaign_id`, `user_id`, `donor_name`, `amount`, `currency`, `message`, `is_anonymous`, `is_public`, `source`, `paypal_txn_id`, `status`, `created_at`) VALUES
('1', '1', '271', 'Alex', '20.00', 'EUR', 'Bon courage à toute la team !', '0', '1', 'manual', NULL, 'completed', '2026-09-15 11:46:29'),
('2', '1', '329', 'Anonyme', '10.00', 'EUR', '', '1', '1', 'manual', NULL, 'completed', '2026-09-14 11:46:29'),
('3', '1', '330', 'Sam', '50.00', 'EUR', 'Continuez comme ça.', '0', '1', 'manual', NULL, 'completed', '2026-09-13 11:46:29');
DELETE FROM `nf_ads`;
INSERT INTO `nf_ads` (`id`, `placement`, `format`, `image_url`, `url`, `html`, `active`, `starts_at`, `ends_at`, `position`, `impressions`, `clicks`, `created_at`) VALUES
('1', 'sidebar', 'html', '', 'https://example.com', '<div style=\"padding:1rem;text-align:center\">Espace partenaire</div>', '1', NULL, NULL, '0', '120', '8', '2026-09-16 11:46:29');
DELETE FROM `nf_ads_lang`;
INSERT INTO `nf_ads_lang` (`ad_id`, `lang`, `title`) VALUES
('1', 'fr', 'Bannière partenaire'),
('1', 'en', 'Partner banner'),
('1', 'de', 'Partnerbanner'),
('1', 'es', 'Banner de socio'),
('1', 'it', 'Banner partner'),
('1', 'pt', 'Banner de parceiro');
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
('1', 'de', 'Über die Gemeinschaft', 'Wer wir sind', '<p>2019 rund um Counter-Strike gegründet, versammelt unsere Gemeinschaft heute rund hundert Spielerinnen und Spieler in vier Spielen. Man kommt wegen des Niveaus und bleibt wegen der Stimmung.</p><p>Drei Wettkampfteams, wöchentliches Training, eine jährliche LAN – und ein Discord-Server, der allen offensteht.</p>'),
('1', 'en', 'About the community', 'Who we are', '<p>Founded in 2019 around Counter-Strike, our community now brings together a hundred or so players across four games. People come for the level and stay for the atmosphere.</p><p>Three competitive teams, weekly practice sessions, an annual LAN — and a Discord server open to everyone.</p>'),
('1', 'es', 'Sobre la comunidad', 'Quiénes somos', '<p>Fundada en 2019 en torno a Counter-Strike, nuestra comunidad reúne hoy a un centenar de jugadores en cuatro juegos. Se viene por el nivel y se queda uno por el ambiente.</p><p>Tres equipos competitivos, entrenamientos semanales, una LAN anual y un servidor de Discord abierto a todo el mundo.</p>'),
('1', 'fr', 'À propos de la communauté', 'Qui sommes-nous', '<p>Fondée en 2019 autour de Counter-Strike, notre communauté réunit aujourd\'hui une centaine de joueurs sur quatre jeux. On y vient pour le niveau, on y reste pour l\'ambiance.</p><p>Trois équipes compétitives, des entraînements hebdomadaires, une LAN annuelle — et un serveur Discord ouvert à tous.</p>'),
('1', 'it', 'La comunità', 'Chi siamo', '<p>Fondata nel 2019 attorno a Counter-Strike, la nostra comunità riunisce oggi un centinaio di giocatori su quattro giochi. Si arriva per il livello e si resta per l’atmosfera.</p><p>Tre squadre competitive, allenamenti settimanali, una LAN annuale e un server Discord aperto a tutti.</p>'),
('1', 'pt', 'Sobre a comunidade', 'Quem somos', '<p>Fundada em 2019 à volta do Counter-Strike, a nossa comunidade reúne hoje uma centena de jogadores em quatro jogos. Vem-se pelo nível e fica-se pelo ambiente.</p><p>Três equipas competitivas, treinos semanais, uma LAN anual e um servidor de Discord aberto a todos.</p>'),
('2', 'de', 'Hausordnung', 'Was wir von allen erwarten', '<p>Respekt vor allem: keine Beleidigungen, keine diskriminierenden Äußerungen, kein Cheaten. Ein Verstoß wird zuerst verwarnt, danach ausgeschlossen.</p><ul><li>Mikrofon im Training empfohlen, im offiziellen Match Pflicht.</li><li>Abwesenheit mindestens 24 Stunden vorher ankündigen.</li><li>Das Staff entscheidet bei Streitfällen; seine Entscheidungen sind öffentlich.</li></ul>'),
('2', 'en', 'House rules', 'What we expect from everyone', '<p>Respect above all: no insults, no discriminatory remarks, no cheating. A breach is first met with a warning, then with an exclusion.</p><ul><li>Microphone recommended in practice, mandatory in official matches.</li><li>Let us know if you will be absent, at least 24 hours in advance.</li><li>Staff settle disputes; their decisions are public.</li></ul>'),
('2', 'es', 'Normas internas', 'Lo que esperamos de cada uno', '<p>Respeto ante todo: nada de insultos, ni comentarios discriminatorios, ni trampas. Una falta se resuelve primero con un aviso y después con una expulsión.</p><ul><li>Micrófono recomendado en los entrenamientos y obligatorio en partido oficial.</li><li>Avisa de tus ausencias con al menos 24 horas de antelación.</li><li>El staff resuelve los conflictos; sus decisiones son públicas.</li></ul>'),
('2', 'fr', 'Règlement intérieur', 'Ce qu\'on attend de chacun', '<p>Respect avant tout : pas d\'insultes, pas de propos discriminatoires, pas de triche. Un manquement se règle d\'abord par un avertissement, ensuite par une exclusion.</p><ul><li>Micro conseillé en entraînement, obligatoire en match officiel.</li><li>Prévenir en cas d\'absence, au moins 24 h à l\'avance.</li><li>Le staff tranche les litiges ; ses décisions sont publiques.</li></ul>'),
('2', 'it', 'Regolamento interno', 'Cosa chiediamo a ciascuno', '<p>Rispetto prima di tutto: niente insulti, niente frasi discriminatorie, niente trucchi. Una mancanza si risolve prima con un avvertimento, poi con l’esclusione.</p><ul><li>Microfono consigliato in allenamento, obbligatorio in partita ufficiale.</li><li>Avvisa in caso di assenza, almeno 24 ore prima.</li><li>Lo staff decide sulle controversie; le sue decisioni sono pubbliche.</li></ul>'),
('2', 'pt', 'Regulamento interno', 'O que esperamos de cada um', '<p>Respeito acima de tudo: nada de insultos, de comentários discriminatórios ou de batota. Uma falta resolve-se primeiro com um aviso e depois com a exclusão.</p><ul><li>Microfone aconselhado nos treinos, obrigatório em jogo oficial.</li><li>Avisa em caso de ausência, com pelo menos 24 horas de antecedência.</li><li>O staff decide os litígios; as suas decisões são públicas.</li></ul>'),
('3', 'de', 'Mach mit', 'So bewirbst du dich', '<p>Die offenen Rekrutierungen stehen im eigenen Bereich. Du kannst dich auch im Forum vorstellen: wir sehen uns jede Initiativbewerbung an.</p>'),
('3', 'en', 'Join us', 'How to apply', '<p>Open recruitments are listed in the dedicated section. You can also introduce yourself on the forum: we look at every unsolicited application.</p>'),
('3', 'es', 'Únete', 'Cómo presentar tu candidatura', '<p>Los reclutamientos abiertos están en la sección dedicada. También puedes presentarte en el foro: miramos todas las candidaturas espontáneas.</p>'),
('3', 'fr', 'Nous rejoindre', 'Comment postuler', '<p>Les recrutements ouverts sont listés dans la rubrique dédiée. Tu peux aussi te présenter sur le forum : on regarde toutes les candidatures spontanées.</p>'),
('3', 'it', 'Unisciti a noi', 'Come candidarsi', '<p>I reclutamenti aperti sono elencati nella sezione dedicata. Puoi anche presentarti sul forum: guardiamo tutte le candidature spontanee.</p>'),
('3', 'pt', 'Junta-te a nós', 'Como candidatar-te', '<p>Os recrutamentos abertos estão na secção dedicada. Também te podes apresentar no fórum: vemos todas as candidaturas espontâneas.</p>'),
('4', 'de', 'Unsere Partner', 'Sie unterstützen uns', '<p>Drei Partner begleiten uns bei Hosting, Hardware und Videogestaltung. Ihre Unterstützung finanziert unsere Fahrten zu LAN-Events.</p>'),
('4', 'en', 'Our partners', 'They support us', '<p>Three partners support us with hosting, hardware and video branding. Their backing funds our trips to LAN events.</p>'),
('4', 'es', 'Nuestros socios', 'Nos apoyan', '<p>Tres socios nos acompañan en el alojamiento, el material y la imagen de vídeo. Su apoyo financia nuestros desplazamientos a las LAN.</p>'),
('4', 'fr', 'Nos partenaires', 'Ils nous soutiennent', '<p>Trois partenaires nous accompagnent sur l\'hébergement, le matériel et l\'habillage vidéo. Leur présence finance nos déplacements en LAN.</p>'),
('4', 'it', 'I nostri partner', 'Ci sostengono', '<p>Tre partner ci affiancano su hosting, hardware e grafica video. Il loro sostegno finanzia le nostre trasferte alle LAN.</p>'),
('4', 'pt', 'Os nossos parceiros', 'Apoiam-nos', '<p>Três parceiros acompanham-nos no alojamento, no material e na imagem de vídeo. O seu apoio financia as nossas deslocações às LAN.</p>');
DELETE FROM `nf_pages_instances`;
-- nf_pages_instances : aucune donnée.
DELETE FROM `nf_menus`;
INSERT INTO `nf_menus` (`menu_id`, `name`, `title`) VALUES
('1', 'communaute', 'La communauté');
DELETE FROM `nf_menus_items`;
INSERT INTO `nf_menus_items` (`item_id`, `menu_id`, `parent_id`, `url`, `icon`, `target`, `position`) VALUES
('1', '1', NULL, 'pages/a-propos', 'fas fa-circle-info', '', '0'),
('2', '1', NULL, 'pages/reglement', 'fas fa-gavel', '', '1'),
('3', '1', NULL, 'recruits', 'fas fa-user-plus', '', '2'),
('4', '1', NULL, 'pages/partenaires', 'fas fa-handshake', '', '3'),
('5', '1', NULL, 'https://discord.gg/exemple', 'fab fa-discord', '_blank', '4');
DELETE FROM `nf_menus_items_lang`;
INSERT INTO `nf_menus_items_lang` (`item_id`, `lang`, `title`) VALUES
('1', 'fr', 'À propos'),
('1', 'en', 'About'),
('1', 'de', 'Über uns'),
('1', 'es', 'Acerca de'),
('1', 'it', 'Chi siamo'),
('1', 'pt', 'Sobre nós'),
('2', 'fr', 'Règlement'),
('2', 'en', 'Rules'),
('2', 'de', 'Regeln'),
('2', 'es', 'Reglamento'),
('2', 'it', 'Regolamento'),
('2', 'pt', 'Regulamento'),
('3', 'fr', 'Nous rejoindre'),
('3', 'en', 'Join us'),
('3', 'de', 'Mitmachen'),
('3', 'es', 'Únete'),
('3', 'it', 'Unisciti a noi'),
('3', 'pt', 'Junta-te a nós'),
('4', 'fr', 'Nos partenaires'),
('4', 'en', 'Our partners'),
('4', 'de', 'Unsere Partner'),
('4', 'es', 'Nuestros socios'),
('4', 'it', 'I nostri partner'),
('4', 'pt', 'Os nossos parceiros'),
('5', 'fr', 'Discord'),
('5', 'en', 'Discord'),
('5', 'de', 'Discord'),
('5', 'es', 'Discord'),
('5', 'it', 'Discord'),
('5', 'pt', 'Discord');
DELETE FROM `nf_custom_emojis`;
INSERT INTO `nf_custom_emojis` (`id`, `name`, `image_id`, `created_at`) VALUES
('1', 'gg', '34', '2026-08-27 11:46:29'),
('2', 'clutch', '35', '2026-08-27 11:46:29'),
('3', 'ace', '36', '2026-08-27 11:46:29'),
('4', 'salt', '37', '2026-08-27 11:46:29'),
('5', 'pog', '38', '2026-08-27 11:46:29');
DELETE FROM `nf_slider_slides`;
INSERT INTO `nf_slider_slides` (`id`, `image_url`, `link`, `sort_order`, `active`, `created_at`, `updated_at`) VALUES
('1', 'upload/demo/demo-slide-0.jpg', 'news', '0', '1', '2026-09-09 11:46:29', '2026-09-09 11:46:29'),
('2', 'upload/demo/demo-slide-1.jpg', 'gallery', '1', '1', '2026-09-09 11:46:29', '2026-09-09 11:46:29'),
('3', 'upload/demo/demo-slide-2.jpg', 'recruits', '2', '1', '2026-09-09 11:46:29', '2026-09-09 11:46:29');
DELETE FROM `nf_slider_slides_lang`;
INSERT INTO `nf_slider_slides_lang` (`slide_id`, `lang`, `title`, `caption`) VALUES
('1', 'fr', 'Bienvenue sur la démo', 'Toutes les rubriques sont peuplées : navigue librement.'),
('1', 'en', 'Welcome to the demo', 'Every section is filled in: have a look around.'),
('1', 'de', 'Willkommen in der Demo', 'Alle Bereiche sind gefüllt: sieh dich frei um.'),
('1', 'es', 'Bienvenido a la demo', 'Todas las secciones están llenas: navega a tu aire.'),
('1', 'it', 'Benvenuto nella demo', 'Tutte le sezioni sono popolate: esplora liberamente.'),
('1', 'pt', 'Bem-vindo à demonstração', 'Todas as secções estão preenchidas: navega à vontade.'),
('2', 'fr', 'LAN d\'été 2025', 'Retrouve les photos dans la galerie.'),
('2', 'en', 'Summer LAN 2025', 'The photos are in the gallery.'),
('2', 'de', 'Sommer-LAN 2025', 'Die Fotos findest du in der Galerie.'),
('2', 'es', 'LAN de verano 2025', 'Encuentra las fotos en la galería.'),
('2', 'it', 'LAN estiva 2025', 'Trovi le foto nella galleria.'),
('2', 'pt', 'LAN de verão 2025', 'Encontra as fotos na galeria.'),
('3', 'fr', 'Recrutement ouvert', 'Deux postes à pourvoir sur CS2 et Valorant.'),
('3', 'en', 'Recruiting now', 'Two spots open on CS2 and Valorant.'),
('3', 'de', 'Wir rekrutieren', 'Zwei Plätze frei in CS2 und Valorant.'),
('3', 'es', 'Reclutamiento abierto', 'Dos plazas libres en CS2 y Valorant.'),
('3', 'it', 'Reclutamento aperto', 'Due posti liberi su CS2 e Valorant.'),
('3', 'pt', 'Recrutamento aberto', 'Duas vagas em CS2 e Valorant.');
DELETE FROM `nf_glossary_categories`;
INSERT INTO `nf_glossary_categories` (`id`, `title`, `sort_order`) VALUES
('1', 'Vocabulaire de jeu', '0'),
('2', 'Communauté', '1');
DELETE FROM `nf_glossary_terms`;
INSERT INTO `nf_glossary_terms` (`id`, `category_id`, `term`, `initial`, `definition`, `synonyms`, `published`, `created_at`, `updated_at`) VALUES
('103', '1', 'Clutch', 'C', 'Situation où un joueur reste seul face à plusieurs adversaires et remporte tout de même la manche.', 'clutcher', '1', '2026-09-23 07:52:04', '2026-09-23 07:52:04'),
('104', '1', 'Eco round', 'E', 'Manche jouée volontairement sans acheter d\'équipement, pour économiser de quoi s\'équiper à la suivante.', 'éco', '1', '2026-09-23 07:52:04', '2026-09-23 07:52:04'),
('105', '1', 'Peek', 'P', 'Action de se découvrir brièvement pour observer ou tirer, puis de se remettre à couvert.', 'peeker, jiggle peek', '1', '2026-09-23 07:52:04', '2026-09-23 07:52:04'),
('106', '2', 'Roster', 'R', 'Liste des joueurs qui composent une équipe à un moment donné.', 'effectif', '1', '2026-09-23 07:52:04', '2026-09-23 07:52:04'),
('107', '2', 'Scrim', 'S', 'Match d\'entraînement organisé entre deux équipes, sans enjeu de classement.', 'scrimmage', '1', '2026-09-23 07:52:04', '2026-09-23 07:52:04'),
('108', '2', 'Shoutcast', 'S', 'Commentaire en direct d\'une partie, assuré par un ou deux casteurs.', 'cast, casteur', '1', '2026-09-23 07:52:04', '2026-09-23 07:52:04');
DELETE FROM `nf_places_categories`;
INSERT INTO `nf_places_categories` (`id`, `title`, `icon`, `color`, `sort_order`) VALUES
('1', 'Salles et LAN', 'fas fa-map-marker-alt', '', '0'),
('2', 'Points de rencontre', 'fas fa-map-marker-alt', '', '1');
DELETE FROM `nf_places`;
INSERT INTO `nf_places` (`id`, `category_id`, `title`, `description`, `address`, `latitude`, `longitude`, `link`, `sort_order`, `published`, `created_at`, `updated_at`) VALUES
('69', '1', 'Salle de la LAN d\'été', 'Deux cents postes, fibre dédiée, buvette ouverte toute la nuit.', '12 rue des Halles, 69002 Lyon', '45.760000', '4.832000', '', '0', '1', '2026-09-23 07:52:04', '2026-09-23 07:52:04'),
('70', '1', 'Gymnase du tournoi régional', 'Le tournoi d\'automne s\'y tient chaque année depuis trois saisons.', 'Avenue du Stade, 33000 Bordeaux', '44.837800', '-0.579100', '', '1', '1', '2026-09-23 07:52:04', '2026-09-23 07:52:04'),
('71', '2', 'Bar associatif Le Respawn', 'Rencontre mensuelle de la communauté, premier jeudi du mois.', '5 place Saint-Pierre, 31000 Toulouse', '43.604700', '1.443700', '', '0', '1', '2026-09-23 07:52:04', '2026-09-23 07:52:04'),
('72', '2', 'Espace jeu de la médiathèque', 'Initiations le samedi après-midi, ouvert à tous.', '2 quai de Seine, 75019 Paris', '48.883000', '2.373000', '', '1', '1', '2026-09-23 07:52:04', '2026-09-23 07:52:04');
DELETE FROM `nf_quotes_categories`;
INSERT INTO `nf_quotes_categories` (`id`, `title`, `sort_order`) VALUES
('1', 'Sur le jeu', '0'),
('2', 'Sur l\'équipe', '1');
DELETE FROM `nf_quotes`;
INSERT INTO `nf_quotes` (`id`, `category_id`, `quote`, `author`, `source`, `source_url`, `sort_order`, `published`, `created_at`, `updated_at`) VALUES
('69', '1', 'On ne perd pas une manche parce qu\'on vise mal, on la perd parce qu\'on a arrêté de se parler.', 'Rekkles_FR', 'Débrief du tournoi régional', '', '0', '1', '2026-09-23 07:52:04', '2026-09-23 07:52:04'),
('70', '1', 'Le meilleur réglage de souris, c\'est celui qu\'on garde trois mois.', 'NovaStrike', 'Guide du débutant', '', '1', '1', '2026-09-23 07:52:04', '2026-09-23 07:52:04'),
('71', '2', 'Une équipe, c\'est cinq joueurs qui font la même erreur en même temps — et qui la corrigent ensemble.', 'ShadowFox', 'Interview d\'avant-saison', '', '0', '1', '2026-09-23 07:52:04', '2026-09-23 07:52:04'),
('72', '2', 'On recrute des gens, pas des statistiques.', 'LunaByte', 'Annonce de recrutement', '', '1', '1', '2026-09-23 07:52:04', '2026-09-23 07:52:04');
DELETE FROM `nf_recipes_categories`;
INSERT INTO `nf_recipes_categories` (`id`, `title`, `sort_order`) VALUES
('1', 'Soirées LAN', '0'),
('2', 'Boissons', '1');
DELETE FROM `nf_recipes`;
INSERT INTO `nf_recipes` (`id`, `category_id`, `title`, `intro`, `ingredients`, `steps`, `servings`, `prep_minutes`, `cook_minutes`, `sort_order`, `published`, `created_at`, `updated_at`) VALUES
('52', '1', 'Wraps froids de la LAN', 'Se tiennent d\'une main, ne graissent pas le clavier.', '6 galettes de blé\n300 g de poulet rôti\n1 avocat\n2 tomates\n4 cuillères de fromage frais\nQuelques feuilles de salade', 'Émincer le poulet et les tomates.\nÉtaler le fromage frais sur chaque galette.\nGarnir, rouler serré, couper en deux.\nRéserver au frais jusqu\'au coup d\'envoi.', '6', '20', '0', '0', '1', '2026-09-23 07:52:04', '2026-09-23 07:52:04'),
('53', '1', 'Pop-corn au paprika fumé', 'Le bruit en moins : on n\'en mange pas pendant les phases silencieuses.', '100 g de maïs à éclater\n2 cuillères d\'huile\n1 cuillère de paprika fumé\nSel', 'Chauffer l\'huile dans une grande casserole.\nVerser le maïs, couvrir, secouer jusqu\'à la fin des éclatements.\nSaupoudrer de paprika et de sel, mélanger.', '4', '5', '10', '1', '1', '2026-09-23 07:52:04', '2026-09-23 07:52:04'),
('54', '2', 'Thé glacé menthe-citron', 'De quoi tenir une soirée sans finir électrique.', '1 l d\'eau\n3 sachets de thé vert\n1 citron\nUne dizaine de feuilles de menthe\nMiel selon le goût', 'Infuser le thé cinq minutes, laisser refroidir.\nAjouter le jus de citron et la menthe froissée.\nSucrer au miel, servir bien frais.', '4', '10', '5', '0', '1', '2026-09-23 07:52:04', '2026-09-23 07:52:04');
DELETE FROM `nf_webradio_shows`;
INSERT INTO `nf_webradio_shows` (`id`, `host`, `day`, `start_time`, `end_time`, `published`, `created_at`, `updated_at`) VALUES
('52', 'LunaByte', '1', '08:00', '10:00', '1', '2026-09-23 07:52:04', '2026-09-23 07:52:04'),
('53', 'ShadowFox', '3', '20:00', '21:30', '1', '2026-09-23 07:52:04', '2026-09-23 07:52:04'),
('54', 'NovaStrike', '6', '22:00', '02:00', '1', '2026-09-23 07:52:04', '2026-09-23 07:52:04');
DELETE FROM `nf_webradio_shows_lang`;
INSERT INTO `nf_webradio_shows_lang` (`show_id`, `lang`, `title`, `description`) VALUES
('52', 'fr', 'Le réveil du serveur', 'Les actualités de la communauté et la playlist du matin.'),
('52', 'en', 'The server wake-up', 'The community news and the morning playlist.'),
('52', 'de', 'Der Server wacht auf', 'Die Neuigkeiten der Community und die Morgen-Playlist.'),
('52', 'es', 'El despertar del servidor', 'Las noticias de la comunidad y la lista de la mañana.'),
('52', 'it', 'La sveglia del server', 'Le novità della comunità e la playlist del mattino.'),
('52', 'pt', 'O despertar do servidor', 'As notícias da comunidade e a playlist da manhã.'),
('53', 'fr', 'Débrief des scrims', 'Retour sur les entraînements de la semaine, avec les joueurs.'),
('53', 'en', 'Scrims debrief', 'A look back at the week’s practice, with the players.'),
('53', 'de', 'Scrim-Nachbesprechung', 'Rückblick auf das Training der Woche, mit den Spielern.'),
('53', 'es', 'Análisis de los scrims', 'Repaso a los entrenamientos de la semana, con los jugadores.'),
('53', 'it', 'Debriefing degli scrim', 'Uno sguardo agli allenamenti della settimana, con i giocatori.'),
('53', 'pt', 'Análise dos scrims', 'Revisão dos treinos da semana, com os jogadores.'),
('54', 'fr', 'Nuit blanche', 'Musique et discussions jusqu\'au bout de la nuit, pendant les LAN.'),
('54', 'en', 'All-nighter', 'Music and talk until dawn, during the LAN parties.'),
('54', 'de', 'Durchmachen', 'Musik und Gespräche bis zum Morgengrauen, während der LANs.'),
('54', 'es', 'Noche en blanco', 'Música y charla hasta el amanecer, durante las LAN.'),
('54', 'it', 'Notte in bianco', 'Musica e chiacchiere fino all’alba, durante le LAN.'),
('54', 'pt', 'Noite em claro', 'Música e conversa até de manhã, durante as LAN.');
DELETE FROM `nf_talks`;
INSERT INTO `nf_talks` (`talk_id`, `name`, `type`, `audience`, `creator_id`, `description`, `created_at`, `updated_at`, `deleted_at`) VALUES
('1', 'Salon général', 'public', 'all', '271', 'Le salon ouvert à tous les membres.', '2026-08-22 11:46:29', '2026-09-16 10:46:29', NULL),
('2', 'Staff', 'public', 'staff', '271', 'Coordination interne du staff.', '2026-08-27 11:46:29', '2026-09-16 09:46:29', NULL),
('3', 'Scrims CS2', 'group', 'all', '271', 'Organisation des entraînements CS2.', '2026-09-01 11:46:29', '2026-09-16 08:46:29', NULL);
DELETE FROM `nf_talks_lang`;
INSERT INTO `nf_talks_lang` (`talk_id`, `lang`, `name`, `description`) VALUES
('1', 'fr', 'Salon général', 'Le salon ouvert à tous les membres.'),
('1', 'en', 'General lounge', 'The room open to all members.'),
('1', 'de', 'Allgemeiner Raum', 'Der Raum für alle Mitglieder.'),
('1', 'es', 'Sala general', 'La sala abierta a todos los miembros.'),
('1', 'it', 'Stanza generale', 'La stanza aperta a tutti i membri.'),
('1', 'pt', 'Sala geral', 'A sala aberta a todos os membros.'),
('2', 'fr', 'Staff', 'Coordination interne du staff.'),
('2', 'en', 'Staff', 'Internal staff coordination.'),
('2', 'de', 'Staff', 'Interne Abstimmung des Staffs.'),
('2', 'es', 'Staff', 'Coordinación interna del staff.'),
('2', 'it', 'Staff', 'Coordinamento interno dello staff.'),
('2', 'pt', 'Staff', 'Coordenação interna do staff.');
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
INSERT INTO `nf_wiki_pages` (`id`, `slug`, `parent_id`, `sort_order`, `published`, `user_id`, `views`, `created_at`, `updated_at`) VALUES
('1', 'bienvenue', NULL, '1', '1', '271', '87', '2026-09-02 18:40:00', '2026-09-12 20:05:00'),
('2', 'communaute', NULL, '2', '1', '271', '41', '2026-09-02 18:52:00', '2026-09-02 18:52:00'),
('3', 'reglement', '2', '1', '1', '271', '64', '2026-09-03 20:15:00', '2026-09-08 21:02:00'),
('4', 'rejoindre-une-equipe', '2', '2', '1', '271', '38', '2026-09-04 19:30:00', '2026-09-04 19:30:00'),
('5', 'jeux', NULL, '3', '1', '271', '29', '2026-09-05 21:10:00', '2026-09-05 21:10:00'),
('6', 'strategies-cs2', '5', '1', '1', '271', '52', '2026-09-06 17:45:00', '2026-09-10 22:30:00'),
('7', 'organiser-un-tournoi', '5', '2', '1', '271', '21', '2026-09-07 16:20:00', '2026-09-07 16:20:00');
DELETE FROM `nf_wiki_pages_lang`;
INSERT INTO `nf_wiki_pages_lang` (`page_id`, `lang`, `title`, `content`) VALUES
('1', 'fr', 'Bienvenue sur le wiki', '<p>Ce wiki rassemble le savoir de la communauté : le règlement, les guides pour bien démarrer et nos stratégies de jeu. Tout le monde peut le lire ; l\'équipe le tient à jour.</p>\n<h2>Par où commencer</h2>\n<ul>\n<li>Lire le <strong>règlement</strong>, dans la rubrique « La communauté ».</li>\n<li>Découvrir comment <strong>rejoindre une équipe</strong>.</li>\n<li>Réviser les <strong>bases de CS2</strong> avant votre premier entraînement.</li>\n</ul>\n<h2>Proposer une page</h2>\n<p>Une idée de guide ? Proposez-la sur le forum, dans « Discussions générales » : une page validée arrive ici, et son historique garde chaque version.</p>'),
('1', 'en', 'Welcome to the wiki', '<p>This wiki gathers the community\'s knowledge: the rules, guides to get started and our game strategies. Everyone can read it; the team keeps it up to date.</p>\n<h2>Where to start</h2>\n<ul>\n<li>Read the <strong>rules</strong>, in the “The community” section.</li>\n<li>Find out how to <strong>join a team</strong>.</li>\n<li>Brush up on the <strong>CS2 basics</strong> before your first practice.</li>\n</ul>\n<h2>Suggest a page</h2>\n<p>An idea for a guide? Suggest it on the forum, in “General discussion”: an approved page lands here, and its history keeps every version.</p>'),
('1', 'de', 'Willkommen im Wiki', '<p>Dieses Wiki sammelt das Wissen der Community: die Regeln, Anleitungen für den Einstieg und unsere Spielstrategien. Jeder kann es lesen; das Team hält es aktuell.</p>\n<h2>Wo anfangen</h2>\n<ul>\n<li>Die <strong>Regeln</strong> lesen, in der Rubrik „Die Community“.</li>\n<li>Erfahren, wie man <strong>einem Team beitritt</strong>.</li>\n<li>Die <strong>CS2-Grundlagen</strong> vor dem ersten Training auffrischen.</li>\n</ul>\n<h2>Eine Seite vorschlagen</h2>\n<p>Eine Idee für eine Anleitung? Schlag sie im Forum vor, unter „Allgemeine Diskussionen“: Eine angenommene Seite landet hier, und ihr Verlauf bewahrt jede Version.</p>'),
('1', 'es', 'Bienvenido a la wiki', '<p>Esta wiki reúne el saber de la comunidad: el reglamento, las guías para empezar con buen pie y nuestras estrategias de juego. Todo el mundo puede leerla; el equipo la mantiene al día.</p>\n<h2>Por dónde empezar</h2>\n<ul>\n<li>Leer el <strong>reglamento</strong>, en la sección «La comunidad».</li>\n<li>Descubrir cómo <strong>unirse a un equipo</strong>.</li>\n<li>Repasar los <strong>fundamentos de CS2</strong> antes de tu primer entrenamiento.</li>\n</ul>\n<h2>Proponer una página</h2>\n<p>¿Una idea de guía? Propónla en el foro, en «Discusiones generales»: una página aprobada llega aquí, y su historial guarda cada versión.</p>'),
('1', 'it', 'Benvenuti nel wiki', '<p>Questo wiki raccoglie il sapere della comunità: il regolamento, le guide per iniziare bene e le nostre strategie di gioco. Tutti possono leggerlo; il team lo tiene aggiornato.</p>\n<h2>Da dove cominciare</h2>\n<ul>\n<li>Leggere il <strong>regolamento</strong>, nella sezione «La comunità».</li>\n<li>Scoprire come <strong>entrare in una squadra</strong>.</li>\n<li>Ripassare le <strong>basi di CS2</strong> prima del primo allenamento.</li>\n</ul>\n<h2>Proporre una pagina</h2>\n<p>Un’idea per una guida? Proponila sul forum, in «Discussioni generali»: una pagina approvata arriva qui, e la sua cronologia conserva ogni versione.</p>'),
('1', 'pt', 'Bem-vindo à wiki', '<p>Esta wiki reúne o saber da comunidade: o regulamento, os guias para começar bem e as nossas estratégias de jogo. Todos a podem ler; a equipa mantém-na atualizada.</p>\n<h2>Por onde começar</h2>\n<ul>\n<li>Ler o <strong>regulamento</strong>, na secção «A comunidade».</li>\n<li>Descobrir como <strong>entrar numa equipa</strong>.</li>\n<li>Rever as <strong>bases de CS2</strong> antes do primeiro treino.</li>\n</ul>\n<h2>Propor uma página</h2>\n<p>Uma ideia de guia? Propõe-na no fórum, em «Discussões gerais»: uma página aprovada chega aqui, e o seu histórico guarda cada versão.</p>'),
('2', 'fr', 'La communauté', '<p>Les règles de vie, et tout ce qu\'il faut savoir pour trouver sa place parmi nous.</p>'),
('2', 'en', 'The community', '<p>The house rules, and everything you need to know to find your place among us.</p>'),
('2', 'de', 'Die Community', '<p>Die Regeln des Miteinanders und alles, was du wissen musst, um deinen Platz bei uns zu finden.</p>'),
('2', 'es', 'La comunidad', '<p>Las normas de convivencia, y todo lo que hay que saber para encontrar tu lugar entre nosotros.</p>'),
('2', 'it', 'La comunità', '<p>Le regole di convivenza, e tutto ciò che serve sapere per trovare il proprio posto tra noi.</p>'),
('2', 'pt', 'A comunidade', '<p>As regras de convivência, e tudo o que precisas de saber para encontrar o teu lugar entre nós.</p>'),
('3', 'fr', 'Règlement', '<p>Ce règlement vaut pour le site, le forum et le serveur Discord.</p>\n<h2>Les règles</h2>\n<ol>\n<li><strong>Respect</strong> : pas d\'insulte, de harcèlement ni de propos discriminants.</li>\n<li><strong>Fair-play</strong> : la triche et l\'exploitation de bugs sont interdites, en match comme à l\'entraînement.</li>\n<li><strong>Pas de spam</strong> : ni publicité, ni messages répétés, ni liens douteux.</li>\n<li><strong>Chaque sujet à sa place</strong> : chaque forum a son thème ; les annonces sont réservées à l\'équipe.</li>\n</ol>\n<h2>Les sanctions</h2>\n<p>Selon la gravité : un avertissement, une exclusion temporaire, puis un bannissement. Une sanction se conteste auprès d\'un modérateur, par message privé.</p>'),
('3', 'en', 'Rules', '<p>These rules apply to the website, the forum and the Discord server.</p>\n<h2>The rules</h2>\n<ol>\n<li><strong>Respect</strong>: no insults, harassment or discriminatory remarks.</li>\n<li><strong>Fair play</strong>: cheating and bug exploits are forbidden, in matches as in practice.</li>\n<li><strong>No spam</strong>: no advertising, no repeated messages, no dubious links.</li>\n<li><strong>Every topic in its place</strong>: each forum has its theme; announcements are reserved for the team.</li>\n</ol>\n<h2>Sanctions</h2>\n<p>Depending on severity: a warning, a temporary ban, then a permanent ban. A sanction can be appealed to a moderator, by private message.</p>'),
('3', 'de', 'Regeln', '<p>Diese Regeln gelten für die Website, das Forum und den Discord-Server.</p>\n<h2>Die Regeln</h2>\n<ol>\n<li><strong>Respekt</strong>: keine Beleidigungen, keine Belästigung, keine diskriminierenden Äußerungen.</li>\n<li><strong>Fairplay</strong>: Cheaten und das Ausnutzen von Bugs sind verboten, im Match wie im Training.</li>\n<li><strong>Kein Spam</strong>: keine Werbung, keine wiederholten Nachrichten, keine zweifelhaften Links.</li>\n<li><strong>Jedes Thema an seinem Platz</strong>: Jedes Forum hat sein Thema; Ankündigungen sind dem Team vorbehalten.</li>\n</ol>\n<h2>Die Sanktionen</h2>\n<p>Je nach Schwere: eine Verwarnung, ein vorübergehender Ausschluss, dann ein Bann. Gegen eine Sanktion kann man bei einem Moderator per Privatnachricht Einspruch einlegen.</p>'),
('3', 'es', 'Reglamento', '<p>Este reglamento se aplica al sitio, al foro y al servidor de Discord.</p>\n<h2>Las normas</h2>\n<ol>\n<li><strong>Respeto</strong>: nada de insultos, acoso ni comentarios discriminatorios.</li>\n<li><strong>Juego limpio</strong>: las trampas y el aprovechamiento de bugs están prohibidos, tanto en partida como en entrenamiento.</li>\n<li><strong>Nada de spam</strong>: ni publicidad, ni mensajes repetidos, ni enlaces dudosos.</li>\n<li><strong>Cada tema en su sitio</strong>: cada foro tiene su temática; los anuncios están reservados al equipo.</li>\n</ol>\n<h2>Las sanciones</h2>\n<p>Según la gravedad: una advertencia, una expulsión temporal y después un baneo. Una sanción se puede recurrir ante un moderador, por mensaje privado.</p>'),
('3', 'it', 'Regolamento', '<p>Questo regolamento vale per il sito, il forum e il server Discord.</p>\n<h2>Le regole</h2>\n<ol>\n<li><strong>Rispetto</strong>: niente insulti, molestie o commenti discriminatori.</li>\n<li><strong>Fair play</strong>: barare e sfruttare i bug è vietato, in partita come in allenamento.</li>\n<li><strong>Niente spam</strong>: né pubblicità, né messaggi ripetuti, né link sospetti.</li>\n<li><strong>Ogni argomento al suo posto</strong>: ogni forum ha il suo tema; gli annunci sono riservati al team.</li>\n</ol>\n<h2>Le sanzioni</h2>\n<p>A seconda della gravità: un avvertimento, un’esclusione temporanea, poi un ban. Una sanzione si contesta presso un moderatore, con un messaggio privato.</p>'),
('3', 'pt', 'Regulamento', '<p>Este regulamento aplica-se ao site, ao fórum e ao servidor Discord.</p>\n<h2>As regras</h2>\n<ol>\n<li><strong>Respeito</strong>: nada de insultos, assédio ou comentários discriminatórios.</li>\n<li><strong>Fair-play</strong>: a batota e a exploração de bugs são proibidas, tanto em jogo como no treino.</li>\n<li><strong>Nada de spam</strong>: nem publicidade, nem mensagens repetidas, nem ligações duvidosas.</li>\n<li><strong>Cada tema no seu lugar</strong>: cada fórum tem o seu tema; os anúncios são reservados à equipa.</li>\n</ol>\n<h2>As sanções</h2>\n<p>Consoante a gravidade: um aviso, uma exclusão temporária e depois um banimento. Uma sanção contesta-se junto de um moderador, por mensagem privada.</p>'),
('4', 'fr', 'Rejoindre une équipe', '<p>Nos équipes recrutent régulièrement. Voici comment se passe une candidature.</p>\n<h2>Les étapes</h2>\n<ol>\n<li>Consultez les offres de la page <strong>Recrutement</strong> et choisissez un poste.</li>\n<li>Envoyez votre candidature depuis l\'offre : votre jeu, votre rôle, vos disponibilités.</li>\n<li>Un capitaine vous propose un <strong>essai</strong> : un entraînement avec l\'équipe.</li>\n<li>Après l\'essai, la réponse arrive par message privé.</li>\n</ol>\n<h2>Ce que nous attendons</h2>\n<ul>\n<li>de la régularité aux entraînements ;</li>\n<li>un micro, et Discord ;</li>\n<li>l\'envie de progresser ensemble.</li>\n</ul>'),
('4', 'en', 'Join a team', '<p>Our teams recruit regularly. Here is how an application works.</p>\n<h2>The steps</h2>\n<ol>\n<li>Browse the offers on the <strong>Recruitment</strong> page and pick a position.</li>\n<li>Send your application from the offer: your game, your role, your availability.</li>\n<li>A captain offers you a <strong>trial</strong>: a practice session with the team.</li>\n<li>After the trial, the answer arrives by private message.</li>\n</ol>\n<h2>What we expect</h2>\n<ul>\n<li>regular attendance at practice;</li>\n<li>a microphone, and Discord;</li>\n<li>the will to improve together.</li>\n</ul>'),
('4', 'de', 'Einem Team beitreten', '<p>Unsere Teams suchen regelmäßig Verstärkung. So läuft eine Bewerbung ab.</p>\n<h2>Die Schritte</h2>\n<ol>\n<li>Sieh dir die Angebote auf der Seite <strong>Rekrutierung</strong> an und wähle eine Position.</li>\n<li>Schicke deine Bewerbung über das Angebot: dein Spiel, deine Rolle, deine Verfügbarkeit.</li>\n<li>Ein Kapitän schlägt dir ein <strong>Probetraining</strong> mit dem Team vor.</li>\n<li>Nach dem Probetraining kommt die Antwort per Privatnachricht.</li>\n</ol>\n<h2>Was wir erwarten</h2>\n<ul>\n<li>Regelmäßigkeit beim Training;</li>\n<li>ein Mikrofon, und Discord;</li>\n<li>die Lust, gemeinsam besser zu werden.</li>\n</ul>'),
('4', 'es', 'Unirse a un equipo', '<p>Nuestros equipos reclutan con regularidad. Así funciona una candidatura.</p>\n<h2>Los pasos</h2>\n<ol>\n<li>Consulta las ofertas de la página <strong>Reclutamiento</strong> y elige un puesto.</li>\n<li>Envía tu candidatura desde la oferta: tu juego, tu rol, tu disponibilidad.</li>\n<li>Un capitán te propone una <strong>prueba</strong>: un entrenamiento con el equipo.</li>\n<li>Tras la prueba, la respuesta llega por mensaje privado.</li>\n</ol>\n<h2>Lo que esperamos</h2>\n<ul>\n<li>regularidad en los entrenamientos;</li>\n<li>un micrófono, y Discord;</li>\n<li>ganas de mejorar juntos.</li>\n</ul>'),
('4', 'it', 'Entrare in una squadra', '<p>Le nostre squadre reclutano regolarmente. Ecco come funziona una candidatura.</p>\n<h2>Le tappe</h2>\n<ol>\n<li>Consulta le offerte della pagina <strong>Reclutamento</strong> e scegli un ruolo.</li>\n<li>Invia la tua candidatura dall’offerta: il tuo gioco, il tuo ruolo, le tue disponibilità.</li>\n<li>Un capitano ti propone una <strong>prova</strong>: un allenamento con la squadra.</li>\n<li>Dopo la prova, la risposta arriva per messaggio privato.</li>\n</ol>\n<h2>Cosa ci aspettiamo</h2>\n<ul>\n<li>costanza negli allenamenti;</li>\n<li>un microfono, e Discord;</li>\n<li>la voglia di migliorare insieme.</li>\n</ul>'),
('4', 'pt', 'Entrar numa equipa', '<p>As nossas equipas recrutam regularmente. Eis como decorre uma candidatura.</p>\n<h2>As etapas</h2>\n<ol>\n<li>Consulta as ofertas da página <strong>Recrutamento</strong> e escolhe uma posição.</li>\n<li>Envia a tua candidatura a partir da oferta: o teu jogo, o teu papel, a tua disponibilidade.</li>\n<li>Um capitão propõe-te um <strong>teste</strong>: um treino com a equipa.</li>\n<li>Após o teste, a resposta chega por mensagem privada.</li>\n</ol>\n<h2>O que esperamos</h2>\n<ul>\n<li>regularidade nos treinos;</li>\n<li>um microfone, e Discord;</li>\n<li>vontade de evoluir em conjunto.</li>\n</ul>'),
('5', 'fr', 'Jeux et stratégies', '<p>Les guides de nos équipes : les bases à connaître, et l\'organisation de nos événements.</p>'),
('5', 'en', 'Games and strategies', '<p>Our teams\' guides: the basics to know, and how we organise our events.</p>'),
('5', 'de', 'Spiele und Strategien', '<p>Die Anleitungen unserer Teams: die Grundlagen, die man kennen sollte, und die Organisation unserer Events.</p>'),
('5', 'es', 'Juegos y estrategias', '<p>Las guías de nuestros equipos: lo básico que hay que saber, y la organización de nuestros eventos.</p>'),
('5', 'it', 'Giochi e strategie', '<p>Le guide delle nostre squadre: le basi da conoscere, e l’organizzazione dei nostri eventi.</p>'),
('5', 'pt', 'Jogos e estratégias', '<p>Os guias das nossas equipas: as bases a conhecer, e a organização dos nossos eventos.</p>'),
('6', 'fr', 'Stratégies CS2 : les bases', '<p>Les principes que nos joueurs appliquent sur toutes les cartes.</p>\n<h2>L\'économie</h2>\n<ul>\n<li>Achetez ensemble : un round d\'économie se joue à cinq.</li>\n<li>Après un round au pistolet perdu, préparez le suivant plutôt que de forcer.</li>\n</ul>\n<h2>La communication</h2>\n<ul>\n<li>Annoncez ce que vous voyez, pas ce que vous supposez : « deux en B, un blessé ».</li>\n<li>Un seul joueur mène le round.</li>\n</ul>\n<h2>Les cartes de notre pool</h2>\n<p>Mirage, Inferno, Nuke et Ancient. Chaque carte a son sujet dans le forum « Stratégies ».</p>'),
('6', 'en', 'CS2 strategies: the basics', '<p>The principles our players apply on every map.</p>\n<h2>The economy</h2>\n<ul>\n<li>Buy together: an eco round is played by all five.</li>\n<li>After a lost pistol round, set up the next one rather than forcing.</li>\n</ul>\n<h2>Communication</h2>\n<ul>\n<li>Call what you see, not what you assume: “two B, one hurt”.</li>\n<li>Only one player leads the round.</li>\n</ul>\n<h2>Our map pool</h2>\n<p>Mirage, Inferno, Nuke and Ancient. Each map has its topic in the “Strategies” forum.</p>'),
('6', 'de', 'CS2-Strategien: die Grundlagen', '<p>Die Prinzipien, die unsere Spieler auf allen Maps anwenden.</p>\n<h2>Die Economy</h2>\n<ul>\n<li>Kauft gemeinsam: Eine Eco-Runde spielt man zu fünft.</li>\n<li>Nach einer verlorenen Pistolenrunde die nächste vorbereiten, statt zu forcen.</li>\n</ul>\n<h2>Die Kommunikation</h2>\n<ul>\n<li>Sagt an, was ihr seht, nicht was ihr vermutet: „zwei auf B, einer angeschlagen“.</li>\n<li>Nur ein Spieler führt die Runde.</li>\n</ul>\n<h2>Unser Map-Pool</h2>\n<p>Mirage, Inferno, Nuke und Ancient. Jede Map hat ihr Thema im Forum „Strategien“.</p>'),
('6', 'es', 'Estrategias de CS2: lo básico', '<p>Los principios que nuestros jugadores aplican en todos los mapas.</p>\n<h2>La economía</h2>\n<ul>\n<li>Comprad juntos: una ronda de ahorro se juega entre los cinco.</li>\n<li>Tras perder la ronda de pistolas, preparad la siguiente en lugar de forzar.</li>\n</ul>\n<h2>La comunicación</h2>\n<ul>\n<li>Cantad lo que veis, no lo que suponéis: «dos en B, uno tocado».</li>\n<li>Un solo jugador dirige la ronda.</li>\n</ul>\n<h2>Los mapas de nuestro pool</h2>\n<p>Mirage, Inferno, Nuke y Ancient. Cada mapa tiene su tema en el foro «Estrategias».</p>'),
('6', 'it', 'Strategie CS2: le basi', '<p>I principi che i nostri giocatori applicano su tutte le mappe.</p>\n<h2>L’economia</h2>\n<ul>\n<li>Comprate insieme: un round di eco si gioca in cinque.</li>\n<li>Dopo un round di pistole perso, preparate il successivo invece di forzare.</li>\n</ul>\n<h2>La comunicazione</h2>\n<ul>\n<li>Chiamate ciò che vedete, non ciò che supponete: «due in B, uno ferito».</li>\n<li>Un solo giocatore guida il round.</li>\n</ul>\n<h2>Le mappe del nostro pool</h2>\n<p>Mirage, Inferno, Nuke e Ancient. Ogni mappa ha il suo argomento nel forum «Strategie».</p>'),
('6', 'pt', 'Estratégias de CS2: as bases', '<p>Os princípios que os nossos jogadores aplicam em todos os mapas.</p>\n<h2>A economia</h2>\n<ul>\n<li>Comprem juntos: uma ronda de poupança joga-se a cinco.</li>\n<li>Depois de perder a ronda de pistolas, preparem a seguinte em vez de forçar.</li>\n</ul>\n<h2>A comunicação</h2>\n<ul>\n<li>Anunciem o que veem, não o que supõem: «dois no B, um ferido».</li>\n<li>Um só jogador lidera a ronda.</li>\n</ul>\n<h2>Os mapas do nosso pool</h2>\n<p>Mirage, Inferno, Nuke e Ancient. Cada mapa tem o seu tópico no fórum «Estratégias».</p>'),
('7', 'fr', 'Organiser un tournoi', '<p>Le guide des bénévoles pour nos tournois communautaires.</p>\n<h2>Avant</h2>\n<ul>\n<li>Créez l\'événement dans le <strong>calendrier</strong>, avec le règlement du tournoi.</li>\n<li>Ouvrez les inscriptions, puis annoncez-les sur Discord.</li>\n</ul>\n<h2>Pendant</h2>\n<ul>\n<li>Un arbitre par match, joignable sur Discord.</li>\n<li>Saisissez les scores au fil des matchs.</li>\n</ul>\n<h2>Après</h2>\n<p>Publiez le palmarès, et une actualité avec les plus belles actions.</p>'),
('7', 'en', 'Organise a tournament', '<p>The volunteers’ guide to our community tournaments.</p>\n<h2>Before</h2>\n<ul>\n<li>Create the event in the <strong>calendar</strong>, with the tournament rules.</li>\n<li>Open registrations, then announce them on Discord.</li>\n</ul>\n<h2>During</h2>\n<ul>\n<li>One referee per match, reachable on Discord.</li>\n<li>Enter the scores as the matches go.</li>\n</ul>\n<h2>After</h2>\n<p>Publish the results, and a news item with the best plays.</p>'),
('7', 'de', 'Ein Turnier organisieren', '<p>Der Leitfaden für Freiwillige bei unseren Community-Turnieren.</p>\n<h2>Vorher</h2>\n<ul>\n<li>Lege das Event im <strong>Kalender</strong> an, mit dem Turnierreglement.</li>\n<li>Öffne die Anmeldungen und kündige sie auf Discord an.</li>\n</ul>\n<h2>Währenddessen</h2>\n<ul>\n<li>Ein Schiedsrichter pro Match, auf Discord erreichbar.</li>\n<li>Trage die Ergebnisse im Laufe der Matches ein.</li>\n</ul>\n<h2>Danach</h2>\n<p>Veröffentliche die Platzierungen und eine Neuigkeit mit den schönsten Aktionen.</p>'),
('7', 'es', 'Organizar un torneo', '<p>La guía de los voluntarios para nuestros torneos comunitarios.</p>\n<h2>Antes</h2>\n<ul>\n<li>Crea el evento en el <strong>calendario</strong>, con el reglamento del torneo.</li>\n<li>Abre las inscripciones y anúncialas en Discord.</li>\n</ul>\n<h2>Durante</h2>\n<ul>\n<li>Un árbitro por partida, localizable en Discord.</li>\n<li>Introduce los resultados a medida que avanzan las partidas.</li>\n</ul>\n<h2>Después</h2>\n<p>Publica el palmarés, y una noticia con las mejores jugadas.</p>'),
('7', 'it', 'Organizzare un torneo', '<p>La guida dei volontari per i nostri tornei della comunità.</p>\n<h2>Prima</h2>\n<ul>\n<li>Crea l’evento nel <strong>calendario</strong>, con il regolamento del torneo.</li>\n<li>Apri le iscrizioni, poi annunciale su Discord.</li>\n</ul>\n<h2>Durante</h2>\n<ul>\n<li>Un arbitro per partita, raggiungibile su Discord.</li>\n<li>Inserisci i punteggi man mano che le partite si svolgono.</li>\n</ul>\n<h2>Dopo</h2>\n<p>Pubblica il palmarès, e una notizia con le azioni più belle.</p>'),
('7', 'pt', 'Organizar um torneio', '<p>O guia dos voluntários para os nossos torneios comunitários.</p>\n<h2>Antes</h2>\n<ul>\n<li>Cria o evento no <strong>calendário</strong>, com o regulamento do torneio.</li>\n<li>Abre as inscrições e anuncia-as no Discord.</li>\n</ul>\n<h2>Durante</h2>\n<ul>\n<li>Um árbitro por jogo, contactável no Discord.</li>\n<li>Regista os resultados à medida que os jogos decorrem.</li>\n</ul>\n<h2>Depois</h2>\n<p>Publica o palmarés, e uma notícia com as melhores jogadas.</p>');
DELETE FROM `nf_wiki_revisions`;
INSERT INTO `nf_wiki_revisions` (`id`, `page_id`, `lang`, `content`, `title`, `user_id`, `comment`, `created_at`) VALUES
('1', '1', 'fr', '<p>Ce wiki rassemble le savoir de la communauté : le règlement, les guides pour bien démarrer et nos stratégies de jeu.</p>', 'Bienvenue sur le wiki', '271', 'Première rédaction', '2026-09-02 18:40:00'),
('2', '3', 'fr', '<p>Ce règlement vaut pour le site, le forum et le serveur Discord.</p>\n<h2>Les règles</h2>\n<ol>\n<li><strong>Respect</strong> : pas d\'insulte, de harcèlement ni de propos discriminants.</li>\n<li><strong>Fair-play</strong> : la triche et l\'exploitation de bugs sont interdites, en match comme à l\'entraînement.</li>\n<li><strong>Pas de spam</strong> : ni publicité, ni messages répétés, ni liens douteux.</li>\n</ol>', 'Règlement', '271', 'Première rédaction', '2026-09-03 20:15:00'),
('3', '3', 'fr', '<p>Ce règlement vaut pour le site, le forum et le serveur Discord.</p>\n<h2>Les règles</h2>\n<ol>\n<li><strong>Respect</strong> : pas d\'insulte, de harcèlement ni de propos discriminants.</li>\n<li><strong>Fair-play</strong> : la triche et l\'exploitation de bugs sont interdites, en match comme à l\'entraînement.</li>\n<li><strong>Pas de spam</strong> : ni publicité, ni messages répétés, ni liens douteux.</li>\n<li><strong>Chaque sujet à sa place</strong> : chaque forum a son thème ; les annonces sont réservées à l\'équipe.</li>\n</ol>\n<h2>Les sanctions</h2>\n<p>Selon la gravité : un avertissement, une exclusion temporaire, puis un bannissement. Une sanction se conteste auprès d\'un modérateur, par message privé.</p>', 'Règlement', '271', 'Ajout des sanctions', '2026-09-08 21:02:00');
DELETE FROM `nf_games_maps`;
INSERT INTO `nf_games_maps` (`map_id`, `game_id`, `image_id`) VALUES
('1', '1', NULL),
('2', '1', NULL),
('3', '1', NULL),
('4', '1', NULL),
('5', '1', NULL),
('6', '2', NULL),
('7', '2', NULL),
('8', '2', NULL),
('9', '2', NULL),
('10', '3', NULL),
('11', '3', NULL),
('12', '4', NULL),
('13', '4', NULL),
('14', '4', NULL);
DELETE FROM `nf_games_maps_lang`;
-- Un nom de carte est un nom propre : la même ligne dans les six langues.
INSERT INTO `nf_games_maps_lang` (`map_id`, `lang`, `title`) VALUES
('1', 'fr', 'Mirage'),
('1', 'en', 'Mirage'),
('1', 'de', 'Mirage'),
('1', 'es', 'Mirage'),
('1', 'it', 'Mirage'),
('1', 'pt', 'Mirage'),
('2', 'fr', 'Inferno'),
('2', 'en', 'Inferno'),
('2', 'de', 'Inferno'),
('2', 'es', 'Inferno'),
('2', 'it', 'Inferno'),
('2', 'pt', 'Inferno'),
('3', 'fr', 'Nuke'),
('3', 'en', 'Nuke'),
('3', 'de', 'Nuke'),
('3', 'es', 'Nuke'),
('3', 'it', 'Nuke'),
('3', 'pt', 'Nuke'),
('4', 'fr', 'Ancient'),
('4', 'en', 'Ancient'),
('4', 'de', 'Ancient'),
('4', 'es', 'Ancient'),
('4', 'it', 'Ancient'),
('4', 'pt', 'Ancient'),
('5', 'fr', 'Anubis'),
('5', 'en', 'Anubis'),
('5', 'de', 'Anubis'),
('5', 'es', 'Anubis'),
('5', 'it', 'Anubis'),
('5', 'pt', 'Anubis'),
('6', 'fr', 'Ascent'),
('6', 'en', 'Ascent'),
('6', 'de', 'Ascent'),
('6', 'es', 'Ascent'),
('6', 'it', 'Ascent'),
('6', 'pt', 'Ascent'),
('7', 'fr', 'Bind'),
('7', 'en', 'Bind'),
('7', 'de', 'Bind'),
('7', 'es', 'Bind'),
('7', 'it', 'Bind'),
('7', 'pt', 'Bind'),
('8', 'fr', 'Haven'),
('8', 'en', 'Haven'),
('8', 'de', 'Haven'),
('8', 'es', 'Haven'),
('8', 'it', 'Haven'),
('8', 'pt', 'Haven'),
('9', 'fr', 'Lotus'),
('9', 'en', 'Lotus'),
('9', 'de', 'Lotus'),
('9', 'es', 'Lotus'),
('9', 'it', 'Lotus'),
('9', 'pt', 'Lotus'),
('10', 'fr', 'Faille de l\'invocateur'),
('10', 'en', 'Faille de l\'invocateur'),
('10', 'de', 'Faille de l\'invocateur'),
('10', 'es', 'Faille de l\'invocateur'),
('10', 'it', 'Faille de l\'invocateur'),
('10', 'pt', 'Faille de l\'invocateur'),
('11', 'fr', 'ARAM — Abîme hurlant'),
('11', 'en', 'ARAM — Abîme hurlant'),
('11', 'de', 'ARAM — Abîme hurlant'),
('11', 'es', 'ARAM — Abîme hurlant'),
('11', 'it', 'ARAM — Abîme hurlant'),
('11', 'pt', 'ARAM — Abîme hurlant'),
('12', 'fr', 'Champions Field'),
('12', 'en', 'Champions Field'),
('12', 'de', 'Champions Field'),
('12', 'es', 'Champions Field'),
('12', 'it', 'Champions Field'),
('12', 'pt', 'Champions Field'),
('13', 'fr', 'Mannfield'),
('13', 'en', 'Mannfield'),
('13', 'de', 'Mannfield'),
('13', 'es', 'Mannfield'),
('13', 'it', 'Mannfield'),
('13', 'pt', 'Mannfield'),
('14', 'fr', 'Urban Central'),
('14', 'en', 'Urban Central'),
('14', 'de', 'Urban Central'),
('14', 'es', 'Urban Central'),
('14', 'it', 'Urban Central'),
('14', 'pt', 'Urban Central');
DELETE FROM `nf_games_modes`;
INSERT INTO `nf_games_modes` (`mode_id`, `game_id`) VALUES
('1', '1'),
('2', '1'),
('3', '1'),
('4', '2'),
('5', '2'),
('6', '3'),
('7', '3'),
('8', '4'),
('9', '4');
DELETE FROM `nf_games_modes_lang`;
INSERT INTO `nf_games_modes_lang` (`mode_id`, `lang`, `title`) VALUES
('1', 'fr', 'Compétitif 5v5'),
('1', 'en', 'Competitive 5v5'),
('1', 'de', 'Wettkampf 5v5'),
('1', 'es', 'Competitivo 5c5'),
('1', 'it', 'Competitivo 5v5'),
('1', 'pt', 'Competitivo 5v5'),
('2', 'fr', 'Wingman 2v2'),
('2', 'en', 'Wingman 2v2'),
('2', 'de', 'Wingman 2v2'),
('2', 'es', 'Wingman 2c2'),
('2', 'it', 'Wingman 2v2'),
('2', 'pt', 'Wingman 2v2'),
('3', 'fr', 'Premier'),
('3', 'en', 'Premier'),
('3', 'de', 'Premier'),
('3', 'es', 'Premier'),
('3', 'it', 'Premier'),
('3', 'pt', 'Premier'),
('4', 'fr', 'Compétitif 5v5'),
('4', 'en', 'Competitive 5v5'),
('4', 'de', 'Wettkampf 5v5'),
('4', 'es', 'Competitivo 5c5'),
('4', 'it', 'Competitivo 5v5'),
('4', 'pt', 'Competitivo 5v5'),
('5', 'fr', 'Swiftplay'),
('5', 'en', 'Swiftplay'),
('5', 'de', 'Swiftplay'),
('5', 'es', 'Swiftplay'),
('5', 'it', 'Swiftplay'),
('5', 'pt', 'Swiftplay'),
('6', 'fr', 'Faille classée 5v5'),
('6', 'en', 'Ranked Summoner’s Rift 5v5'),
('6', 'de', 'Gewertete Kluft 5v5'),
('6', 'es', 'Grieta clasificatoria 5c5'),
('6', 'it', 'Landa classificata 5v5'),
('6', 'pt', 'Fenda classificada 5v5'),
('7', 'fr', 'ARAM'),
('7', 'en', 'ARAM'),
('7', 'de', 'ARAM'),
('7', 'es', 'ARAM'),
('7', 'it', 'ARAM'),
('7', 'pt', 'ARAM'),
('8', 'fr', 'Duo 2v2'),
('8', 'en', 'Duos 2v2'),
('8', 'de', 'Duo 2v2'),
('8', 'es', 'Dúos 2c2'),
('8', 'it', 'Duo 2v2'),
('8', 'pt', 'Duplas 2v2'),
('9', 'fr', 'Standard 3v3'),
('9', 'en', 'Standard 3v3'),
('9', 'de', 'Standard 3v3'),
('9', 'es', 'Estándar 3c3'),
('9', 'it', 'Standard 3v3'),
('9', 'pt', 'Padrão 3v3');
DELETE FROM `nf_teams_roles`;
INSERT INTO `nf_teams_roles` (`role_id`, `order`) VALUES
('1', '0'),
('2', '1'),
('3', '2'),
('4', '3');
DELETE FROM `nf_teams_roles_lang`;
INSERT INTO `nf_teams_roles_lang` (`role_id`, `lang`, `title`) VALUES
('1', 'fr', 'Capitaine'),
('1', 'en', 'Captain'),
('1', 'de', 'Kapitän'),
('1', 'es', 'Capitán'),
('1', 'it', 'Capitano'),
('1', 'pt', 'Capitão'),
('2', 'fr', 'Titulaire'),
('2', 'en', 'Starter'),
('2', 'de', 'Stammspieler'),
('2', 'es', 'Titular'),
('2', 'it', 'Titolare'),
('2', 'pt', 'Titular'),
('3', 'fr', 'Remplaçant'),
('3', 'en', 'Substitute'),
('3', 'de', 'Ersatzspieler'),
('3', 'es', 'Suplente'),
('3', 'it', 'Riserva'),
('3', 'pt', 'Suplente'),
('4', 'fr', 'Coach'),
('4', 'en', 'Coach'),
('4', 'de', 'Coach'),
('4', 'es', 'Entrenador'),
('4', 'it', 'Allenatore'),
('4', 'pt', 'Treinador');
DELETE FROM `nf_articles_series`;
-- nf_articles_series : aucune donnée.
DELETE FROM `nf_articles_series_lang`;
-- nf_articles_series_lang : aucune donnée.
DELETE FROM `nf_notifications`;
INSERT INTO `nf_notifications` (`id`, `user_id`, `actor_id`, `type`, `title`, `url`, `is_read`, `created_at`) VALUES
('1', '271', '329', 'comment', 'ShadowFox a commenté « Notre équipe CS2 se qualifie »', 'news', '1', '2026-09-16 10:46:29'),
('2', '271', '330', 'forum_reply', 'NovaStrike a répondu dans « Vos réglages CS2 »', 'forum', '1', '2026-09-16 09:46:29'),
('3', '271', '331', 'event', 'Rappel : Tournoi régional CS2 dans 5 jours', 'events', '1', '2026-09-16 08:46:29'),
('4', '271', '332', 'reaction', 'LunaByte a réagi à ton message', 'forum', '1', '2026-09-16 07:46:29'),
('5', '271', NULL, 'event-reminder', 'Rappel : l\'événement « Tournoi régional CS2 » commence bientôt', 'events/1/tournoi-regional-cs2', '0', '2026-09-20 11:50:01'),
('6', '330', NULL, 'event-reminder', 'Rappel : l\'événement « Tournoi régional CS2 » commence bientôt', 'events/1/tournoi-regional-cs2', '0', '2026-09-20 11:50:01'),
('7', '332', NULL, 'event-reminder', 'Rappel : l\'événement « Tournoi régional CS2 » commence bientôt', 'events/1/tournoi-regional-cs2', '0', '2026-09-20 11:50:01'),
('8', '333', NULL, 'event-reminder', 'Rappel : l\'événement « Tournoi régional CS2 » commence bientôt', 'events/1/tournoi-regional-cs2', '0', '2026-09-20 11:50:01'),
('9', '336', NULL, 'event-reminder', 'Rappel : l\'événement « Tournoi régional CS2 » commence bientôt', 'events/1/tournoi-regional-cs2', '0', '2026-09-20 11:50:01'),
('10', '337', NULL, 'event-reminder', 'Rappel : l\'événement « Tournoi régional CS2 » commence bientôt', 'events/1/tournoi-regional-cs2', '0', '2026-09-20 11:50:01'),
('11', '271', NULL, 'event-reminder', 'Rappel : l\'événement « Coupe Valorant communautaire » commence bientôt', 'events/2/coupe-valorant-communautaire', '0', '2026-09-27 11:50:01'),
('12', '330', NULL, 'event-reminder', 'Rappel : l\'événement « Coupe Valorant communautaire » commence bientôt', 'events/2/coupe-valorant-communautaire', '0', '2026-09-27 11:50:01'),
('13', '332', NULL, 'event-reminder', 'Rappel : l\'événement « Coupe Valorant communautaire » commence bientôt', 'events/2/coupe-valorant-communautaire', '0', '2026-09-27 11:50:01'),
('14', '333', NULL, 'event-reminder', 'Rappel : l\'événement « Coupe Valorant communautaire » commence bientôt', 'events/2/coupe-valorant-communautaire', '0', '2026-09-27 11:50:01'),
('15', '336', NULL, 'event-reminder', 'Rappel : l\'événement « Coupe Valorant communautaire » commence bientôt', 'events/2/coupe-valorant-communautaire', '0', '2026-09-27 11:50:01'),
('16', '337', NULL, 'event-reminder', 'Rappel : l\'événement « Coupe Valorant communautaire » commence bientôt', 'events/2/coupe-valorant-communautaire', '0', '2026-09-27 11:50:01');
DELETE FROM `nf_notifications_preferences`;
-- nf_notifications_preferences : aucune donnée.
DELETE FROM `nf_points_log`;
INSERT INTO `nf_points_log` (`id`, `user_id`, `amount`, `type`, `reason`, `created_at`) VALUES
('1', '271', '5', 'forum_message', 'Message publié sur le forum', '2026-09-15 11:46:29'),
('2', '271', '2', 'comment', 'Commentaire publié', '2026-09-15 10:46:29'),
('3', '271', '25', 'event', 'Participation à un événement', '2026-09-15 08:46:29'),
('4', '329', '5', 'forum_message', 'Message publié sur le forum', '2026-09-14 11:46:29'),
('5', '329', '10', 'daily', 'Connexion quotidienne', '2026-09-14 09:46:29'),
('6', '329', '25', 'event', 'Participation à un événement', '2026-09-14 08:46:29'),
('7', '330', '2', 'comment', 'Commentaire publié', '2026-09-13 10:46:29'),
('8', '330', '10', 'daily', 'Connexion quotidienne', '2026-09-13 09:46:29'),
('9', '331', '5', 'forum_message', 'Message publié sur le forum', '2026-09-12 11:46:29'),
('10', '331', '2', 'comment', 'Commentaire publié', '2026-09-12 10:46:29'),
('11', '331', '25', 'event', 'Participation à un événement', '2026-09-12 08:46:29'),
('12', '332', '5', 'forum_message', 'Message publié sur le forum', '2026-09-11 11:46:29'),
('13', '332', '10', 'daily', 'Connexion quotidienne', '2026-09-11 09:46:29'),
('14', '332', '25', 'event', 'Participation à un événement', '2026-09-11 08:46:29'),
('15', '333', '2', 'comment', 'Commentaire publié', '2026-09-10 10:46:29'),
('16', '333', '10', 'daily', 'Connexion quotidienne', '2026-09-10 09:46:29'),
('17', '334', '5', 'forum_message', 'Message publié sur le forum', '2026-09-09 11:46:29'),
('18', '334', '2', 'comment', 'Commentaire publié', '2026-09-09 10:46:29'),
('19', '334', '25', 'event', 'Participation à un événement', '2026-09-09 08:46:29'),
('20', '335', '5', 'forum_message', 'Message publié sur le forum', '2026-09-08 11:46:29'),
('21', '335', '10', 'daily', 'Connexion quotidienne', '2026-09-08 09:46:29'),
('22', '335', '25', 'event', 'Participation à un événement', '2026-09-08 08:46:29'),
('23', '336', '2', 'comment', 'Commentaire publié', '2026-09-07 10:46:29'),
('24', '336', '10', 'daily', 'Connexion quotidienne', '2026-09-07 09:46:29'),
('25', '337', '5', 'forum_message', 'Message publié sur le forum', '2026-09-06 11:46:29'),
('26', '337', '2', 'comment', 'Commentaire publié', '2026-09-06 10:46:29'),
('27', '337', '25', 'event', 'Participation à un événement', '2026-09-06 08:46:29'),
('28', '271', '20', 'news', '', '2026-09-16 17:47:15'),
('29', '271', '20', 'news', '', '2026-09-16 17:51:18'),
('30', '1', '5', 'login', 'Connexion quotidienne', '2026-09-22 14:01:30');
DELETE FROM `nf_role_permissions`;
INSERT INTO `nf_role_permissions` (`role_id`, `permission`, `scope_id`, `authorized`) VALUES
('1', '*.*', '0', 'allow'),
('2', 'events.access_events_type', '0', 'allow'),
('2', 'files.read_directory', '1', 'allow'),
('2', 'forum.category_read', '0', 'allow'),
('2', 'forum.category_write', '0', 'allow'),
('2', 'gallery.gallery_see', '0', 'allow'),
('2', 'pages.access_page', '0', 'allow'),
('2', 'recruits.recruit_postulate', '0', 'allow'),
('2', 'talks.read', '0', 'allow'),
('2', 'talks.write', '0', 'allow'),
('3', 'events.access_events_type', '0', 'allow'),
('3', 'files.read_directory', '1', 'allow'),
('3', 'forum.category_read', '0', 'allow'),
('3', 'gallery.gallery_see', '0', 'allow'),
('3', 'pages.access_page', '0', 'allow'),
('3', 'talks.read', '0', 'allow'),
('4', 'moderation.handle_reports', '0', 'allow'),
('4', 'moderation.mediation', '0', 'allow'),
('4', 'moderation.mute', '0', 'allow'),
('4', 'moderation.restrict', '0', 'allow'),
('4', 'moderation.view_reports', '0', 'allow'),
('4', 'moderation.warn', '0', 'allow'),
('5', 'moderation.access_private', '0', 'allow'),
('5', 'moderation.approve', '0', 'allow'),
('5', 'moderation.ban_temp', '0', 'allow');
DELETE FROM `nf_sanctions`;
INSERT INTO `nf_sanctions` (`id`, `user_id`, `type`, `scope`, `reason`, `duration_seconds`, `starts_at`, `expires_at`, `issued_by`, `requires_approval`, `approved_by`, `approved_at`, `revoked_at`, `revoked_by`, `revoke_reason`, `related_report_id`, `notify_user`, `created_at`) VALUES
('1', '332', 'warning', 'global', 'Premier rappel à l\'ordre — propos déplacés sur le forum.', NULL, '2026-09-15 11:46:29', NULL, '271', '0', NULL, NULL, NULL, NULL, NULL, NULL, '1', '2026-09-15 11:46:29'),
('2', '333', 'mute', 'talks', 'Spam répété dans le salon général.', '86400', '2026-09-14 11:46:29', '2026-09-15 11:46:29', '271', '0', NULL, NULL, NULL, NULL, NULL, NULL, '1', '2026-09-14 11:46:29');
DELETE FROM `nf_ip_banlist`;
-- nf_ip_banlist : aucune donnée.
DELETE FROM `nf_dispositions`;
INSERT INTO `nf_dispositions` (`disposition_id`, `theme`, `page`, `zone`, `disposition`) VALUES
('82', 'nebula', '*', '0', '[]'),
('83', 'nebula', '*', '2', '[{\"style\":\"row-default\",\"cols\":[{\"size\":\"col-md-8\",\"widgets\":[{\"id\":135,\"style\":null,\"size\":null}]},{\"size\":\"col-md-4\",\"widgets\":[{\"id\":136,\"style\":\"panel-color\",\"size\":null},{\"id\":137,\"style\":\"panel-default\",\"size\":null},{\"id\":138,\"style\":\"panel-default\",\"size\":null}]}]}]'),
('84', 'nebula', '*', '1', '[]'),
('85', 'nebula', '*', '3', '[]'),
('86', 'nebula', '*', '4', '[]'),
('87', 'nebula', '/', '1', '[{\"style\":\"row-default\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":134,\"style\":null,\"size\":null}]}]}]'),
('88', 'nebula', 'forum/*', '2', '[{\"style\":\"row-default\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":139,\"style\":null,\"size\":null}]}]},{\"style\":\"row-default\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":140,\"style\":null,\"size\":null}]}]}]'),
('89', 'nebula', 'forum/*', '3', '[{\"style\":\"row-default\",\"cols\":[{\"size\":\"col-md-4\",\"widgets\":[{\"id\":145,\"style\":\"panel-header\",\"size\":null}]},{\"size\":\"col-md-8\",\"widgets\":[{\"id\":146,\"style\":\"panel-header\",\"size\":null}]}]}]'),
('90', 'nebula', 'news/*', '2', '[{\"style\":\"row-default\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":141,\"style\":null,\"size\":null}]}]},{\"style\":\"row-default\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":142,\"style\":null,\"size\":null}]}]}]'),
('91', 'nebula', 'user/*', '2', '[{\"style\":\"row-default\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":143,\"style\":null,\"size\":null}]}]},{\"style\":\"row-default\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":144,\"style\":null,\"size\":null}]}]}]'),
('134', 'nebula', '*', '5', '[{\"style\":\"row-default\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":133,\"style\":null,\"size\":null}]}]}]'),
('114', 'forge', '*', '0', '[{\"style\":\"row-default\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":9072,\"style\":null,\"size\":null}]}]}]'),
('115', 'forge', '*', '2', '[{\"style\":\"row-default\",\"cols\":[{\"size\":\"col-lg-8\",\"widgets\":[{\"id\":9075,\"style\":null,\"size\":null}]},{\"size\":\"col-lg-4\",\"widgets\":[{\"id\":9076,\"style\":\"panel-default\",\"size\":null},{\"id\":9077,\"style\":\"panel-default\",\"size\":null},{\"id\":9078,\"style\":\"panel-default\",\"size\":null}]}]}]'),
('116', 'forge', '*', '4', '[{\"style\":\"row-default\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":9087,\"style\":null,\"size\":null}]}]}]'),
('117', 'forge', '*', '1', '[]'),
('118', 'forge', '*', '3', '[]'),
('119', 'forge', '/', '1', '[{\"style\":\"row-default\",\"cols\":[{\"size\":\"col-lg-8\",\"widgets\":[{\"id\":9073,\"style\":null,\"size\":null}]},{\"size\":\"col-lg-4\",\"widgets\":[{\"id\":9074,\"style\":\"panel-default\",\"size\":null}]}]}]'),
('120', 'forge', 'forum/*', '2', '[{\"style\":\"row-default\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":9079,\"style\":null,\"size\":null}]}]},{\"style\":\"row-default\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":9080,\"style\":null,\"size\":null}]}]}]'),
('121', 'forge', 'forum/*', '3', '[{\"style\":\"row-default\",\"cols\":[{\"size\":\"col-md-4\",\"widgets\":[{\"id\":9085,\"style\":\"panel-default\",\"size\":null}]},{\"size\":\"col-md-8\",\"widgets\":[{\"id\":9086,\"style\":\"panel-default\",\"size\":null}]}]}]'),
('122', 'forge', 'news/*', '2', '[{\"style\":\"row-default\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":9081,\"style\":null,\"size\":null}]}]},{\"style\":\"row-default\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":9082,\"style\":null,\"size\":null}]}]}]'),
('123', 'forge', 'user/*', '2', '[{\"style\":\"row-default\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":9083,\"style\":null,\"size\":null}]}]},{\"style\":\"row-default\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":9084,\"style\":null,\"size\":null}]}]}]'),
('124', 'granite', '*', '0', '[{\"style\":\"row-default\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":9088,\"style\":null,\"size\":null}]}]}]'),
('125', 'granite', '*', '2', '[{\"style\":\"row-default\",\"cols\":[{\"size\":\"col-lg-8\",\"widgets\":[{\"id\":9093,\"style\":null,\"size\":null}]},{\"size\":\"col-lg-4\",\"widgets\":[{\"id\":9090,\"style\":\"panel-default\",\"size\":null},{\"id\":9091,\"style\":\"panel-default\",\"size\":null},{\"id\":9092,\"style\":\"panel-default\",\"size\":null}]}]}]'),
('126', 'granite', '*', '4', '[{\"style\":\"row-default\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":9102,\"style\":null,\"size\":null}]}]}]'),
('127', 'granite', '*', '1', '[]'),
('128', 'granite', '*', '3', '[]'),
('129', 'granite', '/', '1', '[{\"style\":\"row-default\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":9089,\"style\":null,\"size\":null}]}]}]'),
('130', 'granite', 'forum/*', '2', '[{\"style\":\"row-default\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":9094,\"style\":null,\"size\":null}]}]},{\"style\":\"row-default\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":9095,\"style\":null,\"size\":null}]}]}]'),
('131', 'granite', 'forum/*', '3', '[{\"style\":\"row-default\",\"cols\":[{\"size\":\"col-md-4\",\"widgets\":[{\"id\":9100,\"style\":\"panel-default\",\"size\":null}]},{\"size\":\"col-md-8\",\"widgets\":[{\"id\":9101,\"style\":\"panel-default\",\"size\":null}]}]}]'),
('132', 'granite', 'news/*', '2', '[{\"style\":\"row-default\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":9096,\"style\":null,\"size\":null}]}]},{\"style\":\"row-default\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":9097,\"style\":null,\"size\":null}]}]}]'),
('133', 'granite', 'user/*', '2', '[{\"style\":\"row-default\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":9098,\"style\":null,\"size\":null}]}]},{\"style\":\"row-default\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":9099,\"style\":null,\"size\":null}]}]}]'),
('135', 'chronique', '*', '0', '[{\"style\":\"row-default\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":9103,\"style\":null,\"size\":null}]}]}]'),
('136', 'chronique', '*', '2', '[{\"style\":\"row-default\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":9105,\"style\":null,\"size\":null}]}]}]'),
('137', 'chronique', '*', '3', '[{\"style\":\"row-default\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":9113,\"style\":\"panel-color\",\"size\":null},{\"id\":9114,\"style\":\"panel-default\",\"size\":null},{\"id\":9115,\"style\":\"panel-default\",\"size\":null}]}]}]'),
('138', 'chronique', '*', '4', '[{\"style\":\"row-default\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":9118,\"style\":null,\"size\":null}]}]}]'),
('139', 'chronique', '*', '1', '[]'),
('140', 'chronique', '/', '1', '[{\"style\":\"row-default\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":9104,\"style\":\"panel-default\",\"size\":null}]}]}]'),
('141', 'chronique', '/', '2', '[{\"style\":\"row-default\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":9106,\"style\":null,\"size\":null}]}]}]'),
('142', 'chronique', 'forum/*', '2', '[{\"style\":\"row-default\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":9107,\"style\":null,\"size\":null}]}]},{\"style\":\"row-default\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":9108,\"style\":null,\"size\":null}]}]}]'),
('143', 'chronique', 'forum/*', '3', '[{\"style\":\"row-default\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":9116,\"style\":\"panel-default\",\"size\":null},{\"id\":9117,\"style\":\"panel-default\",\"size\":null}]}]}]'),
('144', 'chronique', 'news/*', '2', '[{\"style\":\"row-default\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":9109,\"style\":null,\"size\":null}]}]},{\"style\":\"row-default\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":9110,\"style\":null,\"size\":null}]}]}]'),
('145', 'chronique', 'user/*', '2', '[{\"style\":\"row-default\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":9111,\"style\":null,\"size\":null}]}]},{\"style\":\"row-default\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":9112,\"style\":null,\"size\":null}]}]}]'),
('146', 'chronique', 'user/*', '3', '[]'),
('147', 'pulse', '*', '0', '[{\"style\":\"row-default\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":9119,\"style\":null,\"size\":null}]}]}]'),
('148', 'pulse', '*', '2', '[{\"style\":\"row-default\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":9129,\"style\":null,\"size\":null}]}]}]'),
('149', 'pulse', '*', '3', '[]'),
('150', 'pulse', '*', '4', '[{\"style\":\"row-default\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":9138,\"style\":null,\"size\":null}]}]}]'),
('151', 'pulse', '*', '1', '[]'),
('152', 'pulse', '/', '1', '[{\"style\":\"row-default\",\"cols\":[{\"size\":\"col-lg-6\",\"widgets\":[{\"id\":9120,\"style\":\"panel-color\",\"size\":null}]},{\"size\":\"col-lg-6\",\"widgets\":[{\"id\":9121,\"style\":\"panel-default\",\"size\":null}]}]},{\"style\":\"row-default\",\"cols\":[{\"size\":\"col-lg-8\",\"widgets\":[{\"id\":9122,\"style\":\"panel-default\",\"size\":null}]},{\"size\":\"col-lg-4\",\"widgets\":[{\"id\":9123,\"style\":\"panel-default\",\"size\":null}]}]},{\"style\":\"row-default\",\"cols\":[{\"size\":\"col-lg-4\",\"widgets\":[{\"id\":9124,\"style\":\"panel-header\",\"size\":null}]},{\"size\":\"col-lg-4\",\"widgets\":[{\"id\":9125,\"style\":\"panel-default\",\"size\":null}]},{\"size\":\"col-lg-4\",\"widgets\":[{\"id\":9126,\"style\":\"panel-default\",\"size\":null}]}]},{\"style\":\"row-default\",\"cols\":[{\"size\":\"col-lg-7\",\"widgets\":[{\"id\":9127,\"style\":\"panel-default\",\"size\":null}]},{\"size\":\"col-lg-5\",\"widgets\":[{\"id\":9128,\"style\":\"panel-default\",\"size\":null}]}]}]'),
('153', 'pulse', '/', '2', '[]'),
('154', 'pulse', 'forum/*', '2', '[{\"style\":\"row-default\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":9130,\"style\":null,\"size\":null}]}]},{\"style\":\"row-default\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":9131,\"style\":null,\"size\":null}]}]}]'),
('155', 'pulse', 'forum/*', '3', '[{\"style\":\"row-default\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":9136,\"style\":\"panel-color\",\"size\":null},{\"id\":9137,\"style\":\"panel-default\",\"size\":null}]}]}]'),
('156', 'pulse', 'news/*', '2', '[{\"style\":\"row-default\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":9132,\"style\":null,\"size\":null}]}]},{\"style\":\"row-default\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":9133,\"style\":null,\"size\":null}]}]}]'),
('157', 'pulse', 'user/*', '2', '[{\"style\":\"row-default\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":9134,\"style\":null,\"size\":null}]}]},{\"style\":\"row-default\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":9135,\"style\":null,\"size\":null}]}]}]'),
('92', 'blockcraft', '*', '0', '[{\"style\":\"row-default\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":9139,\"style\":null,\"size\":null}]}]}]'),
('93', 'blockcraft', '*', '1', '[{\"style\":\"row-default\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":9140,\"style\":null,\"size\":null}]}]}]'),
('94', 'blockcraft', '*', '2', '[]'),
('95', 'blockcraft', '*', '3', '[]'),
('96', 'blockcraft', '*', '4', '[{\"style\":\"row-default\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":9155,\"style\":null,\"size\":null}]}]}]'),
('97', 'blockcraft', '/', '1', '[{\"style\":\"row-default\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":9141,\"style\":\"panel-default\",\"size\":null}]}]},{\"style\":\"row-default\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":9142,\"style\":\"panel-default\",\"size\":null}]}]}]'),
('98', 'blockcraft', '/', '2', '[{\"style\":\"row-default\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":9149,\"style\":\"panel-color\",\"size\":null},{\"id\":9150,\"style\":\"panel-default\",\"size\":null},{\"id\":9151,\"style\":\"panel-default\",\"size\":null}]}]}]'),
('99', 'blockcraft', '/', '3', '[{\"style\":\"row-default\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":9154,\"style\":\"panel-default\",\"size\":null}]}]}]'),
('100', 'blockcraft', 'forum/*', '1', '[{\"style\":\"row-default\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":9143,\"style\":null,\"size\":null}]}]},{\"style\":\"row-default\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":9144,\"style\":null,\"size\":null}]}]}]'),
('101', 'blockcraft', 'forum/*', '2', '[{\"style\":\"row-default\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":9152,\"style\":\"panel-color\",\"size\":null},{\"id\":9153,\"style\":\"panel-default\",\"size\":null}]}]}]'),
('158', 'blockcraft', 'news/*', '1', '[{\"style\":\"row-default\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":9145,\"style\":null,\"size\":null}]}]},{\"style\":\"row-default\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":9146,\"style\":null,\"size\":null}]}]}]'),
('159', 'blockcraft', 'user/*', '1', '[{\"style\":\"row-default\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":9147,\"style\":null,\"size\":null}]}]},{\"style\":\"row-default\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":9148,\"style\":null,\"size\":null}]}]}]'),
('102', 'extend', '*', '0', '[{\"style\":\"row-default\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":9156,\"style\":null,\"size\":null}]}]}]'),
('103', 'extend', '*', '1', '[]'),
('104', 'extend', '*', '2', '[{\"style\":\"row-default\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":9158,\"style\":null,\"size\":null}]}]}]'),
('105', 'extend', '*', '3', '[{\"style\":\"row-default\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":9168,\"style\":\"panel-default\",\"size\":null},{\"id\":9169,\"style\":\"panel-default\",\"size\":null}]}]}]'),
('106', 'extend', '*', '4', '[]'),
('107', 'extend', '/', '1', '[{\"style\":\"row-default\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":9157,\"style\":null,\"size\":null}]}]}]'),
('108', 'extend', '/', '2', '[{\"style\":\"row-default\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":9159,\"style\":\"panel-default\",\"size\":null}]}]},{\"style\":\"row-default\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":9160,\"style\":\"panel-default\",\"size\":null}]}]},{\"style\":\"row-default\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":9161,\"style\":\"panel-default\",\"size\":null}]}]}]'),
('109', 'extend', 'forum/*', '2', '[{\"style\":\"row-default\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":9162,\"style\":null,\"size\":null}]}]},{\"style\":\"row-default\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":9163,\"style\":null,\"size\":null}]}]}]'),
('110', 'extend', 'forum/*', '3', '[{\"style\":\"row-default\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":9170,\"style\":\"panel-default\",\"size\":null},{\"id\":9171,\"style\":\"panel-default\",\"size\":null}]}]}]'),
('111', 'extend', 'news/*', '2', '[{\"style\":\"row-default\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":9164,\"style\":null,\"size\":null}]}]},{\"style\":\"row-default\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":9165,\"style\":null,\"size\":null}]}]}]'),
('112', 'extend', 'user/*', '2', '[{\"style\":\"row-default\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":9166,\"style\":null,\"size\":null}]}]},{\"style\":\"row-default\",\"cols\":[{\"size\":null,\"widgets\":[{\"id\":9167,\"style\":null,\"size\":null}]}]}]');
DELETE FROM `nf_widgets`;
INSERT INTO `nf_widgets` (`widget_id`, `widget`, `type`, `settings`) VALUES
('133', 'navigation', 'index', '{\"links\":[{\"title\":\"Accueil\",\"url\":\"\"},{\"title\":\"Actualit&eacute;s\",\"url\":\"news\"},{\"title\":\"Forum\",\"url\":\"forum\"},{\"title\":\"Galerie\",\"url\":\"gallery\"},{\"title\":\"Membres\",\"url\":\"members\"},{\"title\":\"Contact\",\"url\":\"contact\"}]}'),
('134', 'slider', 'index', NULL),
('135', 'module', 'index', NULL),
('136', 'user', 'index', NULL),
('137', 'members', 'online', NULL),
('138', 'news', 'categories', NULL),
('139', 'breadcrumb', 'index', NULL),
('140', 'module', 'index', NULL),
('141', 'breadcrumb', 'index', NULL),
('142', 'module', 'index', NULL),
('143', 'breadcrumb', 'index', NULL),
('144', 'module', 'index', NULL),
('145', 'forum', 'statistics', NULL),
('146', 'forum', 'activity', NULL),
('9001', 'module', 'index', NULL),
('9072', 'navigation', 'vertical', '{\"links\":[{\"title\":\"Accueil\",\"url\":\"\",\"icon\":\"fas fa-house\"},{\"title\":\"Actualit&eacute;s\",\"url\":\"news\",\"icon\":\"far fa-newspaper\"},{\"title\":\"Forum\",\"url\":\"forum\",\"icon\":\"far fa-comments\"},{\"title\":\"Matchs\",\"url\":\"events/matches\",\"icon\":\"fas fa-crosshairs\"},{\"title\":\"&Eacute;quipes\",\"url\":\"teams\",\"icon\":\"fas fa-users\"},{\"title\":\"Galerie\",\"url\":\"gallery\",\"icon\":\"far fa-images\"},{\"title\":\"Nous rejoindre\",\"url\":\"recruits\",\"icon\":\"fas fa-user-plus\"},{\"title\":\"Contact\",\"url\":\"contact\",\"icon\":\"far fa-envelope\"}],\"panel\":0}'),
('9073', 'slider', 'index', NULL),
('9074', 'events', 'matches', NULL),
('9075', 'module', 'index', NULL),
('9076', 'events', 'upcoming', NULL),
('9077', 'awards', 'index', NULL),
('9078', 'members', 'online', NULL),
('9079', 'breadcrumb', 'index', NULL),
('9080', 'module', 'index', NULL),
('9081', 'breadcrumb', 'index', NULL),
('9082', 'module', 'index', NULL),
('9083', 'breadcrumb', 'index', NULL),
('9084', 'module', 'index', NULL),
('9085', 'forum', 'statistics', NULL),
('9086', 'forum', 'activity', NULL),
('9087', 'partners', 'column', NULL),
('9088', 'navigation', 'index', '{\"links\":[{\"title\":\"&Agrave; la une\",\"url\":\"\"},{\"title\":\"Actualit&eacute;s\",\"url\":\"news\"},{\"title\":\"Agenda\",\"url\":\"calendar\"},{\"title\":\"Forum\",\"url\":\"forum\"},{\"title\":\"Galerie\",\"url\":\"gallery\"},{\"title\":\"Documents\",\"url\":\"downloads\"},{\"title\":\"Membres\",\"url\":\"members\"},{\"title\":\"Contact\",\"url\":\"contact\"}]}'),
('9089', 'forum', 'topics', NULL),
('9090', 'calendar', 'upcoming', NULL),
('9091', 'surveys', 'current', NULL),
('9092', 'members', 'online', NULL),
('9093', 'module', 'index', NULL),
('9094', 'breadcrumb', 'index', NULL),
('9095', 'module', 'index', NULL),
('9096', 'breadcrumb', 'index', NULL),
('9097', 'module', 'index', NULL),
('9098', 'breadcrumb', 'index', NULL),
('9099', 'module', 'index', NULL),
('9100', 'forum', 'statistics', NULL),
('9101', 'forum', 'activity', NULL),
('9102', 'partners', 'column', NULL),
('9103', 'navigation', 'vertical', '{\"links\":[{\"title\":\"Accueil\",\"url\":\"\"},{\"title\":\"Actualit&eacute;s\",\"url\":\"news\"},{\"title\":\"Agenda\",\"url\":\"calendar\"},{\"title\":\"Forum\",\"url\":\"forum\"},{\"title\":\"Galerie\",\"url\":\"gallery\"},{\"title\":\"Documents\",\"url\":\"downloads\"},{\"title\":\"Membres\",\"url\":\"members\"},{\"title\":\"Contact\",\"url\":\"contact\"}],\"panel\":0}'),
('9104', 'calendar', 'semaine', NULL),
('9105', 'module', 'index', NULL),
('9106', 'frise', 'index', NULL),
('9107', 'breadcrumb', 'index', NULL),
('9108', 'module', 'index', NULL),
('9109', 'breadcrumb', 'index', NULL),
('9110', 'module', 'index', NULL),
('9111', 'breadcrumb', 'index', NULL),
('9112', 'module', 'index', NULL),
('9113', 'user', 'index', NULL),
('9114', 'downloads', 'popular', NULL),
('9115', 'partners', 'column', NULL),
('9116', 'forum', 'statistics', NULL),
('9117', 'forum', 'activity', NULL),
('9118', 'navigation', 'index', '{\"links\":[{\"title\":\"Agenda\",\"url\":\"calendar\"},{\"title\":\"Documents\",\"url\":\"downloads\"},{\"title\":\"Contact\",\"url\":\"contact\"}],\"panel\":0}'),
('9119', 'navigation', 'index', '{\"links\":[{\"title\":\"Accueil\",\"url\":\"\"},{\"title\":\"Actualit&eacute;s\",\"url\":\"news\"},{\"title\":\"Agenda\",\"url\":\"calendar\"},{\"title\":\"Forum\",\"url\":\"forum\"},{\"title\":\"Galerie\",\"url\":\"gallery\"},{\"title\":\"Documents\",\"url\":\"downloads\"},{\"title\":\"Contact\",\"url\":\"contact\"}],\"panel\":0}'),
('9120', 'calendar', 'prochain', NULL),
('9121', 'chiffres', 'index', NULL),
('9122', 'news', 'index', NULL),
('9123', 'calendar', 'upcoming', NULL),
('9124', 'surveys', 'current', NULL),
('9125', 'forum', 'topics', NULL),
('9126', 'downloads', 'popular', NULL),
('9127', 'gallery', 'grille', NULL),
('9128', 'partners', 'column', NULL),
('9129', 'module', 'index', NULL),
('9130', 'breadcrumb', 'index', NULL),
('9131', 'module', 'index', NULL),
('9132', 'breadcrumb', 'index', NULL),
('9133', 'module', 'index', NULL),
('9134', 'breadcrumb', 'index', NULL),
('9135', 'module', 'index', NULL),
('9136', 'forum', 'statistics', NULL),
('9137', 'forum', 'activity', NULL),
('9138', 'navigation', 'vertical', '{\"links\":[{\"title\":\"Agenda\",\"url\":\"calendar\"},{\"title\":\"Documents\",\"url\":\"downloads\"},{\"title\":\"Membres\",\"url\":\"members\"},{\"title\":\"Contact\",\"url\":\"contact\"}],\"panel\":0}'),
('9139', 'navigation', 'index', '{\"links\":[{\"title\":\"Accueil\",\"url\":\"\"},{\"title\":\"Nouvelles\",\"url\":\"news\"},{\"title\":\"Forum\",\"url\":\"forum\"},{\"title\":\"Agenda\",\"url\":\"calendar\"},{\"title\":\"Galerie\",\"url\":\"gallery\"},{\"title\":\"Membres\",\"url\":\"members\"},{\"title\":\"Mon espace\",\"url\":\"user\"}],\"panel\":0}'),
('9140', 'module', 'index', NULL),
('9141', 'news', 'index', NULL),
('9142', 'forum', 'topics', NULL),
('9143', 'breadcrumb', 'index', NULL),
('9144', 'module', 'index', NULL),
('9145', 'breadcrumb', 'index', NULL),
('9146', 'module', 'index', NULL),
('9147', 'breadcrumb', 'index', NULL),
('9148', 'module', 'index', NULL),
('9149', 'calendar', 'prochain', NULL),
('9150', 'chiffres', 'index', NULL),
('9151', 'members', 'online', NULL),
('9152', 'forum', 'statistics', NULL),
('9153', 'forum', 'activity', NULL),
('9154', 'gallery', 'grille', NULL),
('9155', 'navigation', 'vertical', '{\"links\":[{\"title\":\"Nouvelles\",\"url\":\"news\"},{\"title\":\"Forum\",\"url\":\"forum\"},{\"title\":\"Membres\",\"url\":\"members\"},{\"title\":\"Contact\",\"url\":\"contact\"}],\"panel\":0}'),
('9156', 'navigation', 'index', '{\"links\":[{\"title\":\"Accueil\",\"url\":\"\",\"icon\":\"fas fa-house\"},{\"title\":\"Actualit&eacute;s\",\"url\":\"news\",\"icon\":\"far fa-newspaper\"},{\"title\":\"Forum\",\"url\":\"forum\",\"icon\":\"far fa-comments\"},{\"title\":\"&Eacute;quipes\",\"url\":\"teams\",\"icon\":\"fas fa-users\"},{\"title\":\"Galerie\",\"url\":\"gallery\",\"icon\":\"far fa-images\"},{\"title\":\"Agenda\",\"url\":\"calendar\",\"icon\":\"far fa-calendar\"}],\"panel\":0}'),
('9157', 'slider', 'index', NULL),
('9158', 'module', 'index', NULL),
('9159', 'calendar', 'upcoming', '{\"count\":3,\"display_panel\":\"oui\"}'),
('9160', 'news', 'index', NULL),
('9161', 'events', 'matches', NULL),
('9162', 'breadcrumb', 'index', NULL),
('9163', 'module', 'index', NULL),
('9164', 'breadcrumb', 'index', NULL),
('9165', 'module', 'index', NULL),
('9166', 'breadcrumb', 'index', NULL),
('9167', 'module', 'index', NULL),
('9168', 'members', 'en_ligne', NULL),
('9169', 'talks', 'salon', NULL),
('9170', 'members', 'en_ligne', NULL),
('9171', 'forum', 'statistics', NULL);

COMMIT;
SET FOREIGN_KEY_CHECKS = 1;
