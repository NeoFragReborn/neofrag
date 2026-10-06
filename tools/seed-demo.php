<?php
declare(strict_types=1);

/**
 * seed-demo — peuple un site de données de DÉMO réalistes (gaming/communauté).
 *
 * Famille : outil
 * Diffusion : publique
 *
 * Ce qu'il fait
 * -------------
 * Idempotent : purge le contenu démo (hors administrateur) avant insertion. Génère du contenu pour
 * les actualités, articles, forum, galerie, jeux, équipes, événements, palmarès, commentaires,
 * réactions, points, et tous les modules « à la carte ». Après ce seed, `tools/dump-demo.php` fige
 * le résultat en install/demo.sql, la charge de l'auto-reset du site de démonstration.
 *
 * Usage
 * -----
 *   php tools/seed-demo.php
 *   php tools/seed-demo.php --sans-config-demo   garnir SANS la configuration du site public de
 *                                                démonstration (thème nebula imposé, vitrine retirée) :
 *                                                pour un site d'essai, qui doit garder tous ses thèmes
 *   php tools/seed-demo.php --anglais            poser SEULEMENT la version anglaise du contenu
 *                                                existant (actualités, articles, pages, catégories,
 *                                                galeries), sans rien purger ni régénérer
 */

require __DIR__.'/lib/outil.php';
require __DIR__.'/lib/site.php';

[$o] = nf_options(['sans-config-demo' => FALSE, 'anglais' => FALSE]);

function sans_config_demo(): bool
{
    global $o;

    return $o['sans-config-demo'];
}

const RACINE = __DIR__.'/..';

if ($o['anglais'])
{
    $db = nf_connexion();
    printf("✓ Version anglaise posée : %d ligne(s).\n", seed_anglais($db));
    exit(0);
}

main();

function main(): void
{
    $db = nf_connexion();
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
    $gaming = seed_gaming($db, $users);
    seed_events_extras($db, $users, $gaming);
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
    // Ajoutés le 2026-09-16 : ces écrans s'ouvraient sur une page vide, faute de données.
    seed_pages($db, $users);
    seed_menus($db);
    seed_slider($db);
    seed_emojis($db);
    seed_talks($db, $users);
    seed_moderation($db, $users);
    seed_gamification($db, $users);
    seed_activite($db, $users);
    seed_anglais($db);

    configure_demo($db);

    $db->query('SET FOREIGN_KEY_CHECKS = 1');

    echo "\n✓ Démo peuplée : " . count($users) . " membres + contenus larges (news/articles/forum/galerie/gaming/wiki/faq/downloads/links/partners/guestbook/sondages/annonces/recrutement/calendrier/bugtracker/dons/pub/newsletter/pages/menus/carrousel/émojis/discussions/modération/gamification/activité).\n";
    echo sans_config_demo()
        ? "  Config démo NON appliquée (--sans-config-demo) : thèmes et widgets laissés en place.\n"
        : "  Config démo : thème par défaut = nebula, vitrine + widget landing retirés.\n";
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
        'nf_recruits', 'nf_recruits_fields', 'nf_recruits_candidacies', 'nf_calendar_events',
        'nf_bug_tickets', 'nf_bug_comments', 'nf_donations', 'nf_donations_campaigns',
        'nf_ads', 'nf_newsletter_subscribers',
        // Ajoutés le 2026-09-16 : 58 tables étaient vides sur la démo, dont la moitié correspond à
        // un écran que le visiteur peut ouvrir. Une page vide donne l'impression que la
        // fonctionnalité n'existe pas — le contraire de ce que la démo doit montrer.
        'nf_media', 'nf_file',
        'nf_games_maps', 'nf_games_modes', 'nf_teams_roles',
        'nf_events_participants', 'nf_events_matches', 'nf_events_matches_opponents',
        'nf_events_matches_rounds',
        'nf_pages', 'nf_pages_lang', 'nf_menus', 'nf_menus_items',
        'nf_slider_slides', 'nf_custom_emojis',
        'nf_talks', 'nf_talks_messages', 'nf_talks_participants',
        'nf_newsletter_campaigns', 'nf_newsletter_templates',
        'nf_notifications', 'nf_points_log', 'nf_shop_items', 'nf_vip',
        'nf_reports', 'nf_sanctions', 'nf_subscriptions',
        'nf_revisions', 'nf_wiki_revisions',
    ] as $t) {
        $db->query("TRUNCATE TABLE `$t`");
    }

    // Les fichiers engendrés par la passe précédente : sans cela, chaque relance empile des images
    // sur le disque que plus aucune ligne ne référence.
    foreach (['upload/gallery', 'upload/gallery/thumbnails', 'upload/gallery/originals', 'upload/demo'] as $dossier) {
        foreach (glob(RACINE . '/' . $dossier . '/demo-*.{jpg,png}', GLOB_BRACE) ?: [] as $fichier) {
            @unlink($fichier);
        }
    }

    echo "  purge OK\n";
}

/**
 * Fabrique une image d'illustration et l'enregistre comme fichier du CMS.
 *
 * Pourquoi c'est fabriqué plutôt que livré : une démo publique ne peut pas embarquer de vraies
 * captures de jeux (droits), et un dépôt n'a pas à porter des mégaoctets d'images de démonstration.
 * GD produit un dégradé lisible portant le titre — assez pour que la galerie, le carrousel et les
 * émojis montrent quelque chose, et jamais assez pour se faire passer pour une vraie photo.
 *
 * Rend l'identifiant `nf_file` créé.
 */
function fabrique_image(mysqli $db, string $dossier, string $nom, string $texte, int $l, int $h, array $couleur, ?int $user_id = null): int
{
    $chemin_rel = $dossier . '/' . $nom;
    $chemin_abs = RACINE . '/' . $chemin_rel;

    if (!is_dir(dirname($chemin_abs))) {
        mkdir(dirname($chemin_abs), 0775, true);
    }

    $img = imagecreatetruecolor($l, $h);

    // Dégradé vertical de la couleur donnée vers son quart le plus sombre.
    [$r, $v, $b] = $couleur;
    for ($y = 0; $y < $h; $y++) {
        $f = 1 - ($y / $h) * 0.72;
        $c = imagecolorallocate($img, (int) ($r * $f), (int) ($v * $f), (int) ($b * $f));
        imagefilledrectangle($img, 0, $y, $l, $y, $c);
    }

    // Trame diagonale discrète, pour que l'image ne soit pas un aplat.
    $trame = imagecolorallocatealpha($img, 255, 255, 255, 118);
    for ($x = -$h; $x < $l; $x += 18) {
        imagefilledpolygon($img, [$x, $h, $x + 6, $h, $x + 6 + $h, 0, $x + $h, 0], $trame);
    }

    if ($texte !== '' && $l >= 160) {
        $blanc = imagecolorallocate($img, 255, 255, 255);
        $ombre = imagecolorallocatealpha($img, 0, 0, 0, 70);
        $police = police_ttf();

        if ($police !== null) {
            // Une police vectorielle donne un titre lisible à n'importe quelle taille. Les polices
            // internes de GD plafonnent à ~15 px de haut : sur une image de 1280 px de large, le
            // titre était illisible.
            $corps = max(16, (int) ($l / 26));
            $boite = imagettfbbox($corps, 0, $police, $texte);
            $tw    = $boite[2] - $boite[0];
            $x     = max(12, (int) (($l - $tw) / 2));
            $y     = (int) ($h / 2 + $corps / 2);
            imagettftext($img, $corps, 0, $x + 2, $y + 2, $ombre, $police, $texte);
            imagettftext($img, $corps, 0, $x, $y, $blanc, $police, $texte);
        } else {
            // Repli sans police vectorielle : on dessine petit puis on agrandit, plutôt que de
            // laisser un titre minuscule au centre d'une grande image.
            $taille = 5;
            $tw     = imagefontwidth($taille) * strlen($texte);
            $th     = imagefontheight($taille);
            $vignette = imagecreatetruecolor($tw, $th);
            imagefill($vignette, 0, 0, imagecolorallocate($vignette, 0, 0, 0));
            imagecolortransparent($vignette, imagecolorallocate($vignette, 0, 0, 0));
            imagestring($vignette, $taille, 0, 0, $texte, imagecolorallocate($vignette, 255, 255, 255));
            $facteur = max(1, (int) floor(($l * 0.8) / max(1, $tw)));
            imagecopyresampled(
                $img, $vignette,
                (int) (($l - $tw * $facteur) / 2), (int) (($h - $th * $facteur) / 2),
                0, 0, $tw * $facteur, $th * $facteur, $tw, $th
            );
        }
    }

    imagejpeg($img, $chemin_abs, 82);

    ins($db, 'nf_file', [
        'user_id' => $user_id,
        'name'    => $nom,
        'path'    => $chemin_rel,
        'date'    => date('Y-m-d H:i:s'),
    ]);

    return (int) $db->insert_id;
}

/** ~10 membres gamers. Mot de passe : « demo » pour tous (compte vitrine inclus). */
function seed_users(mysqli $db): array
{
    $pass = password_hash('demo', PASSWORD_ARGON2ID);

    // Le compte `demo` n'est PAS recréé ici.
    //
    // Il existe déjà, et il est ADMINISTRATEUR — c'est le compte que le bandeau annonce au
    // visiteur pour qu'il puisse essayer le panneau d'administration. `purge()` le préserve
    // (elle ne supprime que les comptes `admin = '0'`). Le recréer produisait un DEUXIÈME compte
    // nommé `demo`, non-administrateur, avec la même adresse : deux lignes homonymes en base et
    // une connexion ambiguë. Constaté le 2026-09-16 : ids 271 (admin) et 301 (doublon).
    //
    // On le retrouve et on le place en tête, pour qu'il soit l'auteur du contenu — le visiteur
    // qui se connecte retrouve ainsi ses propres publications.
    //
    // Le pays est un CODE (`fr`), comme ce qu'enregistre le formulaire de profil — pas un nom.
    // Semé « France », il donnait un profil sans pays affiché et le drapeau introuvable
    // `images/flags/France.png` (vu par check-mise-en-page, 2026-09-23). Même règle que les
    // adversaires plus bas.
    $people = [
        ['ShadowFox',  'Lucas',   'Bernard',  'male',   'fr', 'GG WP à tous.'],
        ['NovaStrike', 'Emma',    'Dubois',   'female', 'be', 'La précision avant tout.'],
        ['VortexQc',   'Hugo',    'Lefebvre', 'male',   'ca', 'Aim first, think later.'],
        ['LunaByte',   'Chloé',   'Moreau',   'female', 'ch', 'Support main, toujours là.'],
        ['Rekkles_FR', 'Nathan',  'Garcia',   'male',   'fr', 'On grind la ranked.'],
        ['PixelWitch', 'Léa',     'Roux',     'female', 'fr', 'Montage & highlights.'],
        ['Zenith',     'Théo',    'Fournier', 'male',   'fr', 'IGL de la team.'],
        ['Kira',       'Manon',   'Girard',   'female', 'fr', 'Clutch or kick.'],
        ['OldSchool',  'Pierre',  'Lambert',  'male',   'fr', 'Je joue depuis Quake.'],
    ];

    $ids = [];

    $r = $db->query("SELECT id FROM nf_user WHERE username = 'demo' AND admin = '1' ORDER BY id LIMIT 1");
    if ($r && ($ligne = $r->fetch_row())) {
        $ids[] = (int) $ligne[0];
    } else {
        // Sur une installation neuve, le compte `demo` n'existe pas encore : on retombe sur le
        // premier administrateur, pour que le contenu ait toujours un auteur valide.
        $r = $db->query("SELECT MIN(id) FROM nf_user WHERE admin = '1'");
        $ids[] = (int) (($r ? $r->fetch_row()[0] : 0) ?: 1);
    }

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
    echo "  users OK (" . count($ids) . ", dont le compte administrateur `demo` déjà en place)\n";
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

        // `count_messages` compte les RÉPONSES, comme le module : un sujet neuf part de 0 et chaque
        // réponse ajoute 1, au sujet comme au forum. Le premier message n'en est pas une — le compter
        // affichait « 4 réponses » dans la liste pour un sujet qui en a 3.
        $reponses = $count - 1;
        $db->query("UPDATE nf_forum_topics SET message_id = $first_id, last_message_id = $last_id, count_messages = $reponses WHERE topic_id = $tid");

        $forum_stats[$fid] ??= ['topics' => 0, 'messages' => 0, 'last' => 0];
        $forum_stats[$fid]['topics']++;
        $forum_stats[$fid]['messages'] += $reponses;
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
    // Les photos de chaque album. Un album vide affichait « Aucune image » : le visiteur en
    // concluait que la galerie ne marchait pas, alors qu'il n'y avait simplement rien dedans.
    $photos = [
        'LAN d\'été 2025'   => [
            ['Installation des postes', [46, 134, 193]],
            ['La finale sur grand écran', [155, 89, 182]],
            ['Remise des trophées', [230, 126, 34]],
            ['Photo de groupe', [26, 188, 156]],
        ],
        'Finale régionale'  => [
            ['Le dernier round', [231, 76, 60]],
            ['Le clutch de Zenith', [52, 152, 219]],
            ['Célébration', [241, 196, 15]],
        ],
        'Best of du mois'   => [
            ['Ace sur Mirage', [142, 68, 173]],
            ['Triple kill en overtime', [39, 174, 96]],
            ['Le but impossible', [211, 84, 0]],
        ],
    ];

    $i = 0;
    $total_photos = 0;
    foreach ($galleries as [$cat, $name, $desc]) {
        ins($db, 'nf_gallery', [
            'category_id' => $cat_by_name[$cat], 'image_id' => null, 'name' => $name,
            'published' => '1', 'date' => date('Y-m-d H:i:s', time() - 86400 * (10 - $i * 2)),
        ]);
        $gid = (int) $db->insert_id;
        ins($db, 'nf_gallery_lang', ['gallery_id' => $gid, 'lang' => 'fr', 'title' => $name, 'description' => $desc]);

        $couverture = null;

        foreach ($photos[$name] ?? [] as $k => [$titre, $couleur]) {
            $base = 'demo-' . $gid . '-' . $k . '.jpg';

            // Trois fichiers par image, comme le fait `Gallery::add_image()` : l'affichage,
            // la vignette et l'original. Les mêmes dossiers, pour que la suppression depuis
            // l'administration retrouve ses petits.
            $affichage = fabrique_image($db, 'upload/gallery', $base, $titre, 1280, 720, $couleur, $users[$k % count($users)]);
            $vignette  = fabrique_image($db, 'upload/gallery/thumbnails', $base, '', 300, 169, $couleur, $users[$k % count($users)]);
            $original  = fabrique_image($db, 'upload/gallery/originals', $base, $titre, 1600, 900, $couleur, $users[$k % count($users)]);

            ins($db, 'nf_gallery_images', [
                'thumbnail_file_id' => $vignette,
                'original_file_id'  => $original,
                'file_id'           => $affichage,
                'gallery_id'        => $gid,
                'title'             => $titre,
                'description'       => '',
                'date'              => date('Y-m-d H:i:s', time() - 86400 * (10 - $i * 2) + $k * 600),
                'views'             => 12 + $k * 7 + $i * 3,
            ]);

            $couverture = $couverture ?? $affichage;
            $total_photos++;
        }

        if ($couverture !== null) {
            $db->query("UPDATE nf_gallery SET image_id = {$couverture} WHERE gallery_id = {$gid}");
        }

        $i++;
    }
    echo "  galerie OK (" . count($galleries) . " galeries, {$total_photos} photos)\n";
}

function seed_gaming(mysqli $db, array $users): array
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

    // Cartes et modes de jeu. Sans eux, le formulaire de match n'a rien à proposer et l'onglet
    // « Matchs à jouer » ne peut rien afficher : ce sont les clés étrangères de
    // `nf_events_matches.mode_id` et `nf_events_matches_rounds.map_id`.
    $cartes = [
        'Counter-Strike 2'   => ['Mirage', 'Inferno', 'Nuke', 'Ancient', 'Anubis'],
        'Valorant'           => ['Ascent', 'Bind', 'Haven', 'Lotus'],
        'League of Legends'  => ['Faille de l\'invocateur', 'ARAM — Abîme hurlant'],
        'Rocket League'      => ['Champions Field', 'Mannfield', 'Urban Central'],
    ];
    $modes = [
        'Counter-Strike 2'   => ['Compétitif 5v5', 'Wingman 2v2', 'Premier'],
        'Valorant'           => ['Compétitif 5v5', 'Swiftplay'],
        'League of Legends'  => ['Faille classée 5v5', 'ARAM'],
        'Rocket League'      => ['Duo 2v2', 'Standard 3v3'],
    ];
    $map_ids = $mode_ids = [];
    foreach ($game_ids as $jeu => $gid) {
        foreach ($cartes[$jeu] ?? [] as $carte) {
            ins($db, 'nf_games_maps', ['game_id' => $gid, 'image_id' => null, 'title' => $carte]);
            $map_ids[$jeu][] = (int) $db->insert_id;
        }
        foreach ($modes[$jeu] ?? [] as $mode) {
            ins($db, 'nf_games_modes', ['game_id' => $gid, 'title' => $mode]);
            $mode_ids[$jeu][] = (int) $db->insert_id;
        }
    }

    // Rôles au sein d'une équipe — la colonne `nf_teams_users.role_id` restait NULL pour tout le
    // monde, donc la fiche d'équipe affichait cinq joueurs sans rien pour les distinguer.
    $role_ids = [];
    foreach (['Capitaine', 'Titulaire', 'Remplaçant', 'Coach'] as $ordre => $titre) {
        ins($db, 'nf_teams_roles', ['title' => $titre, 'order' => $ordre]);
        $role_ids[] = (int) $db->insert_id;
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
        // membres — un capitaine, trois titulaires, un remplaçant
        for ($k = 0; $k < 5; $k++) {
            ins($db, 'nf_teams_users', [
                'team_id' => $tid,
                'user_id' => $users[($ord + $k) % count($users)],
                'role_id' => $role_ids[$k === 0 ? 0 : ($k === 4 ? 2 : 1)],
            ]);
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
    $event_ids = [];
    foreach ($events as [$type, $title, $loc, $desc, $offset_days]) {
        $d = date('Y-m-d H:i:s', time() + 86400 * $offset_days);
        ins($db, 'nf_events', [
            'type_id' => $type, 'user_id' => $users[0], 'image_id' => null, 'title' => $title,
            'description' => $desc, 'private_description' => '', 'location' => $loc,
            'date' => $d, 'date_end' => null, 'published' => '1', 'publish_date' => date('Y-m-d H:i:s', time() - 86400 * 5),
        ]);
        $event_ids[$title] = (int) $db->insert_id;
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

    return [
        'games'  => $game_ids,
        'teams'  => $team_ids,
        'events' => $event_ids,
        'maps'   => $map_ids,
        'modes'  => $mode_ids,
        'roles'  => $role_ids,
    ];
}

/**
 * Participants, adversaires, matchs et manches.
 *
 * Les trois onglets du module Événements — « Standards », « Résultats », « Matchs à jouer » — se
 * partagent ces quatre tables. Toutes étaient vides : les deux derniers onglets n'affichaient donc
 * rien, ce qui se lit comme une page cassée plutôt que comme une absence de contenu.
 */
function seed_events_extras(mysqli $db, array $users, array $gaming): void
{
    // Inscriptions. 1 = présent, 2 = peut-être, 3 = absent (cf. modules/events/models/participants.php).
    $inscrits = 0;
    foreach ($gaming['events'] as $titre => $eid) {
        foreach ($users as $k => $uid) {
            if ($k % 4 === 3) {
                continue; // tout le monde ne répond pas : c'est plus crédible
            }
            ins($db, 'nf_events_participants', [
                'event_id' => $eid,
                'user_id'  => $uid,
                'status'   => $k % 5 === 1 ? 2 : ($k % 7 === 6 ? 3 : 1),
            ]);
            $inscrits++;
        }
    }

    // Équipes adverses
    $adversaires = [
        // Codes pays en MINUSCULES : `get_countries()` est indexe ainsi (`fr`, pas `FR`), et
        // les drapeaux de `images/flags/` portent le meme nom. Semes en majuscules, ils
        // produisaient un `Undefined array key` a chaque affichage et un drapeau introuvable.
        ['Team Nocturne', 'https://example.org/nocturne', 'fr'],
        ['Aurora Esports', 'https://example.org/aurora', 'be'],
        ['Crimson Owls', 'https://example.org/owls', 'ca'],
        ['Skyline Gaming', '', 'ch'],
    ];
    $adv_ids = [];
    foreach ($adversaires as [$nom, $site, $pays]) {
        ins($db, 'nf_events_matches_opponents', [
            'image_id' => null, 'title' => $nom, 'website' => $site, 'country' => $pays,
        ]);
        $adv_ids[] = (int) $db->insert_id;
    }

    // Matchs : un par événement gaming, avec l'équipe et le mode du bon jeu.
    $rencontres = [
        ['Tournoi régional CS2',         'Équipe principale CS2', 'Counter-Strike 2',  0, [[13, 9], [11, 13], [13, 7]]],
        ['Coupe Valorant communautaire', 'Roster Valorant',       'Valorant',          1, [[13, 11], [9, 13]]],
        ['Entraînement hebdo LoL',       'Équipe LoL',            'League of Legends', 2, []],
    ];
    $matchs = $manches = 0;
    foreach ($rencontres as [$event, $equipe, $jeu, $i_adv, $scores]) {
        if (!isset($gaming['events'][$event], $gaming['teams'][$equipe])) {
            continue;
        }
        $eid = $gaming['events'][$event];

        ins($db, 'nf_events_matches', [
            'event_id'    => $eid,
            'team_id'     => $gaming['teams'][$equipe],
            'opponent_id' => $adv_ids[$i_adv],
            'mode_id'     => $gaming['modes'][$jeu][0] ?? null,
            'webtv'       => $i_adv === 0 ? 'https://twitch.tv/exemple' : '',
            'website'     => '',
        ]);
        $matchs++;

        // Un match sans manche est un match « à jouer » ; avec ses manches, c'est un résultat.
        foreach ($scores as $n => [$s1, $s2]) {
            ins($db, 'nf_events_matches_rounds', [
                'event_id' => $eid,
                'map_id'   => $gaming['maps'][$jeu][$n] ?? null,
                'score1'   => $s1,
                'score2'   => $s2,
            ]);
            $manches++;
        }
    }

    echo "  events+ OK ({$inscrits} inscriptions, " . count($adv_ids) . " adversaires, {$matchs} matchs, {$manches} manches)\n";
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
    // Le formulaire de candidature n'affichait que les champs fixes : `nf_recruits_fields` était
    // vide, donc la possibilité de poser ses propres questions restait invisible.
    $questions = [
        ['Quel est ton pseudo en jeu ?', 'text', 1],
        ['Quelles sont tes disponibilités en soirée ?', 'text', 1],
        ['Décris une situation où tu as pris le lead.', 'textarea', 0],
    ];

    $candidatures = [
        ['Kira', 'kira@demo.local', '2001-03-14', '1',
            "Joueuse depuis 2018, passée par deux équipes amateur.",
            "Je veux progresser dans un cadre sérieux et régulier.",
            "Faceit niveau 8, deux saisons en ligue régionale."],
        ['OldSchool', 'oldschool@demo.local', '1988-11-02', '2',
            "Je joue depuis Quake, donc autant dire un moment.",
            "L'envie de retrouver un collectif structuré.",
            "Beaucoup de LAN, mais rien de compétitif depuis 2015."],
        ['PixelWitch', 'pixelwitch@demo.local', '1999-06-21', '3',
            "Monteuse vidéo et joueuse occasionnelle.",
            "Plutôt pour le contenu que pour le roster.",
            "Trois ans de montage pour une équipe LoL."],
    ];

    $i = 0;
    $recruit_ids = [];
    foreach ($posts as [$title, $intro, $desc, $req, $role, $icon]) {
        ins($db, 'nf_recruits', [
            'title' => $title, 'introduction' => $intro, 'description' => $desc, 'requierments' => $req,
            'date' => date('Y-m-d H:i:s', time() - 86400 * ($i + 3)), 'user_id' => $users[0],
            'size' => 1, 'role' => $role, 'icon' => $icon, 'date_end' => date('Y-m-d', time() + 86400 * 20),
            'closed' => '0', 'team_id' => null, 'image_id' => null,
        ]);
        $rid = (int) $db->insert_id;
        $recruit_ids[] = $rid;

        foreach ($questions as $n => [$libelle, $type, $requis]) {
            ins($db, 'nf_recruits_fields', [
                'recruit_id' => $rid, 'label' => $libelle, 'type' => $type,
                'required' => $requis, 'sort_order' => $n,
            ]);
        }
        $i++;
    }

    // Statut : 1 = en attente, 2 = acceptée, 3 = refusée.
    foreach ($candidatures as $k => [$pseudo, $email, $naissance, $statut, $presentation, $motivations, $experiences]) {
        ins($db, 'nf_recruits_candidacies', [
            'recruit_id'    => $recruit_ids[$k % count($recruit_ids)],
            'date'          => date('Y-m-d H:i:s', time() - 86400 * ($k + 1)),
            'user_id'       => $users[($k + 8) % count($users)],
            'pseudo'        => $pseudo,
            'email'         => $email,
            'date_of_birth' => $naissance,
            'presentation'  => $presentation,
            'motivations'   => $motivations,
            'experiences'   => $experiences,
            'status'        => $statut,
            'reply'         => $statut === '1' ? null : ($statut === '2' ? 'Bienvenue, on te contacte sur Discord.' : 'Merci, mais le poste visé est un joueur.'),
            // La forme du PRODUIT (`recruits/controllers/index.php`) : une liste de libellés et de
            // valeurs. Le semoir écrivait un dictionnaire `libellé => valeur`, et la fiche d'une
            // candidature plantait dès qu'elle lisait `$c['label']` sur une chaîne (2026-09-22).
            'custom'        => json_encode([['label' => 'Quel est ton pseudo en jeu ?', 'value' => $pseudo]], JSON_UNESCAPED_UNICODE),
        ]);
    }

    echo "  recrutement OK (" . count($posts) . " offres, " . (count($posts) * count($questions)) . " champs, " . count($candidatures) . " candidatures)\n";
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
    // Un gabarit et deux campagnes : l'écran d'administration de la newsletter n'avait qu'une
    // liste d'abonnés, sans rien montrer de ce qu'on peut leur envoyer.
    ins($db, 'nf_newsletter_templates', [
        'name'       => 'Lettre mensuelle',
        'subject'    => 'Les nouvelles de la communauté — {mois}',
        'content'    => "<h2>Ce mois-ci</h2>\n<p>Les résultats de nos équipes, les prochains événements et les photos de la dernière LAN.</p>\n<p><a href=\"{site}\">Lire sur le site</a></p>",
        'created_at' => date('Y-m-d H:i:s', time() - 86400 * 40),
    ]);

    $campagnes = [
        ['Les nouvelles de la communauté — septembre', 'sent', 18, 4, 30],
        ['Inscriptions ouvertes pour la LAN d\'hiver', 'scheduled', 0, 0, 3],
    ];
    foreach ($campagnes as [$sujet, $etat, $envoyes, $ouverts, $jours]) {
        ins($db, 'nf_newsletter_campaigns', [
            'subject'          => $sujet,
            'content'          => "<p>" . $sujet . "</p><p>Rendez-vous sur le site pour le détail.</p>",
            'segment'          => 'all',
            'status'           => $etat,
            'scheduled_at'     => $etat === 'scheduled' ? date('Y-m-d H:i:s', time() + 86400 * $jours) : null,
            'sent_at'          => $etat === 'sent' ? date('Y-m-d H:i:s', time() - 86400 * $jours) : null,
            'sent_to'          => $envoyes,
            'recipients_total' => count($emails),
            'failed_to'        => 0,
            'opened_to'        => $ouverts,
            'user_id'          => $users[0],
            'created_at'       => date('Y-m-d H:i:s', time() - 86400 * ($jours + 2)),
        ]);
    }

    echo "  newsletter OK (" . count($emails) . " abonnés, 1 gabarit, " . count($campagnes) . " campagnes)\n";
}

/** Pages libres — le module `pages` n'en avait aucune, son écran d'administration était vide. */
function seed_pages(mysqli $db, array $users): void
{
    $pages = [
        ['a-propos', 'À propos de la communauté', 'Qui sommes-nous',
            "<p>Fondée en 2019 autour de Counter-Strike, notre communauté réunit aujourd'hui une centaine de joueurs sur quatre jeux. On y vient pour le niveau, on y reste pour l'ambiance.</p>"
            . "<p>Trois équipes compétitives, des entraînements hebdomadaires, une LAN annuelle — et un serveur Discord ouvert à tous.</p>"],
        ['reglement', 'Règlement intérieur', 'Ce qu\'on attend de chacun',
            "<p>Respect avant tout : pas d'insultes, pas de propos discriminatoires, pas de triche. Un manquement se règle d'abord par un avertissement, ensuite par une exclusion.</p>"
            . "<ul><li>Micro conseillé en entraînement, obligatoire en match officiel.</li>"
            . "<li>Prévenir en cas d'absence, au moins 24 h à l'avance.</li>"
            . "<li>Le staff tranche les litiges ; ses décisions sont publiques.</li></ul>"],
        ['nous-rejoindre', 'Nous rejoindre', 'Comment postuler',
            "<p>Les recrutements ouverts sont listés dans la rubrique dédiée. Tu peux aussi te présenter sur le forum : on regarde toutes les candidatures spontanées.</p>"],
        ['partenaires', 'Nos partenaires', 'Ils nous soutiennent',
            "<p>Trois partenaires nous accompagnent sur l'hébergement, le matériel et l'habillage vidéo. Leur présence finance nos déplacements en LAN.</p>"],
    ];

    foreach ($pages as $k => [$nom, $titre, $sous_titre, $contenu]) {
        ins($db, 'nf_pages', [
            'name' => $nom, 'published' => '1',
            'date' => date('Y-m-d H:i:s', time() - 86400 * (30 - $k * 5)), 'layout' => 'default',
        ]);
        $pid = (int) $db->insert_id;
        ins($db, 'nf_pages_lang', [
            'page_id' => $pid, 'lang' => 'fr', 'title' => $titre, 'subtitle' => $sous_titre, 'content' => $contenu,
        ]);
    }
    echo "  pages OK (" . count($pages) . ")\n";
}

/** Un menu personnalisé, pour que l'écran « Menus » ne soit pas une page blanche. */
function seed_menus(mysqli $db): void
{
    ins($db, 'nf_menus', ['name' => 'communaute', 'title' => 'La communauté']);
    $mid = (int) $db->insert_id;

    $items = [
        ['À propos', 'pages/a-propos', 'fas fa-circle-info', ''],
        ['Règlement', 'pages/reglement', 'fas fa-gavel', ''],
        ['Nous rejoindre', 'recruits', 'fas fa-user-plus', ''],
        ['Nos partenaires', 'pages/partenaires', 'fas fa-handshake', ''],
        ['Discord', 'https://discord.gg/exemple', 'fab fa-discord', '_blank'],
    ];
    foreach ($items as $k => [$titre, $url, $icone, $cible]) {
        ins($db, 'nf_menus_items', [
            'menu_id' => $mid, 'parent_id' => null, 'title' => $titre,
            'url' => $url, 'icon' => $icone, 'target' => $cible, 'position' => $k,
        ]);
    }
    echo "  menus OK (1 menu, " . count($items) . " entrées)\n";
}

/** Carrousel de la page d'accueil. */
function seed_slider(mysqli $db): void
{
    $slides = [
        ['Bienvenue sur la démo', 'Toutes les rubriques sont peuplées : navigue librement.', 'news', [46, 134, 193]],
        ['LAN d\'été 2025', 'Retrouve les photos dans la galerie.', 'gallery', [155, 89, 182]],
        ['Recrutement ouvert', 'Deux postes à pourvoir sur CS2 et Valorant.', 'recruits', [230, 126, 34]],
    ];
    foreach ($slides as $k => [$titre, $legende, $lien, $couleur]) {
        // L'image ne porte PAS le titre : le carrousel l'écrit déjà par-dessus, avec la légende. Gravé dans
        // l'image, il s'affichait en double, et en travers de la légende au téléphone (vu le 2026-10-06 avec
        // Forge 2.0 ; le défaut valait pour tous les thèmes).
        $fid = fabrique_image($db, 'upload/demo', 'demo-slide-' . $k . '.jpg', '', 1600, 500, $couleur);
        $chemin = $db->query("SELECT path FROM nf_file WHERE id = {$fid}")->fetch_row()[0];
        ins($db, 'nf_slider_slides', [
            'image_url' => $chemin, 'title' => $titre, 'caption' => $legende, 'link' => $lien,
            'sort_order' => $k, 'active' => 1,
            'created_at' => date('Y-m-d H:i:s', time() - 86400 * 7),
            'updated_at' => date('Y-m-d H:i:s', time() - 86400 * 7),
        ]);
    }
    echo "  carrousel OK (" . count($slides) . " diapositives)\n";
}

/** Émojis personnalisés — le rendu `:nom:` est central dans bbcode(), il faut de quoi le montrer. */
function seed_emojis(mysqli $db): void
{
    $emojis = [
        ['gg', [39, 174, 96]],
        ['clutch', [231, 76, 60]],
        ['ace', [241, 196, 15]],
        ['salt', [52, 152, 219]],
        ['pog', [155, 89, 182]],
    ];
    foreach ($emojis as [$nom, $couleur]) {
        $fid = fabrique_image($db, 'upload/demo', 'demo-emoji-' . $nom . '.jpg', '', 64, 64, $couleur);
        ins($db, 'nf_custom_emojis', [
            'name' => $nom, 'image_id' => $fid,
            'created_at' => date('Y-m-d H:i:s', time() - 86400 * 20),
        ]);
    }
    echo "  émojis OK (" . count($emojis) . ")\n";
}

/** Discussions publiques du module `talks` — la messagerie était entièrement vide. */
function seed_talks(mysqli $db, array $users): void
{
    $salons = [
        ['Salon général', 'public', 'all', 'Le salon ouvert à tous les membres.', [
            ['Bienvenue à tous sur le salon général. Présentez-vous ici !', 0],
            ['Salut tout le monde, ravi de rejoindre la communauté 👋', 1],
            ['Bienvenue ! Si tu joues CS2, viens sur les scrims du mardi.', 2],
            ['Merci, je serai là.', 1],
        ]],
        ['Staff', 'public', 'staff', 'Coordination interne du staff.', [
            ['On cale la LAN d\'hiver sur le week-end du 12 ?', 0],
            ['Ça me va. Je réserve la salle demain.', 3],
        ]],
        ['Scrims CS2', 'group', 'all', 'Organisation des entraînements CS2.', [
            ['Scrim ce soir 21 h contre Aurora, tout le monde est dispo ?', 2],
            ['Présent.', 4],
            ['Je serai 10 min en retard.', 5],
        ]],
    ];

    $messages = 0;
    foreach ($salons as $s => [$nom, $type, $audience, $desc, $fil]) {
        ins($db, 'nf_talks', [
            'name' => $nom, 'type' => $type, 'audience' => $audience,
            'creator_id' => $users[0], 'description' => $desc,
            'created_at' => date('Y-m-d H:i:s', time() - 86400 * (25 - $s * 5)),
            'updated_at' => date('Y-m-d H:i:s', time() - 3600 * ($s + 1)),
        ]);
        $tid = (int) $db->insert_id;

        $membres = array_slice($users, 0, $type === 'group' ? 6 : count($users));
        foreach ($membres as $k => $uid) {
            ins($db, 'nf_talks_participants', [
                'talk_id' => $tid, 'user_id' => $uid,
                'role' => $k === 0 ? 'admin' : 'member',
                'joined_at' => date('Y-m-d H:i:s', time() - 86400 * (25 - $s * 5)),
                'last_read_at' => date('Y-m-d H:i:s', time() - 3600 * ($k + 1)),
                'notify_email' => 1,
            ]);
        }

        foreach ($fil as $n => [$texte, $i_auteur]) {
            ins($db, 'nf_talks_messages', [
                'talk_id' => $tid, 'parent_id' => null,
                'user_id' => $users[$i_auteur % count($users)], 'message' => $texte,
                'date' => date('Y-m-d H:i:s', time() - 3600 * (count($fil) - $n) - 3600 * $s),
            ]);
            $messages++;
        }
    }
    echo "  discussions OK (" . count($salons) . " salons, {$messages} messages)\n";
}

/** Modération : signalements et sanctions, pour que les deux écrans montrent un vrai cas. */
function seed_moderation(mysqli $db, array $users): void
{
    $signalements = [
        ['forum_message', '3', 4, 'spam', 'Message publicitaire hors sujet.', 'pending', null],
        ['comment', '2', 5, 'harassment', 'Propos agressifs envers un autre membre.', 'actioned', 0],
        ['guestbook', '1', 6, 'other', 'Doublon du message précédent.', 'dismissed', 0],
    ];
    foreach ($signalements as $k => [$type, $cible, $i_vise, $motif, $note, $etat, $i_traite]) {
        ins($db, 'nf_reports', [
            'reporter_id'    => $users[($k + 1) % count($users)],
            'reporter_ip'    => '203.0.113.' . (10 + $k),
            'target_type'    => $type,
            'target_id'      => $cible,
            'target_user_id' => $users[$i_vise % count($users)],
            'reason'         => $motif,
            'comment'        => $note,
            'url'            => '',
            'status'         => $etat,
            'handled_by'     => $i_traite === null ? null : $users[$i_traite],
            'handled_at'     => $i_traite === null ? null : date('Y-m-d H:i:s', time() - 86400 * $k),
            'created_at'     => date('Y-m-d H:i:s', time() - 86400 * ($k + 2)),
        ]);
    }

    $sanctions = [
        ['warning', 'global', 'Premier rappel à l\'ordre — propos déplacés sur le forum.', null, 4],
        ['mute', 'talks', 'Spam répété dans le salon général.', 86400, 5],
    ];
    foreach ($sanctions as $k => [$type, $portee, $motif, $duree, $i_vise]) {
        ins($db, 'nf_sanctions', [
            'user_id'          => $users[$i_vise % count($users)],
            'type'             => $type,
            'scope'            => $portee,
            'reason'           => $motif,
            'duration_seconds' => $duree,
            'starts_at'        => date('Y-m-d H:i:s', time() - 86400 * ($k + 1)),
            'expires_at'       => $duree === null ? null : date('Y-m-d H:i:s', time() - 86400 * ($k + 1) + $duree),
            'issued_by'        => $users[0],
            'notify_user'      => 1,
            'created_at'       => date('Y-m-d H:i:s', time() - 86400 * ($k + 1)),
        ]);
    }
    echo "  modération OK (" . count($signalements) . " signalements, " . count($sanctions) . " sanctions)\n";
}

/**
 * Gamification : boutique de points, historique et statut VIP.
 *
 * La décision « points seuls » (TODO §11) rend la boutique utilisable sans Stripe : c'est
 * précisément ce que la démo doit montrer.
 */
function seed_gamification(mysqli $db, array $users): void
{
    $articles = [
        ['Pseudo coloré', 'Ton pseudo en couleur sur le forum pendant 30 jours.', 'fas fa-palette', 250, 'perk', 'color'],
        ['Badge Soutien', 'Un badge discret sur ton profil, définitif.', 'fas fa-award', 500, 'badge', 'supporter'],
        ['Accès VIP 1 mois', 'Salons privés et priorité sur les scrims.', 'fas fa-crown', 1200, 'vip', '30'],
        ['Signature étendue', 'Signature de 6 lignes au lieu de 3.', 'fas fa-pen-nib', 300, 'perk', 'signature'],
    ];
    foreach ($articles as $k => [$titre, $desc, $icone, $prix, $type, $charge]) {
        ins($db, 'nf_shop_items', [
            'title' => $titre, 'description' => $desc, 'icon' => $icone, 'price' => $prix,
            'type' => $type, 'payload' => $charge, 'stock' => -1, 'unique_per_user' => 1,
            'active' => 1, 'position' => $k,
            'created_at' => date('Y-m-d H:i:s', time() - 86400 * 30),
        ]);
    }

    $motifs = [
        ['forum_message', 'Message publié sur le forum', 5],
        ['comment', 'Commentaire publié', 2],
        ['daily', 'Connexion quotidienne', 10],
        ['event', 'Participation à un événement', 25],
    ];
    $lignes = 0;
    foreach ($users as $k => $uid) {
        foreach ($motifs as $n => [$type, $raison, $montant]) {
            if (($k + $n) % 3 === 2) {
                continue;
            }
            ins($db, 'nf_points_log', [
                'user_id' => $uid, 'amount' => $montant, 'type' => $type, 'reason' => $raison,
                'created_at' => date('Y-m-d H:i:s', time() - 86400 * ($k + 1) - 3600 * $n),
            ]);
            $lignes++;
        }
    }

    foreach ([1, 2] as $i) {
        ins($db, 'nf_vip', [
            'user_id' => $users[$i], 'expires_at' => date('Y-m-d H:i:s', time() + 86400 * 20 * $i),
            'source' => 'shop', 'updated_at' => date('Y-m-d H:i:s', time() - 86400 * 10),
        ]);
    }
    echo "  gamification OK (" . count($articles) . " articles, {$lignes} lignes de points, 2 VIP)\n";
}

/** Notifications, abonnements et historique de révision : les trois écrans « vides » restants. */
function seed_activite(mysqli $db, array $users): void
{
    $notifs = [
        ['comment', 'ShadowFox a commenté « Notre équipe CS2 se qualifie »', 'news', 1],
        ['forum_reply', 'NovaStrike a répondu dans « Vos réglages CS2 »', 'forum', 1],
        ['event', 'Rappel : Tournoi régional CS2 dans 5 jours', 'events', 0],
        ['reaction', 'LunaByte a réagi à ton message', 'forum', 0],
    ];
    foreach ($notifs as $k => [$type, $titre, $url, $lu]) {
        ins($db, 'nf_notifications', [
            'user_id' => $users[0], 'actor_id' => $users[($k % 4) + 1], 'type' => $type,
            'title' => $titre, 'url' => $url, 'is_read' => $lu,
            'created_at' => date('Y-m-d H:i:s', time() - 3600 * ($k + 1)),
        ]);
    }

    $abos = 0;
    foreach ($users as $k => $uid) {
        if ($k % 3 !== 0) {
            continue;
        }
        foreach ([['news', 1], ['forum_topic', 1]] as [$type, $cid]) {
            ins($db, 'nf_subscriptions', [
                'user_id' => $uid, 'content_type' => $type, 'content_id' => $cid,
                'created_at' => date('Y-m-d H:i:s', time() - 86400 * ($k + 1)),
            ]);
            $abos++;
        }
    }

    // Historique de modification d'une actualité et d'une page du wiki.
    ins($db, 'nf_revisions', [
        'content_type' => 'news', 'content_id' => 1, 'lang' => 'fr', 'user_id' => $users[0],
        'summary' => 'Correction d\'une faute de frappe',
        'data' => json_encode(['title' => 'Notre équipe CS2 se qualifie'], JSON_UNESCAPED_UNICODE),
        'created_at' => date('Y-m-d H:i:s', time() - 86400 * 2),
    ]);

    $wiki = $db->query("SELECT id, title, content FROM nf_wiki_pages ORDER BY id LIMIT 2");
    $rev_wiki = 0;
    while ($page = $wiki ? $wiki->fetch_assoc() : null) {
        ins($db, 'nf_wiki_revisions', [
            'page_id' => (int) $page['id'], 'content' => $page['content'], 'title' => $page['title'],
            'user_id' => $users[0], 'comment' => 'Première rédaction',
            'created_at' => date('Y-m-d H:i:s', time() - 86400 * 5),
        ]);
        $rev_wiki++;
    }

    echo "  activité OK (" . count($notifs) . " notifications, {$abos} abonnements, " . (1 + $rev_wiki) . " révisions)\n";
}

/**
 * Config spécifique au SITE DE DÉMONSTRATION : nebula par défaut, vitrine + landing retirés.
 *
 * Séparée du reste depuis le 2026-09-20, et sautée par `--sans-config-demo`. Motif : ce semeur sert
 * aussi à garnir l'ATELIER, où retirer la vitrine casse les contrôles qui la mesurent —
 * `check-contraste` s'est arrêté sur « Thème(s) non installé(s) : vitrine » après un simple
 * repeuplement. Les droits de lecture des visiteurs, eux, ne sont pas optionnels : sans eux le
 * contenu inséré en SQL reste invisible, sur la démo comme sur un site d'essai.
 *
 * Remettre la vitrine après coup demande deux gestes : réinscrire l'addon dans `nf_addon`, puis
 * `php tools/init-dispositions.php` — un thème sans disposition affiche un site vide.
 */
function configurer_site_demo(mysqli $db): void
{
    $db->query("UPDATE nf_settings SET value = 'nebula' WHERE name = 'nf_default_theme'");
    // Le fuseau du site : une démonstration française montre ses heures à l'heure de Paris, pas à
    // l'heure universelle du serveur (le navigateur du visiteur prend le relais dès sa première page).
    $db->query("INSERT INTO nf_settings (name, site, lang, value, type) VALUES ('nf_timezone', '', '', 'Europe/Paris', 'string') ON DUPLICATE KEY UPDATE value = VALUES(value)");
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

/** Droits de lecture et réglages communs à toute installation garnie par ce semeur. */
function configure_demo(mysqli $db): void
{
    if (!sans_config_demo()) {
        configurer_site_demo($db);
    }

    // ── Droits de lecture des VISITEURS (rôle 3) ────────────────────────────
    //
    // Le semeur insère le contenu directement en SQL. Il saute donc les droits que l'interface
    // d'administration pose en même temps qu'elle crée un forum ou un album — et le contenu devient
    // invisible : la galerie annonçait « Aucun album » alors que trois existaient et étaient
    // publiés, parce que `gallery_see` n'était accordé à personne. Le même piège avait déjà été
    // rencontré sur le forum.
    //
    // scope_id = 0 : la portée globale, c'est-à-dire toutes les catégories du module.
    //
    // Le même piège s'est reproduit le 2026-09-16 sur `pages.access_page` : les quatre pages libres
    // existaient, étaient publiées, et rendaient 403.
    //
    // Le module `talks`, lui, n'est PAS concerné : son checker exige d'être connecté
    // (`$this->error->unauthorized()` quand `user()` est vide), indépendamment des permissions.
    // Son 403 au visiteur anonyme est le comportement voulu, pas un droit manquant — c'est une
    // messagerie. Le visiteur de la démo la voit en se connectant avec le compte `demo`.
    foreach (['forum.category_read', 'gallery.gallery_see', 'pages.access_page', 'events.access_events_type'] as $permission) {
        $p = $db->real_escape_string($permission);
        $db->query("DELETE FROM nf_role_permissions WHERE role_id = 3 AND permission = '$p'");
        $db->query("INSERT INTO nf_role_permissions (role_id, permission, scope_id, authorized) VALUES (3, '$p', 0, 'allow')");
    }
}

/**
 * La version ANGLAISE du contenu à versions linguistiques : actualités, articles, pages, catégories
 * et galeries.
 *
 * L'interface de la démo parle six langues ; son contenu n'en parlait qu'une. Un visiteur anglais
 * voyait chaque actualité en français, sous un bandeau qui le lui signale. Les modules à versions
 * linguistiques savent pourtant porter une traduction par contenu : la démo le montre désormais
 * (2026-09-23). Les messages du forum, écrits par des membres, restent dans leur langue.
 *
 * Chaque traduction se rattache au contenu par son titre FRANÇAIS, pas par un numéro : un
 * contenu renommé ou disparu est simplement ignoré. Idempotent (REPLACE) : rejouable sans doublon.
 *
 * @return int le nombre de lignes anglaises posées
 */
function seed_anglais(mysqli $db): int
{
    $traductions = [
        // table de traduction, clé, titre français => colonnes anglaises
        ['nf_news_lang', 'news_id', 'news', [
            'Bienvenue sur NeoFrag Reborn' => ['title' => 'Welcome to NeoFrag Reborn', 'introduction' => 'The community\'s new website is live!',
                'content' => '<p>We are delighted to welcome you to our new platform, powered by <strong>NeoFrag Reborn</strong>. Forum, news, galleries, tournaments: it\'s all here. Sign up and join the adventure!</p>'],
            'Victoire en finale du tournoi régional' => ['title' => 'Victory in the regional tournament final', 'introduction' => 'Our main team wins the grand final 3-1.',
                'content' => '<p>After a flawless run, our players lifted the regional tournament trophy. Congratulations to the whole team on this performance!</p><p>Next goal: the national qualifiers.</p>'],
            'Soirée communautaire ce vendredi' => ['title' => 'Community night this Friday', 'introduction' => 'Join us for a relaxed evening on the server.',
                'content' => '<p>This Friday at 9 pm, we all meet up for fun, friendly games. Beginners welcome!</p>'],
            'Nouveau partenariat matériel' => ['title' => 'New hardware partnership', 'introduction' => 'Exclusive discounts for our members.',
                'content' => '<p>Thanks to our new partner, enjoy discounts on gaming gear. Details in the members\' area.</p>'],
            'Calendrier des matchs du mois' => ['title' => 'This month\'s match schedule', 'introduction' => 'All the competitive dates not to miss.',
                'content' => '<p>The schedule of upcoming matches is out. Come and cheer on your teams!</p>'],
            'Concours de montage vidéo' => ['title' => 'Video editing contest', 'introduction' => 'Show off your best highlights and earn points.',
                'content' => '<p>Enter our editing contest: the best videos will be rewarded with shop points.</p>'],
        ]],
        ['nf_articles_lang', 'article_id', 'articles', [
            'Bien débuter en compétitif' => ['title' => 'Getting started in competitive play', 'excerpt' => 'Our tips to improve quickly.',
                'content' => '<h2>The basics</h2><p>Master your aim and positioning first. Consistency beats flashy plays.</p><h2>Team spirit</h2><p>Communication is the key to victory.</p>'],
            'Optimiser sa configuration' => ['title' => 'Optimising your setup', 'excerpt' => 'Settings and gear for a top-notch setup.',
                'content' => '<p>A good setup isn\'t everything, but it helps. Here are our recommended settings and peripherals.</p>'],
            'Notre avis sur le dernier patch' => ['title' => 'Our take on the latest patch', 'excerpt' => 'What changes for the competitive meta.',
                'content' => '<p>The latest patch reshuffles the deck. An analysis of the nerfs, buffs and their impact on the meta.</p>'],
            'Casque gaming : le comparatif' => ['title' => 'Gaming headsets: the comparison', 'excerpt' => 'We tested the current models for you.',
                'content' => '<p>Comfort, sound, microphone: our full comparison to help you pick the right headset.</p>'],
        ]],
        ['nf_pages_lang', 'page_id', 'pages', [
            'À propos de la communauté' => ['title' => 'About the community', 'subtitle' => 'Who we are',
                'content' => '<p>Founded in 2019 around Counter-Strike, our community now brings together a hundred or so players across four games. People come for the level and stay for the atmosphere.</p><p>Three competitive teams, weekly practice sessions, an annual LAN — and a Discord server open to everyone.</p>'],
            'Règlement intérieur' => ['title' => 'House rules', 'subtitle' => 'What we expect from everyone',
                'content' => '<p>Respect above all: no insults, no discriminatory remarks, no cheating. A breach is first met with a warning, then with an exclusion.</p><ul><li>Microphone recommended in practice, mandatory in official matches.</li><li>Let us know if you will be absent, at least 24 hours in advance.</li><li>Staff settle disputes; their decisions are public.</li></ul>'],
            'Nous rejoindre' => ['title' => 'Join us', 'subtitle' => 'How to apply',
                'content' => '<p>Open recruitments are listed in the dedicated section. You can also introduce yourself on the forum: we look at every unsolicited application.</p>'],
            'Nos partenaires' => ['title' => 'Our partners', 'subtitle' => 'They support us',
                'content' => '<p>Three partners support us with hosting, hardware and video branding. Their backing funds our trips to LAN events.</p>'],
        ]],
        ['nf_news_categories_lang', 'category_id', 'news_categories', [
            'Annonces' => ['title' => 'Announcements'], 'Compétition' => ['title' => 'Competition'], 'Communauté' => ['title' => 'Community'],
        ]],
        ['nf_articles_categories_lang', 'category_id', 'articles_categories', [
            'Guides' => ['title' => 'Guides'], 'Tests' => ['title' => 'Reviews'],
        ]],
        ['nf_gallery_lang', 'gallery_id', 'gallery', [
            'LAN d\'été 2025' => ['title' => 'Summer LAN 2025', 'description' => 'The best moments of our annual LAN.'],
            'Finale régionale' => ['title' => 'Regional final', 'description' => 'Our victory in pictures.'],
            'Best of du mois' => ['title' => 'Best of the month', 'description' => 'A compilation of the finest plays.'],
        ]],
        ['nf_gallery_categories_lang', 'category_id', 'gallery_categories', [
            'Événements' => ['title' => 'Events'], 'Highlights' => ['title' => 'Highlights'],
        ]],
    ];

    $posees = 0;

    foreach ($traductions as [$table, $cle, , $contenus])
    {
        if (!nf_table_existe($db, $table))
        {
            continue;
        }

        foreach ($contenus as $titre_fr => $colonnes)
        {
            $id = nf_scalar($db, "SELECT `$cle` FROM `$table` WHERE lang = 'fr' AND title = '".$db->real_escape_string($titre_fr)."' LIMIT 1");

            if ($id === NULL)
            {
                continue;
            }

            // Les colonnes que la ligne française porte et que la traduction ne donne pas (tags…)
            // sont reprises telles quelles : la ligne anglaise est complète.
            $francaise = $db->query("SELECT * FROM `$table` WHERE `$cle` = ".(int) $id." AND lang = 'fr'")->fetch_assoc();
            $ligne     = array_merge($francaise, $colonnes, ['lang' => 'en']);

            $noms    = implode(', ', array_map(static fn ($c) => "`$c`", array_keys($ligne)));
            $valeurs = implode(', ', array_map(static fn ($v) => $v === NULL ? 'NULL' : "'".$db->real_escape_string((string) $v)."'", $ligne));

            if (!$db->query("REPLACE INTO `$table` ($noms) VALUES ($valeurs)"))
            {
                nf_refus("version anglaise impossible dans $table : ".$db->error);
            }

            $posees++;
        }
    }

    return $posees;
}

// ── Helpers ─────────────────────────────────────────────────────────────────

/** Première police vectorielle trouvée sur la machine, ou NULL. Aucune n'est livrée avec le dépôt. */
function police_ttf(): ?string
{
    static $trouvee = false;
    static $chemin = null;

    if ($trouvee) {
        return $chemin;
    }
    $trouvee = true;

    foreach ([
        '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
        '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
        '/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf',
        'C:/Windows/Fonts/arialbd.ttf',
    ] as $candidat) {
        if (is_file($candidat) && function_exists('imagettftext')) {
            return $chemin = $candidat;
        }
    }
    return null;
}

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

/**
 * Tables dont la colonne `name` est un PERMALIEN, pas un libellé.
 *
 * Le produit s'en sert comme segment d'adresse (`/gallery/1/<name>`) et le compare au permalien du
 * titre pour valider l'URL. Les modèles écrivent donc `url_title($titre)`. Le semeur, lui, y posait
 * le titre brut : la galerie pointait vers `/gallery/1/Événements` quand la page vivait à
 * `/gallery/1/evenements`. Résultat, des pages « vides un peu partout » — signalé, puis
 * retrouvé par `tools/check-liens.php` sur la galerie, les équipes, les jeux, les partenaires et
 * les catégories d'actualités et d'articles.
 */
function tables_a_permalien(): array
{
    // Une FONCTION et non une constante de fichier : PHP connait les fonctions avant de lire le
    // fichier, mais execute les `const` dans l'ordre — et `main()` est appele plus haut. La
    // constante n'existait donc pas encore au premier appel.
    return [
        'nf_news_categories', 'nf_articles_categories', 'nf_gallery_categories',
        'nf_gallery', 'nf_games', 'nf_teams', 'nf_partners',
    ];
}

/** Même transformation que `url_title()` du cœur, que le semeur n'a pas chargé. */
function permalien(string $texte): string
{
    if (function_exists('transliterator_transliterate')) {
        $texte = (string) transliterator_transliterate('Any-Latin; Latin-ASCII; [-翿] remove', $texte);
    }

    $texte = preg_replace('/[^a-z0-9]+/i', '-', $texte);

    return trim(strtolower((string) $texte), '-');
}

function ins(mysqli $db, string $table, array $cols): void
{
    if (isset($cols['name']) && is_string($cols['name']) && in_array($table, tables_a_permalien(), TRUE)) {
        $cols['name'] = permalien($cols['name']);
    }

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
        nf_refus("erreur sur $table : " . $db->error . "\n  $sql");
    }
}
