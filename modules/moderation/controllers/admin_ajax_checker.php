<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 */

namespace NF\Modules\Moderation\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module_Checker;

class Admin_Ajax_Checker extends Module_Checker
{
	public function _ajax_sanction_modal($id)
	{
		if (!$this->access('moderation', 'handle_reports'))
		{
			$this->error->unauthorized();
			return;
		}
		$this->ajax();
		return [(int)$id];
	}

	public function _ajax_revoke_modal($id)
	{
		if (!$this->access('moderation', 'revoke'))
		{
			$this->error->unauthorized();
			return;
		}
		$this->ajax();
		return [(int)$id];
	}

}
