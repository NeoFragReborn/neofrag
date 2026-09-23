<?php
declare(strict_types=1);

/**
 * NeoFrag Reborn — profils d'installation proposés par l'étape « Modules ».
 *
 * Ce fichier ne décrit QUE la présentation d'un profil : son titre, sa phrase, son icône. Sa
 * composition, elle, n'est PAS écrite ici : chaque addon déclare lui-même les profils qui le
 * pré-cochent, via `'presets' => [...]` dans son `__info()`.
 *
 * C'est un choix délibéré, et c'est ce qui change par rapport à la version de juin 2026 (retirée
 * du code le 2026-06-08, `c4aa961`) : elle recopiait ici la liste des modules de chaque profil,
 * appariés à la main avec leurs widgets. Deux listes à tenir en parallèle du manifeste, donc deux
 * occasions de diverger — et un addon nouveau était invisible des profils jusqu'à ce que quelqu'un
 * pense à l'y ajouter. Désormais un addon qui se déclare `'presets' => ['gaming']` apparaît dans le
 * profil Gaming sans que ce fichier ne bouge.
 *
 * Le CŒUR n'est jamais un choix : install/seed.sql l'installe toujours, quel que soit le profil.
 * Les dépendances déclarées (`requires`) sont fermées automatiquement à la validation
 * (Installer::close_requires) — cocher « Palmarès » sans « Équipes » ne peut pas produire un site
 * cassé.
 *
 * Ordre du tableau = ordre d'affichage. Le premier est pré-sélectionné.
 *
 * @return array<string,array{title:string,tagline:string,icon:string,tag:?string}>
 *   `tag` = l'étiquette cherchée dans les déclarations. NULL a un sens particulier :
 *     - 'complete'  => tag NULL et clé 'complete' : tout ce qui n'est pas cœur ;
 *     - 'core'      => tag NULL et clé 'core'     : rien de plus que le cœur.
 */

// Titres et phrases en français, la langue source : `lang()` les rend dans celle de l'assistant
// (install/lib/langue.php, traductions dans install/langs/).
return [
	'complete' => [
		'title'   => lang('Complet'),
		'tagline' => lang('Tous les modules, widgets et thèmes livrés sont installés et activés. Vous désactivez ensuite ce dont vous n’avez pas besoin depuis l’administration. Le choix le plus sûr si vous hésitez.'),
		'icon'    => '🧩',
		'tag'     => NULL,
	],

	'gaming' => [
		'title'   => lang('Gaming / eSport'),
		'tagline' => lang('Le socle communautaire plus l’attirail compétitif : équipes, matchs, événements, recrutement, palmarès, partenaires et gamification.'),
		'icon'    => '🎮',
		'tag'     => 'gaming',
	],

	'communaute' => [
		'title'   => lang('Communauté'),
		'tagline' => lang('Actualités, forum de discussion et galeries. De quoi faire vivre une communauté, sans l’attirail esport.'),
		'icon'    => '💬',
		'tag'     => 'communaute',
	],

	'core' => [
		'title'   => lang('Cœur seul'),
		'tagline' => lang('Rien que l’essentiel : pages, commentaires, menu, contact, membres et messagerie. Vous ajouterez le reste depuis le marketplace, quand vous en aurez besoin.'),
		'icon'    => '🪶',
		'tag'     => NULL,
	],
];
