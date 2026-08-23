<?php
declare(strict_types=1);
// Outil d'administration : jamais servi en HTTP (sinon maintenance/migrations/dumps
// seraient executables par n'importe qui si tools/ etait expose par erreur).
if (PHP_SAPI !== 'cli')
{
	http_response_code(404);
	exit;
}


/**
 * NeoFrag Reborn — peuple un site de données de DÉMO réalistes (gaming/communauté).
 *
 * Standalone mysqli (pattern tools/dump-schema.php) — le framework ne boote pas en CLI.
 * Idempotent : purge le contenu démo (hors admin) avant insertion. NE crée PAS de fichiers
 * (avatars/images = NULL) : un seed sans dépendance disque. Génère du contenu pour news,
 * articles, forum, galerie, jeux/équipes/events/palmarès, commentaires/réactions/points.
 *
 * Après ce seed, `tools/dump-demo.php` fige le résultat en install/demo.sql (auto-reset démo).
 *
 * Connexion : config/db.php ($db[0]) surchargé par NF_DB_* (cf. dump-schema.php).
 * Usage : docker compose exec -T web php tools/seed-demo.php
 */

const CONFIG_DB = __DIR__ . '/../config/db.php';

main();

function main(): void
{
    $db = connect();
    $db->query('SET FOREIGN_KEY_CHECKS = 0');
    // ins() fait exit(1) en cas d'erreur (court-circuite un try/finally) → restaurer FK_CHECKS via
    // un shutdown. La connexion est par-session (donc auto-réinitialisée), mais on reste propre.
    register_shutdown_function(static function () use ($db) { @$db->query('SET FOREIGN_KEY_CHECKS = 1'); });

    purge($db);

    $users = seed_users($db);
    seed_news($db, $users);
    seed_articles($db, $users);
    seed_forum($db, $users);
    seed_gallery($db, $users);
    seed_gaming($db, $users);
    seed_social($db, $users);
    // Couverture large : modules « à la carte ». (Le wiki = doc, géré par seed-wiki-docs + dump-wiki.)
    seed_faq($db);
    seed_downloads($db);
    seed_links($db);
    seed_partners($db);
    seed_guestbook($db, $users);
    seed_surveys($db, $users);
    seed_classifieds($db, $users);
    seed_recruits($db, $users);
    seed_calendar($db, $users);
    seed_bugtracker($db, $users);
    seed_donations($db, $users);
    seed_ads($db);
    seed_newsletter($db, $users);

    configure_demo($db);

    $db->query('SET FOREIGN_KEY_CHECKS = 1');

    echo "\n✓ Démo peuplée : " . count($users) . " membres + contenus larges (news/articles/forum/galerie/gaming/wiki/faq/downloads/links/partners/guestbook/sondages/annonces/recrutement/calendrier/bugtracker/dons/pub/newsletter).\n";
    echo "  Config démo : thème par défaut = nebula, vitrine + widget landing retirés.\n";
}

/** Purge le contenu démo. Garde l'admin (id 1) et la config. */
function purge(mysqli $db): void
{
    $db->query("DELETE FROM nf_user WHERE admin = '0'");
    // FK_CHECKS=0 (en tête de main) n'a pas cascadé les profils → on les nettoie explicitement,
    // sinon les profils non-admin/orphelins s'accumulent à chaque relance et polluent demo.sql.
    $db->query("DELETE FROM nf_user_profile WHERE id NOT IN (SELECT id FROM nf_user WHERE admin = '1')");
    foreach ([
        'nf_user_points', 'nf_karma',
        'nf_news', 'nf_news_lang', 'nf_news_categories', 'nf_news_categories_lang',
        'nf_articles', 'nf_articles_lang', 'nf_articles_categories', 'nf_articles_categories_lang',
        'nf_forum', 'nf_forum_categories', 'nf_forum_topics', 'nf_forum_messages', 'nf_forum_url',
        'nf_gallery', 'nf_gallery_lang', 'nf_gallery_categories', 'nf_gallery_categories_lang', 'nf_gallery_images',
        'nf_games', 'nf_games_lang', 'nf_teams', 'nf_teams_lang', 'nf_teams_users',
        'nf_events', 'nf_events_types', 'nf_awards',
        'nf_comment', 'nf_reactions',
        'nf_faq_categories', 'nf_faq_questions',
        'nf_downloads', 'nf_downloads_categories', 'nf_links', 'nf_links_categories',
        'nf_partners', 'nf_partners_lang', 'nf_guestbook',
        'nf_surveys', 'nf_surveys_options', 'nf_surveys_votes', 'nf_classifieds',
        'nf_recruits', 'nf_recruits_fields', 'nf_calendar_events',
        'nf_bug_tickets', 'nf_bug_comments', 'nf_donations', 'nf_donations_campaigns',
        'nf_ads', 'nf_newsletter_subscribers',
    ] as $t) {
        $db->query("TRUNCATE TABLE `$t`");
    }
    echo "  purge OK\n";
}

/** ~10 membres gamers. Mot de passe : « demo » pour tous (compte vitrine inclus). */
function seed_users(mysqli $db): array
{
    $pass = password_hash('demo', PASSWORD_ARGON2ID);
    $people = [
        ['demo',       'Alex',    'Martin',   'male',   'France',  'Ici pour tester NeoFrag Reborn !'],
        ['ShadowFox',  'Lucas',   'Bernard',  'male',   'France',  'GG WP à tous.'],
        ['NovaStrike', 'Emma',    'Dubois',   'female', 'Belgique','La précision avant tout.'],
        ['VortexQc',   'Hugo',    'Lefebvre', 'male',   'Canada',  'Aim first, think later.'],
        ['LunaByte',   'Chloé',   'Moreau',   'female', 'Suisse',  'Support main, toujours là.'],
        ['Rekkles_FR', 'Nathan',  'Garcia',   'male',   'France',  'On grind la ranked.'],
        ['PixelWitch', 'Léa',     'Roux',     'female', 'France',  'Montage & highlights.'],
        ['Zenith',     'Théo',    'Fournier', 'male',   'France',  'IGL de la team.'],
        ['Kira',       'Manon',   'Girard',   'female', 'France',  'Clutch or kick.'],
        ['OldSchool',  'Pierre',  'Lambert',  'male',   'France',  'Je joue depuis Quake.'],
    ];

    $ids = [];
    $i = 0;
    foreach ($people as [$username, $first, $last, $sex, $country, $quote]) {
        $reg = date('Y-m-d H:i:s', time() - 86400 * (60 - $i * 4));
        ins($db, 'nf_user', [
            'username'           => $username,
            'password'           => $pass,
            'salt'               => '',
            'email'              => strtolower($username) . '@demo.local',
            'registration_date'  => $reg,
            'last_activity_date' => date('Y-m-d H:i:s', time() - 3600 * $i),
            'admin'              => '0',
            'language'           => null,
            'data'               => '',
            'deleted'            => '0',
            'totp_enabled'       => 0,
        ]);
        $uid = (int) $db->insert_id;
        ins($db, 'nf_user_profile', [
            'id' => $uid, 'first_name' => $first, 'last_name' => $last,
            'signature' => '', 'sex' => $sex, 'country' => $country, 'timezone' => 'Europe/Paris',
            'location' => '', 'quote' => $quote, 'website' => '', 'linkedin' => '',
            'github' => '', 'instagram' => '', 'twitch' => '',
        ]);
        ins($db, 'nf_user_points', ['user_id' => $uid, 'total' => 50 + $i * 35, 'earned' => 60 + $i * 35, 'spent' => 10]);
        ins($db, 'nf_karma', ['user_id' => $uid, 'score' => $i * 7, 'reactions_received' => $i * 3, 'content_count' => $i]);
        $ids[] = $uid;
        $i++;
    }
    echo "  users OK (" . count($ids) . ")\n";
    return $ids;
}

function seed_news(mysqli $db, array $users): void
{
    $cats = [
        'Annonces'    => 'Annonces',
        'Compétition' => 'Compétition',
        'Communauté'  => 'Communauté',
    ];
    $cat_ids = [];
    foreach ($cats as $name => $title) {
        ins($db, 'nf_news_categories', ['name' => $name]);
        $cid = (int) $db->insert_id;
        ins($db, 'nf_news_categories_lang', ['category_id' => $cid, 'lang' => 'fr', 'title' => $title]);
        $cat_ids[] = $cid;
    }

    $news = [
        ['Annonces', 'Bienvenue sur NeoFrag Reborn', "Le nouveau site de la communauté est en ligne !", "<p>Nous sommes ravis de vous accueillir sur notre nouvelle plateforme propulsée par <strong>NeoFrag Reborn</strong>. Forum, actualités, galeries, tournois : tout y est. Inscrivez-vous et rejoignez l'aventure !</p>"],
        ['Compétition', 'Victoire en finale du tournoi régional', "Notre équipe principale s'impose 3-1 en grande finale.", "<p>Après un parcours sans faute, nos joueurs ont décroché le trophée du tournoi régional. Félicitations à toute l'équipe pour cette performance !</p><p>Prochain objectif : les qualifications nationales.</p>"],
        ['Communauté', 'Soirée communautaire ce vendredi', "Rejoignez-nous pour une soirée détente sur le serveur.", "<p>Ce vendredi à 21h, on se retrouve tous pour des parties fun et conviviales. Débutants bienvenus !</p>"],
        ['Annonces', 'Nouveau partenariat matériel', "Des réductions exclusives pour nos membres.", "<p>Grâce à notre nouveau partenaire, profitez de réductions sur le matériel gaming. Détails dans l'espace membre.</p>"],
        ['Compétition', 'Calendrier des matchs du mois', "Tous les rendez-vous compétitifs à ne pas manquer.", "<p>Le calendrier des prochains matchs est disponible. Venez supporter vos équipes !</p>"],
        ['Communauté', 'Concours de montage vidéo', "Montrez vos plus beaux highlights et gagnez des points.", "<p>Participez à notre concours de montage : les meilleures vidéos seront récompensées en points boutique.</p>"],
    ];

    $cat_by_name = [];
    $r = $db->query("SELECT c.category_id, l.title FROM nf_news_categories c JOIN nf_news_categories_lang l ON l.category_id = c.category_id");
    while ($row = $r->fetch_assoc()) { $cat_by_name[$row['title']] = (int) $row['category_id']; }

    $i = 0;
    foreach ($news as [$cat, $title, $intro, $content]) {
        $date = date('Y-m-d H:i:s', time() - 86400 * (20 - $i * 3));
        ins($db, 'nf_news', [
            'category_id' => $cat_by_name[$cat], 'user_id' => $users[$i % count($users)],
            'image_id' => null, 'date' => $date, 'published' => '1', 'announced_at' => $date,
            'views' => 30 + $i * 17, 'vote' => '1',
        ]);
        $nid = (int) $db->insert_id;
        ins($db, 'nf_news_lang', [
            'news_id' => $nid, 'lang' => 'fr', 'title' => $title,
            'introduction' => $intro, 'content' => $content, 'tags' => '',
        ]);
        $i++;
    }
    echo "  news OK (" . count($news) . ")\n";
}

function seed_articles(mysqli $db, array $users): void
{
    foreach (['Guides' => 'Guides', 'Tests' => 'Tests'] as $name => $title) {
        ins($db, 'nf_articles_categories', ['name' => $name]);
        $cid = (int) $db->insert_id;
        ins($db, 'nf_articles_categories_lang', ['category_id' => $cid, 'lang' => 'fr', 'title' => $title]);
    }
    $cat_by_name = [];
    $r = $db->query("SELECT c.category_id, l.title FROM nf_articles_categories c JOIN nf_articles_categories_lang l ON l.category_id = c.category_id");
    while ($row = $r->fetch_assoc()) { $cat_by_name[$row['title']] = (int) $row['category_id']; }

    $articles = [
        ['Guides', 'Bien débuter en compétitif', 'Nos conseils pour progresser rapidement.', "<h2>Les bases</h2><p>Maîtrisez d'abord votre visée et votre placement. La régularité prime sur les coups d'éclat.</p><h2>L'esprit d'équipe</h2><p>La communication est la clé de la victoire.</p>"],
        ['Guides', 'Optimiser sa configuration', 'Réglages et matériel pour un setup au top.', "<p>Un bon setup ne fait pas tout, mais il aide. Voici nos recommandations réglages et périphériques.</p>"],
        ['Tests', 'Notre avis sur le dernier patch', 'Ce qui change pour la méta compétitive.', "<p>Le dernier patch rebat les cartes. Analyse des nerfs, buffs et de leur impact sur la méta.</p>"],
        ['Tests', 'Casque gaming : le comparatif', 'On a testé pour vous les modèles du moment.', "<p>Confort, son, micro : notre comparatif complet pour choisir le bon casque.</p>"],
    ];
    $i = 0;
    foreach ($articles as [$cat, $title, $excerpt, $content]) {
        $date = date('Y-m-d H:i:s', time() - 86400 * (15 - $i * 3));
        ins($db, 'nf_articles', [
            'category_id' => $cat_by_name[$cat], 'user_id' => $users[($i + 3) % count($users)],
            'image_id' => null, 'date' => $date, 'published' => '1', 'announced_at' => $date, 'views' => 40 + $i * 22,
        ]);
        $aid = (int) $db->insert_id;
        ins($db, 'nf_articles_lang', [
            'article_id' => $aid, 'lang' => 'fr', 'title' => $title,
            'excerpt' => $excerpt, 'content' => $content, 'tags' => '',
        ]);
        $i++;
    }
    echo "  articles OK (" . count($articles) . ")\n";
}

function seed_forum(mysqli $db, array $users): void
{
    // Catégories -> forums -> topics -> messages (+ compteurs dénormalisés).
    $structure = [
        'Communauté' => [
            'Présentations'      => 'Présentez-vous à la communauté',
            'Discussions générales' => 'Pour parler de tout et de rien',
        ],
        'Jeux & Compétition' => [
            'Stratégies'         => 'Partagez vos tactiques',
            'Recherche d\'équipe' => 'Trouvez des coéquipiers',
        ],
    ];

    $topics = [
        ['Présentations', 'Salut tout le monde !', "Nouveau ici, hâte de jouer avec vous.", ['Bienvenue à toi !', 'Salut, on se voit en jeu !', 'Welcome !']],
        ['Présentations', 'Présentation rapide', "Joueur depuis des années, ravi de rejoindre.", ['Bienvenue parmi nous !']],
        ['Discussions générales', 'Votre setup du moment ?', "Montrez vos installations !", ['Clavier méca + souris légère, le combo.', 'Double écran obligatoire pour moi.']],
        ['Stratégies', 'Gérer la pression en finale', "Comment vous restez calmes dans les moments clés ?", ['Respiration et routine avant match.', 'On parle peu mais on parle utile.', 'Le mental, c\'est 50% du jeu.']],
        ['Recherche d\'équipe', 'Cherche support pour ranked', "Niveau diamant, dispo le soir.", ['Intéressé, je t\'ajoute !']],
    ];

    $cat_ids = [];
    $forum_ids = [];
    $order = 0;
    foreach ($structure as $cat => $forums) {
        ins($db, 'nf_forum_categories', ['title' => $cat, 'order' => $order++, 'vip_only' => 0]);
        $cid = (int) $db->insert_id;
        $cat_ids[$cat] = $cid;
        $forder = 0;
        foreach ($forums as $ftitle => $fdesc) {
            ins($db, 'nf_forum', [
                'parent_id' => $cid, 'is_subforum' => '0', 'title' => $ftitle,
                'description' => $fdesc, 'order' => $forder++, 'count_topics' => 0, 'count_messages' => 0,
            ]);
            $fid = (int) $db->insert_id;
            ins($db, 'nf_forum_url', ['forum_id' => $fid, 'url' => '', 'redirects' => 0]);
            $forum_ids[$ftitle] = $fid;
        }
    }

    $forum_stats = [];
    $t = 0;
    foreach ($topics as [$forum, $title, $first_msg, $replies]) {
        $fid = $forum_ids[$forum];
        $base = time() - 86400 * (12 - $t);
        // topic d'abord (message_id à compléter)
        ins($db, 'nf_forum_topics', [
            'forum_id' => $fid, 'message_id' => null, 'title' => $title, 'status' => '0',
            'views' => 10 + $t * 9, 'count_messages' => 0, 'last_message_id' => null,
            'is_announced' => 0, 'is_locked' => 0,
        ]);
        $tid = (int) $db->insert_id;

        // 1er message
        ins($db, 'nf_forum_messages', [
            'topic_id' => $tid, 'parent_id' => null, 'user_id' => $users[$t % count($users)],
            'message' => $first_msg, 'date' => date('Y-m-d H:i:s', $base),
        ]);
        $first_id = (int) $db->insert_id;
        $last_id = $first_id;
        $count = 1;

        foreach ($replies as $k => $reply) {
            ins($db, 'nf_forum_messages', [
                'topic_id' => $tid, 'parent_id' => null, 'user_id' => $users[($t + $k + 1) % count($users)],
                'message' => $reply, 'date' => date('Y-m-d H:i:s', $base + 3600 * ($k + 1)),
            ]);
            $last_id = (int) $db->insert_id;
            $count++;
        }

        $db->query("UPDATE nf_forum_topics SET message_id = $first_id, last_message_id = $last_id, count_messages = $count WHERE topic_id = $tid");

        $forum_stats[$fid] ??= ['topics' => 0, 'messages' => 0, 'last' => 0];
        $forum_stats[$fid]['topics']++;
        $forum_stats[$fid]['messages'] += $count;
        $forum_stats[$fid]['last'] = $last_id;
        $t++;
    }

    foreach ($forum_stats as $fid => $s) {
        $db->query("UPDATE nf_forum SET count_topics = {$s['topics']}, count_messages = {$s['messages']}, last_message_id = {$s['last']} WHERE forum_id = $fid");
    }
    echo "  forum OK (" . count($forum_ids) . " forums, " . count($topics) . " sujets)\n";
}

function seed_gallery(mysqli $db, array $users): void
{
    foreach (['Événements' => 'Événements', 'Highlights' => 'Highlights'] as $name => $title) {
        ins($db, 'nf_gallery_categories', ['name' => $name]);
        $cid = (int) $db->insert_id;
        ins($db, 'nf_gallery_categories_lang', ['category_id' => $cid, 'lang' => 'fr', 'title' => $title]);
    }
    $cat_by_name = [];
    $r = $db->query("SELECT c.category_id, l.title FROM nf_gallery_categories c JOIN nf_gallery_categories_lang l ON l.category_id = c.category_id");
    while ($row = $r->fetch_assoc()) { $cat_by_name[$row['title']] = (int) $row['category_id']; }

    $galleries = [
        ['Événements', 'LAN d\'été 2025', 'Les meilleurs moments de notre LAN annuelle.'],
        ['Événements', 'Finale régionale', 'Retour en images sur notre victoire.'],
        ['Highlights', 'Best of du mois', 'Compilation des plus belles actions.'],
    ];
    $i = 0;
    foreach ($galleries as [$cat, $name, $desc]) {
        ins($db, 'nf_gallery', [
            'category_id' => $cat_by_name[$cat], 'image_id' => null, 'name' => $name,
            'published' => '1', 'date' => date('Y-m-d H:i:s', time() - 86400 * (10 - $i * 2)),
        ]);
        $gid = (int) $db->insert_id;
        ins($db, 'nf_gallery_lang', ['gallery_id' => $gid, 'lang' => 'fr', 'title' => $name, 'description' => $desc]);
        $i++;
    }
    echo "  galerie OK (" . count($galleries) . " galeries, sans photos)\n";
}

function seed_gaming(mysqli $db, array $users): void
{
    // Jeux
    $games = ['Counter-Strike 2', 'Valorant', 'League of Legends', 'Rocket League'];
    $game_ids = [];
    foreach ($games as $name) {
        ins($db, 'nf_games', ['parent_id' => null, 'image_id' => null, 'icon_id' => null, 'name' => $name]);
        $gid = (int) $db->insert_id;
        ins($db, 'nf_games_lang', ['game_id' => $gid, 'lang' => 'fr', 'title' => $name]);
        $game_ids[$name] = $gid;
    }

    // Équipes
    $teams = [
        ['Counter-Strike 2', 'Équipe principale CS2', 'Notre roster compétitif sur CS2.'],
        ['Valorant', 'Roster Valorant', 'Cinq joueurs, un objectif : le top.'],
        ['League of Legends', 'Équipe LoL', 'La faille n\'a qu\'à bien se tenir.'],
    ];
    $team_ids = [];
    $ord = 0;
    foreach ($teams as [$game, $name, $desc]) {
        ins($db, 'nf_teams', ['game_id' => $game_ids[$game], 'image_id' => null, 'icon_id' => null, 'name' => $name, 'order' => $ord++]);
        $tid = (int) $db->insert_id;
        ins($db, 'nf_teams_lang', ['team_id' => $tid, 'lang' => 'fr', 'title' => $name, 'description' => $desc]);
        $team_ids[$name] = $tid;
        // membres
        for ($k = 0; $k < 5; $k++) {
            ins($db, 'nf_teams_users', ['team_id' => $tid, 'user_id' => $users[($ord + $k) % count($users)], 'role_id' => null]);
        }
    }

    // Types d'événement
    ins($db, 'nf_events_types', ['type' => 1, 'title' => 'Tournoi', 'color' => '#e74c3c', 'icon' => 'fas fa-trophy']);
    $type_tournoi = (int) $db->insert_id;
    ins($db, 'nf_events_types', ['type' => 1, 'title' => 'Entraînement', 'color' => '#3498db', 'icon' => 'fas fa-dumbbell']);
    $type_entrain = (int) $db->insert_id;

    $events = [
        [$type_tournoi, 'Tournoi régional CS2', 'En ligne', 'Qualifications ouvertes à tous les niveaux.', 5],
        [$type_tournoi, 'Coupe Valorant communautaire', 'Discord', 'Format double élimination, BO3.', 12],
        [$type_entrain, 'Entraînement hebdo LoL', 'Faille de l\'invocateur', 'Scrims et review de games.', -2],
    ];
    foreach ($events as [$type, $title, $loc, $desc, $offset_days]) {
        $d = date('Y-m-d H:i:s', time() + 86400 * $offset_days);
        ins($db, 'nf_events', [
            'type_id' => $type, 'user_id' => $users[0], 'image_id' => null, 'title' => $title,
            'description' => $desc, 'private_description' => '', 'location' => $loc,
            'date' => $d, 'date_end' => null, 'published' => '1', 'publish_date' => date('Y-m-d H:i:s', time() - 86400 * 5),
        ]);
    }

    // Palmarès
    $awards = [
        ['Équipe principale CS2', 'Counter-Strike 2', 'Tournoi régional 2025', 'Lyon', 'PC', 1, 16],
        ['Roster Valorant', 'Valorant', 'Coupe d\'hiver', 'En ligne', 'PC', 2, 24],
        ['Équipe LoL', 'League of Legends', 'Ligue communautaire', 'En ligne', 'PC', 3, 12],
    ];
    foreach ($awards as [$team, $game, $name, $loc, $platform, $rank, $part]) {
        ins($db, 'nf_awards', [
            'team_id' => $team_ids[$team] ?? null, 'game_id' => $game_ids[$game], 'image_id' => null,
            'name' => $name, 'location' => $loc, 'date' => date('Y-m-d', time() - 86400 * 40),
            'description' => '', 'platform' => $platform, 'ranking' => $rank, 'participants' => $part,
        ]);
    }
    echo "  gaming OK (" . count($games) . " jeux, " . count($teams) . " équipes, " . count($events) . " events, " . count($awards) . " trophées)\n";
}

function seed_social(mysqli $db, array $users): void
{
    // Commentaires sur les news (module 'news', module_id = news_id)
    $news_ids = [];
    $r = $db->query("SELECT news_id FROM nf_news ORDER BY news_id LIMIT 4");
    while ($row = $r->fetch_assoc()) { $news_ids[] = (int) $row['news_id']; }

    $comments = ['Super nouvelle, merci !', 'Hâte d\'y être !', 'GG à l\'équipe !', 'On compte sur vous !', 'Excellent, vivement la suite.'];
    $cn = 0;
    foreach ($news_ids as $nid) {
        $n = 1 + ($nid % 3);
        for ($k = 0; $k < $n; $k++) {
            ins($db, 'nf_comment', [
                'parent_id' => null, 'user_id' => $users[($cn + $k) % count($users)],
                'module_id' => $nid, 'module' => 'news', 'content' => $comments[$cn % count($comments)],
                'date' => date('Y-m-d H:i:s', time() - 3600 * ($cn + 1)),
            ]);
            $cn++;
        }
    }

    // Réactions sur les news
    $rn = 0;
    foreach ($news_ids as $nid) {
        $n = 2 + ($nid % 4);
        for ($k = 0; $k < $n && $k < count($users); $k++) {
            ins($db, 'nf_reactions', [
                'user_id' => $users[($rn + $k) % count($users)], 'content_type' => 'news', 'content_id' => $nid,
                'created_at' => date('Y-m-d H:i:s', time() - 1800 * ($rn + 1)),
            ]);
            $rn++;
        }
    }
    echo "  social OK ($cn commentaires, $rn réactions)\n";
}

function seed_wiki(mysqli $db, array $users): void
{
    $pages = [
        ['Bienvenue', "<h2>Bienvenue sur le wiki</h2><p>Ce wiki rassemble guides, règlement et ressources de la communauté. Naviguez via le sommaire.</p>"],
        ['Règlement', "<h2>Règlement</h2><p>Respect, fair-play et bonne humeur. Tout comportement toxique est sanctionné.</p>"],
        ['Rejoindre une équipe', "<h2>Rejoindre une équipe</h2><p>Consultez la page Recrutement et postulez. Un essai sera organisé.</p>"],
    ];
    $i = 0;
    foreach ($pages as [$title, $content]) {
        ins($db, 'nf_wiki_pages', [
            'slug' => slugify($title), 'title' => $title, 'content' => $content,
            'parent_id' => null, 'sort_order' => $i, 'published' => 1, 'user_id' => $users[0], 'views' => 12 + $i * 5,
        ]);
        $i++;
    }
    echo "  wiki OK (" . count($pages) . " pages)\n";
}

function seed_faq(mysqli $db): void
{
    ins($db, 'nf_faq_categories', ['title' => 'Général', 'sort_order' => 0]);
    $cid = (int) $db->insert_id;
    $qa = [
        ['Comment rejoindre la communauté ?', "Inscrivez-vous gratuitement, puis présentez-vous sur le forum."],
        ['Le site est-il gratuit ?', "Oui, NeoFrag Reborn est 100% gratuit et open source."],
        ['Comment gagner des points ?', "En participant : poster, commenter, réagir et contribuer."],
    ];
    $i = 0;
    foreach ($qa as [$q, $a]) {
        ins($db, 'nf_faq_questions', ['category_id' => $cid, 'question' => $q, 'answer' => $a, 'sort_order' => $i, 'published' => 1]);
        $i++;
    }
    echo "  faq OK (" . count($qa) . ")\n";
}

function seed_downloads(mysqli $db): void
{
    ins($db, 'nf_downloads_categories', ['title' => 'Ressources', 'description' => 'Configs, fonds d\'écran et outils.', 'sort_order' => 0]);
    $cid = (int) $db->insert_id;
    $dls = [
        ['Pack de fonds d\'écran', 'Une sélection de wallpapers aux couleurs de la team.', 'https://example.com/wallpapers.zip', 15728640, 'zip', '1.0'],
        ['Config CS2 recommandée', 'Notre fichier de configuration partagé.', 'https://example.com/cs2-config.cfg', 8192, 'cfg', '2.3'],
    ];
    $i = 0;
    foreach ($dls as [$title, $desc, $url, $size, $type, $ver]) {
        ins($db, 'nf_downloads', [
            'category_id' => $cid, 'title' => $title, 'description' => $desc, 'file_url' => $url,
            'file_size_bytes' => $size, 'file_type' => $type, 'version' => $ver, 'downloads_count' => 20 + $i * 31, 'published' => 1,
        ]);
        $i++;
    }
    echo "  downloads OK (" . count($dls) . ")\n";
}

function seed_links(mysqli $db): void
{
    ins($db, 'nf_links_categories', ['title' => 'Liens utiles', 'sort_order' => 0]);
    $cid = (int) $db->insert_id;
    $links = [
        ['Notre Discord', 'https://discord.gg/example', 'Rejoignez le serveur vocal de la communauté.'],
        ['Chaîne Twitch', 'https://twitch.tv/example', 'Suivez nos lives et tournois.'],
        ['NeoFrag Reborn', 'https://neofr.ag', 'Le CMS qui propulse ce site.'],
    ];
    $i = 0;
    foreach ($links as [$title, $url, $desc]) {
        ins($db, 'nf_links', ['category_id' => $cid, 'title' => $title, 'url' => $url, 'description' => $desc, 'clicks' => $i * 13, 'published' => 1, 'sort_order' => $i]);
        $i++;
    }
    echo "  links OK (" . count($links) . ")\n";
}

function seed_partners(mysqli $db): void
{
    $partners = [
        ['GamerGear', 'https://example.com', 'Matériel gaming partenaire officiel.'],
        ['EnergyDrink', 'https://example.com', 'Le carburant de nos joueurs.'],
        ['HostPro', 'https://example.com', 'Serveurs de jeu haute performance.'],
    ];
    $i = 0;
    foreach ($partners as [$name, $site, $desc]) {
        ins($db, 'nf_partners', [
            'name' => $name, 'logo_light' => null, 'logo_dark' => null, 'website' => $site,
            'facebook' => '', 'twitter' => '', 'code' => '', 'count' => $i * 9, 'order' => $i,
        ]);
        $pid = (int) $db->insert_id;
        ins($db, 'nf_partners_lang', ['partner_id' => $pid, 'lang' => 'fr', 'title' => $name, 'description' => $desc]);
        $i++;
    }
    echo "  partners OK (" . count($partners) . ")\n";
}

function seed_guestbook(mysqli $db, array $users): void
{
    $msgs = [
        ['Alex', 'Super communauté, accueil au top !'],
        ['Sam', 'Le site est vraiment propre, bravo.'],
        ['Jordan', 'Hâte de participer au prochain tournoi.'],
    ];
    $i = 0;
    foreach ($msgs as [$name, $message]) {
        ins($db, 'nf_guestbook', [
            'user_id' => $users[$i % count($users)], 'name' => $name, 'message' => $message,
            'ip_address' => '', 'status' => 'approved', 'created_at' => date('Y-m-d H:i:s', time() - 86400 * ($i + 1)),
        ]);
        $i++;
    }
    echo "  guestbook OK (" . count($msgs) . ")\n";
}

function seed_surveys(mysqli $db, array $users): void
{
    ins($db, 'nf_surveys', [
        'title' => 'Quel jeu pour le prochain tournoi ?', 'description' => 'Votez pour le jeu de notre prochain événement.',
        'user_id' => $users[0], 'multiple_choice' => 0, 'show_results' => 'always', 'closed_at' => null, 'published' => 1,
    ]);
    $sid = (int) $db->insert_id;
    $options = ['Counter-Strike 2', 'Valorant', 'League of Legends', 'Rocket League'];
    $opt_ids = [];
    foreach ($options as $k => $label) {
        ins($db, 'nf_surveys_options', ['survey_id' => $sid, 'label' => $label, 'sort_order' => $k]);
        $opt_ids[] = (int) $db->insert_id;
    }
    foreach ($users as $k => $uid) {
        ins($db, 'nf_surveys_votes', [
            'survey_id' => $sid, 'option_id' => $opt_ids[$k % count($opt_ids)], 'user_id' => $uid,
            'ip_hash' => '', 'created_at' => date('Y-m-d H:i:s', time() - 3600 * $k),
        ]);
    }
    echo "  sondage OK (1 sondage, " . count($options) . " options, " . count($users) . " votes)\n";
}

function seed_classifieds(mysqli $db, array $users): void
{
    $r = $db->query("SELECT id FROM nf_classifieds_categories ORDER BY id LIMIT 1");
    $cat = (int) ($r->fetch_row()[0] ?? 0);
    if (!$cat) {
        ins($db, 'nf_classifieds_categories', ['title' => 'Matériel', 'sort_order' => 0]);
        $cat = (int) $db->insert_id;
    }
    $ads = [
        ['offer', 'Vends clavier mécanique', 'Switch rouges, très bon état, peu servi.', '60.00', 'discord: alex#0001'],
        ['request', 'Cherche casque gaming', 'Budget 50€, micro indispensable.', '0.00', 'mp sur le forum'],
    ];
    $i = 0;
    foreach ($ads as [$type, $title, $desc, $price, $contact]) {
        $d = date('Y-m-d H:i:s', time() - 86400 * ($i + 2));
        ins($db, 'nf_classifieds', [
            'category_id' => $cat, 'user_id' => $users[$i % count($users)], 'ad_type' => $type,
            'title' => $title, 'description' => $desc, 'price' => $price, 'contact' => $contact, 'image' => '',
            'status' => 'published', 'views' => 5 + $i * 8, 'created_at' => $d, 'updated_at' => $d,
        ]);
        $i++;
    }
    echo "  annonces OK (" . count($ads) . ")\n";
}

function seed_recruits(mysqli $db, array $users): void
{
    $posts = [
        ['Recrutement CS2 - Joueur AWP', "Notre équipe CS2 cherche un sniper.", "Tu maîtrises l'AWP et tu cherches une équipe sérieuse ? Postule !", "Niveau Faceit 7+, dispo 3 soirs/semaine, micro obligatoire.", 'AWPer', 'fas fa-crosshairs'],
        ['Recrutement Valorant - Support', "On cherche un joueur support/initiateur.", "Rejoins notre roster Valorant en construction.", "Immortal+, bonne communication, esprit d'équipe.", 'Initiateur', 'fas fa-shield'],
    ];
    $i = 0;
    foreach ($posts as [$title, $intro, $desc, $req, $role, $icon]) {
        ins($db, 'nf_recruits', [
            'title' => $title, 'introduction' => $intro, 'description' => $desc, 'requierments' => $req,
            'date' => date('Y-m-d H:i:s', time() - 86400 * ($i + 3)), 'user_id' => $users[0],
            'size' => 1, 'role' => $role, 'icon' => $icon, 'date_end' => date('Y-m-d', time() + 86400 * 20),
            'closed' => '0', 'team_id' => null, 'image_id' => null,
        ]);
        $i++;
    }
    echo "  recrutement OK (" . count($posts) . ")\n";
}

function seed_calendar(mysqli $db, array $users): void
{
    $events = [
        ['Entraînement CS2', 'Scrims du soir', 'Serveur communautaire', 2, false],
        ['Soirée détente', 'Parties fun ouvertes à tous', 'Discord', 5, false],
        ['Maintenance serveur', 'Indisponibilité prévue', '', 9, true],
    ];
    foreach ($events as $k => [$title, $desc, $loc, $offset, $allday]) {
        $start = date('Y-m-d H:i:s', time() + 86400 * $offset);
        ins($db, 'nf_calendar_events', [
            'title' => $title, 'description' => $desc, 'location' => $loc,
            'start_at' => $start, 'end_at' => date('Y-m-d H:i:s', time() + 86400 * $offset + 7200),
            'all_day' => $allday ? 1 : 0, 'user_id' => $users[0], 'color' => '#1abc9c', 'published' => 1,
        ]);
    }
    echo "  calendrier OK (" . count($events) . ")\n";
}

function seed_bugtracker(mysqli $db, array $users): void
{
    $tickets = [
        ['Bouton de connexion mal aligné sur mobile', "Sur petit écran, le bouton dépasse légèrement.", 'bug', 'normal', 'resolved'],
        ['Ajouter un mode sombre au profil', "Ce serait agréable d'avoir le thème sombre partout.", 'feature', 'low', 'open'],
        ['Comment changer mon avatar ?', "Je ne trouve pas l'option dans les réglages.", 'question', 'normal', 'closed'],
    ];
    $i = 0;
    foreach ($tickets as [$title, $desc, $type, $prio, $status]) {
        ins($db, 'nf_bug_tickets', [
            'title' => $title, 'description' => $desc, 'type' => $type, 'priority' => $prio, 'status' => $status,
            'user_id' => $users[($i + 1) % count($users)], 'assigned_to' => null,
        ]);
        $tid = (int) $db->insert_id;
        ins($db, 'nf_bug_comments', [
            'ticket_id' => $tid, 'user_id' => $users[0], 'content' => 'Merci pour le retour, on regarde ça.',
            'is_status_change' => 0, 'created_at' => date('Y-m-d H:i:s', time() - 3600 * ($i + 1)),
        ]);
        $i++;
    }
    echo "  bugtracker OK (" . count($tickets) . ")\n";
}

function seed_donations(mysqli $db, array $users): void
{
    ins($db, 'nf_donations_campaigns', [
        'name' => 'serveur-2025', 'title' => 'Financement du serveur 2025',
        'description' => 'Aidez-nous à financer l\'hébergement de nos serveurs de jeu.',
        'goal_amount' => '500.00', 'currency' => 'EUR', 'paypal_email' => '', 'paypal_button_id' => '',
        'deadline' => date('Y-m-d', time() + 86400 * 60), 'status' => 'active',
    ]);
    $camp = (int) $db->insert_id;
    $dons = [['Alex', '20.00', 'Bon courage à toute la team !'], ['Anonyme', '10.00', ''], ['Sam', '50.00', 'Continuez comme ça.']];
    $i = 0;
    foreach ($dons as [$name, $amount, $msg]) {
        ins($db, 'nf_donations', [
            'campaign_id' => $camp, 'user_id' => $users[$i % count($users)], 'donor_name' => $name,
            'amount' => $amount, 'currency' => 'EUR', 'message' => $msg, 'is_anonymous' => ($name === 'Anonyme' ? 1 : 0),
            'is_public' => 1, 'source' => 'manual', 'paypal_txn_id' => null, 'status' => 'completed',
            'created_at' => date('Y-m-d H:i:s', time() - 86400 * ($i + 1)),
        ]);
        $i++;
    }
    echo "  dons OK (1 campagne, " . count($dons) . " dons)\n";
}

function seed_ads(mysqli $db): void
{
    ins($db, 'nf_ads', [
        'title' => 'Bannière partenaire', 'placement' => 'sidebar', 'format' => 'html',
        'image_url' => '', 'url' => 'https://example.com', 'html' => '<div style="padding:1rem;text-align:center">Espace partenaire</div>',
        'active' => 1, 'starts_at' => null, 'ends_at' => null, 'position' => 0,
        'impressions' => 120, 'clicks' => 8,
    ]);
    echo "  pub OK (1)\n";
}

function seed_newsletter(mysqli $db, array $users): void
{
    $emails = ['fan1@demo.local', 'fan2@demo.local', 'fan3@demo.local', 'fan4@demo.local'];
    foreach ($emails as $k => $email) {
        ins($db, 'nf_newsletter_subscribers', [
            'email' => $email, 'token' => bin2hex(random_bytes(16)), 'confirmed' => 1,
            'user_id' => $users[$k % count($users)], 'created_at' => date('Y-m-d H:i:s', time() - 86400 * ($k + 1)),
            'confirmed_at' => date('Y-m-d H:i:s', time() - 86400 * ($k + 1)),
        ]);
    }
    echo "  newsletter OK (" . count($emails) . " abonnés)\n";
}

/** Config spécifique au site de démo : nebula par défaut, vitrine + landing retirés. */
function configure_demo(mysqli $db): void
{
    $db->query("UPDATE nf_settings SET value = 'nebula' WHERE name = 'nf_default_theme'");
    $theme_t  = type_id($db, 'theme');
    $widget_t = type_id($db, 'widget');
    if ($theme_t) {
        $db->query("DELETE FROM nf_addon WHERE name = 'vitrine' AND type_id = $theme_t");
        $db->query("DELETE FROM nf_dispositions WHERE theme = 'vitrine'");
    }
    if ($widget_t) {
        $db->query("DELETE FROM nf_addon WHERE name = 'landing' AND type_id = $widget_t");
        $db->query("DELETE FROM nf_widgets WHERE widget = 'landing'");
    }
}

// ── Helpers ─────────────────────────────────────────────────────────────────

function type_id(mysqli $db, string $name): int
{
    $r = $db->query("SELECT id FROM nf_addon_type WHERE name = '" . $db->real_escape_string($name) . "'");
    return (int) ($r->fetch_row()[0] ?? 0);
}

function slugify(string $s): string
{
    $s = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s) ?: $s;
    $s = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $s));
    return trim($s, '-');
}

function ins(mysqli $db, string $table, array $cols): void
{
    $names = [];
    $vals  = [];
    foreach ($cols as $name => $value) {
        $names[] = "`$name`";
        if ($value === null) {
            $vals[] = 'NULL';
        } elseif (is_int($value)) {
            $vals[] = (string) $value;
        } else {
            $vals[] = "'" . $db->real_escape_string((string) $value) . "'";
        }
    }
    $sql = "INSERT INTO `$table` (" . implode(', ', $names) . ") VALUES (" . implode(', ', $vals) . ")";
    if (!$db->query($sql)) {
        fwrite(STDERR, "ERREUR sur $table : " . $db->error . "\n  $sql\n");
        exit(1);
    }
}

function connect(): mysqli
{
    $cfg = ['hostname' => '127.0.0.1', 'port' => 3306, 'username' => 'root', 'password' => '', 'database' => 'neofrag'];
    if (is_file(CONFIG_DB)) {
        $db = [];
        require CONFIG_DB;
        if (!empty($db[0]) && is_array($db[0])) {
            $cfg = array_merge($cfg, $db[0]);
        }
    }
    foreach (['hostname' => 'NF_DB_HOST', 'port' => 'NF_DB_PORT', 'username' => 'NF_DB_USER', 'password' => 'NF_DB_PASS', 'database' => 'NF_DB_NAME'] as $key => $var) {
        $val = getenv($var);
        if ($val !== false && $val !== '') {
            $cfg[$key] = $val;
        }
    }
    mysqli_report(MYSQLI_REPORT_OFF);
    $conn = @new mysqli($cfg['hostname'], $cfg['username'], (string) $cfg['password'], $cfg['database'], (int) $cfg['port']);
    if ($conn->connect_errno) {
        fwrite(STDERR, "Connexion BDD impossible : {$conn->connect_error}\n");
        exit(1);
    }
    $conn->set_charset('utf8mb4');
    return $conn;
}
