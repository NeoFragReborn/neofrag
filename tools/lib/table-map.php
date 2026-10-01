<?php
declare(strict_types=1);
require_once __DIR__.'/outil.php';

/**
 * NeoFrag Reborn — mapping table → module (ownership) pour le SQL-par-module.
 *
 * « Possède » = la table est EXCLUSIVE au module (lui seul la crée/manipule). Une table
 * lue par plusieurs modules mais conceptuellement partagée (nf_user, nf_file, nf_comment,
 * nf_reactions, nf_users_groups…) reste CORE. Désinstaller un module exécute son
 * install/uninstall.sql → DROP de SES tables (données perdues, comme donations/shop).
 *
 * Consommé par tools/extract-module-sql.php (génère les install/uninstall.sql embarqués).
 * Garde-fou : modules ∪ core doit recouvrir EXACTEMENT les tables vives — toute table
 * non classée fait échouer le générateur (anti-oubli lors d'ajouts futurs).
 *
 * Vérifié 2026-06-05 contre la base vive (126 tables) + workflow d'audit adversarial
 * (usage exclusif par module, FK, migrations). nf_user_points rattaché à gamification
 * (exclusif, créé par 2026_06_03_gamification_points). Tables ordonnées parent→enfant
 * (le générateur inverse pour le DROP) ; FK désactivées le temps du batch de toute façon.
 */

return [
    'modules' => [
        // ── Tier 1 — Identité ────────────────────────────────────────────────
        'news'         => ['nf_news', 'nf_news_lang', 'nf_news_categories', 'nf_news_categories_lang'],
        'forum'        => ['nf_forum', 'nf_forum_lang', 'nf_forum_categories', 'nf_forum_categories_lang', 'nf_forum_identities', 'nf_forum_prefixes', 'nf_forum_prefixes_lang', 'nf_forum_topics', 'nf_forum_messages', 'nf_forum_attachments', 'nf_forum_mentions', 'nf_forum_read', 'nf_forum_topics_read', 'nf_forum_track', 'nf_forum_url'],
        'gallery'      => ['nf_gallery', 'nf_gallery_lang', 'nf_gallery_categories', 'nf_gallery_categories_lang', 'nf_gallery_images'],
        'teams'        => ['nf_teams', 'nf_teams_lang', 'nf_teams_roles', 'nf_teams_users'],
        'events'       => ['nf_events', 'nf_events_types', 'nf_events_participants', 'nf_events_matches', 'nf_events_matches_opponents', 'nf_events_matches_rounds'],
        'calendar'     => ['nf_calendar_events'],
        'awards'       => ['nf_awards'],
        'recruits'     => ['nf_recruits', 'nf_recruits_fields', 'nf_recruits_candidacies', 'nf_recruits_candidacies_votes'],
        'games'        => ['nf_games', 'nf_games_lang', 'nf_games_maps', 'nf_games_modes'],
        'partners'     => ['nf_partners', 'nf_partners_lang'],
        'gamification' => ['nf_user_points', 'nf_karma', 'nf_points_log', 'nf_vip'],

        // ── Tier 2 — À la carte ──────────────────────────────────────────────
        'api'          => ['nf_api_tokens', 'nf_api_events'],
        'discord'      => ['nf_discord_channels', 'nf_discord_links', 'nf_discord_logs', 'nf_discord_roles', 'nf_discord_state'],
        'articles'     => ['nf_articles', 'nf_articles_lang', 'nf_articles_categories', 'nf_articles_categories_lang', 'nf_articles_series', 'nf_articles_series_lang'],
        'wiki'         => ['nf_wiki_pages', 'nf_wiki_revisions'],
        'faq'          => ['nf_faq_categories', 'nf_faq_questions'],
        'quotes'       => ['nf_quotes_categories', 'nf_quotes'],
        'recipes'      => ['nf_recipes_categories', 'nf_recipes'],
        'glossary'     => ['nf_glossary_categories', 'nf_glossary_terms'],
        'places'       => ['nf_places_categories', 'nf_places'],
        'webradio'     => ['nf_webradio_shows'],
        'sandbox'      => ['nf_sandbox_drafts'],
        'downloads'    => ['nf_downloads', 'nf_downloads_categories'],
        'classifieds'  => ['nf_classifieds', 'nf_classifieds_categories'],
        'surveys'      => ['nf_surveys', 'nf_surveys_options', 'nf_surveys_votes'],
        'guestbook'    => ['nf_guestbook'],
        'bugtracker'   => ['nf_bug_tickets', 'nf_bug_comments'],
        'links'        => ['nf_links', 'nf_links_categories'],
        'shop'         => ['nf_shop_items', 'nf_shop_purchases'],
        'donations'    => ['nf_donations_campaigns', 'nf_donations'],
        'payments'     => ['nf_payment_packs', 'nf_payments'],
        'ads'          => ['nf_ads'],
        'newsletter'   => ['nf_newsletter_campaigns', 'nf_newsletter_subscribers', 'nf_newsletter_queue', 'nf_newsletter_templates'],
        'files'        => ['nf_files_directories'],
        'emojis'       => ['nf_custom_emojis'],

        // Sans table propre (lit nf_news/nf_articles) → pas de SQL embarqué.
        'feeds'        => [],
    ],

    // Tout le reste : infra + modules core (comments/reactions/revisions/notifications/
    // moderation/pages/menu/statistics/talks/slider/media/webhooks) + données utilisateur.
    'core' => [
        'nf_addon', 'nf_addon_type',
        'nf_user', 'nf_user_auth', 'nf_user_profile', 'nf_user_token', 'nf_user_totp_recovery',
        // Champs de profil definis par l'administrateur : du coeur, comme le profil
        // lui-meme — ils prolongent nf_user_profile et vivent avec le compte.
        'nf_user_fields', 'nf_user_fields_values',
        'nf_users_groups', 'nf_users_roles',
        'nf_groups', 'nf_groups_lang', 'nf_groups_roles',
        'nf_roles', 'nf_roles_lang', 'nf_role_permissions',
        'nf_settings', 'nf_dispositions', 'nf_widgets',
        'nf_pages', 'nf_pages_lang', 'nf_pages_instances', 'nf_menus', 'nf_menus_items',
        'nf_comment', 'nf_reactions', 'nf_revisions', 'nf_notifications', 'nf_subscriptions',
        'nf_sanctions', 'nf_reports', 'nf_reports_attachments_snapshot',
        'nf_talks', 'nf_talks_participants', 'nf_talks_messages', 'nf_talks_attachments',
        'nf_media', 'nf_file', 'nf_slider_slides', 'nf_statistics', 'nf_webhooks',
        'nf_email_templates', 'nf_email_template_translations',
        'nf_i18n', 'nf_tracking', 'nf_audit_log', 'nf_cookie_consent',
        'nf_ip_banlist', 'nf_rate_limit', 'nf_session', 'nf_session_history',
        'nf_log_db', 'nf_log_i18n', 'nf_migrations', 'nf_addon_migrations',
    ],
];
