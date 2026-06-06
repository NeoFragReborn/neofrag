<?php
/**
 * https://neofr.ag
 * Auth checker pour les routes ajax/moderation/* user-side.
 */

namespace NF\Modules\Moderation\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module_Checker;

class Ajax_Checker extends Module_Checker
{
	public function _ajax_report_modal()
	{
		if (!$this->user())
		{
			$this->error->unauthorized();
			return;
		}
		$this->ajax();
		return [];
	}

	public function _ajax_report_submit()
	{
		if (!$this->user())
		{
			$this->error->unauthorized();
			return;
		}
		$this->ajax();
		return [];
	}
}
