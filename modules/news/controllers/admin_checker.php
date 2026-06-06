<?php
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\News\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module_Checker;

class Admin_Checker extends Module_Checker
{
	public function index($page = '')
	{
		$categories = [];
		foreach ($this->model('categories')->get_categories() as $c)
		{
			$categories[(int)$c['category_id']] = $c['title'];
		}

		$filters = [
			'q'        => isset($_GET['q']) ? trim((string)$_GET['q']) : '',
			'category' => isset($_GET['category']) && isset($categories[(int)$_GET['category']]) ? (int)$_GET['category'] : 0,
			'status'   => isset($_GET['status']) && in_array($_GET['status'], ['published', 'draft'], TRUE) ? $_GET['status'] : ''
		];

		$news = array_values(array_filter($this->model()->get_news(), function($n) use ($filters)
		{
			if ($filters['q'] !== '' && stripos((string)$n['title'], $filters['q']) === FALSE && stripos((string)$n['introduction'], $filters['q']) === FALSE)
			{
				return FALSE;
			}
			if ($filters['category'] && (int)$n['category_id'] !== $filters['category'])
			{
				return FALSE;
			}
			if ($filters['status'] === 'published' && empty($n['published']))
			{
				return FALSE;
			}
			if ($filters['status'] === 'draft' && !empty($n['published']))
			{
				return FALSE;
			}
			return TRUE;
		}));

		$filters['categories'] = $categories;
		$filters['matched']    = count($news);
		$filters['active']     = $filters['q'] !== '' || $filters['category'] || $filters['status'] !== '';

		return [$this->module->pagination->fix_items_per_page(12)->get_data($news, $page), $filters];
	}

	public function add()
	{
		if (!$this->is_authorized('add_news'))
		{
			$this->error->unauthorized();
		}

		return [];
	}

	public function _edit($news_id, $title, $tab = 'default')
	{
		if (!$this->is_authorized('modify_news'))
		{
			$this->error->unauthorized();
		}

		if ($news = $this->model()->check_news($news_id, $title, $tab))
		{
			// PHP 8 : call_user_func_array refuse les arrays mixtes string+numeric.
			// On retourne un array purement indexé matchant la signature du contrôleur :
			// _edit($news_id, $category_id, $user_id, $image_id, $date, $published, $views, $vote, $title, $introduction, $content, $tags, $category_name, $category_title, $news_image, $category_image, $category_icon)
			return [
				$news['news_id'],
				$news['category_id'],
				$news['user_id'],
				$news['image_id'],
				$news['date'],
				$news['published'],
				$news['views'],
				$news['vote'],
				$news['title'],
				$news['introduction'],
				$news['content'],
				$news['tags'],
				$news['category_name'],
				$news['category_title'],
				$news['image'] ?? NULL,
				$news['category_image'] ?? NULL,
				$news['category_icon']
			];
		}
	}

	public function _delete($news_id, $title)
	{
		if (!$this->is_authorized('delete_news'))
		{
			$this->error->unauthorized();
		}

		$this->ajax();

		if ($news = $this->model()->check_news($news_id, $title))
		{
			return [$news['news_id'], $news['title']];
		}
	}

	public function _history($news_id, $title)
	{
		if (!$this->is_authorized('modify_news'))
		{
			$this->error->unauthorized();
		}

		if ($news = $this->model()->check_news($news_id, $title))
		{
			return [$news['news_id'], $news['title']];
		}
	}

	public function _revision_restore($news_id, $title, $revision_id)
	{
		if (!$this->is_authorized('modify_news'))
		{
			$this->error->unauthorized();
		}

		if ($news = $this->model()->check_news($news_id, $title))
		{
			return [$news['news_id'], $news['title'], (int)$revision_id];
		}
	}

	public function _categories_add()
	{
		if (!$this->is_authorized('add_news_category'))
		{
			$this->error->unauthorized();
		}

		return [];
	}

	public function _categories_edit($category_id, $name)
	{
		if (!$this->is_authorized('modify_news_category'))
		{
			$this->error->unauthorized();
		}

		if ($category = $this->model('categories')->check_category($category_id, $name, 'default'))
		{
			return $category;
		}
	}

	public function _categories_delete($category_id, $name)
	{
		if (!$this->is_authorized('delete_news_category'))
		{
			$this->error->unauthorized();
		}

		$this->ajax();

		if ($category = $this->model('categories')->check_category($category_id, $name, 'default'))
		{
			return [$category_id, $category['title']];
		}
	}
}
