<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\News\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module_Checker;

class Checker extends Module_Checker
{
	public function index($page = '')
	{
		return [$this->module->pagination->fix_items_per_page($this->config->news_per_page)->get_data($this->model()->get_news(), $page)];
	}

	public function _tag($tag, $page = '')
	{
		return [$tag, $this->module->pagination->fix_items_per_page($this->config->news_per_page)->get_data($this->model()->get_news('tag', $tag), $page)];
	}

	public function _category($category_id, $name, $page = '')
	{
		// L'adresse porte le nom court de la catégorie, refait à chaque changement de titre : vérifiée avec le VRAI, une
		// ancienne adresse mène à la nouvelle au lieu de répondre 404 (m06).
		$vrai = nf_titre_lu($this->db->select('name')->from('nf_news_categories')->where('category_id', (int) $category_id)->row());

		if ($vrai !== '' && ($category = $this->model('categories')->check_category($category_id, $vrai)))
		{
			nf_bon_titre((string) $name, $vrai, 'news/category/'.(int) $category_id, (string) $page);

			return [$category['title'], $this->module->pagination->fix_items_per_page($this->config->news_per_page)->get_data($this->model()->get_news('category', $category_id), $page), (int)$category_id];
		}
	}

	public function _news($news_id, $title)
	{
		if ($news = $this->model()->check_news($news_id, $title))
		{
			if (count_view('news', $news['news_id']))
			{
				$this->model()->increment_views($news['news_id']);
			}
			return $news;
		}
	}
}
