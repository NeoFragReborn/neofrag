<?php
/**
 * https://neofr.ag
 * Module Notifications — centre de notifications in-site (cloche + non-lus).
 * Alimenté par appels directs depuis les modules émetteurs :
 *   $this->module('notifications')->push_to_content_owner('news', $id, 'comment', $title, $url, $actor_id);
 * Rendu dans la navbar via bell() (thème).
 */

namespace NF\Modules\Notifications;

use NF\NeoFrag\Addons\Module;

class Notifications extends Module
{
	// Résolution du propriétaire d'un contenu (table, clé primaire) par type émetteur.
	const OWNER_MAP = [
		'news'          => ['nf_news',           'news_id'],
		'articles'      => ['nf_articles',       'article_id'],
		'article'       => ['nf_articles',       'article_id'],
		'comment'       => ['nf_comment',        'id'],
		'forum-message' => ['nf_forum_messages', 'message_id'],
	];

	// Cibles d'abonnement autorisées (tokens URL-safe, routés via {url_title}).
	const SUB_TYPES = ['news', 'article', 'news-category', 'article-category'];

	protected function __info()
	{
		return [
			'title'       => $this->lang('Notifications'),
			'description' => $this->lang('Centre de notifications in-site (commentaires, réactions…).'),
			'icon'        => 'far fa-bell',
			'link'        => 'https://neofr.ag',
			'author'      => 'NeoFrag Reborn',
			'license'     => 'LGPLv3 <https://neofr.ag/license>',
			'version'     => '1.0',
			'depends'     => ['neofrag' => '1.0.0'],
			'routes'      => [
				'{pages}'                          => 'index',
				'ajax/read/{id}'                   => '_read',
				'ajax/read-all'                    => '_read_all',
				'ajax/subscribe/{url_title}/{id}'  => '_subscribe',
			]
		];
	}

	/** Crée une notification. Ignore l'auto-notification (acteur == destinataire). @return int|null */
	public function push($user_id, $type, $title, $url = '', $actor_id = NULL)
	{
		$user_id  = (int)$user_id;
		$actor_id = $actor_id !== NULL ? (int)$actor_id : NULL;

		if (!$user_id || ($actor_id !== NULL && $actor_id === $user_id))
		{
			return NULL;
		}

		return $this->db->insert('nf_notifications', [
			'user_id'  => $user_id,
			'actor_id' => $actor_id,
			'type'     => mb_substr((string)$type, 0, 50),
			'title'    => mb_substr((string)$title, 0, 255),
			'url'      => mb_substr((string)$url, 0, 255)
		]);
	}

	/** Comme push() mais ignore si une notif non-lue identique (user, type, url) existe déjà (anti-spam). */
	public function push_unique($user_id, $type, $title, $url = '', $actor_id = NULL)
	{
		$user_id = (int)$user_id;

		if ($user_id && !$this->db	->from('nf_notifications')
									->where('user_id', $user_id)
									->where('type', (string)$type)
									->where('url', (string)$url)
									->where('is_read', 0)
									->empty())
		{
			return NULL;
		}

		return $this->push($user_id, $type, $title, $url, $actor_id);
	}

	/** Notifie le propriétaire d'un contenu (propriétaire + URL résolus via OWNER_MAP / content_url). */
	public function push_to_content_owner($content, $content_id, $type, $title, $actor_id = NULL)
	{
		if (!isset(self::OWNER_MAP[$content]))
		{
			return NULL;
		}

		list($table, $pk) = self::OWNER_MAP[$content];

		$owner = $this->db	->select('user_id')
							->from($table)
							->where($pk, (int)$content_id)
							->row();

		// push_unique : 1 seule notif non-lue par (destinataire, type, contenu) — anti-spam.
		return $owner ? $this->push_unique((int)$owner, $type, $title, $this->content_url($content, $content_id), $actor_id) : NULL;
	}

	/** URL publique (best-effort) d'un contenu connu, pour le lien de la notification. */
	public function content_url($content, $content_id)
	{
		$content_id = (int)$content_id;
		$lang       = $this->config->lang->info()->name;

		// Modèle « tout bundlé, activé à la carte » : la table d'un module non installé peut être absente.
		// On tolère son absence (lien vide) au lieu de fataliser sur « table doesn't exist ».
		$content_tables = [
			'comment'       => 'nf_comment',
			'news'          => 'nf_news_lang',
			'articles'      => 'nf_articles_lang',
			'article'       => 'nf_articles_lang',
			'forum-message' => 'nf_forum_messages',
		];
		if (isset($content_tables[$content]) && !$this->db->table_exists($content_tables[$content]))
		{
			return '';
		}

		if ($content === 'comment')
		{
			$c = $this->db->select('module', 'module_id')->from('nf_comment')->where('id', $content_id)->row(FALSE);
			if (!$c)
			{
				return '';
			}
			$inner = $this->content_url($c['module'], (int)$c['module_id']);
			return $inner ? $inner.'#comments' : '';
		}

		if ($content === 'news')
		{
			$t = $this->db->select('title')->from('nf_news_lang')->where('news_id', $content_id)->where('lang', $lang)->row();
			return $t ? 'news/'.$content_id.'/'.url_title($t) : '';
		}

		if ($content === 'articles' || $content === 'article')
		{
			$t = $this->db->select('title')->from('nf_articles_lang')->where('article_id', $content_id)->where('lang', $lang)->row();
			return $t ? 'articles/'.$content_id.'/'.url_title($t) : '';
		}

		if ($content === 'forum-message')
		{
			$topic_id = $this->db->select('topic_id')->from('nf_forum_messages')->where('message_id', $content_id)->row();
			if (!$topic_id)
			{
				return '';
			}
			$t = $this->db->select('title')->from('nf_forum_topics')->where('topic_id', (int)$topic_id)->row();
			return $t ? 'forum/topic/'.(int)$topic_id.'/'.url_title($t).'#'.$content_id : '';
		}

		return '';
	}

	// --- Abonnements -------------------------------------------------------

	public static function is_subscribable($type)
	{
		return in_array($type, self::SUB_TYPES, TRUE);
	}

	public function is_subscribed($type, $id, $user_id = NULL)
	{
		$user_id = $user_id !== NULL ? (int)$user_id : ($this->user() ? (int)$this->user->id : 0);

		if (!$user_id)
		{
			return FALSE;
		}

		return !$this->db	->from('nf_subscriptions')
							->where('user_id', $user_id)
							->where('content_type', $type)
							->where('content_id', (int)$id)
							->empty();
	}

	/** Bascule l'abonnement du user courant. @return bool nouvel état (TRUE = abonné). */
	public function toggle_subscription($type, $id)
	{
		if (!$this->user() || !self::is_subscribable($type))
		{
			return FALSE;
		}

		$user_id = (int)$this->user->id;
		$id      = (int)$id;

		if ($this->is_subscribed($type, $id, $user_id))
		{
			$this->db	->where('user_id', $user_id)
						->where('content_type', $type)
						->where('content_id', $id)
						->delete('nf_subscriptions');

			return FALSE;
		}

		$this->db->insert('nf_subscriptions', [
			'user_id'      => $user_id,
			'content_type' => $type,
			'content_id'   => $id
		]);

		return TRUE;
	}

	/** IDs des abonnés à une cible (optionnellement hors un user). */
	public function subscribers($type, $id, $exclude_user_id = NULL)
	{
		$this->db	->select('user_id')
					->from('nf_subscriptions')
					->where('content_type', $type)
					->where('content_id', (int)$id);

		if ($exclude_user_id !== NULL)
		{
			$this->db->where('user_id !=', (int)$exclude_user_id);
		}

		return array_map('intval', $this->db->get());
	}

	/** Bouton « Suivre / Suivi » (toggle AJAX). À appeler depuis une vue. */
	public function follow_button($type, $id)
	{
		if (!$this->user() || !self::is_subscribable($type))
		{
			return '';
		}

		$this->css('notifications')->js('notifications');

		$subscribed = $this->is_subscribed($type, $id);
		$follow     = $this->lang('Suivre');
		$following  = $this->lang('Suivi');

		return '<button type="button" class="btn btn-sm nf-follow-btn'.($subscribed ? ' following btn-secondary' : ' btn-outline-secondary').'"'
			.' data-follow-toggle data-follow-type="'.htmlspecialchars($type).'" data-follow-id="'.(int)$id.'"'
			.' data-label-follow="'.htmlspecialchars($follow, ENT_QUOTES).'" data-label-following="'.htmlspecialchars($following, ENT_QUOTES).'">'
			.'<i class="'.($subscribed ? 'fas' : 'far').' fa-bell"></i> <span class="nf-follow-label">'.($subscribed ? $following : $follow).'</span>'
			.'</button>';
	}

	public function unread_count($user_id = NULL)
	{
		$user_id = $user_id !== NULL ? (int)$user_id : ($this->user() ? (int)$this->user->id : 0);

		if (!$user_id)
		{
			return 0;
		}

		return (int)$this->db	->select('COUNT(*)')
								->from('nf_notifications')
								->where('user_id', $user_id)
								->where('is_read', 0)
								->row();
	}

	/** Notifications récentes du user courant (avec pseudo de l'acteur). */
	public function recent($limit = 10)
	{
		if (!$this->user())
		{
			return [];
		}

		return $this->db	->select('n.id', 'n.type', 'n.title', 'n.url', 'n.is_read', 'n.created_at', 'a.username AS actor')
							->from('nf_notifications n')
							->join('nf_user a', 'a.id = n.actor_id', 'LEFT')
							->where('n.user_id', (int)$this->user->id)
							->order_by('n.id DESC')
							->limit((int)$limit)
							->get(FALSE);
	}

	public function mark_read($id)
	{
		if (!$this->user())
		{
			return;
		}

		$this->db	->where('id', (int)$id)
					->where('user_id', (int)$this->user->id)
					->update('nf_notifications', ['is_read' => 1]);
	}

	public function mark_all_read()
	{
		if (!$this->user())
		{
			return;
		}

		$this->db	->where('user_id', (int)$this->user->id)
					->where('is_read', 0)
					->update('nf_notifications', ['is_read' => 1]);
	}

	/** Rend l'item <li> de cloche pour la navbar (compteur + dropdown des récentes). */
	public function bell()
	{
		if (!$this->user())
		{
			return '';
		}

		$this->css('notifications')->js('notifications');

		$count = $this->unread_count();
		$items = $this->recent(8);

		$list = '';
		if (!$items)
		{
			$list = '<span class="dropdown-item-text text-muted">'.$this->lang('Aucune notification').'</span>';
		}
		else
		{
			foreach ($items as $n)
			{
				$list .= '<a class="dropdown-item nf-notif-item'.(empty($n['is_read']) ? ' unread' : '').'" href="'.url($n['url'] ?: 'notifications').'" data-notif-id="'.(int)$n['id'].'">'
					.'<span class="nf-notif-title">'.htmlspecialchars($n['title']).'</span>'
					.'<small class="text-muted d-block">'.htmlspecialchars($n['created_at']).'</small>'
					.'</a>';
			}
		}

		$badge = $count > 0 ? '<span class="badge text-bg-danger nf-notif-badge">'.($count > 99 ? '99+' : $count).'</span>' : '';

		return '<li class="nav-item dropdown nf-notif">'
			.'<a class="nav-link" href="#" data-bs-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false" title="'.htmlspecialchars($this->lang('Notifications'), ENT_QUOTES).'">'
			.icon('far fa-bell').$badge
			.'</a>'
			.'<div class="dropdown-menu dropdown-menu-end nf-notif-menu">'
			.'<div class="dropdown-header d-flex justify-content-between align-items-center">'
			.'<span>'.$this->lang('Notifications').'</span>'
			.($count > 0 ? '<a href="#" data-notif-read-all class="small">'.$this->lang('Tout marquer comme lu').'</a>' : '')
			.'</div>'
			.'<div class="dropdown-divider"></div>'
			.$list
			.'<div class="dropdown-divider"></div>'
			.'<a class="dropdown-item text-center small" href="'.url('notifications').'">'.$this->lang('Voir tout').'</a>'
			.'</div>'
			.'</li>';
	}
}
