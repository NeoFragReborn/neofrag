<?php
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
				'render' => function(){
					$news = array_slice(array_filter($this->module('news')->model()->get_news(), function($a){
						return $a['published'];
					}), 0, 5);

					if (!$news)
					{
						return '';
					}

					$html = '<div class="nf-block nf-block-news"><ul class="list-group list-group-flush">';

					foreach ($news as $n)
					{
						$html .= '<li class="list-group-item"><a href="'.url('news/'.$n['news_id'].'/'.url_title($n['title'])).'">'
							.icon('far fa-newspaper').' '.htmlspecialchars($n['title']).'</a></li>';
					}

					return $html.'</ul></div>';
				}
			]
		];
	}
}
