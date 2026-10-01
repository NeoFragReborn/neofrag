<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 */

namespace NF\Modules\News\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Activity extends Controller_Module
{
	public function activity($user_id, $limit)
	{
		$items = [];

		foreach ($this->db	->select('n.news_id', 'nl.title', 'UNIX_TIMESTAMP(n.date) AS date')
							->from('nf_news n')
							->join('nf_news_lang nl', 'nl.news_id = n.news_id')
							->where('nl.lang', $this->config->lang->info()->name)
							->where('n.user_id', $user_id)
							->where('n.published', '1')
							->where('n.deleted_at IS NULL')
							->where('n.date <=', date('Y-m-d H:i:s'))
							->order_by('n.date DESC')
							->limit($limit)
							->get() as $row)
		{
			$items[] = [
				'date'  => (int)$row['date'],
				'icon'  => 'far fa-newspaper',
				'type'  => 'news',
				'title' => $row['title'],
				'url'   => 'news/'.$row['news_id'].'/'.url_title($row['title'])
			];
		}

		return $items;
	}
}
