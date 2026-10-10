<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\Talks;

use NF\NeoFrag\Addons\Module;

class Talks extends Module
{
	public function __init()
	{
		// ID du salon "Publique" (talk type=public le plus actif). Configurable via setting.
		$default_public_id = isset($this->config->talks_bot_public_id)
				? (int)$this->config->talks_bot_public_id
				: 2;

		// Listener forum.topic.created
		$this->events->on('forum.topic.created', function($payload) use ($default_public_id){
			$url = \url('forum/topic/'.(int)$payload['topic_id'].'/'.\url_title($payload['title']));
			$msg = '📝 '.$this->lang('Nouveau sujet').' : '.$payload['title'].' '.$url;
			$this->_post_system_message($default_public_id, $msg);
		});

		// Listener forum.post.created (uniquement pour les replies, pas les starters)
		// → Skip car bruit. Seul le topic.created est posté.

		// Listener news.published (émis par modules/news/controllers/admin.php).
		// NB : les listeners articles.published / events.published / recruits.opened
		// ont été retirés car aucun module ne fire ces events (code mort). À
		// réintroduire avec le fire() correspondant si le feed doit les couvrir.
		$this->events->on('news.published', function($payload) use ($default_public_id){
			$url = \url('news/'.(int)$payload['news_id'].'/'.\url_title($payload['title']));
			$msg = '📰 '.$this->lang('Nouvelle actu').' : '.$payload['title'].' '.$url;
			$this->_post_system_message($default_public_id, $msg);
		});

		// Notif email aux participants offline depuis 5+ min (T5)
		$this->events->on('talks.message.created', function($payload){
			if (empty($payload['talk_id']) || !empty($payload['is_system']))
			{
				return;
			}

			$recipients = $this->db->select('p.user_id', 'u.username', 'u.email')
								   ->from('nf_talks_participants p')
								   ->join('nf_user u', 'u.id = p.user_id AND u.deleted = "0"')
								   ->join('nf_session s', 's.user_id = u.id', 'LEFT')
								   ->where('p.talk_id', (int)$payload['talk_id'])
								   ->where('p.archived_at', NULL)
								   ->where('p.notify_email', 1)
								   ->where('p.user_id !=', (int)$payload['user_id'])
								   ->where('u.email !=', '')
								   ->where('(s.last_activity IS NULL OR s.last_activity < DATE_SUB(NOW(), INTERVAL 5 MINUTE))')
								   ->group_by('p.user_id')
								   ->get();

			if (empty($recipients))
			{
				return;
			}

			$talk = $this->db->select('t.name', 't.type', 'u.username as author')
							 ->from('nf_talks t')
							 ->join('nf_user u', 'u.id = '.(int)$payload['user_id'])
							 ->where('t.talk_id', (int)$payload['talk_id'])
							 ->row();
			if (empty($talk) || !is_array($talk)) return;

			// Adresse ABSOLUE : elle part dans un courriel, où un lien relatif se résout contre le
			// domaine du client de messagerie (même règle que `user.registration`, relevé le 2026-10-04).
			$talk_url = \absolute_url('talks/'.(int)$payload['talk_id'].'/'.\url_title($talk['name']));

			// Le membre qui ne veut plus de courriel pour ses messages (préférences, chantier A, étape A4).
			$notifications = \NF\NeoFrag\Addons\Module::__load(\NeoFrag(), ['notifications']);

			foreach ($recipients as $r)
			{
				if ($notifications && !$notifications->veut((int) $r['user_id'], 'talks_message', 'email'))
				{
					continue;
				}

				try
				{
					// Dans la langue du destinataire, pas de celui qui écrit (audit du 2026-10-09).
					nf_dans_la_langue_du_membre((int) $r['user_id'], fn () => $this->email->template('talks.new_message', [
									'username'  => $r['username'],
									'talk_name' => $talk['name'],
									'talk_url'  => $talk_url,
									'author'    => $talk['author']
								])
								->to($r['email'])
								->send());
				}
				catch (\Throwable $e)
				{
					// Silent fail
				}
			}
		});

		// Notification in-site (cloche) aux participants offline (anti-spam : 1 non-lue / conversation).
		$this->events->on('talks.message.created', function($payload){
			if (empty($payload['talk_id']) || !empty($payload['is_system']))
			{
				return;
			}

			// $this->module() depuis une classe Module mis-résout le type d'addon → loader explicite.
			$notifications = \NF\NeoFrag\Addons\Module::__load(\NeoFrag(), ['notifications']);

			if (!$notifications)
			{
				return;
			}

			$recipients = $this->db
				->select('p.user_id')
				->from('nf_talks_participants p')
				->join('nf_session s', 's.user_id = p.user_id', 'LEFT')
				->where('p.talk_id', (int)$payload['talk_id'])
				->where('p.archived_at', NULL)
				->where('p.user_id !=', (int)$payload['user_id'])
				->where('(s.last_activity IS NULL OR s.last_activity < DATE_SUB(NOW(), INTERVAL 5 MINUTE))')
				->group_by('p.user_id')
				->get(FALSE);

			if (empty($recipients))
			{
				return;
			}

			$talk = $this->db
				->select('t.name', 'u.username as author')
				->from('nf_talks t')
				->join('nf_user u', 'u.id = '.(int)$payload['user_id'])
				->where('t.talk_id', (int)$payload['talk_id'])
				->row();

			if (empty($talk) || !is_array($talk))
			{
				return;
			}

			$url = 'talks/'.(int)$payload['talk_id'].'/'.\url_title($talk['name']);

			foreach ($recipients as $r)
			{
				$notifications->push_unique((int)$r['user_id'], 'talks_message', $this->lang('%s vous a envoyé un message', $talk['author']), $url, (int)$payload['user_id']);
			}
		});
	}

	private function _post_system_message($talk_id, $message)
	{
		try
		{
			$this->db->insert('nf_talks_messages', [
				'talk_id' => (int)$talk_id,
				'user_id' => NULL,
				'message' => $message
			]);
			$this->db	->where('talk_id', (int)$talk_id)
						->update('nf_talks', 'updated_at = CURRENT_TIMESTAMP');
		}
		catch (\Throwable $e)
		{
			// Silent fail : ne pas crasher l'event source si la chatbox cible est cassée
		}
	}

	protected function __info()
	{
		return [
			'title'       => $this->lang('Discussion'),
			'description' => $this->lang('Talkbox / chat rapide entre membres connectés.'),
			'icon'        => 'far fa-comment',
			'link'        => 'https://neofrag-reborn.xyz',
			'author'      => 'Michaël BILCOT & Jérémy VALENTIN <contact@neofrag.com>',
			'license'     => 'LGPLv3 <https://www.gnu.org/licenses/lgpl-3.0.html>',
			// Decouplage du paquet : cf. tools/check-addon-declarations.php.
			'core'        => TRUE,
			'presets'     => [],
			'requires'    => [],
			'admin'       => TRUE,
			'version'     => '1.0',
			'depends'     => [
				'neofrag' => '0.2.0'
			],
			'routes'      => [
				// Routes user (Phase 2 unification + archives/delete user-side)
				''                                => 'index',
				'new'                             => '_new',
				'archives'                        => '_archives',
				'trash'                           => '_trash',
				'staff-chat-send'                 => '_staff_chat_send',
				'{id}/{url_title}{page}'          => '_view',
				'{id}/{url_title}/invite'         => '_invite',
				'{id}/{url_title}/leave'          => '_leave',
				'{id}/{url_title}/archive'        => '_archive',
				'{id}/{url_title}/unarchive'      => '_unarchive',
				'{id}/{url_title}/delete'         => '_delete',
				'{id}/{url_title}/restore'        => '_restore',
				'search'                          => '_search',
				'piece-jointe/{id}'               => '_piece_jointe',

				// Routes admin (existantes + ajouts T6)
				'admin{pages}'                    => 'index',
				'admin/{id}/{url_title*}'         => '_edit',
				'admin/delete/{id}/{url_title*}'  => '_admin_delete'
			]
		];
	}

	public function permissions()
	{
		return [
			'default' => [
				'access' => [[
					'title'  => $this->lang('Discussion'),
					'icon'   => 'far fa-comment',
					'access' => [
						'create_conversation' => [
							'title' => $this->lang('Créer une conversation'),
							'icon'  => 'fas fa-plus',
							'admin' => FALSE
						],
						'admin_moderate' => [
							'title' => $this->lang('Modérer toutes conversations'),
							'icon'  => 'fas fa-user-shield',
							'admin' => TRUE
						]
					]
				]]
			],
			'talk' => [
				'get_all' => function(){
					return NeoFrag()->db->select('talk_id', 'name')->from('nf_talks')->where('talk_id >', 1)->get();
				},
				'check'   => function($talk_id){
					if ($talk_id > 1 && ($talk = NeoFrag()->db->select('name')->from('nf_talks')->where('talk_id', $talk_id)->row()) !== [])
					{
						return $talk;
					}
				},
				'init'    => [
					'read'   => [
					],
					'write'  => [
						['visitors', FALSE]
					],
					'delete' => [
						['admins', TRUE]
					]
				],
				'access'  => [
					[
						'title'  => $this->lang('Discussion'),
						'icon'   => 'far fa-comment',
						'access' => [
							'read' => [
								'title' => $this->lang('Lire'),
								'icon'  => 'far fa-eye'
							],
							'write' => [
								'title' => $this->lang('Écrire'),
								'icon'  => 'fas fa-reply'
							]
						]
					],
					[
						'title'  => $this->lang('Modération'),
						'icon'   => 'fas fa-user',
						'access' => [
							'delete' => [
								'title' => $this->lang('Supprimer un message'),
								'icon'  => 'far fa-trash-alt'
							]
						]
					]
				]
			]
		];
	}

	/**
	 * La messagerie dans le menu de l'espace membre (User::menu_espace(), chantier A), avec le nombre de
	 * messages non lus — comme le widget « Espace membre » le montrait déjà.
	 *
	 * @return list<array<string, mixed>>
	 */
	public function espace_membre($user): array
	{
		/** @var \NF\Modules\Talks\Models\Talks $talks */
		$talks = $this->model('talks');

		return [['url' => 'talks', 'titre' => (string) $this->lang('Messagerie'), 'icone' => 'far fa-envelope', 'badge' => (int) $talks->get_unread_count((int) $user->id), 'compact' => TRUE, 'ordre' => 10]];
	}

	/**
	 * Les notifications que ce module envoie, pour les préférences de chaque membre (Notifications::types(),
	 * chantier A, étape A4).
	 *
	 * @return list<array<string, mixed>>
	 */
	public function types_de_notification(): array
	{
		return [
			['type' => 'talks_message', 'titre' => (string) $this->lang('Un nouveau message privé'), 'email' => TRUE, 'ordre' => 10],
		];
	}
}
