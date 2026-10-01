<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * Module API — l'API REST du site (2026-10-01). Elle sert aux programmes qui parlent au
 * site sans navigateur : en premier lieu le bot Discord, puis toute intégration.
 *
 * Un programme s'authentifie par une CLÉ D'ACCÈS créée dans l'administration : stockée hachée,
 * montrée une seule fois, révocable, limitée à des droits choisis et à un nombre de requêtes par
 * minute. Les adresses sont versionnées (`/api/v1/…`) et rendent du JSON.
 */

namespace NF\Modules\Api;

use NF\NeoFrag\Addons\Module;

class Api extends Module
{
	/*
	 * Les droits d'une clé : des identifiants techniques, stables, que les programmes connaissent.
	 * Pour les AFFICHER : scope_labels().
	 */
	const SCOPES = ['members:read', 'forum:read', 'forum:write', 'events:read', 'discord:bot', 'bugtracker:read', 'bugtracker:write'];

	/** Requêtes permises par minute et par clé. */
	const PAR_MINUTE = 120;

	/*
	 * Le fil d'événements (étape 2). Un programme — le bot Discord — demande « ce qui
	 * s'est passé depuis l'événement n° X » au lieu que le site le contacte : il n'ouvre aucun port.
	 * Le journal ne garde que des IDENTIFIANTS : le contenu se lit par l'API au moment voulu, si bien
	 * qu'un message supprimé ne survit pas dans le journal.
	 *
	 * Chaque événement : les clés de sa charge qui sont gardées.
	 */
	const EVENEMENTS = [
		'forum.topic.created' => ['topic_id', 'message_id', 'forum_id', 'user_id', 'identity_id'],
		'forum.post.created'  => ['message_id', 'topic_id', 'forum_id', 'user_id', 'identity_id', 'is_starter'],
		'forum.post.edited'   => ['message_id', 'topic_id', 'forum_id', 'user_id', 'is_topic'],
		'forum.post.deleted'  => ['message_id', 'topic_id', 'forum_id', 'is_topic', 'hard_delete', 'deleted_by'],
		'forum.topic.split'   => ['source_topic_id', 'new_topic_id', 'message_ids', 'forum_id'],
		'forum.topics.merged' => ['source_topic_id', 'target_topic_id', 'forum_id'],
		'forum.topic.prefixed' => ['topic_id', 'forum_id', 'prefix_id'],
		// Le Bugtracker (point 7) : le bot Discord tient le fil de chaque ticket.
		'bugtracker.ticket.created'  => ['ticket_id', 'user_id', 'type'],
		'bugtracker.ticket.updated'  => ['ticket_id', 'status', 'type', 'fields'],
		'bugtracker.ticket.deleted'  => ['ticket_id'],
		'bugtracker.comment.created' => ['comment_id', 'ticket_id', 'user_id'],
		'bugtracker.comment.edited'  => ['comment_id', 'ticket_id'],
		'bugtracker.comment.deleted' => ['comment_id', 'ticket_id'],
		'user.groups.changed' => ['user_id'],
		// Un compte Discord lié ou délié (connexion par Discord, « Mes comptes liés », `/forum account`).
		'user.discord.linked'   => ['user_id', 'discord_id'],
		'user.discord.unlinked' => ['user_id', 'discord_id'],
	];

	/** Durée de vie d'un événement dans le journal, en jours. */
	const RETENTION = 30;

	/** La clé de l'API qui sert la requête en cours, s'il y en a une : l'événement en porte la trace. */
	public static ?int $cle_courante = NULL;

	/**
	 * Pendant une requête de l'API, c'est ce module qui sert la page : le forum n'y fait pas son
	 * `__init()`, et ses écouteurs ne sont pas posés. L'API écoute donc elle-même les événements que
	 * ses écritures déclenchent. Hors de l'API, c'est le forum qui les lui confie — jamais les deux à
	 * la fois, puisqu'un seul module sert une page.
	 */
	public function __init()
	{
		foreach (array_keys(self::EVENEMENTS) as $type)
		{
			$this->events->on($type, function ($charge) use ($type) {
				if (is_array($charge))
				{
					$this->consigner($type, $charge);
				}
			});
		}
	}

	/**
	 * Inscrit un événement au journal. Appelé par les modules qui émettent l'événement (le forum, les
	 * membres) : ce module-ci n'est pas chargé pendant leurs actions, et ne peut donc pas écouter.
	 *
	 * `source` dit d'où vient le changement : la clé de l'API qui l'a fait, ou NULL pour le site. Un
	 * programme qui écrit par l'API reconnaît ainsi ses propres écritures dans le fil, et ne les
	 * recopie pas une seconde fois.
	 */
	public function consigner(string $type, array $charge): void
	{
		if (!isset(self::EVENEMENTS[$type]))
		{
			return;
		}

		$gardees = array_intersect_key($charge, array_flip(self::EVENEMENTS[$type]));

		$this->db->insert('nf_api_events', [
			'type'    => $type,
			'payload' => json_encode($gardees, JSON_UNESCAPED_UNICODE),
			'source'  => self::$cle_courante,
		]);

		// Le ménage, de temps en temps plutôt qu'à chaque fois : un événement sur cent.
		if (random_int(1, 100) === 1)
		{
			$this->db->where('created_at <', date('Y-m-d H:i:s', time() - self::RETENTION * 86400))->delete('nf_api_events');
		}
	}

	protected function __info()
	{
		return [
			'title'       => $this->lang('API'),
			'description' => $this->lang('L’API REST du site : des clés d’accès pour les programmes (le bot Discord, une intégration), des adresses versionnées qui rendent du JSON.'),
			'icon'        => 'fas fa-plug',
			'link'        => 'https://neofr.ag',
			'author'      => 'NeoFrag Reborn',
			'license'     => 'LGPLv3 <https://neofr.ag/license>',
			'core'        => FALSE,
			'presets'     => ['communaute'],
			'requires'    => [],
			'admin'       => TRUE,
			'version'     => '1.0',
			'depends'     => ['neofrag' => '0.2.0'],
			'routes'      => [
				// L'API : un seul point d'entrée par version, qui aiguille lui-même selon l'adresse et
				// la méthode HTTP — une adresse inconnue doit rendre une erreur JSON, pas une page 404.
				'v1'                              => '_v1',
				'v1/{url_title*}'                 => '_v1',

				'admin'                           => 'index',
				'admin/add'                       => '_add',
				'admin/revoke/{id}/{url_title}'   => '_revoke',
			]
		];
	}

	public function permissions()
	{
		return [
			'default' => [
				'access' => [
					[
						'title'  => $this->lang('API'),
						'icon'   => 'fas fa-plug',
						'access' => [
							'manage' => ['title' => $this->lang('Gérer les clés d’accès'), 'icon' => 'fas fa-key', 'admin' => TRUE]
						]
					]
				]
			]
		];
	}

	/** Les libellés TRADUITS des droits d'une clé (mêmes clés que SCOPES). */
	public function scope_labels(): array
	{
		return [
			'members:read' => (string) $this->lang('Lire les membres et les groupes'),
			'forum:read'   => (string) $this->lang('Lire le forum'),
			'forum:write'  => (string) $this->lang('Écrire sur le forum au nom d’un membre ou d’un compte Discord'),
			'events:read'  => (string) $this->lang('Suivre le fil d’événements'),
			'discord:bot'  => (string) $this->lang('Être le bot Discord du site (sa configuration, sa clé Discord comprise)'),
			'bugtracker:read'  => (string) $this->lang('Lire le Bugtracker'),
			'bugtracker:write' => (string) $this->lang('Écrire dans le Bugtracker : ouvrir un ticket, commenter, au nom d’un membre ou d’un compte Discord'),
		];
	}
}
