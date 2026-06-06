<?php
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\Forum\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module_Checker;

class Ajax_Checker extends Module_Checker
{
	public function _topic_move($topic_id, $title)
	{
		if ($topic = $this->model()->check_topic($topic_id, $title))
		{
			if ($this->access('forum', 'category_move', $topic['category_id']))
			{
				return [$topic_id, $topic['forum_id']];
			}
			else
			{
				$this->error->unauthorized();
			}
		}
	}

	public function _mentions_autocomplete()
	{
		// Pas besoin d'ACL stricte : on autorise tout user authentifié
		if (!$this->user())
		{
			$this->error->unauthorized();
			return;
		}
		return [];
	}
}
