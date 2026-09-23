<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Widgets\News\Controllers;

use NF\NeoFrag\Loadables\Controllers\Widget as Controller_Widget;

class Index extends Controller_Widget
{
	public function index($config = [])
	{
		$this->css('news');

		$news = array_filter($this->module('news')->model()->get_news(), function($a){
			return $a['published'];
		});

		if (!empty($news))
		{
			return $this->panel()
						->heading($this->lang('Actualités récentes'))
						->body($this->view('index', [
							'news' => array_slice($news, 0, 3)
						]))
						->footer('<a href="'.url('news').'">'.icon('far fa-arrow-alt-circle-right').' '.$this->lang('Voir toutes les actualités').'</a>', 'right');
		}
		else
		{
			return $this->panel()
						->heading($this->lang('Actualités récentes'))
						->body($this->lang('Aucune actualité pour le moment'));
		}
	}

	public function categories($config = [])
	{
		$categories = $this->module('news')->model('categories')->get_categories();

		if (!empty($categories))
		{
			return $this->panel()
						->heading($this->lang('Catégories'))
						->body($this->view('categories', [
							'categories' => $categories
						]), FALSE);
		}
		else
		{
			return $this->panel()
						->heading($this->lang('Catégories'))
						->body($this->lang('Aucune catégorie pour le moment'));
		}
	}

	public function tags($config = [])
	{
		$counts = [];

		if ($tags = $this->db	->select('nl.tags')
								->from('nf_news_lang nl')
								->join('nf_news n', 'nl.news_id = n.news_id')
								->where('n.published', TRUE)
								->get())
		{
			foreach ($tags as $tag)
			{
				foreach (explode(',', (string)$tag) as $t)
				{
					if (($t = trim($t)) !== '')
					{
						$counts[$t] = ($counts[$t] ?? 0) + 1; // fréquence → taille dans le nuage
					}
				}
			}
		}

		if (!empty($counts))
		{
			uksort($counts, 'strnatcasecmp'); // ordre alpha stable, la taille traduit la fréquence

			return $this->panel()
						->heading($this->lang('Tags'))
						->body($this->view('tags', [
							'tags' => $counts
						]));
		}
		else
		{
			return $this->panel()
						->heading($this->lang('Tags'))
						->body($this->lang('Aucun tag pour le moment'));
		}
	}
}
