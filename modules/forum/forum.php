<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\Forum;

use NF\NeoFrag\Addons\Module;

class Forum extends Module
{

	/** Descripteurs de contenu — cf. Module::content_types(). */
	public function declare_content_types()
	{
		return [
			'forum-message' => [
				'table' => 'nf_forum_messages', 'pk' => 'message_id', 'author' => 'user_id',
				'reactable' => TRUE,
			],
		];
	}

	/** URL publique d'un message : ancre sur le message, dans le sujet qui le porte. */
	public function content_url($type, $id)
	{
		if ($type !== 'forum-message')
		{
			return '';
		}

		$topic_id = $this->db->select('topic_id')->from('nf_forum_messages')->where('message_id', (int) $id)->row();

		if (!$topic_id)
		{
			return '';
		}

		$title = $this->db->select('title')->from('nf_forum_topics')->where('topic_id', (int) $topic_id)->row();

		return $title ? 'forum/topic/'.(int) $topic_id.'/'.url_title($title).'#'.(int) $id : '';
	}

	/** Corbeille : type restaurable declare par le module lui-meme (cf. Trash::types()). */
	public function trash_types()
	{
		return [
			'forum' => [
				'label'   => 'Message forum', 'table' => 'nf_forum_messages',
				'pk'      => 'message_id', 'content' => 'message',
				'restore' => 'restore_message', 'purge' => 'hard_delete_message',
			],
		];
	}
	protected function __info()
	{
		return [
			'title'       => $this->lang('Forum'),
			'description' => $this->lang('Forum communautaire avec catégories, sous-forums, sujets épinglés et permissions.'),
			'icon'        => 'fas fa-comments',
			'link'        => 'https://neofr.ag',
			'author'      => 'Michaël BILCOT & Jérémy VALENTIN <contact@neofrag.com>',
			'license'     => 'LGPLv3 <https://neofr.ag/license>',
			// Decouplage du paquet : cf. tools/check-addon-declarations.php.
			'core'        => FALSE,
			'presets'     => ['communaute', 'gaming'],
			'requires'    => [],
			'admin'       => TRUE,
			'version'     => '1.0',
			'depends'     => [
				'neofrag' => '0.2.0'
			],
			'routes'      => [
				//Index
				'{id}/{url_title}{page}'                   => '_forum',
				'new/{id}/{url_title}'                     => '_new',
				'topic/{id}/{url_title}{page}'             => '_topic',
				'announce/{id}/{url_title}'                => '_topic_announce',
				'lock/{id}/{url_title}'                    => '_topic_lock',
				'topic/move/{id}/{url_title}'              => '_topic_move',
				'ajax/topic/move/{id}/{url_title}'         => '_topic_move',
				'message/edit/{id}/{url_title}'            => '_message_edit',
				'message/delete/{id}/{url_title}'          => '_message_delete',
				'mark-all-as-read/{id}/{url_title}'        => '_mark_all_as_read',
				'solution/{id}/{url_title}'                => '_solution',

				//Subscriptions (Phase 3)
				'topic/subscribe/{id}/{url_title}'         => '_subscribe',
				'topic/unsubscribe/{id}/{url_title}'       => '_unsubscribe',
				'subscriptions{page}'                      => '_subscriptions',

				//Search FT (Phase 8)
				'search'                                   => '_search',

				//Admin
				'admin/{id}/{url_title}'                   => '_edit',
				'admin/categories/add'                     => '_categories_add',
				'admin/prefixes'                           => '_prefixes',
				'admin/prefixes/add'                       => '_prefixes_add',
				'admin/prefixes/{id}/{url_title}'          => '_prefixes_edit',
				'admin/prefixes/delete/{id}/{url_title}'   => '_prefixes_delete',
				'admin/categories/{id}/{url_title}'        => '_categories_edit',
				'admin/categories/delete/{id}/{url_title}' => '_categories_delete',
				'admin/ajax/categories/move'               => '_categories_move',

				//Mod avancée (Phase 7-bis)
				'admin/topic/split/{id}/{url_title}'       => '_admin_topic_split',
				'admin/topic/merge/{id}/{url_title}'       => '_admin_topic_merge',
				'admin/trash{page}'                        => '_admin_trash',

				//Admin attachments (Phase 5-bis)
				'admin/attachments{page}'                  => '_admin_attachments',

				//Admin subscriptions (Phase 3-bis)
				'admin/subscriptions{page}'                => '_admin_subscriptions',

				//Admin mentions (Phase 4-bis)
				'admin/mentions{page}'                     => '_admin_mentions',

				//Mentions autocomplete (Phase 4-bis)
				'ajax/mentions/autocomplete'               => '_mentions_autocomplete',

				//Admin search reindex (Phase 8-bis)
				'admin/search-config'                      => '_admin_search_config'
			],
			'settings'    => function(){
				return $this->form2()
							->rule($this->form_number('topics_per_page')
										->title($this->lang('Sujets par page'))
										->value($this->config->forum_topics_per_page)
							)
							->rule($this->form_number('messages_per_page')
										->title($this->lang('Réponses par page'))
										->value($this->config->forum_messages_per_page)
							)
							->rule($this->form_checkbox('subscriptions_email')
										->title($this->lang('Notifier les abonnés par email'))
										->info($this->lang('Envoie un email aux utilisateurs abonnés à un sujet quand une nouvelle réponse est postée'))
										->value(!isset($this->config->forum_subscriptions_email) || $this->config->forum_subscriptions_email)
							)
							->rule($this->form_checkbox('mentions_email')
										->title($this->lang('Notifier les mentions @user par email'))
										->info($this->lang('Envoie un email à un utilisateur quand il est mentionné via @username dans un message'))
										->value(!isset($this->config->forum_mentions_email) || $this->config->forum_mentions_email)
							)
							->rule($this->form_text('attachments_mimes')
										->title($this->lang('Types MIME autorisés (pièces jointes)'))
										->info($this->lang('Liste séparée par virgules. Ex : image/jpeg,image/png,application/pdf'))
										->value($this->config->forum_attachments_mimes ?: 'image/jpeg,image/png,image/gif,image/webp,application/pdf,text/plain,application/zip')
							)
							->rule($this->form_number('attachments_size_max_kb')
										->title($this->lang('Taille max par pièce jointe (Ko)'))
										->value($this->config->forum_attachments_size_max_kb ?: 5120)
							)
							->rule($this->form_number('threading_max_depth')
										->title($this->lang('Profondeur max des réponses imbriquées'))
										->info($this->lang('Au-delà de cette profondeur, le bouton "Répondre" remet la réponse au niveau du topic. Default : 3'))
										->value($this->config->forum_threading_max_depth ?: 3)
							)
							->rule($this->form_checkbox('search_members_only')
										->title($this->lang('Recherche réservée aux membres'))
										->info($this->lang('Si activé, les visiteurs anonymes ne peuvent pas faire de recherche full-text dans le forum'))
										->value(!empty($this->config->forum_search_members_only))
							)
							->success(function($data){
								$this	->config('forum_topics_per_page',         $data['topics_per_page'])
										->config('forum_messages_per_page',       $data['messages_per_page'])
										->config('forum_subscriptions_email',     !empty($data['subscriptions_email']) ? 1 : 0)
										->config('forum_mentions_email',          !empty($data['mentions_email']) ? 1 : 0)
										->config('forum_attachments_mimes',       $data['attachments_mimes'])
										->config('forum_attachments_size_max_kb', max(1, (int)$data['attachments_size_max_kb']))
										->config('forum_threading_max_depth',     max(1, min(10, (int)$data['threading_max_depth'])))
										->config('forum_search_members_only',     !empty($data['search_members_only']) ? 1 : 0);
								notify($this->lang('Configuration modifiée'));
								refresh();
							});
			}
		];
	}

	public function permissions()
	{
		return [
			'category' => [
				'get_all' => function(){
					return NeoFrag()->db->select('category_id', 'title')->from('nf_forum_categories')->get();
				},
				'check'   => function($category_id){
					if (($category = NeoFrag()->db->select('title')->from('nf_forum_categories')->where('category_id', $category_id)->row()) !== [])
					{
						return $category;
					}
				},
				'init'    => [
					'category_read'     => [
						['visitors', TRUE]
					],
					'category_write'    => [
						['visitors', FALSE]
					],
					'category_modify'   => [
						['admins', TRUE]
					],
					'category_delete'   => [
						['admins', TRUE]
					],
					'category_announce' => [
						['admins', TRUE]
					],
					'category_lock'     => [
						['admins', TRUE]
					],
					'category_move'     => [
						['admins', TRUE]
					]
				],
				'access'  => [
					[
						'title'  => $this->lang('Catégorie'),
						'icon'   => 'fas fa-bars',
						'access' => [
							'category_read' => [
								'title' => $this->lang('Lire'),
								'icon'  => 'far fa-eye'
							],
							'category_write' => [
								'title' => $this->lang('Écrire'),
								'icon'  => 'fas fa-reply'
							]
						]
					],
					[
						'title'  => $this->lang('Modération'),
						'icon'   => 'fas fa-user',
						'access' => [
							'category_modify' => [
								'title' => $this->lang('Éditer un sujet / message'),
								'icon'  => 'fas fa-edit'
							],
							'category_delete' => [
								'title' => $this->lang('Supprimer un sujet / message'),
								'icon'  => 'far fa-trash-alt'
							],
							'category_announce' => [
								'title' => $this->lang('Mettre un sujet en annonce'),
								'icon'  => 'fas fa-flag'
							],
							'category_lock' => [
								'title' => $this->lang('Vérouiller un sujet'),
								'icon'  => 'fas fa-lock'
							],
							'category_move' => [
								'title' => $this->lang('Déplacer un sujet'),
								'icon'  => 'fas fa-reply fa-flip-horizontal'
							]
						]
					]
				]
			]
		];
	}

	public function __init()
	{
		if (!$this->url->admin && !$this->url->ajax)
		{
			$this->css('forum');
			$this->js('mentions');
		}

		// Le fil d'événements de l'API : le module api n'est pas chargé pendant une action
		// du forum, il ne peut donc pas écouter lui-même — le forum lui confie ses événements.
		// couplage(api): facultatif — sans le module api, `Module::__load` rend NULL et rien n'est inscrit.
		foreach (['forum.topic.created', 'forum.post.created', 'forum.post.edited', 'forum.post.deleted', 'forum.topic.split', 'forum.topics.merged'] as $evenement)
		{
			$this->events->on($evenement, static function ($charge) use ($evenement) {
				if (is_array($charge) && ($api = \NF\NeoFrag\Addons\Module::__load(\NeoFrag(), ['api'])) instanceof \NF\Modules\Api\Api)
				{
					$api->consigner($evenement, $charge);
				}
			});
		}

		// Listener : notifier les @mentions par email (priorité sur subscriptions)
		$this->events->on('forum.post.created', function($payload){
			$this->_notify_mentions($payload);
		});
		$this->events->on('forum.post.edited', function($payload){
			$this->_notify_mentions($payload);
		});

		// Listener : notifier les subscribers d'un topic à chaque nouvelle réponse
		// (skip si user a déjà reçu un email de mention pour le même post)
		$this->events->on('forum.post.created', function($payload){
			$this->_notify_subscribers($payload);
		});

			// Listener : notifications in-site (cloche), independant des emails.
			$this->events->on('forum.post.created', function($payload){
				$this->_notify_bell($payload);
			});

			// Listener : gamification (points + karma). forum est chargé pendant l'action
			// → ce listener est fiable (gamification lui-même n'est pas chargé à ce moment).
			$this->events->on('forum.topic.created', function($payload){
				if (($g = \NF\NeoFrag\Addons\Module::__load(\NeoFrag(), ['gamification'])) && !empty($payload['user_id']))
				{
					$g->earn((int)$payload['user_id'], 'forum_topic');
					$g->recompute((int)$payload['user_id']);
				}
			});
			$this->events->on('forum.post.created', function($payload){
				if (!empty($payload['is_starter']))
				{
					return;
				}
				if (($g = \NF\NeoFrag\Addons\Module::__load(\NeoFrag(), ['gamification'])) && !empty($payload['user_id']))
				{
					$g->earn((int)$payload['user_id'], 'forum_message');
					$g->recompute((int)$payload['user_id']);
				}
			});
	}

	private function _notify_bell($payload)
	{
		// $this->module() depuis une classe Module mis-resout le type d'addon -> loader explicite.
		$notifications = \NF\NeoFrag\Addons\Module::__load(\NeoFrag(), ['notifications']);

		if (!$notifications)
		{
			return;
		}

		$topic = $this->db	->select('t.title', 'u.username as actor')
							->from('nf_forum_topics t')
							->join('nf_user u', 'u.id = '.(int)$payload['user_id'])
							->where('t.topic_id', (int)$payload['topic_id'])
							->row();

		if (empty($topic))
		{
			return;
		}

		// Un auteur venu de Discord sans compte lié n'a pas de nom de membre : celui de son identité.
		$topic['actor'] = $topic['actor'] ?: (string) ($payload['author_name'] ?? '');

		$actor     = (int)$payload['user_id'];
		$base      = 'forum/topic/'.(int)$payload['topic_id'].'/'.\url_title($topic['title']);
		$msg_url   = $base.'#'.(int)$payload['message_id'];
		$seen      = [];

		if (!empty($payload['mentioned_users']))
		{
			foreach ($payload['mentioned_users'] as $u)
			{
				$notifications->push((int)$u['id'], 'forum_mention', $this->lang('%s vous a mentionné dans « %s »', $topic['actor'], $topic['title']), $msg_url, $actor);
				$seen[(int)$u['id']] = TRUE;
			}
		}

		if (empty($payload['is_starter']))
		{
			foreach ($this->model()->get_subscribers((int)$payload['topic_id'], $actor) as $sub)
			{
				$uid = (int)$sub['user_id'];

				if (isset($seen[$uid]))
				{
					continue;
				}

				$notifications->push_unique($uid, 'forum_reply', $this->lang('%s a répondu à « %s »', $topic['actor'], $topic['title']), $base, $actor);
				$seen[$uid] = TRUE;
			}
		}
	}

	private function _notify_mentions($payload)
	{
		if (empty($payload['mentioned_users']))
		{
			return;
		}

		// Mentions désactivées dans les settings ?
		if (isset($this->config->forum_mentions_email) && !$this->config->forum_mentions_email)
		{
			return;
		}

		$topic = $this->db	->select('t.title', 'u.username as mentioner')
							->from('nf_forum_topics t')
							->join('nf_user u', 'u.id = '.(int)$payload['user_id'])
							->where('t.topic_id', (int)$payload['topic_id'])
							->row();

		if (empty($topic))
		{
			return;
		}

		$post_url = \url('forum/topic/'.$payload['topic_id'].'/'.\url_title($topic['title'])).'#'.(int)$payload['message_id'];

		foreach ($payload['mentioned_users'] as $user)
		{
			if (empty($user['email']))
			{
				continue;
			}

			try
			{
				$this->email->template('forum.mention', [
								'username'    => $user['username'],
								'topic_title' => $topic['title'],
								'topic_url'   => $post_url,
								'mentioner'   => $topic['mentioner']
							])
							->to($user['email'])
							->send();
			}
			catch (\Throwable $e)
			{
				// Silent fail
			}
		}
	}

	private function _notify_subscribers($payload)
	{
		// Le starter post (création de topic) ne notifie personne (pas encore de subscribers)
		if (!empty($payload['is_starter']))
		{
			return;
		}

		// Email notifications désactivées dans les settings ?
		if (isset($this->config->forum_subscriptions_email) && !$this->config->forum_subscriptions_email)
		{
			return;
		}

		$subscribers = $this->model()->get_subscribers($payload['topic_id'], $payload['user_id']);

		if (empty($subscribers))
		{
			return;
		}

		// Exclure les users déjà notifiés via @mention pour éviter double email
		$mentioned_ids = [];
		if (!empty($payload['mentioned_users']))
		{
			foreach ($payload['mentioned_users'] as $u)
			{
				$mentioned_ids[(int)$u['id']] = TRUE;
			}
		}

		$topic = $this->db	->select('t.title', 't.forum_id', 'u.username as author')
							->from('nf_forum_topics t')
							->join('nf_forum_messages m', 'm.message_id = '.(int)$payload['message_id'])
							->join('nf_user u',           'u.id = m.user_id')
							->where('t.topic_id', (int)$payload['topic_id'])
							->row();

		if (empty($topic))
		{
			return;
		}

		// Un auteur venu de Discord sans compte lié : le nom de son identité (cf. _notify_bell).
		$topic['author'] = $topic['author'] ?: (string) ($payload['author_name'] ?? '');

		$topic_url = \url('forum/topic/'.$payload['topic_id'].'/'.\url_title($topic['title']));

		$notified_user_ids = [];

		foreach ($subscribers as $subscriber)
		{
			if (isset($mentioned_ids[(int)$subscriber['user_id']]))
			{
				continue; // Déjà notifié via mention
			}

			try
			{
				$this->email->template('forum.subscription_reply', [
								'username'    => $subscriber['username'],
								'topic_title' => $topic['title'],
								'topic_url'   => $topic_url,
								'author'      => $topic['author']
							])
							->to($subscriber['email'])
							->send();

				$notified_user_ids[] = $subscriber['user_id'];
			}
			catch (\Throwable $e)
			{
				// Silent fail — un email qui échoue ne doit pas casser le post
			}
		}

		if (!empty($notified_user_ids))
		{
			$this->model()->mark_subscribers_notified($payload['topic_id'], $notified_user_ids);
		}
	}

	public function forum_render($content)
	{
		// Les posts (éditeur TinyMCE) sont du HTML. Si le contenu commence par une balise bloc reconnue,
		// on rend sa structure telle quelle après sanitize serveur ; sinon (texte brut sans balise) on
		// passe par bbcode() (nl2br + auto-lien + sanitize) pour les sauts de ligne et les liens.
		$content = (string)$content;
		$trimmed = trim($content);
		$is_html = (bool)preg_match('#^<(p|div|h[1-6]|table|ul|ol|blockquote|figure|pre|hr)[\s>]#i', $trimmed);

		if ($is_html)
		{
			return sanitize_html($content);
		}

		return bbcode($content);
	}

	public function render_mentions($content)
	{
		// Transforme @username en lien vers le profil user.
		// Doit être appelé APRÈS bbcode() pour ne pas casser les balises BBCode.
		// Cache static des résolutions pour éviter N queries dans une boucle messages.
		static $resolved = [];

		return preg_replace_callback(
			'/(^|[\s\(\[\>])@(?:"([^"]+)"|([a-zA-Z0-9_\-]+))/u',
			function($match) use (&$resolved) {
				$prefix = $match[1];
				$username = !empty($match[2]) ? $match[2] : $match[3];

				if (!array_key_exists($username, $resolved))
				{
					$user = $this->db	->select('id')
										->from('nf_user')
										->where('username', $username)
										->where('deleted', '0')
										->row();

					$resolved[$username] = $user ?: NULL;
				}

				if (!$resolved[$username])
				{
					return $match[0]; // Pas un user valide → laissé brut
				}

				return $prefix.'<a class="forum-mention" href="'.\url('user/'.(int)$resolved[$username].'/'.\url_title($username)).'" data-bs-toggle="tooltip" title="'.htmlspecialchars((string) ($username)).'">@'.htmlspecialchars((string) ($username)).'</a>';
			},
			$content
		);
	}

	public function render_attachments($message_id)
	{
		static $cache = [];

		if (!array_key_exists($message_id, $cache))
		{
			$attachments = $this->db->select('a.attachment_id', 'a.file_id', 'a.file_size', 'a.mime_type', 'f.name', 'f.path')
									->from('nf_forum_attachments a')
									->join('nf_file f', 'f.id = a.file_id')
									->where('a.message_id', (int)$message_id)
									->order_by('a.attachment_id')
									->get();

			$cache[$message_id] = $attachments;
		}

		if (empty($cache[$message_id]))
		{
			return '';
		}

		$html  = '<div class="forum-attachments mt-2">';
		$html .= '<div class="forum-attachments-label small text-muted mb-1">'.\icon('fas fa-paperclip').' '.$this->lang('Pièces jointes').'</div>';

		foreach ($cache[$message_id] as $att)
		{
			$is_image = strpos((string)$att['mime_type'], 'image/') === 0;
			$file_url = \url($att['path']);
			$name_esc = htmlspecialchars((string) ($att['name']));
			$size_str = \human_size((int)$att['file_size']);

			if ($is_image)
			{
				$html .= '<a class="forum-attachment forum-attachment-image" href="'.$file_url.'" target="_blank" rel="noopener" title="'.$name_esc.' ('.$size_str.')">'
					   . '<img src="'.$file_url.'" alt="'.$name_esc.'" loading="lazy" />'
					   . '</a>';
			}
			else
			{
				$html .= '<a class="forum-attachment forum-attachment-file" href="'.$file_url.'" target="_blank" rel="noopener" download="'.$name_esc.'">'
					   . \icon('fas fa-file').' '.$name_esc.' <small class="text-muted">('.$size_str.')</small>'
					   . '</a>';
			}
		}

		$html .= '</div>';

		return $html;
	}

	/**
	 * L'auteur d'un message qui n'est pas un membre : le nom de son identité externe (un compte
	 * Discord non lié), marqué de son logo — ou « Visiteur » s'il n'en a pas.
	 */
	public function auteur_sans_compte(?string $nom_identite): string
	{
		if ($nom_identite === NULL || trim($nom_identite) === '')
		{
			return '<i>'.$this->lang('Visiteur').'</i>';
		}

		return '<span class="forum-auteur-externe" title="'.$this->lang('Écrit depuis Discord').'">'.icon('fab fa-discord').' '.htmlspecialchars($nom_identite).'</span>';
	}

	/**
	 * La carte de l'auteur sous chaque message. `$identite` : l'identité externe d'un message écrit
	 * depuis Discord par quelqu'un qui n'a pas lié son compte (cf. `get_messages()`).
	 */
	public function get_profile($user_id = NULL, &$data = [], ?array $identite = NULL)
	{
		if (!$user_id && $identite)
		{
			return $this->view('profile', $data = ['identite' => $identite]);
		}

		static $profiles = [];

		$user_id = (int)$user_id;

		if (!isset($profiles[$user_id]))
		{
			$profiles[$user_id] = $this->db	->select('u.id as user_id', 'u.username', 'up.avatar', 'up.signature', 'up.sex', 'u.admin', 'MAX(s.last_activity) > DATE_SUB(NOW(), INTERVAL 5 MINUTE) as online')
											->from('nf_user u')
											->join('nf_user_profile up', 'u.id = up.id')
											// LEFT : voir news.php. En stricte, le profil d'un auteur sans session
											// ouverte etait vide sous chacun de ses messages.
											->join('nf_session      s',  'u.id = s.user_id', 'LEFT')
											->where('u.id', $user_id)
											->where('u.deleted', FALSE)
											->group_by('u.id')
											->row();

			if (empty($profiles[$user_id]))
			{
				$profiles[$user_id] = [];
			}
			else
			{
				$profiles[$user_id]['topics'] = $this->db	->from('nf_forum_topics t')
															->join('nf_forum_messages m', 't.message_id = m.message_id')
															->where('m.user_id', $user_id)
															->count();

				$profiles[$user_id]['replies'] = $this->db->from('nf_forum_messages')->where('user_id', $user_id)->count() - $profiles[$user_id]['topics'];
			}
		}

		return $this->view('profile', $data = $profiles[$user_id]);
	}
}
