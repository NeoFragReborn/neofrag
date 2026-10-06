<?php
declare(strict_types=1);

/**
 * dump-demo — fige l'état de la démo (après tools/seed-demo.php) en install/demo.sql.
 *
 * Famille : outil
 * Diffusion : publique
 *
 * Ce qu'il produit
 * ----------------
 * `install/demo.sql` est (1) la charge de l'AUTO-RESET du site de démo, rechargée périodiquement
 * par le cron gardé, et (2) le contenu initial du site de démo.
 *
 * CE QU'IL RESTAURE : la configuration d'affichage démo (nebula, sans vitrine), les réglages non
 * sensibles, TOUS les membres sauf le compte de secours, tout le contenu, les mises en page et les
 * menus. CE QU'IL NE RESTAURE PAS, et qui doit donc rester VERROUILLÉ en mode démo : les addons
 * installés (`nf_addon`), les rôles et permissions. La liste des modules verrouillés vit dans
 * `NF_DEMO_MODULES_VERROUILLES` (neofrag/helpers/system.php) — `check-demo-lock` vérifie que les
 * deux listes ne divergent pas.
 *
 * Usage
 * -----
 *   php tools/dump-demo.php            après seed-demo.php
 *   php tools/dump-demo.php --force    accepte un instantané plus pauvre que l'actuel
 */

require __DIR__.'/lib/outil.php';
require __DIR__.'/lib/site.php';
require __DIR__.'/lib/sql.php';
require __DIR__.'/lib/demo.php';

[$o] = nf_options(['force' => FALSE]);

const DEMO_OUT = __DIR__.'/../install/demo.sql';

/** Tables de CONTENU rechargées à chaque reset (DELETE + INSERT). */
const CONTENT_TABLES = [
    'nf_news_categories', 'nf_news_categories_lang', 'nf_news', 'nf_news_lang',
    'nf_articles_categories', 'nf_articles_categories_lang', 'nf_articles', 'nf_articles_lang',
    'nf_forum_categories', 'nf_forum', 'nf_forum_url', 'nf_forum_topics', 'nf_forum_messages',
    'nf_gallery_categories', 'nf_gallery_categories_lang', 'nf_gallery', 'nf_gallery_lang', 'nf_gallery_images',
    'nf_games', 'nf_games_lang', 'nf_teams', 'nf_teams_lang', 'nf_teams_users',
    'nf_events_types', 'nf_events', 'nf_awards',
    'nf_comment', 'nf_reactions',
    'nf_faq_categories', 'nf_faq_questions',
    'nf_downloads_categories', 'nf_downloads', 'nf_links_categories', 'nf_links',
    'nf_partners', 'nf_partners_lang', 'nf_guestbook',
    'nf_surveys', 'nf_surveys_options', 'nf_surveys_votes', 'nf_classifieds',
    'nf_recruits', 'nf_recruits_fields', 'nf_calendar_events',
    'nf_bug_tickets', 'nf_bug_comments', 'nf_donations_campaigns', 'nf_donations',
    'nf_ads', 'nf_newsletter_subscribers',

    // ── Ajouté le 2026-09-16, quand l'administration de la démo est passée de LECTURE SEULE à
    // « le contenu est modifiable ». Le critère de cette liste est exactement celui du verrou
    // (cf. NF_DEMO_MODULES_VERROUILLES) : tout ce qu'un visiteur peut désormais changer doit être
    // rétabli ici, sinon la démo se dégrade définitivement, une visite après l'autre.
    'nf_events_matches', 'nf_events_matches_opponents', 'nf_events_matches_rounds',
    'nf_events_participants',
    'nf_pages', 'nf_pages_lang', 'nf_pages_instances',
    'nf_menus', 'nf_menus_items',
    'nf_custom_emojis', 'nf_slider_slides',

    // ── Ajouté le 2026-09-23 : les modules installés sur la démo le 2026-09-22 pour les vignettes de
    // la place de marché. Leur contenu avait été ajouté À LA MAIN à l'instantané ; la première
    // régénération qui a suivi l'aurait perdu, et c'est le garde-fou « instantané plus pauvre » qui l'a
    // refusée (77 INSERT contre 87). L'instantané redevient entièrement produit par cet outil.
    'nf_glossary_categories', 'nf_glossary_terms',
    'nf_places_categories', 'nf_places',
    'nf_quotes_categories', 'nf_quotes',
    'nf_recipes_categories', 'nf_recipes',
    'nf_webradio_shows',
    // `nf_media` n'y est PAS, et le module `media` est verrouillé : il efface ses fichiers lui-même.
    // Partout ailleurs, sur une démonstration, File::delete() ne supprime plus rien — ni le fichier
    // ni sa ligne `nf_file`, que l'instantané ne porte pas (audit du 2026-10-02 : la galerie, qu'on
    // disait ne supprimer « que la ligne », effaçait bel et bien le fichier).
    'nf_talks', 'nf_talks_messages', 'nf_talks_participants', 'nf_talks_attachments',
    'nf_revisions',
    'nf_wiki_pages', 'nf_wiki_revisions',

    // ── Ajouté le 2026-10-02, après l'audit de sécurité de la démo : des tables que les modules
    // OUVERTS écrivent, et que l'instantané ne rétablissait pas. Un rôle d'équipe renommé ou une
    // carte de jeu supprimée l'étaient pour toujours, et publiquement ; les notifications et les
    // points d'une publication s'accumulaient ; créer une galerie ou une page écrit des permissions.
    // Les préférences de notifications (chantier A, étape A4, 2026-10-05) : le compte partagé peut tout
    // couper, et la démonstration ne recevrait plus rien, pour tous ses visiteurs.
    'nf_games_maps', 'nf_games_modes', 'nf_teams_roles',
    'nf_articles_series', 'nf_articles_series_lang',
    'nf_notifications', 'nf_notifications_preferences', 'nf_points_log',
    'nf_role_permissions',
    // La modération est verrouillée ; ses deux tables sont rétablies quand même, par sûreté.
    'nf_sanctions', 'nf_ip_banlist',

    // Les MISES EN PAGE, par sûreté : l'éditeur en direct, seul à les écrire, est verrouillé sur la
    // démonstration (un widget HTML écrit par un visiteur exécuterait son script chez tous les autres).
    'nf_dispositions', 'nf_widgets',
];

$db       = nf_connexion();
$theme_t  = nf_type_id($db, 'theme');
$widget_t = nf_type_id($db, 'widget');

$out  = nf_sql_entete('dump-demo', 'instantané du site de DÉMO (config affichage + membres + contenu), rechargé par l\'auto-reset');
$out .= "-- Ne touche pas le compte admin ni les secrets (verrouillés en mode démo).\n";
// Le jour où ce contenu est « aujourd'hui » : à chaque remise à zéro, la démo fait avancer ses dates du temps écoulé
// depuis (Monitoring::demo_au_present(), 2026-10-06) — sans quoi ses prochains rendez-vous finissent tous passés.
$out .= "-- nf-demo-present: ".gmdate('Y-m-d')."\n\n";
$out .= "SET FOREIGN_KEY_CHECKS = 0;\n";
$out .= "SET NAMES utf8mb4;\n";
// La remise à zéro est ATOMIQUE : un visiteur ne doit jamais voir l'état intermédiaire.
//
// Constaté le 2026-09-16 en naviguant pendant un import : le module Événements journalisait
// `Trying to access array offset on null` parce qu'il lisait `nf_events` alors que
// `nf_events_types` venait d'être vidée et pas encore réécrite. C'est devenu possible en
// remplaçant les TRUNCATE par des DELETE : TRUNCATE est du DDL, il provoque un commit implicite.
$out .= "START TRANSACTION;\n\n";

// 1. Config d'affichage démo (idempotent) : nebula par défaut, vitrine + landing retirés.
$out .= "-- Config démo (idempotent)\n";
$out .= "UPDATE `nf_settings` SET `value` = 'nebula' WHERE `name` = 'nf_default_theme';\n";
$out .= "DELETE FROM `nf_addon` WHERE `name` = 'vitrine' AND `type_id` = {$theme_t};\n";
$out .= "DELETE FROM `nf_dispositions` WHERE `theme` = 'vitrine';\n";
$out .= "DELETE FROM `nf_addon` WHERE `name` = 'landing' AND `type_id` = {$widget_t};\n";
$out .= "DELETE FROM `nf_widgets` WHERE `widget` = 'landing';\n";

// Forum, galerie, pages et événements lisibles par les VISITEURS (rôle 3) : le seed crée les
// catégories en SQL sans grant de lecture → 403 sur le détail. Permission `module.action`, scope 0.
// Et par les MEMBRES (rôle 2) : leur rôle n'hérite pas de celui des visiteurs (2026-10-05).
foreach (['forum.category_read', 'gallery.gallery_see', 'pages.access_page', 'events.access_events_type'] as $permission)
{
    foreach ([3, 2] as $role)
    {
        $out .= "DELETE FROM `nf_role_permissions` WHERE `role_id` = {$role} AND `permission` = '{$permission}';\n";
        $out .= "INSERT INTO `nf_role_permissions` (`role_id`, `permission`, `scope_id`, `authorized`) VALUES ({$role}, '{$permission}', 0, 'allow');\n";
    }
}

$out .= "\n";

// 1 bis. Les RÉGLAGES du site — restaurés depuis le 2026-09-16, ce qui permet de laisser ouverts
// les écrans de configuration des modules de contenu. Deux précautions : les secrets et les réglages
// propres à l'installation sont exclus — la liste et ses raisons vivent dans tools/lib/demo.php, que
// check-demo-lock confronte au code —, et `ON DUPLICATE KEY UPDATE` plutôt que DELETE : un réglage
// apparu depuis l'instantané reste.
$sensibles = array_keys(NF_DEMO_REGLAGES_EXCLUS);

$exclusion = "`name` NOT IN ('".implode("', '", array_map([$db, 'real_escape_string'], $sensibles))."')";

$out .= "-- Réglages du site (les réglages sensibles sont exclus, cf. tools/dump-demo.php)\n";
$out .= nf_sql_upserts($db, 'nf_settings', ['value'], "WHERE {$exclusion}");
$out .= "\n";

// 2. Membres démo. UN SEUL compte échappe à la remise à zéro — le plus ancien administrateur, celui
// que l'installeur a créé : le compte de SECOURS, jamais annoncé nulle part. `demo` est administrateur
// et doit être rétabli comme n'importe quel autre membre (sinon le premier visiteur qui change son mot
// de passe ferme la porte à tout le monde).
$secours = (int) nf_scalar($db, "SELECT MIN(`id`) FROM `nf_user` WHERE `admin` = '1'");

if ($secours <= 0)
{
    nf_refus('aucun compte administrateur : impossible de désigner un compte de secours');
}

// Les comptes de l'instantané sont REMIS EN ÉTAT, jamais supprimés puis réinsérés : `nf_session`
// porte une clé étrangère vers `nf_user`, et supprimer la ligne d'un visiteur connecté bloquait sa
// requête 30 s (page blanche toutes les 15 minutes, 2026-09-16). Seuls disparaissent les comptes
// créés DEPUIS l'instantané.
$ids_demo  = array_map('intval', nf_colonne($db, "SELECT `id` FROM `nf_user` WHERE `id` <> {$secours}"));
$conserves = implode(', ', array_merge([$secours], $ids_demo));

$out .= "-- Membres démo — remis en état (upsert), jamais supprimés : une session vivante\n";
$out .= "-- s'appuie sur la ligne `nf_user`, et la retirer bloquait le site 30 s (cf. tools/dump-demo.php).\n";
$out .= "DELETE FROM `nf_user_profile` WHERE `id` NOT IN ({$conserves});\n";
$out .= "DELETE FROM `nf_user` WHERE `id` NOT IN ({$conserves});\n";
// `nf_session` a bien une clé étrangère `ON DELETE CASCADE` — mais l'import tourne avec
// `FOREIGN_KEY_CHECKS = 0`, donc la cascade NE JOUE PAS : les sessions survivaient à leur compte,
// et un visiteur « se souvenir de moi » restait bloqué sur une page qui ne se chargeait jamais.
$out .= "DELETE FROM `nf_session` WHERE `user_id` IS NOT NULL AND `user_id` NOT IN ({$conserves});\n";
$out .= "DELETE FROM `nf_user_points`;\n";
$out .= "DELETE FROM `nf_karma`;\n";
$out .= nf_sql_upserts($db, 'nf_user', ['*'], "WHERE `id` <> {$secours}");
$out .= nf_sql_upserts($db, 'nf_user_profile', ['*'], "WHERE `id` <> {$secours}");
$out .= nf_sql_inserts($db, 'nf_user_points');
$out .= nf_sql_inserts($db, 'nf_karma');

// 3. Contenu. `DELETE FROM` et non `TRUNCATE` : TRUNCATE est du DDL, il prend un verrou de
// métadonnées EXCLUSIF et attend derrière toute lecture en cours. DELETE est du DML ordinaire.
$out .= "\n-- Contenu\n";

foreach (CONTENT_TABLES as $table)
{
    if (!nf_table_existe($db, $table))
    {
        continue;
    }

    // Les réglages d'un widget peuvent porter une clé (Twitch, TeamSpeak) : vidée, cf. tools/lib/demo.php.
    // La ligne garde l'ordre de ses colonnes : les valeurs s'écrivent dans cet ordre.
    $sans_secret = $table === 'nf_widgets'
        ? static function (array $ligne): array
        {
            $ligne['settings'] = nf_demo_reglages_widget($ligne['settings']);

            return $ligne;
        }
        : NULL;

    $out .= "DELETE FROM `{$table}`;\n";
    $out .= nf_sql_inserts($db, $table, '', $sans_secret);
}

$out .= "\nCOMMIT;\n";
$out .= "SET FOREIGN_KEY_CHECKS = 1;\n";

// ── Garde-fou : ne jamais remplacer un instantané par un instantané plus pauvre ──────────
//
// Incident du 2026-09-16 : le cron recharge l'instantané toutes les 15 minutes ; il a tiré une
// version périmée ENTRE le peuplement et la prise d'instantané, et le dump a figé une base déjà
// appauvrie. L'instantané suivant a propagé le vide. Rien dans la chaîne ne pouvait le signaler :
// on compare donc le nouvel instantané à celui qu'il remplace, et on refuse la régression.
$nb_inserts = substr_count($out, "\nINSERT INTO ") + (str_starts_with($out, 'INSERT INTO ') ? 1 : 0);

if (!$o['force'] && is_file(DEMO_OUT))
{
    $avant = substr_count((string) file_get_contents(DEMO_OUT), "\nINSERT INTO ");

    if ($nb_inserts < $avant)
    {
        nf_refus("le nouvel instantané est plus pauvre que l'actuel ({$nb_inserts} INSERT contre {$avant}).\n"
            ."  La base de la démo a probablement été rechargée depuis un instantané périmé pendant le\n"
            ."  peuplement — le cron de remise à zéro passe toutes les 15 minutes. Repeuple la base\n"
            ."  (php tools/seed-demo.php) puis relance, ou passe --force si la perte est voulue.");
    }
}

file_put_contents(DEMO_OUT, $out);
printf("install/demo.sql écrit (%d tables, %d INSERT, config démo incluse).\n", count(CONTENT_TABLES) + 4, $nb_inserts);
