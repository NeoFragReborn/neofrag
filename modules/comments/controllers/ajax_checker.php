<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\Comments\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module_Checker;

class Ajax_Checker extends Module_Checker
{
	public function delete($comment_id)
	{
		$comment = $this->db->select('user_id', 'module_id', 'module')
							->from('nf_comment')
							->where('id', (int)$comment_id)
							->row();

		if ($comment && ($this->access->effective_admin() || ($this->user() && $comment['user_id'] == $this->user->id)))
		{
			return [$comment_id, $comment['module_id'], $comment['module']];
		}
	}
}
