<?php
declare(strict_types=1);

/**
 * NeoFrag Reborn — manifeste de découplage / packaging (3 tiers). Voir les notes du mainteneur.
 *
 * Tier 0 — Noyau : tout ce qui n'est listé NI dans 'identity' NI dans 'optional'. Toujours installé,
 *   non désinstallable, jamais packagé. Infra (access, admin, addons, settings, user, members,
 *   live_editor, tools, emails, media, search, statistics, monitoring, trash, reactions, revisions,
 *   notifications, moderation) + CMS de base (pages, comments, contact, menu).
 *
 * Tier 1 — Identité : pré-coché par les presets (Gaming / Communauté) à l'installation, désinstallable.
 * Tier 2 — À la carte : jamais pré-coché ; via le marketplace ou coche manuelle.
 *
 * Le packaging marketplace (tools/package-addons.php) zippe Tier 1 ∪ Tier 2 (tout le désinstallable).
 * Le futur seed core-only et l'étape « Modules » de l'installeur consommeront les tiers.
 *
 * ⚠ Ne pas faire passer en Tier 0/optionnel les addons d'infra ci-dessus : le site doit booter sans
 * les Tier 1/2. ⚠ Les thèmes granite/blockcraft/forge référencent des widgets (talks/slider…) dans
 * leurs dispositions : la répartition fine des widgets par tier est différée (tous en 'optional' ici).
 */

// Répartition figée 2026-06-06 (cœur lean upstream-based + presets installeur + marketplace distant).
// CLÔTURE DES DÉPENDANCES : chaque widget Tier 1/2 est apparié au tier de son module ; les widgets
// transverses/réf-thème (talks, slider) restent en CŒUR → aucun orphelin quel que soit le preset.
return [
	// ── Tier 1 — Identité (pré-cochés par preset Gaming/Communauté, désinstallables) ─────────
	'identity' => [
		'module' => [
			// Communauté
			'news', 'forum', 'gallery',
			// eSport / gaming
			'teams', 'events', 'calendar', 'awards', 'recruits', 'games', 'partners',
			// Engagement (identité gaming)
			'gamification',
		],
		// Widgets appariés aux modules ci-dessus + widgets gaming à config (sans module → jamais orphelins).
		'widget' => [
			'news', 'forum', 'gallery', 'teams', 'events', 'awards', 'recruits', 'partners',
			'calendar', 'about',                       // about → tables teams
			'gameserver', 'steam', 'teamspeak', 'twitch', // gaming à réglages, pas de module
		],
	],

	// ── Tier 2 — À la carte (marketplace distant / coche manuelle) ──────────────────────────
	'optional' => [
		'module' => [
			// Contenu avancé
			'articles', 'wiki', 'faq', 'downloads', 'classifieds', 'surveys', 'guestbook',
			'bugtracker', 'links',
			// Monétisation / diffusion
			'shop', 'donations', 'payments', 'ads', 'newsletter', 'feeds',
		],
		// Widgets appariés aux modules Tier 2 ci-dessus.
		'widget' => [
			'ads', 'articles', 'donations', 'downloads', 'guestbook', 'links', 'newsletter', 'surveys',
		],
		// Thèmes additionnels (admin + nebula restent cœur ; vitrine = hors distribution publique).
		// granite/blockcraft/forge référencent talks/slider → OK car talks/slider sont en CŒUR.
		'theme' => [
			'granite', 'blockcraft', 'forge', 'extend',
		],
		// NB : les connecteurs sociaux (discord/github/google) sont en CŒUR (décision 2026-06-06 :
		// « login avec Discord/Google/GitHub » par défaut, gaming oblige). Pas listés ici.
	],

	// CŒUR (Tier 0, implicite = ni identity ni optional) : infra + CMS base + talks + slider +
	// widgets génériques (header, navigation, module, user, breadcrumb, search, socials, html,
	// members, latest_comments, copyright, clock, discord, video, talks, slider).
];
