<?php
declare(strict_types=1);
/**
 * R1.8 — Mapping permission → checks de sanction.
 *
 * Pour chaque permission applicative, liste des checks à effectuer dans la library moderation.
 * Si UN check renvoie "bloqué", la permission est refusée même si le rôle l'autorise.
 *
 * Format :
 *   'module.action' => [
 *       'is_muted'           => 'scope',     // appel is_muted($user_id, 'scope')
 *       'is_banned'          => 'scope',
 *       'is_banned_global'   => true,        // alias is_banned($user_id, 'global')
 *       'can_upload_files'   => true,        // appel can_upload_files($user_id) (négation)
 *       'can_post_links'     => true,
 *       'can_change_avatar'  => true,
 *       'can_change_signature' => true,
 *       'can_comment'        => true,
 *   ]
 *
 * Le nom de scope DOIT être présent dans la enum `nf_sanctions.scope`
 * (global, forum, talks, comments, wiki, gallery, guestbook, profile, bugtracker, recruits).
 *
 * Si une permission n'est PAS dans ce mapping → aucune sanction ne la bloque (only-roles).
 *
 * Jusqu'au 2026-10-09, aucun formulaire ne consultait cette carte : seuls le muet du forum et de la messagerie et les
 * pièces jointes de la messagerie s'appliquaient. Chaque point d'écriture d'un membre l'appelle désormais
 * (`$this->moderation->is_blocked_for()`), et ce qu'il publie passe par `lien_refuse()` pour la restriction des liens.
 */

return [
	// === FORUM ===
	'forum.category_write'   => ['is_muted' => 'forum',    'is_banned' => 'forum',    'is_banned_global' => TRUE],
	'forum.message_post'     => ['is_muted' => 'forum',    'is_banned' => 'forum',    'is_banned_global' => TRUE],
	'forum.message_attach'   => ['can_upload_files' => TRUE, 'is_muted' => 'forum'],
	'forum.message_edit'     => ['is_muted' => 'forum',    'is_banned' => 'forum'],

	// === TALKS ===
	'talks.write'            => ['is_muted' => 'talks',    'is_banned' => 'talks',    'is_banned_global' => TRUE],
	'talks.attach'           => ['can_upload_files' => TRUE, 'is_muted' => 'talks'],
	'talks.create_conversation' => ['is_muted' => 'talks', 'is_banned' => 'talks',    'is_banned_global' => TRUE],

	// === COMMENTS ===
	'comments.write'         => ['can_comment' => TRUE,    'is_banned' => 'comments', 'is_banned_global' => TRUE],

	// Le MUET empêche de publier, où que ce soit : il ne valait que pour le forum, la messagerie et les commentaires, et
	// un membre muet signait le livre d'or, déposait une annonce ou ouvrait un ticket (audit du 2026-10-09). Sa portée
	// est celle du module ; un muet de tout le site (`global`) les ferme tous.

	// === GALLERY ===
	'gallery.upload'         => ['can_upload_files' => TRUE, 'is_muted' => 'gallery', 'is_banned' => 'gallery', 'is_banned_global' => TRUE],
	'gallery.write'          => ['is_muted' => 'gallery', 'is_banned' => 'gallery', 'is_banned_global' => TRUE],

	// (Le wiki et la médiathèque ne s'écrivent que depuis l'administration : aucun membre n'y écrit.)

	// === GUESTBOOK ===
	'guestbook.write'        => ['is_muted' => 'guestbook', 'is_banned' => 'guestbook', 'is_banned_global' => TRUE],

	// === PETITES ANNONCES ===
	'classifieds.write'      => ['is_muted' => 'global', 'is_banned_global' => TRUE],

	// === ÉDITEUR RICHE : une image collée ou déposée, envoyée au site ===
	'editor.image_upload'    => ['can_upload_files' => TRUE, 'is_muted' => 'global', 'is_banned_global' => TRUE],

	// === BUGTRACKER ===
	'bugtracker.write'       => ['is_muted' => 'bugtracker', 'is_banned' => 'bugtracker', 'is_banned_global' => TRUE],

	// === RECRUITS ===
	'recruits.recruit_postulate' => ['is_muted' => 'recruits', 'is_banned' => 'recruits', 'is_banned_global' => TRUE],

	// === USER PROFILE ===
	// Le bannissement de portée « profile » ferme toute la page « Modifier mon profil » ; les restrictions ferment leur
	// formulaire : l'avatar et la couverture sont des envois de fichiers, les liens du profil sont des liens externes.
	'user.profile_edit'      => ['is_muted' => 'profile', 'is_banned' => 'profile', 'is_banned_global' => TRUE],
	'user.profile_avatar'    => ['can_change_avatar' => TRUE,    'can_upload_files' => TRUE, 'is_banned' => 'profile'],
	'user.profile_cover'     => ['can_upload_files' => TRUE,     'is_banned' => 'profile'],
	'user.profile_links'     => ['can_post_links' => TRUE,       'is_banned' => 'profile'],
	'user.profile_signature' => ['can_change_signature' => TRUE, 'is_banned' => 'profile'],
];
