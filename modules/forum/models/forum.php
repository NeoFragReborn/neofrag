<?php
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\Forum\Models;

use NF\NeoFrag\Loadables\Model;

class Forum extends Model
{
	public function get_categories_list($forum_id = NULL)
	{
		$categories = [];

		foreach ($this->db	->select('c.category_id', 'c.title', 'f.forum_id', 'f.title as forum_title')
							->from('nf_forum_categories c')
							->join('nf_forum f', 'c.category_id = f.parent_id AND f.is_subforum = "0"')
							->order_by('c.order', 'f.order')
							->get() as $category)
		{
			if (!isset($categories[$category['category_id']]))
			{
				$categories[$category['category_id']] = $category['title'];
			}

			if ($category['forum_id'] && (!$forum_id || $category['forum_id'] != $forum_id))
			{
				$categories['f'.$category['forum_id']] = str_repeat('&nbsp;', 10).$category['forum_title'];
			}
		}

		return $categories;
	}

	public function get_categories()
	{
		$categories = [];
		$forums     = $this->get_forums();
		$count_read = $i = 0;

		foreach ($this->db	->select('category_id', 'title', 'image_id')
							->from('nf_forum_categories')
							->order_by('order', 'category_id')
							->get() as $category)
		{
			if ($this->access('forum', 'category_read', $category['category_id']) && !$this->_vip_locked($category['category_id']))
			{
				$category['forums'] = [];

				foreach ($forums as $forum)
				{
					if ($forum['parent_id'] == $category['category_id'])
					{
						$category['forums'][] = $forum;

						$count_read += !$forum['has_unread'];
						$i++;
					}
				}

				$categories[] = $category;
			}
		}

		if ($count_read == $i)
		{
			$this->mark_all_as_read();
		}

		return $categories;
	}

	public function get_forums_tree()
	{
		$tree = [];

		foreach ($this->db	->select('category_id', 'title')
							->from('nf_forum_categories')
							->order_by('order', 'category_id')
							->get() as $category)
		{
			if ($this->access('forum', 'category_read', $category['category_id']) && !$this->_vip_locked($category['category_id']))
			{
				$forums = [];

				foreach ($this->db	->select('f.forum_id', 'f.title')
									->from('nf_forum f')
									->join('nf_forum_url u', 'u.forum_id = f.forum_id')
									->where('f.parent_id', $category['category_id'])
									->where('f.is_subforum', FALSE)
									->where('u.forum_id', NULL)
									->order_by('f.order', 'f.forum_id')
									->get() as $forum)
				{
					$subforums = [];

					foreach ($this->db	->select('f.forum_id', 'f.title')
										->from('nf_forum f')
										->join('nf_forum_url u', 'u.forum_id = f.forum_id')
										->where('f.parent_id', $forum['forum_id'])
										->where('f.is_subforum', TRUE)
										->where('u.forum_id', NULL)
										->order_by('f.order', 'f.forum_id')
										->get() as $subforum)
					{
						$subforums[$subforum['forum_id']] = $subforum['title'];
					}

					$forums[$forum['forum_id']] = [
						'title'     => $forum['title'],
						'subforums' => $subforums
					];
				}

				if ($forums)
				{
					$tree[$category['category_id']] = [
						'title'  => $category['title'],
						'forums' => $forums
					];
				}
			}
		}

		return $tree;
	}

	public function get_forums($forum_id = NULL, $mini = FALSE)
	{
		if ($forum_id)
		{
			$this->db	->where('f.parent_id', $forum_id)
						->where('f.is_subforum', TRUE);
		}
		else
		{
			$this->db	->join('nf_forum f2', 'f2.parent_id = f.forum_id AND f2.is_subforum = "1"')
						->where('f.is_subforum', FALSE);
		}

		$forums = $this->db	->select(	'f.forum_id',
										'f.parent_id',
										'f.title',
										'f.description',
										!$forum_id ? 'f.count_messages + SUM(IFNULL(f2.count_messages, 0)) as count_messages' : 'f.count_messages',
										!$forum_id ? 'f.count_topics   + SUM(IFNULL(f2.count_topics, 0))   as count_topics'   : 'f.count_topics',
										'f.last_message_id',
										'u.id as user_id',
										'u.username',
										't.topic_id',
										't.title as last_title',
										'm.date as last_message_date',
										't.count_messages as last_count_messages',
										'u2.url',
										'u2.redirects',
										(!$forum_id ? 'COUNT(f2.forum_id)' : 0).' as subforums'
									)
									->from('nf_forum f')
									->join('nf_forum_messages m', 'm.message_id = f.last_message_id')
									->join('nf_forum_topics t',   't.topic_id = m.topic_id')
									->join('nf_user u',           'u.id = m.user_id AND u.deleted = "0"')
									->join('nf_forum_url u2',     'u2.forum_id = f.forum_id')
									->group_by('f.forum_id')
									->order_by('f.order', 'f.forum_id')
									->get();

		foreach ($forums as &$forum)
		{
			$forum['has_unread'] = $forum['url'] ? FALSE : $this->_has_unread($forum);

			if ($forum['subforums'])
			{
				foreach ($forum['subforums'] = $this->get_forums($forum['forum_id'], TRUE) as $subforum)
				{
					if (!$forum['has_unread'] && $subforum['has_unread'])
					{
						$forum['has_unread'] = TRUE;
					}

					if ($subforum['last_message_id'] > $forum['last_message_id'])
					{
						foreach (['last_message_id', 'user_id', 'username', 'topic_id', 'last_title', 'last_message_date', 'last_count_messages'] as $var)
						{
							$forum[$var] = $subforum[$var];
						}
					}
				}
			}
			else
			{
				$forum['subforums'] = [];
			}

			$forum['icon']       = icon(($forum['url'] ? 'fas fa-globe' : ($forum['has_unread'] ? 'fas fa-comments' : 'far fa-comments')).($mini ? '' : ' fa-3x'));
		}

		return $forums;
	}

	public function get_topics($forum_id)
	{
		$topics = $this->db->select('t.topic_id',
									't.title',
									't.views',
									't.count_messages',
									't.last_message_id',
									'u1.id as user_id',
									'u1.username',
									'm1.date',
									'u2.id as last_user_id',
									'u2.username as last_username',
									'm2.date as last_message_date',
									'm2.message',
									't.status IN ("-2", "1") as announce',
									't.status IN ("-2", "-1") as locked'
								)
						->from('nf_forum_topics   t')
						->join('nf_forum_messages m1', 't.message_id = m1.message_id')
						->join('nf_forum_messages m2', 't.last_message_id = m2.message_id')
						->join('nf_user u1',           'u1.id = m1.user_id AND u1.deleted = "0"')
						->join('nf_user u2',           'u2.id = m2.user_id AND u2.deleted = "0"')
						->where('t.forum_id', $forum_id)
						->order_by('IFNULL(m2.date, m1.date) DESC')
						->get();

		if ($this->user())
		{
			$forum_read = $this->db	->select('MAX(UNIX_TIMESTAMP(date))')
									->from('nf_forum_read')
									->where('user_id', $this->user->id)
									->where('forum_id', [0, $forum_id])
									->row();

			$topics_read = [];

			foreach ($this->db->select('t.topic_id', 'r.date')
									->from('nf_forum_topics_read r')
									->join('nf_forum_topics t', 't.topic_id = r.topic_id')
									->where('t.forum_id', $forum_id)
									->where('r.user_id', $this->user->id)
									->get() as $read)
			{
				$topics_read[$read['topic_id']] = strtotime($read['date']);
			}
		}

		$count_read = $i = 0;

		foreach ($topics as &$topic)
		{
			$last_message_date = strtotime($topic['last_message_date'] ?: $topic['date']);
			$unread = $this->user() && $forum_read < $last_message_date && (!isset($topics_read[$topic['topic_id']]) || $topics_read[$topic['topic_id']] < $last_message_date);
			$topic['icon'] = '	<span class="topic-icon">
								'.icon(($unread ? 'fas' : 'far').' fa-'.($topic['announce'] ? 'flag' : 'comments').' fa-3x').'
								'.($topic['locked'] ? icon('fas fa-lock fa-3x') : '').'
							</span>';

			if (!$unread)
			{
				$count_read++;
			}

			$i++;
		}

		if ($count_read == $i)
		{
			$this->mark_all_as_read($forum_id);
		}

		return $topics;
	}

	public function get_messages($topic_id, $forum_id)
	{
		$messages = $this->db	->select('message_id', 'parent_id', 'user_id', 'message', 'UNIX_TIMESTAMP(date) as date')
								->from('nf_forum_messages')
								->where('topic_id', $topic_id)
								->order_by('message_id')
								->get();

		// Calcul de la profondeur pour rendu nested (Phase 6)
		$depths = [];
		foreach ($messages as &$m)
		{
			$pid = (int)$m['parent_id'];
			$m['depth'] = ($pid && isset($depths[$pid])) ? $depths[$pid] + 1 : 0;
			$depths[(int)$m['message_id']] = $m['depth'];
		}
		unset($m);

		// Phase B — enrichir chaque message d'une référence au parent (username + excerpt)
		// pour rendre cliquable le bandeau "En réponse à" dans la vue.
		$by_id = [];
		foreach ($messages as $m) { $by_id[(int)$m['message_id']] = $m; }
		foreach ($messages as &$m)
		{
			$pid = (int)$m['parent_id'];
			$m['parent_username'] = '';
			$m['parent_excerpt']  = '';
			if ($pid && isset($by_id[$pid]))
			{
				$parent = $by_id[$pid];
				$parent_user = $this->db->select('username')->from('nf_user')->where('id', (int)$parent['user_id'])->row();
				$m['parent_username'] = is_array($parent_user) ? $parent_user['username'] : (string)$parent_user;
				$m['parent_excerpt']  = trim(strip_tags(str_replace(['<br>', '<br/>', '<br />'], ' ', (string)$parent['message'])));
			}
		}
		unset($m);

		return $messages;
	}

	public function check_category($category_id, $title)
	{
		$category = $this->db	->select('category_id', 'title')
								->from('nf_forum_categories')
								->where('category_id', $category_id)
								->row();

		if ($category && $title == url_title($category['title']))
		{
			return $category;
		}
		else
		{
			return FALSE;
		}
	}

	/**
	 * Catégorie verrouillée pour le membre courant ? (VIP requis et non satisfait).
	 * Les administrateurs effectifs voient tout.
	 */
	private function _vip_locked($category_id)
	{
		if ($this->access->effective_admin())
		{
			return FALSE;
		}

		if (!(int) $this->db->select('vip_only')->from('nf_forum_categories')->where('category_id', (int) $category_id)->row())
		{
			return FALSE;
		}

		$uid = $this->user() ? (int) $this->user->id : 0;
		$gam = \NF\NeoFrag\Addons\Module::__load(\NeoFrag(), ['gamification']);

		return !($uid && $gam && $gam->is_vip($uid));
	}

	public function check_forum($forum_id, &$title)
	{
		$forum = $this->db	->select('f.forum_id', 'f.title', 'f.description', 'f.parent_id', 'f.is_subforum', 'u.url', 'IFNULL(f3.parent_id, f.parent_id) as category_id', 'COUNT(f2.forum_id) as subforums')
							->from('nf_forum f')
							->join('nf_forum f2', 'f2.parent_id = f.forum_id  AND f2.is_subforum = "1"')
							->join('nf_forum f3', 'f3.forum_id  = f.parent_id AND f.is_subforum  = "1"')
							->join('nf_forum_url u', 'u.forum_id = f.forum_id')
							->where('f.forum_id', $forum_id)
							->row();

		if ($forum && $title == url_title($forum['title']))
		{
			if ($this->_vip_locked($forum['category_id']))
			{
				return FALSE;
			}

			$title = $forum['title'];
			return $forum;
		}
		else
		{
			return FALSE;
		}
	}

	public function check_topic($topic_id, &$title)
	{
		$topic = $this->db	->select('t.title as topic_title', 't.forum_id', 'f.title', 'IFNULL(f2.parent_id, f.parent_id) as category_id', 't.views', 't.status IN ("-2", "1") as announce', 't.status IN ("-2", "-1") as locked')
							->from('nf_forum_topics t')
							->join('nf_forum        f',  't.forum_id  = f.forum_id')
							->join('nf_forum        f2', 'f2.forum_id = f.parent_id AND f.is_subforum = "1"')
							->where('t.topic_id', $topic_id)
							->row();

		if ($topic && $title == url_title($topic['topic_title']))
		{
			if ($this->_vip_locked($topic['category_id']))
			{
				return FALSE;
			}

			$title = $topic['topic_title'];
			return $topic;
		}
		else
		{
			return FALSE;
		}
	}

	public function check_message($message_id, $title)
	{
		$message = $this->db	->select('m.message_id', 't.topic_id', 't.title', 't.message_id = m.message_id as is_topic', 'm.message', 'IFNULL(f2.parent_id, f.parent_id) as category_id', 't.forum_id', 'm.user_id', 't.status IN ("-2", "-1") as locked')
								->from('nf_forum_messages m')
								->join('nf_forum_topics   t',  'm.topic_id = t.topic_id')
								->join('nf_forum          f',  't.forum_id = f.forum_id')
								->join('nf_forum          f2', 'f2.forum_id = f.parent_id AND f.is_subforum = "1"')
								->where('m.message_id', $message_id)
								->row();

		if ($message && $title == url_title($message['title']))
		{
			if ($this->_vip_locked($message['category_id']))
			{
				return FALSE;
			}

			return $message;
		}
		else
		{
			return FALSE;
		}
	}

	public function add_topic($forum_id, $title, $message, $announce)
	{
		$this->db->transaction();

		try
		{
			$topic_id = $this->db	->ignore_foreign_keys()
									->insert('nf_forum_topics', [
										'forum_id' => (int)$forum_id,
										'title'    => $title,
										'status'   => $announce
									]);

			$message_id = $this->db	->insert('nf_forum_messages', [
										'topic_id' => $topic_id,
										'user_id'  => $this->user->id,
										'message'  => $message
									]);

			$count_topics = $this->db->select('count_topics')->from('nf_forum')->where('forum_id', $forum_id)->row();

			$this->db	->where('forum_id', $forum_id)
						->update('nf_forum', [
							'last_message_id' => $message_id,
							'count_topics'    => $count_topics + 1
						]);

			$this->db	->where('topic_id', $topic_id)
						->update('nf_forum_topics', [
							'message_id' => $message_id
						]);

			$this->db	->insert('nf_forum_topics_read', [
							'topic_id' => $topic_id,
							'user_id'  => $this->user->id
						]);

			$this->db->commit();
		}
		catch (\Throwable $e)
		{
			$this->db->rollback();
			throw $e;
		}

		// Auto-subscribe le créateur du topic
		$this->subscribe($topic_id, $this->user->id);

		// Enregistrer les @mentions
		$mentioned_users = $this->record_mentions($message_id, $this->user->id, $message);

		$this->events->fire('forum.topic.created', [
			'topic_id'   => $topic_id,
			'message_id' => $message_id,
			'forum_id'   => (int)$forum_id,
			'user_id'    => $this->user->id,
			'title'      => $title,
			'message'    => $message
		]);

		$this->events->fire('forum.post.created', [
			'message_id'      => $message_id,
			'topic_id'        => $topic_id,
			'forum_id'        => (int)$forum_id,
			'user_id'         => $this->user->id,
			'message'         => $message,
			'is_starter'      => TRUE,
			'mentioned_users' => $mentioned_users
		]);

		$this->get_topics($forum_id);

		return $topic_id;
	}

	public function add_message($topic_id, $message, $parent_id = NULL)
	{
		$topic = $this->db->select('count_messages', 'forum_id')->from('nf_forum_topics')->where('topic_id', $topic_id)->row();
		$count_messages = $this->db->select('count_messages')->from('nf_forum')->where('forum_id', $topic['forum_id'])->row();

		$parent_id = $this->_validate_parent($parent_id, $topic_id);

		$this->db->transaction();

		try
		{
			$insert = [
				'topic_id' => (int)$topic_id,
				'user_id'  => $this->user->id,
				'message'  => $message
			];

			if ($parent_id)
			{
				$insert['parent_id'] = $parent_id;
			}

			$message_id = $this->db->insert('nf_forum_messages', $insert);

			$this->db	->where('forum_id', $topic['forum_id'])
						->update('nf_forum', [
							'last_message_id' => $message_id,
							'count_messages' => $count_messages + 1
						]);

			$this->db	->where('topic_id', $topic_id)
						->update('nf_forum_topics', [
							'last_message_id' => $message_id,
							'count_messages'  => $topic['count_messages'] + 1
						]);

			$this->db	->where('user_id', $this->user->id)
						->where('topic_id', $topic_id)
						->delete('nf_forum_topics_read');

			$this->db	->insert('nf_forum_topics_read', [
							'topic_id' => $topic_id,
							'user_id'  => $this->user->id
						]);

			$this->db->commit();
		}
		catch (\Throwable $e)
		{
			$this->db->rollback();
			throw $e;
		}

		// Enregistrer les @mentions
		$mentioned_users = $this->record_mentions($message_id, $this->user->id, $message);

		$this->events->fire('forum.post.created', [
			'message_id'      => $message_id,
			'topic_id'        => (int)$topic_id,
			'forum_id'        => (int)$topic['forum_id'],
			'user_id'         => $this->user->id,
			'message'         => $message,
			'is_starter'      => FALSE,
			'mentioned_users' => $mentioned_users
		]);

		$this->get_topics($topic['forum_id']);

		return $message_id;
	}

	public function add_category($title, $image_id = NULL, $vip_only = FALSE)
	{
		$category_id = $this->db->insert('nf_forum_categories', [
			'title'    => $title,
			'image_id' => $image_id ?: NULL,
			'vip_only' => $vip_only ? 1 : 0
		]);

		$this->access->init('forum', 'category', $category_id);

		return $category_id;
	}

	public function add_forum($title, $category_id, $description, $url)
	{
		$this->db->transaction();

		try
		{
			$forum_id = $this->db->insert('nf_forum', [
				'title'       => $title,
				'parent_id'   => $this->get_parent_id($category_id, $is_subforum),
				'is_subforum' => $is_subforum,
				'description' => $description
			]);

			if ($url)
			{
				$this->db->insert('nf_forum_url', [
					'forum_id' => $forum_id,
					'url'      => $url
				]);
			}

			$this->db->commit();
		}
		catch (\Throwable $e)
		{
			$this->db->rollback();
			throw $e;
		}

		return $forum_id;
	}

	public function edit_category($category_id, $title, $image_id = NULL, $vip_only = FALSE)
	{
		$this->db	->where('category_id', $category_id)
					->update('nf_forum_categories', [
						'title'    => $title,
						'image_id' => $image_id ?: NULL,
						'vip_only' => $vip_only ? 1 : 0
					]);
	}

	public function delete_category($category_id, $skip_transaction = FALSE)
	{
		if (!$skip_transaction) $this->db->transaction();

		try
		{
			$this->db	->where('category_id', $category_id)
						->delete('nf_forum_categories');

			foreach ($this->db->select('forum_id')->from('nf_forum')->where('parent_id', $category_id)->where('is_subforum', FALSE)->get() as $forum_id)
			{
				$this->delete_forum($forum_id, TRUE);
			}

			$this->access->delete('forum', $category_id);

			if (!$skip_transaction) $this->db->commit();
		}
		catch (\Throwable $e)
		{
			if (!$skip_transaction) $this->db->rollback();
			throw $e;
		}
	}

	public function delete_forum($forum_id, $skip_transaction = FALSE)
	{
		if (!$skip_transaction) $this->db->transaction();

		try
		{
			foreach ($this->db->select('forum_id')->from('nf_forum')->where('parent_id', $forum_id)->where('is_subforum', TRUE)->get() as $subforum_id)
			{
				$this->delete_forum($subforum_id, TRUE);
			}

			$this->db	->where('forum_id', $forum_id)
						->delete('nf_forum');

			$this->db	->where('forum_id', $forum_id)
						->delete('nf_forum_read');

			if (!$skip_transaction) $this->db->commit();
		}
		catch (\Throwable $e)
		{
			if (!$skip_transaction) $this->db->rollback();
			throw $e;
		}
	}

	public function mark_all_as_read($forum_id = 0)
	{
		if (!$this->user())
		{
			return;
		}

		if ($forum_id)
		{
			$this->db	->where('r.user_id', $this->user->id)
						->where('t.topic_id = r.topic_id')
						->where('t.forum_id', $forum_id)
						->delete('r', 'nf_forum_topics_read as r, nf_forum_topics as t');

			$this->db	->where('user_id', $this->user->id)
						->where('forum_id', $forum_id)
						->delete('nf_forum_read');
		}
		else
		{
			$this->db	->where('user_id', $this->user->id)
						->delete('nf_forum_topics_read');

			$this->db	->where('user_id', $this->user->id)
						->delete('nf_forum_read');
		}

		$this->db->insert('nf_forum_read', [
			'user_id'  => $this->user->id,
			'forum_id' => $forum_id
		]);
	}

	public function increment_redirect($forum_id)
	{
		$this->db	->where('forum_id', $forum_id)
					->update('nf_forum_url', 'redirects = redirects + 1');
	}

	public function get_parent_id($parent_id, &$is_subforum)
	{
		$is_subforum = FALSE;

		if (strpos($parent_id, 'f') === 0)
		{
			$parent_id   = substr($parent_id, 1);
			$is_subforum = TRUE;
		}

		return $parent_id;
	}

	public function get_last_message_id($forum_id)
	{
		$message_id = $this->db	->select('m.message_id')
								->from('nf_forum_messages m')
								->join('nf_forum_topics t', 't.topic_id = m.topic_id')
								->where('t.forum_id', $forum_id)
								->order_by('message_id DESC')
								->row();

		return $message_id ?: NULL;
	}

	public function count_messages($topic_id)
	{
		return $this->db->from('nf_forum_messages')
						->where('topic_id', $topic_id)
						->count() - 1;
	}

	// =================================================================
	// Search FT (Phase 8) — MATCH AGAINST sur ft_message + ft_title
	// =================================================================

	public function search_fulltext($query, $forum_id = NULL, $author = NULL, $date_from = NULL, $date_to = NULL, $sort = 'relevance')
	{
		$query = trim((string)$query);
		if ($query === '' || strlen($query) < 3)
		{
			return [];
		}

		$accessible_categories = array_filter(
			$this->db->select('category_id')->from('nf_forum_categories')->get(),
			function($a){
				return $this->access('forum', 'category_read', $a);
			}
		);

		if (empty($accessible_categories))
		{
			return [];
		}

		// MATCH AGAINST en mode boolean (supporte les opérateurs +/-/")
		$boolean = $this->_to_boolean_query($query);

		$relevance_select = 'MATCH(m.message) AGAINST ('.$this->_quote($boolean).' IN BOOLEAN MODE) + IFNULL(MATCH(t.title) AGAINST ('.$this->_quote($boolean).' IN BOOLEAN MODE), 0) AS relevance';

		$this->db	->select(	'm.message_id',
								'm.message',
								'UNIX_TIMESTAMP(m.date) as date',
								't.topic_id',
								't.title as topic_title',
								't.count_messages',
								'f.forum_id',
								'f.title as forum_title',
								'u.id as user_id',
								'u.username',
								$relevance_select
							)
					->from('nf_forum_messages m')
					->join('nf_forum_topics   t',  'm.topic_id = t.topic_id')
					->join('nf_forum          f',  't.forum_id = f.forum_id')
					->join('nf_forum          f2', 'f.parent_id = f2.forum_id AND f.is_subforum = "1"')
					->join('nf_user           u',  'm.user_id = u.id AND u.deleted = "0"')
					->where('IFNULL(f2.parent_id, f.parent_id)', $accessible_categories)
					->where('(MATCH(m.message) AGAINST ('.$this->_quote($boolean).' IN BOOLEAN MODE) > 0 OR MATCH(t.title) AGAINST ('.$this->_quote($boolean).' IN BOOLEAN MODE) > 0)');

		if ($forum_id)
		{
			$this->db->where('t.forum_id', (int)$forum_id);
		}

		if ($author)
		{
			$this->db->where('u.username', (string)$author);
		}

		if ($date_from)
		{
			$this->db->where('m.date >=', date('Y-m-d H:i:s', strtotime($date_from)));
		}

		if ($date_to)
		{
			$this->db->where('m.date <=', date('Y-m-d H:i:s', strtotime($date_to)));
		}

		// Exclure les messages soft-deleted
		$this->db->where('m.deleted_at', NULL);

		if ($sort === 'date_desc')
		{
			$this->db->order_by('m.date DESC');
		}
		else if ($sort === 'date_asc')
		{
			$this->db->order_by('m.date ASC');
		}
		else
		{
			$this->db->order_by('relevance DESC');
		}

		return $this->db->limit(200)->get();
	}

	private function _to_boolean_query($query)
	{
		// Convertit "foo bar" → "+foo* +bar*" pour AND-search avec stemming
		// Garde les "phrases entre guillemets" et les exclusions -mot
		$tokens = preg_split('/\s+/', $query);
		$out = [];

		foreach ($tokens as $tok)
		{
			$tok = trim($tok);
			if ($tok === '') continue;

			if ($tok[0] === '"' && substr($tok, -1) === '"')
			{
				$out[] = $tok;
			}
			else if ($tok[0] === '-' && strlen($tok) > 1)
			{
				$out[] = '-'.preg_replace('/[^\p{L}\p{N}_]/u', '', substr($tok, 1));
			}
			else
			{
				$clean = preg_replace('/[^\p{L}\p{N}_]/u', '', $tok);
				if (strlen($clean) >= 3)
				{
					$out[] = '+'.$clean.'*';
				}
			}
		}

		return implode(' ', $out);
	}

	private function _quote($s)
	{
		return "'".str_replace("'", "''", (string)$s)."'";
	}

	// =================================================================
	// /Search FT
	// =================================================================

	// =================================================================
	// Threading (Phase 6) — parent_id sur nf_forum_messages
	// =================================================================

	private function _validate_parent($parent_id, $topic_id)
	{
		if (!$parent_id)
		{
			return NULL;
		}

		$parent = $this->db	->select('topic_id', 'parent_id')
							->from('nf_forum_messages')
							->where('message_id', (int)$parent_id)
							->row();

		// Le parent doit appartenir au même topic
		if (!$parent || (int)$parent['topic_id'] !== (int)$topic_id)
		{
			return NULL;
		}

		// Max depth depuis settings (default 3 niveaux)
		$max_depth = isset($this->config->forum_threading_max_depth)
				? max(1, (int)$this->config->forum_threading_max_depth)
				: 3;

		// Calcule la depth actuelle du parent en remontant la chain
		$depth = 1;
		$current = $parent;
		while (!empty($current['parent_id']) && $depth < $max_depth + 5)
		{
			$current = $this->db	->select('parent_id')
									->from('nf_forum_messages')
									->where('message_id', (int)$current['parent_id'])
									->row();
			if (!$current) break;
			$depth++;
		}

		if ($depth >= $max_depth)
		{
			return NULL;
		}

		return (int)$parent_id;
	}

	public function get_message_depth($message_id)
	{
		$depth = 0;
		$current_id = (int)$message_id;

		while ($current_id && $depth < 20)
		{
			$row = $this->db	->select('parent_id')
								->from('nf_forum_messages')
								->where('message_id', $current_id)
								->row();
			if (empty($row)) break;
			$current_id = (int)$row;
			if (!$current_id) break;
			$depth++;
		}

		return $depth;
	}

	public function get_messages_with_threading($topic_id)
	{
		// Retourne les messages avec leur depth pré-calculée pour l'affichage
		$messages = $this->db	->select('message_id', 'parent_id', 'user_id', 'message', 'UNIX_TIMESTAMP(date) as date')
								->from('nf_forum_messages')
								->where('topic_id', (int)$topic_id)
								->order_by('message_id')
								->get();

		// Calcul depth via passage parent_id → depth en mémoire
		$depths = [];
		foreach ($messages as &$m)
		{
			$pid = (int)$m['parent_id'];
			$m['depth'] = ($pid && isset($depths[$pid])) ? $depths[$pid] + 1 : 0;
			$depths[(int)$m['message_id']] = $m['depth'];
		}

		return $messages;
	}

	// =================================================================
	// /Threading
	// =================================================================

	// =================================================================
	// Subscriptions (Phase 3) — utilise nf_forum_track
	// =================================================================

	public function is_subscribed($topic_id, $user_id)
	{
		return (bool)$this->db	->select('1')
								->from('nf_forum_track')
								->where('topic_id', (int)$topic_id)
								->where('user_id',  (int)$user_id)
								->where('type',     'topic')
								->row();
	}

	public function subscribe($topic_id, $user_id)
	{
		if ($this->is_subscribed($topic_id, $user_id))
		{
			return FALSE;
		}

		$this->db->insert('nf_forum_track', [
			'topic_id' => (int)$topic_id,
			'user_id'  => (int)$user_id,
			'type'     => 'topic'
		]);

		return TRUE;
	}

	public function unsubscribe($topic_id, $user_id)
	{
		$this->db	->where('topic_id', (int)$topic_id)
					->where('user_id',  (int)$user_id)
					->where('type',     'topic')
					->delete('nf_forum_track');

		return TRUE;
	}

	public function get_subscriptions($user_id)
	{
		return $this->db->select(	't.topic_id',
									't.title',
									't.forum_id',
									't.last_message_id',
									't.count_messages',
									'f.title as forum_title',
									'tr.created_at as subscribed_at',
									'tr.last_notified_at',
									'um.username as last_username',
									'm.date as last_message_date'
								)
						->from('nf_forum_track tr')
						->join('nf_forum_topics t',   't.topic_id = tr.topic_id')
						->join('nf_forum f',          'f.forum_id = t.forum_id')
						->join('nf_forum_messages m', 'm.message_id = t.last_message_id')
						->join('nf_user um',          'um.id = m.user_id AND um.deleted = "0"')
						->where('tr.user_id', (int)$user_id)
						->where('tr.type',    'topic')
						->order_by('m.date DESC')
						->get();
	}

	public function get_subscribers($topic_id, $exclude_user_id = NULL)
	{
		$this->db	->select('tr.user_id', 'u.username', 'u.email')
					->from('nf_forum_track tr')
					->join('nf_user u', 'u.id = tr.user_id AND u.deleted = "0"')
					->where('tr.topic_id', (int)$topic_id)
					->where('tr.type',     'topic')
					->where('u.email !=',  '');

		if ($exclude_user_id !== NULL)
		{
			$this->db->where('tr.user_id !=', (int)$exclude_user_id);
		}

		return $this->db->get();
	}

	public function mark_subscribers_notified($topic_id, $user_ids)
	{
		if (empty($user_ids))
		{
			return;
		}

		$this->db	->where('topic_id', (int)$topic_id)
					->where('user_id',  array_map('intval', $user_ids))
					->where('type',     'topic')
					->update('nf_forum_track', 'last_notified_at = CURRENT_TIMESTAMP');
	}

	// =================================================================
	// /Subscriptions
	// =================================================================

	// =================================================================
	// Mentions @user (Phase 4) — utilise nf_forum_mentions
	// =================================================================

	public function parse_mentions($content)
	{
		// Capture @username ou @"User Name" (avec espaces si guillemets)
		// Username NeoFrag = varchar(100), pas de regex char class restrictive sur ce fork
		// On accepte: lettres, chiffres, _, -, espaces (si entre guillemets)
		$mentions = [];

		if (preg_match_all('/(?:^|[\s\(\[\>])@(?:"([^"]+)"|([a-zA-Z0-9_\-]+))/u', $content, $matches, PREG_SET_ORDER))
		{
			foreach ($matches as $match)
			{
				$username = !empty($match[1]) ? $match[1] : $match[2];
				$mentions[$username] = TRUE;
			}
		}

		return array_keys($mentions);
	}

	public function record_mentions($message_id, $mentioner_user_id, $content)
	{
		$usernames = $this->parse_mentions($content);

		if (empty($usernames))
		{
			return [];
		}

		$users = $this->db	->select('id', 'username', 'email')
							->from('nf_user')
							->where('username', $usernames)
							->where('deleted', '0')
							->where('id !=', (int)$mentioner_user_id)
							->get();

		if (empty($users))
		{
			return [];
		}

		// Cleanup les mentions existantes pour ce message (cas edit)
		$this->db	->where('message_id', (int)$message_id)
					->delete('nf_forum_mentions');

		$mentioned = [];

		foreach ($users as $user)
		{
			$this->db->insert('nf_forum_mentions', [
				'message_id'        => (int)$message_id,
				'mentioned_user_id' => (int)$user['id'],
				'mentioner_user_id' => (int)$mentioner_user_id
			]);

			$mentioned[] = $user;
		}

		return $mentioned;
	}

	public function get_unread_mentions($user_id)
	{
		return $this->db->select(	'mn.mention_id',
									'mn.message_id',
									'mn.created_at',
									'm.topic_id',
									't.title as topic_title',
									'um.username as mentioner_username'
								)
						->from('nf_forum_mentions mn')
						->join('nf_forum_messages m', 'm.message_id = mn.message_id')
						->join('nf_forum_topics t',   't.topic_id = m.topic_id')
						->join('nf_user um',          'um.id = mn.mentioner_user_id')
						->where('mn.mentioned_user_id', (int)$user_id)
						->where('mn.read_at', NULL)
						->order_by('mn.created_at DESC')
						->get();
	}

	public function mark_mention_read($mention_id, $user_id)
	{
		$this->db	->where('mention_id', (int)$mention_id)
					->where('mentioned_user_id', (int)$user_id)
					->update('nf_forum_mentions', 'read_at = CURRENT_TIMESTAMP');
	}

	public function mark_all_mentions_read($user_id)
	{
		$this->db	->where('mentioned_user_id', (int)$user_id)
					->where('read_at', NULL)
					->update('nf_forum_mentions', 'read_at = CURRENT_TIMESTAMP');
	}

	// render_mentions vit dans le module class (forum.php) pour être accessible
	// depuis les vues via $this->output->module()->render_mentions(...)

	// =================================================================
	// /Mentions
	// =================================================================

	// =================================================================
	// Mod avancée (Phase 7-bis) — split / merge / trash
	// =================================================================

	public function split_topic($source_topic_id, array $message_ids, $new_title)
	{
		$message_ids = array_filter(array_map('intval', $message_ids));
		if (empty($message_ids))
		{
			return FALSE;
		}

		$source = $this->db	->select('forum_id', 'message_id as starter_id')
							->from('nf_forum_topics')
							->where('topic_id', (int)$source_topic_id)
							->row();
		if (!$source)
		{
			return FALSE;
		}

		// Sécurité : on ne permet pas de split le starter (il deviendrait orphelin de topic)
		$message_ids = array_diff($message_ids, [(int)$source['starter_id']]);
		if (empty($message_ids))
		{
			return FALSE;
		}

		// Le nouveau starter du new topic = le plus ancien message déplacé
		sort($message_ids);
		$new_starter_id = (int)$message_ids[0];

		$this->db->transaction();

		try
		{
			// Crée le nouveau topic
			$new_topic_id = $this->db->ignore_foreign_keys()->insert('nf_forum_topics', [
				'forum_id'   => (int)$source['forum_id'],
				'message_id' => $new_starter_id,
				'status'     => '0'
			]);

			// Title du nouveau topic
			$this->db	->where('topic_id', $new_topic_id)
						->update('nf_forum_topics', ['title' => (string)$new_title]);

			// Déplace les messages sélectionnés vers le nouveau topic
			$ids_csv = implode(',', $message_ids);
			$this->db	->where('message_id IN ('.$ids_csv.')')
						->update('nf_forum_messages', ['topic_id' => $new_topic_id]);

			// Recompte les counts
			$source_count = $this->db->from('nf_forum_messages')->where('topic_id', (int)$source_topic_id)->count() - 1;
			$new_count    = $this->db->from('nf_forum_messages')->where('topic_id', $new_topic_id)->count() - 1;

			$source_last = $this->db->select('message_id')->from('nf_forum_messages')->where('topic_id', (int)$source_topic_id)->order_by('message_id DESC')->row();
			$new_last    = $this->db->select('message_id')->from('nf_forum_messages')->where('topic_id', $new_topic_id)->order_by('message_id DESC')->row();

			$this->db	->where('topic_id', (int)$source_topic_id)
						->update('nf_forum_topics', [
							'count_messages'  => max(0, $source_count),
							'last_message_id' => $source_last ?: NULL
						]);

			$this->db	->where('topic_id', $new_topic_id)
						->update('nf_forum_topics', [
							'count_messages'  => max(0, $new_count),
							'last_message_id' => $new_last ?: NULL
						]);

			// Update forum count_topics
			$this->db	->where('forum_id', (int)$source['forum_id'])
						->update('nf_forum', 'count_topics = count_topics + 1');

			$this->db->commit();
		}
		catch (\Throwable $e)
		{
			$this->db->rollback();
			throw $e;
		}

		$this->events->fire('forum.topic.split', [
			'source_topic_id' => (int)$source_topic_id,
			'new_topic_id'    => (int)$new_topic_id,
			'message_ids'     => $message_ids,
			'forum_id'        => (int)$source['forum_id']
		]);

		return $new_topic_id;
	}

	public function merge_topics($source_topic_id, $target_topic_id)
	{
		if ((int)$source_topic_id === (int)$target_topic_id)
		{
			return FALSE;
		}

		$source = $this->db	->select('forum_id', 'count_messages')
							->from('nf_forum_topics')
							->where('topic_id', (int)$source_topic_id)
							->row();
		$target = $this->db	->select('forum_id', 'count_messages')
							->from('nf_forum_topics')
							->where('topic_id', (int)$target_topic_id)
							->row();

		if (!$source || !$target)
		{
			return FALSE;
		}

		$this->db->transaction();

		try
		{
			// Déplacer tous les messages du source vers le target
			$this->db	->where('topic_id', (int)$source_topic_id)
						->update('nf_forum_messages', ['topic_id' => (int)$target_topic_id]);

			// Supprimer le source topic (mais garder ses messages déjà déplacés)
			// On set message_id et last_message_id NULL avant pour éviter FK violation
			$this->db	->where('topic_id', (int)$source_topic_id)
						->update('nf_forum_topics', ['message_id' => NULL, 'last_message_id' => NULL]);
			$this->db	->where('topic_id', (int)$source_topic_id)
						->delete('nf_forum_topics');

			// Recompte target
			$target_count = $this->db->from('nf_forum_messages')->where('topic_id', (int)$target_topic_id)->count() - 1;
			$target_last  = $this->db->select('message_id')->from('nf_forum_messages')->where('topic_id', (int)$target_topic_id)->order_by('message_id DESC')->row();

			$this->db	->where('topic_id', (int)$target_topic_id)
						->update('nf_forum_topics', [
							'count_messages'  => max(0, $target_count),
							'last_message_id' => $target_last ?: NULL
						]);

			// Update forum count_topics (-1 car source disparu)
			$this->db	->where('forum_id', (int)$source['forum_id'])
						->update('nf_forum', 'count_topics = GREATEST(count_topics - 1, 0)');

			$this->db->commit();
		}
		catch (\Throwable $e)
		{
			$this->db->rollback();
			throw $e;
		}

		$this->events->fire('forum.topics.merged', [
			'source_topic_id' => (int)$source_topic_id,
			'target_topic_id' => (int)$target_topic_id,
			'forum_id'        => (int)$target['forum_id']
		]);

		return TRUE;
	}

	public function get_trashed_messages($limit = 100)
	{
		return $this->db->select(	'm.message_id',
									'm.topic_id',
									'm.user_id',
									'm.deleted_at',
									'm.deleted_by',
									'm.deleted_reason',
									't.title as topic_title',
									't.forum_id',
									'f.title as forum_title',
									'u.username',
									'ud.username as deleter_username'
								)
						->from('nf_forum_messages m')
						->join('nf_forum_topics t', 't.topic_id = m.topic_id')
						->join('nf_forum f',        'f.forum_id = t.forum_id')
						->join('nf_user u',         'u.id = m.user_id')
						->join('nf_user ud',        'ud.id = m.deleted_by')
						->where('m.deleted_at IS NOT NULL')
						->order_by('m.deleted_at DESC')
						->limit((int)$limit)
						->get();
	}

	public function restore_message($message_id)
	{
		$msg = $this->db	->select('topic_id', 'message_id')
							->from('nf_forum_messages')
							->where('message_id', (int)$message_id)
							->where('deleted_at IS NOT NULL')
							->row();

		if (!$msg)
		{
			return FALSE;
		}

		// On ne restaure pas le message text (NULL) car on l'a perdu au soft-delete legacy
		// Mais on enlève les flags deleted_*
		$this->db	->where('message_id', (int)$message_id)
					->update('nf_forum_messages', 'deleted_at = NULL, deleted_by = NULL, deleted_reason = NULL');

		return TRUE;
	}

	public function hard_delete_message($message_id)
	{
		$this->db->transaction();

		try
		{
			$msg = $this->db	->select('topic_id')
								->from('nf_forum_messages')
								->where('message_id', (int)$message_id)
								->row();

			if (!$msg)
			{
				$this->db->rollback();
				return FALSE;
			}

			$this->db	->where('message_id', (int)$message_id)
						->delete('nf_forum_messages');

			// Update topic count
			$count = $this->db->from('nf_forum_messages')->where('topic_id', (int)$msg)->count() - 1;
			$last  = $this->db->select('message_id')->from('nf_forum_messages')->where('topic_id', (int)$msg)->order_by('message_id DESC')->row();

			$this->db	->where('topic_id', (int)$msg)
						->update('nf_forum_topics', [
							'count_messages'  => max(0, $count),
							'last_message_id' => $last ?: NULL
						]);

			$this->db->commit();
		}
		catch (\Throwable $e)
		{
			$this->db->rollback();
			throw $e;
		}

		return TRUE;
	}

	// =================================================================
	// /Mod avancée
	// =================================================================

	// =================================================================
	// Admin attachments (Phase 5-bis)
	// =================================================================

	public function get_all_attachments($limit = 200)
	{
		return $this->db->select(	'a.attachment_id',
									'a.message_id',
									'a.file_id',
									'a.file_size',
									'a.mime_type',
									'f.name',
									'f.path',
									'f.date as uploaded_at',
									'm.topic_id',
									'm.user_id as uploader_id',
									'u.username as uploader_username',
									't.title as topic_title',
									't.forum_id',
									'fr.title as forum_title'
								)
						->from('nf_forum_attachments a')
						->join('nf_file f',           'f.id = a.file_id')
						->join('nf_forum_messages m', 'm.message_id = a.message_id')
						->join('nf_forum_topics t',   't.topic_id = m.topic_id')
						->join('nf_forum fr',         'fr.forum_id = t.forum_id')
						->join('nf_user u',           'u.id = m.user_id')
						->order_by('a.attachment_id DESC')
						->limit((int)$limit)
						->get();
	}

	public function get_attachments_stats()
	{
		$row = $this->db	->select('COUNT(*) as total', 'COALESCE(SUM(file_size), 0) as total_size', 'COALESCE(AVG(file_size), 0) as avg_size')
							->from('nf_forum_attachments')
							->row();
		return is_array($row) ? $row : ['total' => 0, 'total_size' => 0, 'avg_size' => 0];
	}

	public function find_orphan_attachments()
	{
		// Attachments dont le message_id n'existe plus (théoriquement impossible vu FK CASCADE,
		// mais on check au cas où).
		return $this->db->select('a.attachment_id', 'a.file_id', 'a.message_id', 'f.name', 'f.path')
						->from('nf_forum_attachments a')
						->join('nf_file f', 'f.id = a.file_id')
						->where('a.message_id NOT IN (SELECT message_id FROM nf_forum_messages)')
						->get();
	}

	public function find_orphan_files()
	{
		// Files dans nf_file qui ne sont liés à aucun forum_attachment (et qui pointent vers /upload/forum/)
		// Détection simple : tous les nf_file dont le path commence par 'upload/forum/' et qui ne sont pas dans nf_forum_attachments
		return $this->db->select('f.id as file_id', 'f.name', 'f.path', 'f.date', 'f.user_id')
						->from('nf_file f')
						->where('f.path LIKE', 'upload/forum/%')
						->where('f.id NOT IN (SELECT file_id FROM nf_forum_attachments)')
						->get();
	}

	// =================================================================
	// /Admin attachments
	// =================================================================

	// =================================================================
	// Admin subscriptions / mentions / search (Phase 3-bis / 4-bis / 8-bis)
	// =================================================================

	public function get_all_subscriptions($limit = 500)
	{
		return $this->db->select(	'tr.topic_id',
									'tr.user_id',
									'tr.created_at',
									'tr.last_notified_at',
									'tr.type',
									'u.username',
									'u.email',
									't.title as topic_title',
									't.forum_id',
									'f.title as forum_title'
								)
						->from('nf_forum_track tr')
						->join('nf_user u',         'u.id = tr.user_id')
						->join('nf_forum_topics t', 't.topic_id = tr.topic_id')
						->join('nf_forum f',        'f.forum_id = t.forum_id')
						->order_by('tr.created_at DESC')
						->limit((int)$limit)
						->get();
	}

	public function admin_unsubscribe($topic_id, $user_id)
	{
		$this->db	->where('topic_id', (int)$topic_id)
					->where('user_id',  (int)$user_id)
					->where('type',     'topic')
					->delete('nf_forum_track');
		return TRUE;
	}

	public function get_all_mentions($limit = 500, array $filters = [])
	{
		$q = $this->db	->select(	'mn.mention_id',
									'mn.message_id',
									'mn.mentioned_user_id',
									'mn.mentioner_user_id',
									'mn.created_at',
									'mn.read_at',
									'um.username as mentioned_username',
									'umr.username as mentioner_username',
									'm.topic_id',
									't.title as topic_title'
								)
						->from('nf_forum_mentions mn')
						->join('nf_user um',           'um.id = mn.mentioned_user_id')
						->join('nf_user umr',          'umr.id = mn.mentioner_user_id')
						->join('nf_forum_messages m',  'm.message_id = mn.message_id')
						->join('nf_forum_topics t',    't.topic_id = m.topic_id');

		if (!empty($filters['status']) && in_array($filters['status'], ['read', 'unread'], TRUE))
		{
			$q->where($filters['status'] === 'read' ? 'mn.read_at IS NOT NULL' : 'mn.read_at IS NULL');
		}

		if (!empty($filters['user']))
		{
			$user = trim((string)$filters['user']);
			$q->where('um.username LIKE', $user.'%', 'OR', 'umr.username LIKE', $user.'%');
		}

		return $q	->order_by('mn.created_at DESC')
					->limit((int)$limit)
					->get();
	}

	public function mark_mentions_read(array $mention_ids)
	{
		$ids = array_filter(array_map('intval', $mention_ids));
		if (empty($ids))
		{
			return 0;
		}
		return (int)$this->db	->where('mention_id', $ids)
								->where('read_at', NULL)
								->update('nf_forum_mentions', ['read_at' => date('Y-m-d H:i:s')]);
	}

	public function delete_mentions(array $mention_ids)
	{
		$ids = array_filter(array_map('intval', $mention_ids));
		if (empty($ids))
		{
			return 0;
		}
		return (int)$this->db	->where('mention_id', $ids)
								->delete('nf_forum_mentions');
	}

	public function search_users_for_autocomplete($prefix, $limit = 10)
	{
		$prefix = trim((string)$prefix);
		if (strlen($prefix) < 1)
		{
			return [];
		}

		return $this->db->select('id', 'username')
						->from('nf_user')
						->where('username LIKE', $prefix.'%')
						->where('deleted', '0')
						->order_by('username')
						->limit((int)$limit)
						->get();
	}

	public function reindex_fulltext()
	{
		// Force reindex via OPTIMIZE TABLE qui rebuild les FT indexes
		$this->db->execute('OPTIMIZE TABLE nf_forum_messages');
		$this->db->execute('OPTIMIZE TABLE nf_forum_topics');
		return TRUE;
	}

	public function get_search_stats()
	{
		$msg_count = $this->db->from('nf_forum_messages')->where('deleted_at IS NULL')->count();
		$topic_count = $this->db->from('nf_forum_topics')->count();

		return [
			'indexed_messages' => $msg_count,
			'indexed_topics'   => $topic_count
		];
	}

	// =================================================================
	// /Admin subscriptions / mentions / search
	// =================================================================

	// =================================================================
	// Attachments (Phase 5) — utilise nf_forum_attachments + nf_file FK
	// =================================================================

	public function attach_file($message_id, $file_id, $size, $mime)
	{
		return $this->db->insert('nf_forum_attachments', [
			'message_id' => (int)$message_id,
			'file_id'    => (int)$file_id,
			'file_size'  => (int)$size,
			'mime_type'  => $mime
		]);
	}

	public function get_attachments($message_id)
	{
		return $this->db->select(	'a.attachment_id',
									'a.file_id',
									'a.file_size',
									'a.mime_type',
									'f.name',
									'f.path',
									'f.user_id as uploader_id'
								)
						->from('nf_forum_attachments a')
						->join('nf_file f', 'f.id = a.file_id')
						->where('a.message_id', (int)$message_id)
						->order_by('a.attachment_id')
						->get();
	}

	public function get_attachments_by_topic($topic_id, $limit = NULL)
	{
		$this->db	->select(	'a.attachment_id',
								'a.file_id',
								'a.file_size',
								'a.mime_type',
								'a.message_id',
								'f.name',
								'f.path',
								'm.user_id as uploader_id',
								'u.username as uploader_username'
							)
					->from('nf_forum_attachments a')
					->join('nf_file f',           'f.id = a.file_id')
					->join('nf_forum_messages m', 'm.message_id = a.message_id')
					->join('nf_user u',           'u.id = m.user_id')
					->where('m.topic_id', (int)$topic_id)
					->order_by('a.attachment_id');

		if ($limit)
		{
			$this->db->limit((int)$limit);
		}

		return $this->db->get();
	}

	public function delete_attachment($attachment_id)
	{
		// Lock anti-delete : si l'attachment ou le message parent est référencé par un report
		// pending/reviewed, on bloque la suppression (le fichier doit rester accessible aux modos).
		// La copie défensive existe déjà mais on garde aussi l'original tant que report ouvert.
		if (isset($this->moderation) && ($locked_by = $this->moderation->is_attachment_locked('forum', (int)$attachment_id)))
		{
			return ['locked_by_report' => (int)$locked_by];
		}

		// Récupère le file_id pour pouvoir cascade-delete le nf_file aussi
		$attachment = $this->db	->select('file_id')
								->from('nf_forum_attachments')
								->where('attachment_id', (int)$attachment_id)
								->row();

		if (!$attachment)
		{
			return FALSE;
		}

		$this->db->transaction();

		try
		{
			$this->db	->where('attachment_id', (int)$attachment_id)
						->delete('nf_forum_attachments');

			// Supprime le fichier physique + record nf_file
			if ($file = $this->model2('file', $attachment))
			{
				$file->delete();
			}

			$this->db->commit();
		}
		catch (\Throwable $e)
		{
			$this->db->rollback();
			throw $e;
		}

		return TRUE;
	}

	public function get_allowed_mimes()
	{
		$default = 'image/jpeg,image/png,image/gif,image/webp,application/pdf,text/plain,application/zip';
		$value = isset($this->config->forum_attachments_mimes) && $this->config->forum_attachments_mimes
				? $this->config->forum_attachments_mimes
				: $default;
		return array_filter(array_map('trim', explode(',', $value)));
	}

	public function get_max_size_bytes()
	{
		$kb = isset($this->config->forum_attachments_size_max_kb)
				? (int)$this->config->forum_attachments_size_max_kb
				: 5120; // 5 MB default
		return $kb * 1024;
	}

	// =================================================================
	// /Attachments
	// =================================================================

	public function _has_unread($forum)
	{
		if (!$forum['count_topics'] || !$this->user())
		{
			return FALSE;
		}

		static $forum_reads;

		if ($forum_reads === NULL)
		{
			$forum_reads = [];

			foreach ($this->db	->select('forum_id', 'date')
								->from('nf_forum_read')
								->where('user_id', $this->user->id)
								->get() as $read)
			{
				$forum_reads[$read['forum_id']] = strtotime($read['date']);
			}

			$registration_date = strtotime($this->user->registration_date);
			if (!isset($forum_reads[0]) || $registration_date > $forum_reads[0])
			{
				$forum_reads[0] = $registration_date;
			}
		}

		$dates = [];

		if (isset($forum_reads[0]))
		{
			$dates[] = $forum_reads[0];
		}

		if (isset($forum_reads[$forum['forum_id']]))
		{
			$dates[] = $forum_reads[$forum['forum_id']];
		}

		$forum_read_date = $dates ? max($dates) : NULL;

		return empty($forum_read_date) || $forum_read_date < strtotime($forum['last_message_date']);
	}
}
