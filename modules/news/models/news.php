<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\News\Models;

use NF\NeoFrag\Loadables\Model;

class News extends Model
{
	// Back-end de parution programmée partagé avec le module articles (announce / publish_scheduled /
	// increment_views). La présentation reste propre à news (feed court votable).
	use \NF\NeoFrag\Traits\Publishable_Content;

	protected function publishable_config(): array
	{
		return [
			'table'         => 'nf_news',
			'id'            => 'news_id',
			'lang_table'    => 'nf_news_lang',
			'type'          => 'news',
			'url_prefix'    => 'news',
			'notif_message' => 'Nouvelle actualité : %s',
		];
	}

	public function get_news($filter = '', $filter_data = '')
	{
		/*
		 * La langue d'une LISTE est celle qu'on demande — sauf pour la page d'une catégorie, qui
		 * est une page de contenu comme une autre. Si la catégorie n'existe que dans une langue,
		 * la lister dans une autre rendait une liste vide, donc un 404 pour le visiteur. On sert
		 * alors la langue de la catégorie, et la page le dit.
		 */
		$lang = $filter == 'category' && !empty($filter_data)
			? $this->langue_du_contenu('nf_news_categories_lang', 'category_id', $filter_data)
			: $this->config->lang->info()->name;

		$this->db	->select('n.*', 'nl.title', 'nl.introduction', 'nl.content', 'nl.tags', 'IFNULL(n.image_id, c.image_id) as image', 'c.icon_id as category_icon', 'c.name as category_name', 'cl.title as category_title', 'u.id as user_id', 'u.username', 'up.avatar', 'up.sex')
					->from('nf_news n')
					->join('nf_news_lang nl',            'n.news_id     = nl.news_id')
					->join('nf_news_categories c',       'n.category_id = c.category_id')
					->join('nf_news_categories_lang cl', 'c.category_id = cl.category_id')
					->join('nf_user u',                  'n.user_id     = u.id AND u.deleted = "0"')
					->join('nf_user_profile up',         'up.id         = u.id')
					->where('nl.lang', $lang)
					->where('cl.lang', $lang)
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
			// Pas forcément la langue demandée : un contenu monolingue est servi dans la sienne,
			// avec un bandeau, plutôt que de rendre 404. Jamais de repli en administration.
			$lang = $this->langue_du_contenu('nf_news_lang', 'news_id', $news_id);
		}

		$this->db	->select('n.news_id', 'n.category_id', 'u.id as user_id', 'n.image_id', 'n.date', 'n.published', 'n.views', 'n.vote', 'nl.title', 'nl.introduction', 'nl.content', 'nl.tags', 'c.name as category_name', 'cl.title as category_title', 'IFNULL(n.image_id, c.image_id) as image', 'c.icon_id as category_icon', 'u.username', 'u.admin', 'MAX(s.last_activity) > DATE_SUB(NOW(), INTERVAL 5 MINUTE) as online', 'up.quote', 'up.avatar', 'up.sex')
						->from('nf_news n')
						->join('nf_news_lang nl',            'n.news_id     = nl.news_id')
						->join('nf_news_categories c',       'n.category_id = c.category_id')
						->join('nf_news_categories_lang cl', 'c.category_id = cl.category_id')
						->join('nf_user u',                  'u.id          = n.user_id AND u.deleted = "0"')
						->join('nf_user_profile up',         'u.id          = up.id')
						// LEFT, et non une jointure stricte : la table des sessions ne sert QU'À
						// calculer le témoin « en ligne » de l'auteur. En stricte, une actualité
						// dont l'auteur n'a aucune session ouverte devenait INTROUVABLE — la page
						// répondait 404 alors que l'article existait et était publié. Constaté en
						// production sur la démo : l'auteur de l'actualité 6 avait zéro session,
						// et « Continuer à lire » ne menait nulle part.
						->join('nf_session        s',        'u.id          = s.user_id', 'LEFT')
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

		if ($news && url_title($news['title']) != $title && !$this->url->admin
			&& $this->titre_d_une_autre_langue('nf_news_lang', 'news_id', $news_id, (string) $title))
		{
			// Arrivé par le sélecteur de langue, avec le titre d'une autre version : l'adresse de celle-ci.
			NeoFrag()->url->redirect_http(url('news/'.(int) $news_id.'/'.url_title($news['title'])), 301);
		}

		if ($news && url_title($news['title']) == $title)
		{
			return $news;
		}
		else
		{
			return FALSE;
		}
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
