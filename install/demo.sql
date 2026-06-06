-- NeoFrag Reborn — instantané du site de DÉMO (config affichage + membres + contenu).
-- Généré par tools/dump-demo.php. Rechargé par l'auto-reset démo. NE PAS éditer à la main.
-- Ne touche pas le compte admin ni les secrets (verrouillés en mode démo).

SET FOREIGN_KEY_CHECKS = 0;
SET NAMES utf8mb4;

-- Config démo (idempotent)
UPDATE `nf_settings` SET `value` = 'nebula' WHERE `name` = 'nf_default_theme';
DELETE FROM `nf_addon` WHERE `name` = 'vitrine' AND `type_id` = 2;
DELETE FROM `nf_dispositions` WHERE `theme` = 'vitrine';
DELETE FROM `nf_addon` WHERE `name` = 'landing' AND `type_id` = 3;
DELETE FROM `nf_widgets` WHERE `widget` = 'landing';
DELETE FROM `nf_role_permissions` WHERE `role_id` = 3 AND `permission` = 'forum.category_read';
INSERT INTO `nf_role_permissions` (`role_id`, `permission`, `scope_id`, `authorized`) VALUES (3, 'forum.category_read', 0, 'allow');

-- Membres démo (l'admin est préservé)
DELETE FROM `nf_user_profile` WHERE `id` NOT IN (SELECT `id` FROM `nf_user` WHERE `admin` = '1');
DELETE FROM `nf_user` WHERE `admin` = '0';
TRUNCATE TABLE `nf_user_points`;
TRUNCATE TABLE `nf_karma`;
INSERT INTO `nf_user` (`id`, `username`, `password`, `salt`, `email`, `registration_date`, `last_activity_date`, `admin`, `language`, `data`, `deleted`, `totp_secret`, `totp_enabled`) VALUES
('221', 'demo', '$argon2id$v=19$m=65536,t=4,p=1$b041Wlp4Z2F0ZU44bTRxYg$53XeHT4/WdM7+8PCrKNO3xHzc3ENUpD1D9EEGRLNMA8', '', 'demo@demo.local', '2026-04-06 16:14:33', '2026-06-05 16:14:33', '0', NULL, '', '0', NULL, '0'),
('222', 'ShadowFox', '$argon2id$v=19$m=65536,t=4,p=1$b041Wlp4Z2F0ZU44bTRxYg$53XeHT4/WdM7+8PCrKNO3xHzc3ENUpD1D9EEGRLNMA8', '', 'shadowfox@demo.local', '2026-04-10 16:14:33', '2026-06-05 15:14:33', '0', NULL, '', '0', NULL, '0'),
('223', 'NovaStrike', '$argon2id$v=19$m=65536,t=4,p=1$b041Wlp4Z2F0ZU44bTRxYg$53XeHT4/WdM7+8PCrKNO3xHzc3ENUpD1D9EEGRLNMA8', '', 'novastrike@demo.local', '2026-04-14 16:14:33', '2026-06-05 14:14:33', '0', NULL, '', '0', NULL, '0'),
('224', 'VortexQc', '$argon2id$v=19$m=65536,t=4,p=1$b041Wlp4Z2F0ZU44bTRxYg$53XeHT4/WdM7+8PCrKNO3xHzc3ENUpD1D9EEGRLNMA8', '', 'vortexqc@demo.local', '2026-04-18 16:14:33', '2026-06-05 13:14:33', '0', NULL, '', '0', NULL, '0'),
('225', 'LunaByte', '$argon2id$v=19$m=65536,t=4,p=1$b041Wlp4Z2F0ZU44bTRxYg$53XeHT4/WdM7+8PCrKNO3xHzc3ENUpD1D9EEGRLNMA8', '', 'lunabyte@demo.local', '2026-04-22 16:14:33', '2026-06-05 12:14:33', '0', NULL, '', '0', NULL, '0'),
('226', 'Rekkles_FR', '$argon2id$v=19$m=65536,t=4,p=1$b041Wlp4Z2F0ZU44bTRxYg$53XeHT4/WdM7+8PCrKNO3xHzc3ENUpD1D9EEGRLNMA8', '', 'rekkles_fr@demo.local', '2026-04-26 16:14:33', '2026-06-05 11:14:33', '0', NULL, '', '0', NULL, '0'),
('227', 'PixelWitch', '$argon2id$v=19$m=65536,t=4,p=1$b041Wlp4Z2F0ZU44bTRxYg$53XeHT4/WdM7+8PCrKNO3xHzc3ENUpD1D9EEGRLNMA8', '', 'pixelwitch@demo.local', '2026-04-30 16:14:33', '2026-06-05 10:14:33', '0', NULL, '', '0', NULL, '0'),
('228', 'Zenith', '$argon2id$v=19$m=65536,t=4,p=1$b041Wlp4Z2F0ZU44bTRxYg$53XeHT4/WdM7+8PCrKNO3xHzc3ENUpD1D9EEGRLNMA8', '', 'zenith@demo.local', '2026-05-04 16:14:33', '2026-06-05 09:14:33', '0', NULL, '', '0', NULL, '0'),
('229', 'Kira', '$argon2id$v=19$m=65536,t=4,p=1$b041Wlp4Z2F0ZU44bTRxYg$53XeHT4/WdM7+8PCrKNO3xHzc3ENUpD1D9EEGRLNMA8', '', 'kira@demo.local', '2026-05-08 16:14:33', '2026-06-05 08:14:33', '0', NULL, '', '0', NULL, '0'),
('230', 'OldSchool', '$argon2id$v=19$m=65536,t=4,p=1$b041Wlp4Z2F0ZU44bTRxYg$53XeHT4/WdM7+8PCrKNO3xHzc3ENUpD1D9EEGRLNMA8', '', 'oldschool@demo.local', '2026-05-12 16:14:33', '2026-06-05 07:14:33', '0', NULL, '', '0', NULL, '0');
INSERT INTO `nf_user_profile` (`id`, `first_name`, `last_name`, `avatar`, `cover`, `signature`, `date_of_birth`, `sex`, `country`, `timezone`, `location`, `quote`, `website`, `linkedin`, `github`, `instagram`, `twitch`) VALUES
('221', 'Alex', 'Martin', NULL, NULL, '', NULL, 'male', 'France', 'Europe/Paris', '', 'Ici pour tester NeoFrag Reborn !', '', '', '', '', ''),
('222', 'Lucas', 'Bernard', NULL, NULL, '', NULL, 'male', 'France', 'Europe/Paris', '', 'GG WP à tous.', '', '', '', '', ''),
('223', 'Emma', 'Dubois', NULL, NULL, '', NULL, 'female', 'Belgique', 'Europe/Paris', '', 'La précision avant tout.', '', '', '', '', ''),
('224', 'Hugo', 'Lefebvre', NULL, NULL, '', NULL, 'male', 'Canada', 'Europe/Paris', '', 'Aim first, think later.', '', '', '', '', ''),
('225', 'Chloé', 'Moreau', NULL, NULL, '', NULL, 'female', 'Suisse', 'Europe/Paris', '', 'Support main, toujours là.', '', '', '', '', ''),
('226', 'Nathan', 'Garcia', NULL, NULL, '', NULL, 'male', 'France', 'Europe/Paris', '', 'On grind la ranked.', '', '', '', '', ''),
('227', 'Léa', 'Roux', NULL, NULL, '', NULL, 'female', 'France', 'Europe/Paris', '', 'Montage & highlights.', '', '', '', '', ''),
('228', 'Théo', 'Fournier', NULL, NULL, '', NULL, 'male', 'France', 'Europe/Paris', '', 'IGL de la team.', '', '', '', '', ''),
('229', 'Manon', 'Girard', NULL, NULL, '', NULL, 'female', 'France', 'Europe/Paris', '', 'Clutch or kick.', '', '', '', '', ''),
('230', 'Pierre', 'Lambert', NULL, NULL, '', NULL, 'male', 'France', 'Europe/Paris', '', 'Je joue depuis Quake.', '', '', '', '', '');
INSERT INTO `nf_user_points` (`user_id`, `total`, `earned`, `spent`, `updated_at`) VALUES
('221', '50', '60', '10', '2026-06-05 16:14:33'),
('222', '85', '95', '10', '2026-06-05 16:14:33'),
('223', '120', '130', '10', '2026-06-05 16:14:33'),
('224', '155', '165', '10', '2026-06-05 16:14:33'),
('225', '190', '200', '10', '2026-06-05 16:14:33'),
('226', '225', '235', '10', '2026-06-05 16:14:33'),
('227', '260', '270', '10', '2026-06-05 16:14:33'),
('228', '295', '305', '10', '2026-06-05 16:14:33'),
('229', '330', '340', '10', '2026-06-05 16:14:33'),
('230', '365', '375', '10', '2026-06-05 16:14:33');
INSERT INTO `nf_karma` (`user_id`, `score`, `reactions_received`, `content_count`, `updated_at`) VALUES
('221', '0', '0', '0', '2026-06-05 16:14:33'),
('222', '7', '3', '1', '2026-06-05 16:14:33'),
('223', '14', '6', '2', '2026-06-05 16:14:33'),
('224', '21', '9', '3', '2026-06-05 16:14:33'),
('225', '28', '12', '4', '2026-06-05 16:14:33'),
('226', '35', '15', '5', '2026-06-05 16:14:33'),
('227', '42', '18', '6', '2026-06-05 16:14:33'),
('228', '49', '21', '7', '2026-06-05 16:14:33'),
('229', '56', '24', '8', '2026-06-05 16:14:33'),
('230', '63', '27', '9', '2026-06-05 16:14:33');

-- Contenu
TRUNCATE TABLE `nf_news_categories`;
INSERT INTO `nf_news_categories` (`category_id`, `image_id`, `icon_id`, `name`) VALUES
('1', NULL, NULL, 'Annonces'),
('2', NULL, NULL, 'Compétition'),
('3', NULL, NULL, 'Communauté');
TRUNCATE TABLE `nf_news_categories_lang`;
INSERT INTO `nf_news_categories_lang` (`category_id`, `lang`, `title`) VALUES
('1', 'fr', 'Annonces'),
('2', 'fr', 'Compétition'),
('3', 'fr', 'Communauté');
TRUNCATE TABLE `nf_news`;
INSERT INTO `nf_news` (`news_id`, `category_id`, `user_id`, `image_id`, `date`, `published`, `announced_at`, `views`, `vote`, `deleted_at`, `deleted_by`) VALUES
('1', '1', '221', NULL, '2026-05-16 16:14:33', '1', '2026-05-16 16:14:33', '30', '1', NULL, NULL),
('2', '2', '222', NULL, '2026-05-19 16:14:33', '1', '2026-05-19 16:14:33', '47', '1', NULL, NULL),
('3', '3', '223', NULL, '2026-05-22 16:14:33', '1', '2026-05-22 16:14:33', '64', '1', NULL, NULL),
('4', '1', '224', NULL, '2026-05-25 16:14:33', '1', '2026-05-25 16:14:33', '81', '1', NULL, NULL),
('5', '2', '225', NULL, '2026-05-28 16:14:33', '1', '2026-05-28 16:14:33', '98', '1', NULL, NULL),
('6', '3', '226', NULL, '2026-05-31 16:14:33', '1', '2026-05-31 16:14:33', '115', '1', NULL, NULL);
TRUNCATE TABLE `nf_news_lang`;
INSERT INTO `nf_news_lang` (`news_id`, `lang`, `title`, `introduction`, `content`, `tags`) VALUES
('1', 'fr', 'Bienvenue sur NeoFrag Reborn', 'Le nouveau site de la communauté est en ligne !', '<p>Nous sommes ravis de vous accueillir sur notre nouvelle plateforme propulsée par <strong>NeoFrag Reborn</strong>. Forum, actualités, galeries, tournois : tout y est. Inscrivez-vous et rejoignez l\'aventure !</p>', ''),
('2', 'fr', 'Victoire en finale du tournoi régional', 'Notre équipe principale s\'impose 3-1 en grande finale.', '<p>Après un parcours sans faute, nos joueurs ont décroché le trophée du tournoi régional. Félicitations à toute l\'équipe pour cette performance !</p><p>Prochain objectif : les qualifications nationales.</p>', ''),
('3', 'fr', 'Soirée communautaire ce vendredi', 'Rejoignez-nous pour une soirée détente sur le serveur.', '<p>Ce vendredi à 21h, on se retrouve tous pour des parties fun et conviviales. Débutants bienvenus !</p>', ''),
('4', 'fr', 'Nouveau partenariat matériel', 'Des réductions exclusives pour nos membres.', '<p>Grâce à notre nouveau partenaire, profitez de réductions sur le matériel gaming. Détails dans l\'espace membre.</p>', ''),
('5', 'fr', 'Calendrier des matchs du mois', 'Tous les rendez-vous compétitifs à ne pas manquer.', '<p>Le calendrier des prochains matchs est disponible. Venez supporter vos équipes !</p>', ''),
('6', 'fr', 'Concours de montage vidéo', 'Montrez vos plus beaux highlights et gagnez des points.', '<p>Participez à notre concours de montage : les meilleures vidéos seront récompensées en points boutique.</p>', '');
TRUNCATE TABLE `nf_articles_categories`;
INSERT INTO `nf_articles_categories` (`category_id`, `image_id`, `icon_id`, `name`) VALUES
('1', NULL, NULL, 'Guides'),
('2', NULL, NULL, 'Tests');
TRUNCATE TABLE `nf_articles_categories_lang`;
INSERT INTO `nf_articles_categories_lang` (`category_id`, `lang`, `title`) VALUES
('1', 'fr', 'Guides'),
('2', 'fr', 'Tests');
TRUNCATE TABLE `nf_articles`;
INSERT INTO `nf_articles` (`article_id`, `category_id`, `user_id`, `image_id`, `date`, `published`, `announced_at`, `views`, `deleted_at`, `deleted_by`) VALUES
('1', '1', '224', NULL, '2026-05-21 16:14:33', '1', '2026-05-21 16:14:33', '40', NULL, NULL),
('2', '1', '225', NULL, '2026-05-24 16:14:33', '1', '2026-05-24 16:14:33', '62', NULL, NULL),
('3', '2', '226', NULL, '2026-05-27 16:14:33', '1', '2026-05-27 16:14:33', '84', NULL, NULL),
('4', '2', '227', NULL, '2026-05-30 16:14:33', '1', '2026-05-30 16:14:33', '106', NULL, NULL);
TRUNCATE TABLE `nf_articles_lang`;
INSERT INTO `nf_articles_lang` (`article_id`, `lang`, `title`, `excerpt`, `content`, `tags`) VALUES
('1', 'fr', 'Bien débuter en compétitif', 'Nos conseils pour progresser rapidement.', '<h2>Les bases</h2><p>Maîtrisez d\'abord votre visée et votre placement. La régularité prime sur les coups d\'éclat.</p><h2>L\'esprit d\'équipe</h2><p>La communication est la clé de la victoire.</p>', ''),
('2', 'fr', 'Optimiser sa configuration', 'Réglages et matériel pour un setup au top.', '<p>Un bon setup ne fait pas tout, mais il aide. Voici nos recommandations réglages et périphériques.</p>', ''),
('3', 'fr', 'Notre avis sur le dernier patch', 'Ce qui change pour la méta compétitive.', '<p>Le dernier patch rebat les cartes. Analyse des nerfs, buffs et de leur impact sur la méta.</p>', ''),
('4', 'fr', 'Casque gaming : le comparatif', 'On a testé pour vous les modèles du moment.', '<p>Confort, son, micro : notre comparatif complet pour choisir le bon casque.</p>', '');
TRUNCATE TABLE `nf_forum_categories`;
INSERT INTO `nf_forum_categories` (`category_id`, `title`, `order`, `image_id`, `vip_only`) VALUES
('1', 'Communauté', '0', NULL, '0'),
('2', 'Jeux & Compétition', '1', NULL, '0');
TRUNCATE TABLE `nf_forum`;
INSERT INTO `nf_forum` (`forum_id`, `parent_id`, `is_subforum`, `title`, `description`, `order`, `count_topics`, `count_messages`, `last_message_id`) VALUES
('1', '1', '0', 'Présentations', 'Présentez-vous à la communauté', '0', '2', '6', '6'),
('2', '1', '0', 'Discussions générales', 'Pour parler de tout et de rien', '1', '1', '3', '9'),
('3', '2', '0', 'Stratégies', 'Partagez vos tactiques', '0', '1', '4', '13'),
('4', '2', '0', 'Recherche d\'équipe', 'Trouvez des coéquipiers', '1', '1', '2', '15');
TRUNCATE TABLE `nf_forum_url`;
INSERT INTO `nf_forum_url` (`forum_id`, `url`, `redirects`) VALUES
('1', '', '0'),
('2', '', '0'),
('3', '', '0'),
('4', '', '0');
TRUNCATE TABLE `nf_forum_topics`;
INSERT INTO `nf_forum_topics` (`topic_id`, `forum_id`, `message_id`, `title`, `status`, `views`, `count_messages`, `last_message_id`, `is_announced`, `is_locked`) VALUES
('1', '1', '1', 'Salut tout le monde !', '0', '10', '4', '4', '0', '0'),
('2', '1', '5', 'Présentation rapide', '0', '19', '2', '6', '0', '0'),
('3', '2', '7', 'Votre setup du moment ?', '0', '28', '3', '9', '0', '0'),
('4', '3', '10', 'Gérer la pression en finale', '0', '37', '4', '13', '0', '0'),
('5', '4', '14', 'Cherche support pour ranked', '0', '46', '2', '15', '0', '0');
TRUNCATE TABLE `nf_forum_messages`;
INSERT INTO `nf_forum_messages` (`message_id`, `topic_id`, `parent_id`, `user_id`, `message`, `date`, `deleted_at`, `deleted_by`, `deleted_reason`) VALUES
('1', '1', NULL, '221', 'Nouveau ici, hâte de jouer avec vous.', '2026-05-24 16:14:33', NULL, NULL, NULL),
('2', '1', NULL, '222', 'Bienvenue à toi !', '2026-05-24 17:14:33', NULL, NULL, NULL),
('3', '1', NULL, '223', 'Salut, on se voit en jeu !', '2026-05-24 18:14:33', NULL, NULL, NULL),
('4', '1', NULL, '224', 'Welcome !', '2026-05-24 19:14:33', NULL, NULL, NULL),
('5', '2', NULL, '222', 'Joueur depuis des années, ravi de rejoindre.', '2026-05-25 16:14:33', NULL, NULL, NULL),
('6', '2', NULL, '223', 'Bienvenue parmi nous !', '2026-05-25 17:14:33', NULL, NULL, NULL),
('7', '3', NULL, '223', 'Montrez vos installations !', '2026-05-26 16:14:33', NULL, NULL, NULL),
('8', '3', NULL, '224', 'Clavier méca + souris légère, le combo.', '2026-05-26 17:14:33', NULL, NULL, NULL),
('9', '3', NULL, '225', 'Double écran obligatoire pour moi.', '2026-05-26 18:14:33', NULL, NULL, NULL),
('10', '4', NULL, '224', 'Comment vous restez calmes dans les moments clés ?', '2026-05-27 16:14:33', NULL, NULL, NULL),
('11', '4', NULL, '225', 'Respiration et routine avant match.', '2026-05-27 17:14:33', NULL, NULL, NULL),
('12', '4', NULL, '226', 'On parle peu mais on parle utile.', '2026-05-27 18:14:33', NULL, NULL, NULL),
('13', '4', NULL, '227', 'Le mental, c\'est 50% du jeu.', '2026-05-27 19:14:33', NULL, NULL, NULL),
('14', '5', NULL, '225', 'Niveau diamant, dispo le soir.', '2026-05-28 16:14:33', NULL, NULL, NULL),
('15', '5', NULL, '226', 'Intéressé, je t\'ajoute !', '2026-05-28 17:14:33', NULL, NULL, NULL);
TRUNCATE TABLE `nf_gallery_categories`;
INSERT INTO `nf_gallery_categories` (`category_id`, `image_id`, `icon_id`, `name`) VALUES
('1', NULL, NULL, 'Événements'),
('2', NULL, NULL, 'Highlights');
TRUNCATE TABLE `nf_gallery_categories_lang`;
INSERT INTO `nf_gallery_categories_lang` (`category_id`, `lang`, `title`) VALUES
('1', 'fr', 'Événements'),
('2', 'fr', 'Highlights');
TRUNCATE TABLE `nf_gallery`;
INSERT INTO `nf_gallery` (`gallery_id`, `category_id`, `image_id`, `name`, `published`, `date`, `deleted_at`, `deleted_by`) VALUES
('1', '1', NULL, 'LAN d\'été 2025', '1', '2026-05-26 16:14:33', NULL, NULL),
('2', '1', NULL, 'Finale régionale', '1', '2026-05-28 16:14:33', NULL, NULL),
('3', '2', NULL, 'Best of du mois', '1', '2026-05-30 16:14:33', NULL, NULL);
TRUNCATE TABLE `nf_gallery_lang`;
INSERT INTO `nf_gallery_lang` (`gallery_id`, `lang`, `title`, `description`) VALUES
('1', 'fr', 'LAN d\'été 2025', 'Les meilleurs moments de notre LAN annuelle.'),
('2', 'fr', 'Finale régionale', 'Retour en images sur notre victoire.'),
('3', 'fr', 'Best of du mois', 'Compilation des plus belles actions.');
TRUNCATE TABLE `nf_gallery_images`;
-- nf_gallery_images : aucune donnée.
TRUNCATE TABLE `nf_games`;
INSERT INTO `nf_games` (`game_id`, `parent_id`, `image_id`, `icon_id`, `name`) VALUES
('1', NULL, NULL, NULL, 'Counter-Strike 2'),
('2', NULL, NULL, NULL, 'Valorant'),
('3', NULL, NULL, NULL, 'League of Legends'),
('4', NULL, NULL, NULL, 'Rocket League');
TRUNCATE TABLE `nf_games_lang`;
INSERT INTO `nf_games_lang` (`game_id`, `lang`, `title`) VALUES
('1', 'fr', 'Counter-Strike 2'),
('2', 'fr', 'Valorant'),
('3', 'fr', 'League of Legends'),
('4', 'fr', 'Rocket League');
TRUNCATE TABLE `nf_teams`;
INSERT INTO `nf_teams` (`team_id`, `game_id`, `image_id`, `icon_id`, `name`, `order`) VALUES
('1', '1', NULL, NULL, 'Équipe principale CS2', '0'),
('2', '2', NULL, NULL, 'Roster Valorant', '1'),
('3', '3', NULL, NULL, 'Équipe LoL', '2');
TRUNCATE TABLE `nf_teams_lang`;
INSERT INTO `nf_teams_lang` (`team_id`, `lang`, `title`, `description`) VALUES
('1', 'fr', 'Équipe principale CS2', 'Notre roster compétitif sur CS2.'),
('2', 'fr', 'Roster Valorant', 'Cinq joueurs, un objectif : le top.'),
('3', 'fr', 'Équipe LoL', 'La faille n\'a qu\'à bien se tenir.');
TRUNCATE TABLE `nf_teams_users`;
INSERT INTO `nf_teams_users` (`team_id`, `user_id`, `role_id`) VALUES
('1', '222', NULL),
('1', '223', NULL),
('1', '224', NULL),
('1', '225', NULL),
('1', '226', NULL),
('2', '223', NULL),
('2', '224', NULL),
('2', '225', NULL),
('2', '226', NULL),
('2', '227', NULL),
('3', '224', NULL),
('3', '225', NULL),
('3', '226', NULL),
('3', '227', NULL),
('3', '228', NULL);
TRUNCATE TABLE `nf_events_types`;
INSERT INTO `nf_events_types` (`type_id`, `type`, `title`, `color`, `icon`) VALUES
('1', '1', 'Tournoi', '#e74c3c', 'fas fa-trophy'),
('2', '1', 'Entraînement', '#3498db', 'fas fa-dumbbell');
TRUNCATE TABLE `nf_events`;
INSERT INTO `nf_events` (`event_id`, `type_id`, `user_id`, `image_id`, `title`, `description`, `private_description`, `location`, `date`, `date_end`, `published`, `publish_date`) VALUES
('1', '1', '221', NULL, 'Tournoi régional CS2', 'Qualifications ouvertes à tous les niveaux.', '', 'En ligne', '2026-06-10 16:14:33', NULL, '1', '2026-05-31 16:14:33'),
('2', '1', '221', NULL, 'Coupe Valorant communautaire', 'Format double élimination, BO3.', '', 'Discord', '2026-06-17 16:14:33', NULL, '1', '2026-05-31 16:14:33'),
('3', '2', '221', NULL, 'Entraînement hebdo LoL', 'Scrims et review de games.', '', 'Faille de l\'invocateur', '2026-06-03 16:14:33', NULL, '1', '2026-05-31 16:14:33');
TRUNCATE TABLE `nf_awards`;
INSERT INTO `nf_awards` (`award_id`, `team_id`, `game_id`, `image_id`, `name`, `location`, `date`, `description`, `platform`, `ranking`, `participants`) VALUES
('1', '1', '1', NULL, 'Tournoi régional 2025', 'Lyon', '2026-04-26', '', 'PC', '1', '16'),
('2', '2', '2', NULL, 'Coupe d\'hiver', 'En ligne', '2026-04-26', '', 'PC', '2', '24'),
('3', '3', '3', NULL, 'Ligue communautaire', 'En ligne', '2026-04-26', '', 'PC', '3', '12');
TRUNCATE TABLE `nf_comment`;
INSERT INTO `nf_comment` (`id`, `parent_id`, `user_id`, `module_id`, `module`, `content`, `date`, `deleted_at`, `deleted_by`) VALUES
('1', NULL, '221', '1', 'news', 'Super nouvelle, merci !', '2026-06-05 15:14:33', NULL, NULL),
('2', NULL, '223', '1', 'news', 'Hâte d\'y être !', '2026-06-05 14:14:33', NULL, NULL),
('3', NULL, '223', '2', 'news', 'GG à l\'équipe !', '2026-06-05 13:14:33', NULL, NULL),
('4', NULL, '225', '2', 'news', 'On compte sur vous !', '2026-06-05 12:14:34', NULL, NULL),
('5', NULL, '227', '2', 'news', 'Excellent, vivement la suite.', '2026-06-05 11:14:34', NULL, NULL),
('6', NULL, '226', '3', 'news', 'Super nouvelle, merci !', '2026-06-05 10:14:34', NULL, NULL),
('7', NULL, '227', '4', 'news', 'Hâte d\'y être !', '2026-06-05 09:14:34', NULL, NULL),
('8', NULL, '229', '4', 'news', 'GG à l\'équipe !', '2026-06-05 08:14:34', NULL, NULL);
TRUNCATE TABLE `nf_reactions`;
INSERT INTO `nf_reactions` (`id`, `user_id`, `content_type`, `content_id`, `created_at`) VALUES
('1', '221', 'news', '1', '2026-06-05 15:44:34'),
('2', '223', 'news', '1', '2026-06-05 15:14:34'),
('3', '225', 'news', '1', '2026-06-05 14:44:34'),
('4', '224', 'news', '2', '2026-06-05 14:14:34'),
('5', '226', 'news', '2', '2026-06-05 13:44:34'),
('6', '228', 'news', '2', '2026-06-05 13:14:34'),
('7', '230', 'news', '2', '2026-06-05 12:44:34'),
('8', '228', 'news', '3', '2026-06-05 12:14:34'),
('9', '230', 'news', '3', '2026-06-05 11:44:34'),
('10', '222', 'news', '3', '2026-06-05 11:14:34'),
('11', '224', 'news', '3', '2026-06-05 10:44:34'),
('12', '226', 'news', '3', '2026-06-05 10:14:34'),
('13', '223', 'news', '4', '2026-06-05 09:44:34'),
('14', '225', 'news', '4', '2026-06-05 09:14:34');
TRUNCATE TABLE `nf_faq_categories`;
INSERT INTO `nf_faq_categories` (`id`, `title`, `sort_order`) VALUES
('1', 'Général', '0');
TRUNCATE TABLE `nf_faq_questions`;
INSERT INTO `nf_faq_questions` (`id`, `category_id`, `question`, `answer`, `sort_order`, `published`, `created_at`, `updated_at`) VALUES
('1', '1', 'Comment rejoindre la communauté ?', 'Inscrivez-vous gratuitement, puis présentez-vous sur le forum.', '0', '1', '2026-06-05 16:14:34', '2026-06-05 16:14:34'),
('2', '1', 'Le site est-il gratuit ?', 'Oui, NeoFrag Reborn est 100% gratuit et open source.', '1', '1', '2026-06-05 16:14:34', '2026-06-05 16:14:34'),
('3', '1', 'Comment gagner des points ?', 'En participant : poster, commenter, réagir et contribuer.', '2', '1', '2026-06-05 16:14:34', '2026-06-05 16:14:34');
TRUNCATE TABLE `nf_downloads_categories`;
INSERT INTO `nf_downloads_categories` (`id`, `title`, `description`, `sort_order`) VALUES
('1', 'Ressources', 'Configs, fonds d\'écran et outils.', '0');
TRUNCATE TABLE `nf_downloads`;
INSERT INTO `nf_downloads` (`id`, `category_id`, `title`, `description`, `file_url`, `file_size_bytes`, `file_type`, `version`, `downloads_count`, `published`, `created_at`) VALUES
('1', '1', 'Pack de fonds d\'écran', 'Une sélection de wallpapers aux couleurs de la team.', 'https://example.com/wallpapers.zip', '15728640', 'zip', '1.0', '20', '1', '2026-06-05 16:14:34'),
('2', '1', 'Config CS2 recommandée', 'Notre fichier de configuration partagé.', 'https://example.com/cs2-config.cfg', '8192', 'cfg', '2.3', '51', '1', '2026-06-05 16:14:34');
TRUNCATE TABLE `nf_links_categories`;
INSERT INTO `nf_links_categories` (`id`, `title`, `sort_order`) VALUES
('1', 'Liens utiles', '0');
TRUNCATE TABLE `nf_links`;
INSERT INTO `nf_links` (`id`, `category_id`, `title`, `url`, `description`, `clicks`, `published`, `sort_order`, `created_at`) VALUES
('1', '1', 'Notre Discord', 'https://discord.gg/example', 'Rejoignez le serveur vocal de la communauté.', '0', '1', '0', '2026-06-05 16:14:34'),
('2', '1', 'Chaîne Twitch', 'https://twitch.tv/example', 'Suivez nos lives et tournois.', '13', '1', '1', '2026-06-05 16:14:34'),
('3', '1', 'NeoFrag Reborn', 'https://neofr.ag', 'Le CMS qui propulse ce site.', '26', '1', '2', '2026-06-05 16:14:34');
TRUNCATE TABLE `nf_partners`;
INSERT INTO `nf_partners` (`partner_id`, `name`, `logo_light`, `logo_dark`, `website`, `facebook`, `twitter`, `code`, `count`, `order`) VALUES
('1', 'GamerGear', NULL, NULL, 'https://example.com', '', '', '', '0', '0'),
('2', 'EnergyDrink', NULL, NULL, 'https://example.com', '', '', '', '9', '1'),
('3', 'HostPro', NULL, NULL, 'https://example.com', '', '', '', '18', '2');
TRUNCATE TABLE `nf_partners_lang`;
INSERT INTO `nf_partners_lang` (`partner_id`, `lang`, `title`, `description`) VALUES
('1', 'fr', 'GamerGear', 'Matériel gaming partenaire officiel.'),
('2', 'fr', 'EnergyDrink', 'Le carburant de nos joueurs.'),
('3', 'fr', 'HostPro', 'Serveurs de jeu haute performance.');
TRUNCATE TABLE `nf_guestbook`;
INSERT INTO `nf_guestbook` (`id`, `user_id`, `name`, `message`, `ip_address`, `status`, `created_at`) VALUES
('1', '221', 'Alex', 'Super communauté, accueil au top !', '', 'approved', '2026-06-04 16:14:34'),
('2', '222', 'Sam', 'Le site est vraiment propre, bravo.', '', 'approved', '2026-06-03 16:14:34'),
('3', '223', 'Jordan', 'Hâte de participer au prochain tournoi.', '', 'approved', '2026-06-02 16:14:34');
TRUNCATE TABLE `nf_surveys`;
INSERT INTO `nf_surveys` (`id`, `title`, `description`, `user_id`, `multiple_choice`, `show_results`, `closed_at`, `published`, `created_at`) VALUES
('1', 'Quel jeu pour le prochain tournoi ?', 'Votez pour le jeu de notre prochain événement.', '221', '0', 'always', NULL, '1', '2026-06-05 16:14:34');
TRUNCATE TABLE `nf_surveys_options`;
INSERT INTO `nf_surveys_options` (`id`, `survey_id`, `label`, `sort_order`) VALUES
('1', '1', 'Counter-Strike 2', '0'),
('2', '1', 'Valorant', '1'),
('3', '1', 'League of Legends', '2'),
('4', '1', 'Rocket League', '3');
TRUNCATE TABLE `nf_surveys_votes`;
INSERT INTO `nf_surveys_votes` (`id`, `survey_id`, `option_id`, `user_id`, `ip_hash`, `created_at`) VALUES
('1', '1', '1', '221', '', '2026-06-05 16:14:34'),
('2', '1', '2', '222', '', '2026-06-05 15:14:34'),
('3', '1', '3', '223', '', '2026-06-05 14:14:34'),
('4', '1', '4', '224', '', '2026-06-05 13:14:34'),
('5', '1', '1', '225', '', '2026-06-05 12:14:34'),
('6', '1', '2', '226', '', '2026-06-05 11:14:34'),
('7', '1', '3', '227', '', '2026-06-05 10:14:34'),
('8', '1', '4', '228', '', '2026-06-05 09:14:34'),
('9', '1', '1', '229', '', '2026-06-05 08:14:34'),
('10', '1', '2', '230', '', '2026-06-05 07:14:34');
TRUNCATE TABLE `nf_classifieds`;
INSERT INTO `nf_classifieds` (`id`, `category_id`, `user_id`, `ad_type`, `title`, `description`, `price`, `contact`, `image`, `status`, `views`, `created_at`, `updated_at`) VALUES
('1', '1', '221', 'offer', 'Vends clavier mécanique', 'Switch rouges, très bon état, peu servi.', '60.00', 'discord: alex#0001', '', 'published', '5', '2026-06-03 16:14:34', '2026-06-03 16:14:34'),
('2', '1', '222', 'request', 'Cherche casque gaming', 'Budget 50€, micro indispensable.', '0.00', 'mp sur le forum', '', 'published', '13', '2026-06-02 16:14:34', '2026-06-02 16:14:34');
TRUNCATE TABLE `nf_recruits`;
INSERT INTO `nf_recruits` (`recruit_id`, `title`, `introduction`, `description`, `requierments`, `date`, `user_id`, `size`, `role`, `icon`, `date_end`, `closed`, `team_id`, `image_id`) VALUES
('1', 'Recrutement CS2 - Joueur AWP', 'Notre équipe CS2 cherche un sniper.', 'Tu maîtrises l\'AWP et tu cherches une équipe sérieuse ? Postule !', 'Niveau Faceit 7+, dispo 3 soirs/semaine, micro obligatoire.', '2026-06-02 16:14:34', '221', '1', 'AWPer', 'fas fa-crosshairs', '2026-06-25', '0', NULL, NULL),
('2', 'Recrutement Valorant - Support', 'On cherche un joueur support/initiateur.', 'Rejoins notre roster Valorant en construction.', 'Immortal+, bonne communication, esprit d\'équipe.', '2026-06-01 16:14:34', '221', '1', 'Initiateur', 'fas fa-shield', '2026-06-25', '0', NULL, NULL);
TRUNCATE TABLE `nf_recruits_fields`;
-- nf_recruits_fields : aucune donnée.
TRUNCATE TABLE `nf_calendar_events`;
INSERT INTO `nf_calendar_events` (`id`, `title`, `description`, `location`, `start_at`, `end_at`, `all_day`, `user_id`, `color`, `published`, `created_at`) VALUES
('1', 'Entraînement CS2', 'Scrims du soir', 'Serveur communautaire', '2026-06-07 16:14:34', '2026-06-07 18:14:34', '0', '221', '#1abc9c', '1', '2026-06-05 16:14:34'),
('2', 'Soirée détente', 'Parties fun ouvertes à tous', 'Discord', '2026-06-10 16:14:34', '2026-06-10 18:14:34', '0', '221', '#1abc9c', '1', '2026-06-05 16:14:34'),
('3', 'Maintenance serveur', 'Indisponibilité prévue', '', '2026-06-14 16:14:34', '2026-06-14 18:14:34', '1', '221', '#1abc9c', '1', '2026-06-05 16:14:34');
TRUNCATE TABLE `nf_bug_tickets`;
INSERT INTO `nf_bug_tickets` (`id`, `title`, `description`, `type`, `priority`, `status`, `user_id`, `assigned_to`, `created_at`, `updated_at`) VALUES
('1', 'Bouton de connexion mal aligné sur mobile', 'Sur petit écran, le bouton dépasse légèrement.', 'bug', 'normal', 'resolved', '222', NULL, '2026-06-05 16:14:34', '2026-06-05 16:14:34'),
('2', 'Ajouter un mode sombre au profil', 'Ce serait agréable d\'avoir le thème sombre partout.', 'feature', 'low', 'open', '223', NULL, '2026-06-05 16:14:34', '2026-06-05 16:14:34'),
('3', 'Comment changer mon avatar ?', 'Je ne trouve pas l\'option dans les réglages.', 'question', 'normal', 'closed', '224', NULL, '2026-06-05 16:14:34', '2026-06-05 16:14:34');
TRUNCATE TABLE `nf_bug_comments`;
INSERT INTO `nf_bug_comments` (`id`, `ticket_id`, `user_id`, `content`, `is_status_change`, `created_at`) VALUES
('1', '1', '221', 'Merci pour le retour, on regarde ça.', '0', '2026-06-05 15:14:34'),
('2', '2', '221', 'Merci pour le retour, on regarde ça.', '0', '2026-06-05 14:14:34'),
('3', '3', '221', 'Merci pour le retour, on regarde ça.', '0', '2026-06-05 13:14:34');
TRUNCATE TABLE `nf_donations_campaigns`;
INSERT INTO `nf_donations_campaigns` (`id`, `name`, `title`, `description`, `goal_amount`, `currency`, `paypal_email`, `paypal_button_id`, `deadline`, `status`, `created_at`, `updated_at`) VALUES
('1', 'serveur-2025', 'Financement du serveur 2025', 'Aidez-nous à financer l\'hébergement de nos serveurs de jeu.', '500.00', 'EUR', '', '', '2026-08-04', 'active', '2026-06-05 16:14:34', '2026-06-05 16:14:34');
TRUNCATE TABLE `nf_donations`;
INSERT INTO `nf_donations` (`id`, `campaign_id`, `user_id`, `donor_name`, `amount`, `currency`, `message`, `is_anonymous`, `is_public`, `source`, `paypal_txn_id`, `status`, `created_at`) VALUES
('1', '1', '221', 'Alex', '20.00', 'EUR', 'Bon courage à toute la team !', '0', '1', 'manual', NULL, 'completed', '2026-06-04 16:14:34'),
('2', '1', '222', 'Anonyme', '10.00', 'EUR', '', '1', '1', 'manual', NULL, 'completed', '2026-06-03 16:14:34'),
('3', '1', '223', 'Sam', '50.00', 'EUR', 'Continuez comme ça.', '0', '1', 'manual', NULL, 'completed', '2026-06-02 16:14:34');
TRUNCATE TABLE `nf_ads`;
INSERT INTO `nf_ads` (`id`, `title`, `placement`, `format`, `image_url`, `url`, `html`, `active`, `starts_at`, `ends_at`, `position`, `impressions`, `clicks`, `created_at`) VALUES
('1', 'Bannière partenaire', 'sidebar', 'html', '', 'https://example.com', '<div style=\"padding:1rem;text-align:center\">Espace partenaire</div>', '1', NULL, NULL, '0', '120', '8', '2026-06-05 16:14:34');
TRUNCATE TABLE `nf_newsletter_subscribers`;
INSERT INTO `nf_newsletter_subscribers` (`id`, `email`, `token`, `confirmed`, `user_id`, `created_at`, `confirmed_at`) VALUES
('1', 'fan1@demo.local', 'b4ab02c9cf82a84ed13eed5e4d4cf760', '1', '221', '2026-06-04 16:14:34', '2026-06-04 16:14:34'),
('2', 'fan2@demo.local', '07d4ef2b5c0c2e359ef8c09bac72085c', '1', '222', '2026-06-03 16:14:34', '2026-06-03 16:14:34'),
('3', 'fan3@demo.local', 'b45c03c8ff7d0e232c5fefada386f570', '1', '223', '2026-06-02 16:14:34', '2026-06-02 16:14:34'),
('4', 'fan4@demo.local', 'fff264d70c2b26bab39b5dd2098d4b0a', '1', '224', '2026-06-01 16:14:34', '2026-06-01 16:14:34');

SET FOREIGN_KEY_CHECKS = 1;
