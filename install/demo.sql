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
('1', 'en', 'Announcements'),
('1', 'fr', 'Annonces'),
('2', 'en', 'Competition'),
('2', 'fr', 'Compétition'),
('3', 'en', 'Community'),
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
('1', 'en', 'Welcome to NeoFrag Reborn', 'The community\'s new website is live!', '<p>We are delighted to welcome you to our new platform, powered by <strong>NeoFrag Reborn</strong>. Forum, news, galleries, tournaments: it\'s all here. Sign up and join the adventure!</p>', ''),
('1', 'fr', 'Bienvenue sur NeoFrag Reborn', 'Le nouveau site de la communauté est en ligne !', '<p>Nous sommes ravis de vous accueillir sur notre nouvelle plateforme propulsée par <strong>NeoFrag Reborn</strong>. Forum, actualités, galeries, tournois : tout y est. Inscrivez-vous et rejoignez l\'aventure !</p>', ''),
('2', 'en', 'Victory in the regional tournament final', 'Our main team wins the grand final 3-1.', '<p>After a flawless run, our players lifted the regional tournament trophy. Congratulations to the whole team on this performance!</p><p>Next goal: the national qualifiers.</p>', ''),
('2', 'fr', 'Victoire en finale du tournoi régional', 'Notre équipe principale s\'impose 3-1 en grande finale.', '<p>Après un parcours sans faute, nos joueurs ont décroché le trophée du tournoi régional. Félicitations à toute l\'équipe pour cette performance !</p><p>Prochain objectif : les qualifications nationales.</p>', ''),
('3', 'en', 'Community night this Friday', 'Join us for a relaxed evening on the server.', '<p>This Friday at 9 pm, we all meet up for fun, friendly games. Beginners welcome!</p>', ''),
('3', 'fr', 'Soirée communautaire ce vendredi', 'Rejoignez-nous pour une soirée détente sur le serveur.', '<p>Ce vendredi à 21h, on se retrouve tous pour des parties fun et conviviales. Débutants bienvenus !</p>', ''),
('4', 'en', 'New hardware partnership', 'Exclusive discounts for our members.', '<p>Thanks to our new partner, enjoy discounts on gaming gear. Details in the members\' area.</p>', ''),
('4', 'fr', 'Nouveau partenariat matériel', 'Des réductions exclusives pour nos membres.', '<p>Grâce à notre nouveau partenaire, profitez de réductions sur le matériel gaming. Détails dans l\'espace membre.</p>', ''),
('5', 'en', 'This month\'s match schedule', 'All the competitive dates not to miss.', '<p>The schedule of upcoming matches is out. Come and cheer on your teams!</p>', ''),
('5', 'fr', 'Calendrier des matchs du mois', 'Tous les rendez-vous compétitifs à ne pas manquer.', '<p>Le calendrier des prochains matchs est disponible. Venez supporter vos équipes !</p>', ''),
('6', 'en', 'Video editing contest', 'Show off your best highlights and earn points.', '<p>Enter our editing contest: the best videos will be rewarded with shop points.</p>', ''),
('6', 'fr', 'Concours de montage vidéo', 'Montrez vos plus beaux highlights et gagnez des points.', '<p>Participez à notre concours de montage : les meilleures vidéos seront récompensées en points boutique.</p>', '');
DELETE FROM `nf_articles_categories`;
INSERT INTO `nf_articles_categories` (`category_id`, `image_id`, `icon_id`, `name`) VALUES
('1', NULL, NULL, 'guides'),
('2', NULL, NULL, 'tests');
DELETE FROM `nf_articles_categories_lang`;
INSERT INTO `nf_articles_categories_lang` (`category_id`, `lang`, `title`) VALUES
('1', 'en', 'Guides'),
('1', 'fr', 'Guides'),
('2', 'en', 'Reviews'),
('2', 'fr', 'Tests');
DELETE FROM `nf_articles`;
INSERT INTO `nf_articles` (`article_id`, `category_id`, `series_id`, `series_order`, `user_id`, `image_id`, `date`, `published`, `featured`, `announced_at`, `views`, `deleted_at`, `deleted_by`) VALUES
('1', '1', NULL, '0', '331', NULL, '2026-09-01 11:46:28', '1', '0', '2026-09-01 11:46:28', '40', NULL, NULL),
('2', '1', NULL, '0', '332', NULL, '2026-09-04 11:46:28', '1', '0', '2026-09-04 11:46:28', '62', NULL, NULL),
('3', '2', NULL, '0', '333', NULL, '2026-09-07 11:46:28', '1', '0', '2026-09-07 11:46:28', '84', NULL, NULL),
('4', '2', NULL, '0', '334', NULL, '2026-09-10 11:46:28', '1', '0', '2026-09-10 11:46:28', '106', NULL, NULL);
DELETE FROM `nf_articles_lang`;
INSERT INTO `nf_articles_lang` (`article_id`, `lang`, `title`, `excerpt`, `content`, `tags`) VALUES
('1', 'en', 'Getting started in competitive play', 'Our tips to improve quickly.', '<h2>The basics</h2><p>Master your aim and positioning first. Consistency beats flashy plays.</p><h2>Team spirit</h2><p>Communication is the key to victory.</p>', ''),
('1', 'fr', 'Bien débuter en compétitif', 'Nos conseils pour progresser rapidement.', '<h2>Les bases</h2><p>Maîtrisez d\'abord votre visée et votre placement. La régularité prime sur les coups d\'éclat.</p><h2>L\'esprit d\'équipe</h2><p>La communication est la clé de la victoire.</p>', ''),
('2', 'en', 'Optimising your setup', 'Settings and gear for a top-notch setup.', '<p>A good setup isn\'t everything, but it helps. Here are our recommended settings and peripherals.</p>', ''),
('2', 'fr', 'Optimiser sa configuration', 'Réglages et matériel pour un setup au top.', '<p>Un bon setup ne fait pas tout, mais il aide. Voici nos recommandations réglages et périphériques.</p>', ''),
('3', 'en', 'Our take on the latest patch', 'What changes for the competitive meta.', '<p>The latest patch reshuffles the deck. An analysis of the nerfs, buffs and their impact on the meta.</p>', ''),
('3', 'fr', 'Notre avis sur le dernier patch', 'Ce qui change pour la méta compétitive.', '<p>Le dernier patch rebat les cartes. Analyse des nerfs, buffs et de leur impact sur la méta.</p>', ''),
('4', 'en', 'Gaming headsets: the comparison', 'We tested the current models for you.', '<p>Comfort, sound, microphone: our full comparison to help you pick the right headset.</p>', ''),
('4', 'fr', 'Casque gaming : le comparatif', 'On a testé pour vous les modèles du moment.', '<p>Confort, son, micro : notre comparatif complet pour choisir le bon casque.</p>', '');
DELETE FROM `nf_forum_categories`;
INSERT INTO `nf_forum_categories` (`category_id`, `title`, `order`, `image_id`, `vip_only`) VALUES
('1', 'Communauté', '0', NULL, '0'),
('2', 'Jeux & Compétition', '1', NULL, '0');
DELETE FROM `nf_forum`;
INSERT INTO `nf_forum` (`forum_id`, `parent_id`, `is_subforum`, `title`, `description`, `icon`, `order`, `count_topics`, `count_messages`, `last_message_id`) VALUES
('1', '1', '0', 'Présentations', 'Présentez-vous à la communauté', '', '0', '2', '4', '6'),
('2', '1', '0', 'Discussions générales', 'Pour parler de tout et de rien', '', '1', '1', '2', '9'),
('3', '2', '0', 'Stratégies', 'Partagez vos tactiques', '', '0', '1', '3', '13'),
('4', '2', '0', 'Recherche d\'équipe', 'Trouvez des coéquipiers', '', '1', '1', '1', '15');
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
('1', 'en', 'Events'),
('1', 'fr', 'Événements'),
('2', 'en', 'Highlights'),
('2', 'fr', 'Highlights');
DELETE FROM `nf_gallery`;
INSERT INTO `nf_gallery` (`gallery_id`, `category_id`, `image_id`, `name`, `published`, `date`, `deleted_at`, `deleted_by`) VALUES
('1', '1', '1', 'lan-d-ete-2025', '1', '2026-09-06 11:46:28', NULL, NULL),
('2', '1', '13', 'finale-regionale', '1', '2026-09-08 11:46:28', NULL, NULL),
('3', '2', '22', 'best-of-du-mois', '1', '2026-09-10 11:46:29', NULL, NULL);
DELETE FROM `nf_gallery_lang`;
INSERT INTO `nf_gallery_lang` (`gallery_id`, `lang`, `title`, `description`) VALUES
('1', 'en', 'Summer LAN 2025', 'The best moments of our annual LAN.'),
('1', 'fr', 'LAN d\'été 2025', 'Les meilleurs moments de notre LAN annuelle.'),
('2', 'en', 'Regional final', 'Our victory in pictures.'),
('2', 'fr', 'Finale régionale', 'Retour en images sur notre victoire.'),
('3', 'en', 'Best of the month', 'A compilation of the finest plays.'),
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
('1', 'en', 'About the community', 'Who we are', '<p>Founded in 2019 around Counter-Strike, our community now brings together a hundred or so players across four games. People come for the level and stay for the atmosphere.</p><p>Three competitive teams, weekly practice sessions, an annual LAN — and a Discord server open to everyone.</p>'),
('1', 'fr', 'À propos de la communauté', 'Qui sommes-nous', '<p>Fondée en 2019 autour de Counter-Strike, notre communauté réunit aujourd\'hui une centaine de joueurs sur quatre jeux. On y vient pour le niveau, on y reste pour l\'ambiance.</p><p>Trois équipes compétitives, des entraînements hebdomadaires, une LAN annuelle — et un serveur Discord ouvert à tous.</p>'),
('2', 'en', 'House rules', 'What we expect from everyone', '<p>Respect above all: no insults, no discriminatory remarks, no cheating. A breach is first met with a warning, then with an exclusion.</p><ul><li>Microphone recommended in practice, mandatory in official matches.</li><li>Let us know if you will be absent, at least 24 hours in advance.</li><li>Staff settle disputes; their decisions are public.</li></ul>'),
('2', 'fr', 'Règlement intérieur', 'Ce qu\'on attend de chacun', '<p>Respect avant tout : pas d\'insultes, pas de propos discriminatoires, pas de triche. Un manquement se règle d\'abord par un avertissement, ensuite par une exclusion.</p><ul><li>Micro conseillé en entraînement, obligatoire en match officiel.</li><li>Prévenir en cas d\'absence, au moins 24 h à l\'avance.</li><li>Le staff tranche les litiges ; ses décisions sont publiques.</li></ul>'),
('3', 'en', 'Join us', 'How to apply', '<p>Open recruitments are listed in the dedicated section. You can also introduce yourself on the forum: we look at every unsolicited application.</p>'),
('3', 'fr', 'Nous rejoindre', 'Comment postuler', '<p>Les recrutements ouverts sont listés dans la rubrique dédiée. Tu peux aussi te présenter sur le forum : on regarde toutes les candidatures spontanées.</p>'),
('4', 'en', 'Our partners', 'They support us', '<p>Three partners support us with hosting, hardware and video branding. Their backing funds our trips to LAN events.</p>'),
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
INSERT INTO `nf_webradio_shows` (`id`, `title`, `host`, `description`, `day`, `start_time`, `end_time`, `published`, `created_at`, `updated_at`) VALUES
('52', 'Le réveil du serveur', 'LunaByte', 'Les actualités de la communauté et la playlist du matin.', '1', '08:00', '10:00', '1', '2026-09-23 07:52:04', '2026-09-23 07:52:04'),
('53', 'Débrief des scrims', 'ShadowFox', 'Retour sur les entraînements de la semaine, avec les joueurs.', '3', '20:00', '21:30', '1', '2026-09-23 07:52:04', '2026-09-23 07:52:04'),
('54', 'Nuit blanche', 'NovaStrike', 'Musique et discussions jusqu\'au bout de la nuit, pendant les LAN.', '6', '22:00', '02:00', '1', '2026-09-23 07:52:04', '2026-09-23 07:52:04');
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
('1', 'bienvenue', 'Bienvenue sur le wiki', '<p>Ce wiki rassemble le savoir de la communauté : le règlement, les guides pour bien démarrer et nos stratégies de jeu. Tout le monde peut le lire ; l\'équipe le tient à jour.</p>\n<h2>Par où commencer</h2>\n<ul>\n<li>Lire le <strong>règlement</strong>, dans la rubrique « La communauté ».</li>\n<li>Découvrir comment <strong>rejoindre une équipe</strong>.</li>\n<li>Réviser les <strong>bases de CS2</strong> avant votre premier entraînement.</li>\n</ul>\n<h2>Proposer une page</h2>\n<p>Une idée de guide ? Proposez-la sur le forum, dans « Discussions générales » : une page validée arrive ici, et son historique garde chaque version.</p>', NULL, '1', '1', '271', '87', '2026-09-02 18:40:00', '2026-09-12 20:05:00'),
('2', 'communaute', 'La communauté', '<p>Les règles de vie, et tout ce qu\'il faut savoir pour trouver sa place parmi nous.</p>', NULL, '2', '1', '271', '41', '2026-09-02 18:52:00', '2026-09-02 18:52:00'),
('3', 'reglement', 'Règlement', '<p>Ce règlement vaut pour le site, le forum et le serveur Discord.</p>\n<h2>Les règles</h2>\n<ol>\n<li><strong>Respect</strong> : pas d\'insulte, de harcèlement ni de propos discriminants.</li>\n<li><strong>Fair-play</strong> : la triche et l\'exploitation de bugs sont interdites, en match comme à l\'entraînement.</li>\n<li><strong>Pas de spam</strong> : ni publicité, ni messages répétés, ni liens douteux.</li>\n<li><strong>Chaque sujet à sa place</strong> : chaque forum a son thème ; les annonces sont réservées à l\'équipe.</li>\n</ol>\n<h2>Les sanctions</h2>\n<p>Selon la gravité : un avertissement, une exclusion temporaire, puis un bannissement. Une sanction se conteste auprès d\'un modérateur, par message privé.</p>', '2', '1', '1', '271', '64', '2026-09-03 20:15:00', '2026-09-08 21:02:00'),
('4', 'rejoindre-une-equipe', 'Rejoindre une équipe', '<p>Nos équipes recrutent régulièrement. Voici comment se passe une candidature.</p>\n<h2>Les étapes</h2>\n<ol>\n<li>Consultez les offres de la page <strong>Recrutement</strong> et choisissez un poste.</li>\n<li>Envoyez votre candidature depuis l\'offre : votre jeu, votre rôle, vos disponibilités.</li>\n<li>Un capitaine vous propose un <strong>essai</strong> : un entraînement avec l\'équipe.</li>\n<li>Après l\'essai, la réponse arrive par message privé.</li>\n</ol>\n<h2>Ce que nous attendons</h2>\n<ul>\n<li>de la régularité aux entraînements ;</li>\n<li>un micro, et Discord ;</li>\n<li>l\'envie de progresser ensemble.</li>\n</ul>', '2', '2', '1', '271', '38', '2026-09-04 19:30:00', '2026-09-04 19:30:00'),
('5', 'jeux', 'Jeux et stratégies', '<p>Les guides de nos équipes : les bases à connaître, et l\'organisation de nos événements.</p>', NULL, '3', '1', '271', '29', '2026-09-05 21:10:00', '2026-09-05 21:10:00'),
('6', 'strategies-cs2', 'Stratégies CS2 : les bases', '<p>Les principes que nos joueurs appliquent sur toutes les cartes.</p>\n<h2>L\'économie</h2>\n<ul>\n<li>Achetez ensemble : un round d\'économie se joue à cinq.</li>\n<li>Après un round au pistolet perdu, préparez le suivant plutôt que de forcer.</li>\n</ul>\n<h2>La communication</h2>\n<ul>\n<li>Annoncez ce que vous voyez, pas ce que vous supposez : « deux en B, un blessé ».</li>\n<li>Un seul joueur mène le round.</li>\n</ul>\n<h2>Les cartes de notre pool</h2>\n<p>Mirage, Inferno, Nuke et Ancient. Chaque carte a son sujet dans le forum « Stratégies ».</p>', '5', '1', '1', '271', '52', '2026-09-06 17:45:00', '2026-09-10 22:30:00'),
('7', 'organiser-un-tournoi', 'Organiser un tournoi', '<p>Le guide des bénévoles pour nos tournois communautaires.</p>\n<h2>Avant</h2>\n<ul>\n<li>Créez l\'événement dans le <strong>calendrier</strong>, avec le règlement du tournoi.</li>\n<li>Ouvrez les inscriptions, puis annoncez-les sur Discord.</li>\n</ul>\n<h2>Pendant</h2>\n<ul>\n<li>Un arbitre par match, joignable sur Discord.</li>\n<li>Saisissez les scores au fil des matchs.</li>\n</ul>\n<h2>Après</h2>\n<p>Publiez le palmarès, et une actualité avec les plus belles actions.</p>', '5', '2', '1', '271', '21', '2026-09-07 16:20:00', '2026-09-07 16:20:00');
DELETE FROM `nf_wiki_revisions`;
INSERT INTO `nf_wiki_revisions` (`id`, `page_id`, `content`, `title`, `user_id`, `comment`, `created_at`) VALUES
('1', '1', '<p>Ce wiki rassemble le savoir de la communauté : le règlement, les guides pour bien démarrer et nos stratégies de jeu.</p>', 'Bienvenue sur le wiki', '271', 'Première rédaction', '2026-09-02 18:40:00'),
('2', '3', '<p>Ce règlement vaut pour le site, le forum et le serveur Discord.</p>\n<h2>Les règles</h2>\n<ol>\n<li><strong>Respect</strong> : pas d\'insulte, de harcèlement ni de propos discriminants.</li>\n<li><strong>Fair-play</strong> : la triche et l\'exploitation de bugs sont interdites, en match comme à l\'entraînement.</li>\n<li><strong>Pas de spam</strong> : ni publicité, ni messages répétés, ni liens douteux.</li>\n</ol>', 'Règlement', '271', 'Première rédaction', '2026-09-03 20:15:00'),
('3', '3', '<p>Ce règlement vaut pour le site, le forum et le serveur Discord.</p>\n<h2>Les règles</h2>\n<ol>\n<li><strong>Respect</strong> : pas d\'insulte, de harcèlement ni de propos discriminants.</li>\n<li><strong>Fair-play</strong> : la triche et l\'exploitation de bugs sont interdites, en match comme à l\'entraînement.</li>\n<li><strong>Pas de spam</strong> : ni publicité, ni messages répétés, ni liens douteux.</li>\n<li><strong>Chaque sujet à sa place</strong> : chaque forum a son thème ; les annonces sont réservées à l\'équipe.</li>\n</ol>\n<h2>Les sanctions</h2>\n<p>Selon la gravité : un avertissement, une exclusion temporaire, puis un bannissement. Une sanction se conteste auprès d\'un modérateur, par message privé.</p>', 'Règlement', '271', 'Ajout des sanctions', '2026-09-08 21:02:00');
DELETE FROM `nf_games_maps`;
INSERT INTO `nf_games_maps` (`map_id`, `game_id`, `image_id`, `title`) VALUES
('1', '1', NULL, 'Mirage'),
('2', '1', NULL, 'Inferno'),
('3', '1', NULL, 'Nuke'),
('4', '1', NULL, 'Ancient'),
('5', '1', NULL, 'Anubis'),
('6', '2', NULL, 'Ascent'),
('7', '2', NULL, 'Bind'),
('8', '2', NULL, 'Haven'),
('9', '2', NULL, 'Lotus'),
('10', '3', NULL, 'Faille de l\'invocateur'),
('11', '3', NULL, 'ARAM — Abîme hurlant'),
('12', '4', NULL, 'Champions Field'),
('13', '4', NULL, 'Mannfield'),
('14', '4', NULL, 'Urban Central');
DELETE FROM `nf_games_modes`;
INSERT INTO `nf_games_modes` (`mode_id`, `game_id`, `title`) VALUES
('1', '1', 'Compétitif 5v5'),
('2', '1', 'Wingman 2v2'),
('3', '1', 'Premier'),
('4', '2', 'Compétitif 5v5'),
('5', '2', 'Swiftplay'),
('6', '3', 'Faille classée 5v5'),
('7', '3', 'ARAM'),
('8', '4', 'Duo 2v2'),
('9', '4', 'Standard 3v3');
DELETE FROM `nf_teams_roles`;
INSERT INTO `nf_teams_roles` (`role_id`, `title`, `order`) VALUES
('1', 'Capitaine', '0'),
('2', 'Titulaire', '1'),
('3', 'Remplaçant', '2'),
('4', 'Coach', '3');
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
('9072', 'navigation', 'vertical', NULL, '{\"links\":[{\"title\":\"Accueil\",\"url\":\"\",\"icon\":\"fas fa-house\"},{\"title\":\"Actualit&eacute;s\",\"url\":\"news\",\"icon\":\"far fa-newspaper\"},{\"title\":\"Forum\",\"url\":\"forum\",\"icon\":\"far fa-comments\"},{\"title\":\"Matchs\",\"url\":\"events/matches\",\"icon\":\"fas fa-crosshairs\"},{\"title\":\"&Eacute;quipes\",\"url\":\"teams\",\"icon\":\"fas fa-users\"},{\"title\":\"Galerie\",\"url\":\"gallery\",\"icon\":\"far fa-images\"},{\"title\":\"Nous rejoindre\",\"url\":\"recruits\",\"icon\":\"fas fa-user-plus\"},{\"title\":\"Contact\",\"url\":\"contact\",\"icon\":\"far fa-envelope\"}],\"panel\":0}'),
('9073', 'slider', 'index', NULL, NULL),
('9074', 'events', 'matches', NULL, NULL),
('9075', 'module', 'index', NULL, NULL),
('9076', 'events', 'upcoming', NULL, NULL),
('9077', 'awards', 'index', NULL, NULL),
('9078', 'members', 'online', NULL, NULL),
('9079', 'breadcrumb', 'index', NULL, NULL),
('9080', 'module', 'index', NULL, NULL),
('9081', 'breadcrumb', 'index', NULL, NULL),
('9082', 'module', 'index', NULL, NULL),
('9083', 'breadcrumb', 'index', NULL, NULL),
('9084', 'module', 'index', NULL, NULL),
('9085', 'forum', 'statistics', NULL, NULL),
('9086', 'forum', 'activity', NULL, NULL),
('9087', 'partners', 'column', NULL, NULL),
('9088', 'navigation', 'index', NULL, '{\"links\":[{\"title\":\"&Agrave; la une\",\"url\":\"\"},{\"title\":\"Actualit&eacute;s\",\"url\":\"news\"},{\"title\":\"Agenda\",\"url\":\"calendar\"},{\"title\":\"Forum\",\"url\":\"forum\"},{\"title\":\"Galerie\",\"url\":\"gallery\"},{\"title\":\"Documents\",\"url\":\"downloads\"},{\"title\":\"Membres\",\"url\":\"members\"},{\"title\":\"Contact\",\"url\":\"contact\"}]}'),
('9089', 'forum', 'topics', NULL, NULL),
('9090', 'calendar', 'upcoming', NULL, NULL),
('9091', 'surveys', 'current', NULL, NULL),
('9092', 'members', 'online', NULL, NULL),
('9093', 'module', 'index', NULL, NULL),
('9094', 'breadcrumb', 'index', NULL, NULL),
('9095', 'module', 'index', NULL, NULL),
('9096', 'breadcrumb', 'index', NULL, NULL),
('9097', 'module', 'index', NULL, NULL),
('9098', 'breadcrumb', 'index', NULL, NULL),
('9099', 'module', 'index', NULL, NULL),
('9100', 'forum', 'statistics', NULL, NULL),
('9101', 'forum', 'activity', NULL, NULL),
('9102', 'partners', 'column', NULL, NULL),
('9103', 'navigation', 'vertical', NULL, '{\"links\":[{\"title\":\"Accueil\",\"url\":\"\"},{\"title\":\"Actualit&eacute;s\",\"url\":\"news\"},{\"title\":\"Agenda\",\"url\":\"calendar\"},{\"title\":\"Forum\",\"url\":\"forum\"},{\"title\":\"Galerie\",\"url\":\"gallery\"},{\"title\":\"Documents\",\"url\":\"downloads\"},{\"title\":\"Membres\",\"url\":\"members\"},{\"title\":\"Contact\",\"url\":\"contact\"}],\"panel\":0}'),
('9104', 'calendar', 'semaine', NULL, NULL),
('9105', 'module', 'index', NULL, NULL),
('9106', 'frise', 'index', NULL, NULL),
('9107', 'breadcrumb', 'index', NULL, NULL),
('9108', 'module', 'index', NULL, NULL),
('9109', 'breadcrumb', 'index', NULL, NULL),
('9110', 'module', 'index', NULL, NULL),
('9111', 'breadcrumb', 'index', NULL, NULL),
('9112', 'module', 'index', NULL, NULL),
('9113', 'user', 'index', NULL, NULL),
('9114', 'downloads', 'popular', NULL, NULL),
('9115', 'partners', 'column', NULL, NULL),
('9116', 'forum', 'statistics', NULL, NULL),
('9117', 'forum', 'activity', NULL, NULL),
('9118', 'navigation', 'index', NULL, '{\"links\":[{\"title\":\"Agenda\",\"url\":\"calendar\"},{\"title\":\"Documents\",\"url\":\"downloads\"},{\"title\":\"Contact\",\"url\":\"contact\"}],\"panel\":0}'),
('9119', 'navigation', 'index', NULL, '{\"links\":[{\"title\":\"Accueil\",\"url\":\"\"},{\"title\":\"Actualit&eacute;s\",\"url\":\"news\"},{\"title\":\"Agenda\",\"url\":\"calendar\"},{\"title\":\"Forum\",\"url\":\"forum\"},{\"title\":\"Galerie\",\"url\":\"gallery\"},{\"title\":\"Documents\",\"url\":\"downloads\"},{\"title\":\"Contact\",\"url\":\"contact\"}],\"panel\":0}'),
('9120', 'calendar', 'prochain', NULL, NULL),
('9121', 'chiffres', 'index', NULL, NULL),
('9122', 'news', 'index', NULL, NULL),
('9123', 'calendar', 'upcoming', NULL, NULL),
('9124', 'surveys', 'current', NULL, NULL),
('9125', 'forum', 'topics', NULL, NULL),
('9126', 'downloads', 'popular', NULL, NULL),
('9127', 'gallery', 'grille', NULL, NULL),
('9128', 'partners', 'column', NULL, NULL),
('9129', 'module', 'index', NULL, NULL),
('9130', 'breadcrumb', 'index', NULL, NULL),
('9131', 'module', 'index', NULL, NULL),
('9132', 'breadcrumb', 'index', NULL, NULL),
('9133', 'module', 'index', NULL, NULL),
('9134', 'breadcrumb', 'index', NULL, NULL),
('9135', 'module', 'index', NULL, NULL),
('9136', 'forum', 'statistics', NULL, NULL),
('9137', 'forum', 'activity', NULL, NULL),
('9138', 'navigation', 'vertical', NULL, '{\"links\":[{\"title\":\"Agenda\",\"url\":\"calendar\"},{\"title\":\"Documents\",\"url\":\"downloads\"},{\"title\":\"Membres\",\"url\":\"members\"},{\"title\":\"Contact\",\"url\":\"contact\"}],\"panel\":0}'),
('9139', 'navigation', 'index', NULL, '{\"links\":[{\"title\":\"Accueil\",\"url\":\"\"},{\"title\":\"Nouvelles\",\"url\":\"news\"},{\"title\":\"Forum\",\"url\":\"forum\"},{\"title\":\"Agenda\",\"url\":\"calendar\"},{\"title\":\"Galerie\",\"url\":\"gallery\"},{\"title\":\"Membres\",\"url\":\"members\"},{\"title\":\"Mon espace\",\"url\":\"user\"}],\"panel\":0}'),
('9140', 'module', 'index', NULL, NULL),
('9141', 'news', 'index', NULL, NULL),
('9142', 'forum', 'topics', NULL, NULL),
('9143', 'breadcrumb', 'index', NULL, NULL),
('9144', 'module', 'index', NULL, NULL),
('9145', 'breadcrumb', 'index', NULL, NULL),
('9146', 'module', 'index', NULL, NULL),
('9147', 'breadcrumb', 'index', NULL, NULL),
('9148', 'module', 'index', NULL, NULL),
('9149', 'calendar', 'prochain', NULL, NULL),
('9150', 'chiffres', 'index', NULL, NULL),
('9151', 'members', 'online', NULL, NULL),
('9152', 'forum', 'statistics', NULL, NULL),
('9153', 'forum', 'activity', NULL, NULL),
('9154', 'gallery', 'grille', NULL, NULL),
('9155', 'navigation', 'vertical', NULL, '{\"links\":[{\"title\":\"Nouvelles\",\"url\":\"news\"},{\"title\":\"Forum\",\"url\":\"forum\"},{\"title\":\"Membres\",\"url\":\"members\"},{\"title\":\"Contact\",\"url\":\"contact\"}],\"panel\":0}'),
('9156', 'navigation', 'index', NULL, '{\"links\":[{\"title\":\"Accueil\",\"url\":\"\",\"icon\":\"fas fa-house\"},{\"title\":\"Actualit&eacute;s\",\"url\":\"news\",\"icon\":\"far fa-newspaper\"},{\"title\":\"Forum\",\"url\":\"forum\",\"icon\":\"far fa-comments\"},{\"title\":\"&Eacute;quipes\",\"url\":\"teams\",\"icon\":\"fas fa-users\"},{\"title\":\"Galerie\",\"url\":\"gallery\",\"icon\":\"far fa-images\"},{\"title\":\"Agenda\",\"url\":\"calendar\",\"icon\":\"far fa-calendar\"}],\"panel\":0}'),
('9157', 'slider', 'index', NULL, NULL),
('9158', 'module', 'index', NULL, NULL),
('9159', 'calendar', 'upcoming', NULL, '{\"count\":3,\"display_panel\":\"oui\"}'),
('9160', 'news', 'index', NULL, NULL),
('9161', 'events', 'matches', NULL, NULL),
('9162', 'breadcrumb', 'index', NULL, NULL),
('9163', 'module', 'index', NULL, NULL),
('9164', 'breadcrumb', 'index', NULL, NULL),
('9165', 'module', 'index', NULL, NULL),
('9166', 'breadcrumb', 'index', NULL, NULL),
('9167', 'module', 'index', NULL, NULL),
('9168', 'members', 'en_ligne', NULL, NULL),
('9169', 'talks', 'salon', NULL, NULL),
('9170', 'members', 'en_ligne', NULL, NULL),
('9171', 'forum', 'statistics', NULL, NULL);

COMMIT;
SET FOREIGN_KEY_CHECKS = 1;
