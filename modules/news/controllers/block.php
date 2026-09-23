<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 */

namespace NF\Modules\News\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Block extends Controller_Module
{
	public function block()
	{
		return [
			'news.latest' => [
				'title'  => $this->lang('Dernières actualités'),
				'fields' => [
					'count' => ['type' => 'int', 'default' => 5, 'min' => 1, 'max' => 20],
				],
				'render' => function($settings = []){
					return $this->_list($this->_published($this->module('news')->model()->get_news()), (int) ($settings['count'] ?? 5));
				}
			],
			'news.category' => [
				'title'  => $this->lang('Actualités d\'une catégorie'),
				'fields' => [
					'id'    => ['type' => 'int', 'default' => 0, 'min' => 0],
					'count' => ['type' => 'int', 'default' => 5, 'min' => 1, 'max' => 20],
				],
				'render' => function($settings = []){
					if (!($id = (int) ($settings['id'] ?? 0)))
					{
						return '';
					}

					return $this->_list($this->_published($this->module('news')->model()->get_news('category', $id)), (int) ($settings['count'] ?? 5));
				}
			]
		];
	}

	private function _published($news)
	{
		return array_filter((array) $news, function($a){
			return !empty($a['published']);
		});
	}

	private function _list($news, $count)
	{
		$news = array_slice($news, 0, max(1, $count));

		if (!$news)
		{
			return '';
		}

		$html = '<div class="nf-block nf-block-news"><ul class="list-group list-group-flush">';

		foreach ($news as $n)
		{
			$html .= '<li class="list-group-item"><a href="'.url('news/'.$n['news_id'].'/'.url_title($n['title'])).'">'
				.icon('far fa-newspaper').' '.htmlspecialchars((string) ($n['title'])).'</a></li>';
		}

		return $html.'</ul></div>';
	}
}
