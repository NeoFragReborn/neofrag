<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 */

namespace NF\Modules\Forum\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Activity extends Controller_Module
{
	public function activity($user_id, $limit)
	{
		// Un membre sous shadow ban : son activité ne se montre pas aux autres (audit du 2026-10-09).
		if (in_array((int) $user_id, $this->moderation->auteurs_masques(), TRUE))
		{
			return [];
		}

		// Le droit de lecture ET la réserve VIP (Forum::categories_lisibles()) : le droit seul laissait lire le VIP.
		$modele     = $this->model('forum');
		$categories = $modele instanceof \NF\Modules\Forum\Models\Forum ? $modele->categories_lisibles() : [];

		if (!$categories)
		{
			return [];
		}

		$items = [];

		foreach ($this->db	->select('m.message_id', 'm.topic_id', 't.title', 'm.message', 'UNIX_TIMESTAMP(m.date) AS date')
							->from('nf_forum_messages m')
							->join('nf_forum_topics t',  'm.topic_id  = t.topic_id')
							->join('nf_forum        f',  't.forum_id  = f.forum_id')
							->join('nf_forum        f2', 'f.parent_id = f2.forum_id AND f.is_subforum = "1"')
							->where('m.user_id', $user_id)
							->where('m.deleted_at IS NULL')
							->where('IFNULL(f2.parent_id, f.parent_id)', $categories)
							->order_by('m.date DESC')
							->limit($limit)
							->get() as $row)
		{
			$items[] = [
				'date'    => (int)$row['date'],
				'icon'    => 'far fa-comments',
				'type'    => 'forum',
				'title'   => $row['title'],
				'url'     => 'forum/topic/'.$row['topic_id'].'/'.url_title($row['title']).'#'.$row['message_id'],
				'excerpt' => mb_substr(trim(strip_tags($row['message'])), 0, 140)
			];
		}

		return $items;
	}
}
