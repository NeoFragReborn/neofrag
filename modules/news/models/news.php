<?php
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\News\Models;

use NF\NeoFrag\Loadables\Model;

class News extends Model
{
	public function get_news($filter = '', $filter_data = '')
	{
		$this->db	->select('n.*', 'nl.title', 'nl.introduction', 'nl.content', 'nl.tags', 'IFNULL(n.image_id, c.image_id) as image', 'c.icon_id as category_icon', 'c.name as category_name', 'cl.title as category_title', 'u.id as user_id', 'u.username', 'up.avatar', 'up.sex')
					->from('nf_news n')
					->join('nf_news_lang nl',            'n.news_id     = nl.news_id')
					->join('nf_news_categories c',       'n.category_id = c.category_id')
					->join('nf_news_categories_lang cl', 'c.category_id = cl.category_id')
					->join('nf_user u',                  'n.user_id     = u.id AND u.deleted = "0"')
					->join('nf_user_profile up',         'up.id         = u.id')
					->where('nl.lang', $this->config->lang->info()->name)
					->where('cl.lang', $this->config->lang->info()->name)
					->where('n.deleted_at', NULL)
					->order_by('n.date DESC');

		if (!empty($filter) && !empty($filter_data))
		{
			if ($filter == 'tag')
			{
				$this->db->where('nl.tags FIND_IN_SET', $filter_data);
			}
			else if ($filter == 'category')
			{
				$this->db->where('n.category_id', $filter_data);
			}
		}

		if (!$this->url->admin)
		{
			// Publication programmée : une date future masque l'actualité jusqu'à son heure.
			$this->db->where('n.published', TRUE)->where('n.date <=', date('Y-m-d H:i:s'));
		}

		return $this->db->get();
	}

	public function get_news_by_user($user_id, $news_id)
	{
		return $this->db->select('n.news_id', 'nl.title', 'cl.title as category_title')
						->from('nf_news n')
						->join('nf_news_lang nl',            'n.news_id     = nl.news_id')
						->join('nf_news_categories c',       'n.category_id = c.category_id')
						->join('nf_news_categories_lang cl', 'c.category_id = cl.category_id')
						->where('n.published', TRUE)
						->where('nl.lang', $this->config->lang->info()->name)
						->where('cl.lang', $this->config->lang->info()->name)
						->where('n.user_id', $user_id)
						->where('n.news_id <>', $news_id)
						->where('n.deleted_at', NULL)
						->order_by('n.date DESC')
						->limit(5)
						->get();
	}

	public function check_news($news_id, $title, $lang = 'default')
	{
		if ($lang == 'default')
		{
			$lang = $this->config->lang->info()->name;
		}

		$this->db	->select('n.news_id', 'n.category_id', 'u.id as user_id', 'n.image_id', 'n.date', 'n.published', 'n.views', 'n.vote', 'nl.title', 'nl.introduction', 'nl.content', 'nl.tags', 'c.name as category_name', 'cl.title as category_title', 'IFNULL(n.image_id, c.image_id) as image', 'c.icon_id as category_icon', 'u.username', 'u.admin', 'MAX(s.last_activity) > DATE_SUB(NOW(), INTERVAL 5 MINUTE) as online', 'up.quote', 'up.avatar', 'up.sex')
						->from('nf_news n')
						->join('nf_news_lang nl',            'n.news_id     = nl.news_id')
						->join('nf_news_categories c',       'n.category_id = c.category_id')
						->join('nf_news_categories_lang cl', 'c.category_id = cl.category_id')
						->join('nf_user u',                  'u.id          = n.user_id AND u.deleted = "0"')
						->join('nf_user_profile up',         'u.id          = up.id')
						->join('nf_session        s',        'u.id          = s.user_id')
						->where('n.news_id', $news_id)
						->where('nl.lang', $lang)
						->where('cl.lang', $lang)
						->where('n.deleted_at', NULL);

		if (!$this->url->admin)
		{
			// Vue directe d'une actualité : une programmée (date future) reste masquée hors admin.
			$this->db->where('n.published', TRUE)->where('n.date <=', date('Y-m-d H:i:s'));
		}

		$news = $this->db->row();

		if ($news && url_title($news['title']) == $title)
		{
			return $news;
		}
		else
		{
			return FALSE;
		}
	}

	public function increment_views($news_id)
	{
		$this->db->execute('UPDATE nf_news SET views = views + 1 WHERE news_id = '.(int)$news_id);
	}

	public function add_news($title, $category_id, $image_id, $introduction, $content, $tags, $published, $date = '')
	{
		$news_id = $this->db->insert('nf_news', [
								'category_id' => $category_id,
								'user_id'     => $this->user->id,
								'image_id'    => $image_id,
								'published'   => $published,
								'date'        => $date ?: date('Y-m-d H:i:s')
							]);

		$this->db	->insert('nf_news_lang', [
						'news_id'      => $news_id,
						'lang'         => $this->config->lang->info()->name,
						'title'        => $title,
						'introduction' => $introduction,
						'content'      => $content,
						'tags'         => $this->_tags($tags)
					]);

		return $news_id;
	}

	public function edit_news($news_id, $category_id, $image_id, $published, $title, $introduction, $content, $tags, $lang, $date = '')
	{
		$this->db	->where('news_id', $news_id)
					->update('nf_news', array_merge([
						'category_id' => $category_id,
						'image_id'    => $image_id,
						'published'   => $published
					], $date ? ['date' => $date] : []));

		$this->db	->where('news_id', $news_id)
					->where('lang', $lang)
					->update('nf_news_lang', [
						'title'        => $title,
						'introduction' => $introduction,
						'content'      => $content,
						'tags'         => $this->_tags($tags)
					]);
	}

	/**
	 * Parution effective d'une actualité : émet event + webhook + gamification + notifications
	 * UNE SEULE FOIS, au moment réel de parution. Idempotent via announced_at. Appelée à
	 * l'enregistrement (parution immédiate) ET par l'endpoint de parution (cron) pour le
	 * contenu programmé arrivé à échéance.
	 *
	 * @return bool TRUE si l'annonce vient d'être émise.
	 */
	public function announce($news_id)
	{
		$news_id = (int)$news_id;

		$row = $this->db	->select('n.user_id', 'n.category_id', 'n.date', 'n.published', 'n.announced_at', 'nl.title')
							->from('nf_news n')
							->join('nf_news_lang nl', 'n.news_id = nl.news_id')
							->where('n.news_id', $news_id)
							->where('nl.lang', $this->config->lang->info()->name)
							->where('n.deleted_at', NULL)
							->row();

		if (!$row || $row['published'] != '1' || !empty($row['announced_at']) || strtotime($row['date']) > time())
		{
			return FALSE;
		}

		// Marque AVANT d'émettre : empêche toute double émission (ré-entrance / passages cron concurrents).
		$this->db->where('news_id', $news_id)->update('nf_news', ['announced_at' => date('Y-m-d H:i:s')]);

		$title = (string)$row['title'];
		$url   = 'news/'.$news_id.'/'.url_title($title);
		$owner = (int)$row['user_id'];

		$this->events->fire('news.published', ['news_id' => $news_id, 'title' => $title]);

		if ($gam = $this->module('gamification'))
		{
			if ($gam_owner = $gam->content_owner('news', $news_id))
			{
				$gam->earn($gam_owner, 'news');
				$gam->recompute($gam_owner);
			}
		}

		if ($wh = $this->module('webhooks'))
		{
			$wh->trigger('news.published', ['news_id' => $news_id, 'title' => $title, 'url' => url($url)]);
		}

		if ($notifications = $this->module('notifications'))
		{
			foreach ($notifications->subscribers('news-category', (int)$row['category_id'], $owner) as $uid)
			{
				$notifications->push($uid, 'news', $this->lang('Nouvelle actualité : %s', $title), $url, $owner);
			}
		}

		return TRUE;
	}

	/** Parution des actualités programmées arrivées à échéance (appelée par l'endpoint cron). @return int annoncées */
	public function publish_scheduled()
	{
		$count = 0;

		foreach ($this->db	->select('news_id')
							->from('nf_news')
							->where('published', '1')
							->where('announced_at', NULL)
							->where('date <=', date('Y-m-d H:i:s'))
							->where('deleted_at', NULL)
							->get() as $news_id)
		{
			if ($this->announce($news_id))
			{
				$count++;
			}
		}

		return $count;
	}

	// Soft-delete : l'actualité part à la corbeille (restaurable), elle n'est pas effacée.
	public function delete_news($news_id)
	{
		$this->db	->where('news_id', $news_id)
					->update('nf_news', [
						'deleted_at' => date('Y-m-d H:i:s'),
						'deleted_by' => $this->user() ? (int)$this->user->id : NULL
					]);
	}

	public function restore_news($news_id)
	{
		$this->db	->where('news_id', $news_id)
					->update('nf_news', 'deleted_at = NULL, deleted_by = NULL');
	}

	// Purge : vraie suppression définitive (image + commentaires + lignes).
	public function purge_news($news_id)
	{
		NeoFrag()->model2('file', $this->db->select('image_id')->from('nf_news')->where('news_id', $news_id)->row())->delete();

		if ($comments = $this->module('comments'))
		{
			$comments->delete('news', $news_id);
		}

		$this->db	->where('news_id', $news_id)
					->delete('nf_news');
	}

	private function _tags($tags)
	{
		return implode(',', array_unique(array_map('trim', preg_split('/[ ,;]+/', $tags, -1, PREG_SPLIT_NO_EMPTY))));
	}
}
