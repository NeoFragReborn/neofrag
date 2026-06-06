<?php
declare(strict_types=1);

/**
 * NeoFrag Reborn — presets de l'étape installeur « Modules ».
 *
 * Chaque preset = un sous-ensemble du Tier 1 (cf. tools/addons-manifest.php) pré-coché à
 * l'install + des réglages. Le cœur (Tier 0) est TOUJOURS installé (seed). Le preset
 * « allume » des modules Tier 1 (locaux) ; le Tier 2 reste au marketplace distant (Phase 2).
 *
 * CONTRAINTE ZÉRO ORPHELIN : chaque module est listé avec SES widgets appariés (clé =>
 * widgets). En décochant un module, on n'installe ni le module ni ses widgets. Les widgets
 * sans module (gameserver/steam/teamspeak/twitch, config-only) sont en `extra_widgets`.
 * talks/slider sont en cœur ; la garde Addons\Widget::output() masque tout widget résiduel.
 *
 * `default_page` : route de la page d'accueil — DOIT pointer un module installé sinon 404
 *   (cf. core/output.php). apply_preset() recalcule une valeur sûre selon les modules retenus.
 * `welcome_page` : crée une page « Bienvenue » (module pages, cœur) servie en page d'accueil.
 *
 * Ordre du tableau = ordre d'affichage dans le wizard. Premier = pré-sélectionné.
 *
 * INVARIANT (vérifié par Installer::assert_presets_in_manifest) : chaque module/widget cité
 * ci-dessous appartient au Tier 1 (`identity`) de tools/addons-manifest.php — jamais au cœur
 * (déjà installé) ni au Tier 2 (marketplace).
 */

return [
	'community' => [
		'title'        => 'Communauté',
		'tagline'      => 'Actualités, forum de discussion et galerie média. Le socle d\'un site communautaire.',
		'icon'         => '💬',
		'modules'      => [
			'news'    => ['news'],
			'forum'   => ['forum'],
			'gallery' => ['gallery'],
		],
		'extra_widgets' => [],
		'welcome_page'  => false,
	],

	'gaming' => [
		'title'        => 'Gaming / eSport',
		'tagline'      => 'Tout le Tier 1 : équipes, matchs, événements, recrutement, palmarès, partenaires, gamification.',
		'icon'         => '🎮',
		// Tous les modules identité, appariés à leurs widgets (about lit nf_teams).
		'modules'      => [
			'news'         => ['news'],
			'forum'        => ['forum'],
			'gallery'      => ['gallery'],
			'teams'        => ['teams', 'about'],
			'events'       => ['events'],
			'calendar'     => ['calendar'],
			'awards'       => ['awards'],
			'recruits'     => ['recruits'],
			'games'        => [],
			'partners'     => ['partners'],
			'gamification' => [],
		],
		// Widgets gaming sans module (réglages seuls) → jamais orphelins.
		'extra_widgets' => ['gameserver', 'steam', 'teamspeak', 'twitch'],
		'welcome_page'  => false,
	],

	'simple' => [
		'title'        => 'Site simple',
		'tagline'      => 'Un site / blog propre : actualités + une page d\'accueil de bienvenue. Pas de forum ni galerie.',
		'icon'         => '📄',
		'modules'      => [
			'news' => ['news'],
		],
		'extra_widgets' => [],
		'welcome_page'  => true,
	],
];
