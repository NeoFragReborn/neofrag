<?php
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

	// === GALLERY ===
	'gallery.upload'         => ['can_upload_files' => TRUE, 'is_banned' => 'gallery', 'is_banned_global' => TRUE],
	'gallery.write'          => ['is_banned' => 'gallery', 'is_banned_global' => TRUE],

	// === WIKI ===
	'wiki.edit'              => ['is_banned' => 'wiki',    'is_banned_global' => TRUE],

	// === GUESTBOOK ===
	'guestbook.write'        => ['is_banned' => 'guestbook', 'is_banned_global' => TRUE],

	// === MEDIA ===
	'media.upload'           => ['can_upload_files' => TRUE, 'is_banned_global' => TRUE],

	// === BUGTRACKER ===
	'bugtracker.write'       => ['is_banned' => 'bugtracker', 'is_banned_global' => TRUE],

	// === RECRUITS ===
	'recruits.recruit_postulate' => ['is_banned' => 'recruits', 'is_banned_global' => TRUE],

	// === USER PROFILE ===
	'user.profile_avatar'    => ['can_change_avatar' => TRUE,    'is_banned' => 'profile'],
	'user.profile_signature' => ['can_change_signature' => TRUE, 'is_banned' => 'profile'],
];
