<?php
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\Talks\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module_Checker;

class Admin_Checker extends Module_Checker
{
	public function index($page = '')
	{
		return [$this->module->pagination->get_data($this->model()->get_talks(), $page)];
	}

	public function _edit($talk_id, $title)
	{
		if ($talk_id > 1 && $talk = $this->model()->check_talk($talk_id, $title))
		{
			return $talk;
		}
	}

	public function _admin_delete($talk_id, $title)
	{
		$this->ajax();

		if ($talk_id > 1 && $talk = $this->model()->check_talk($talk_id, $title))
		{
			return $talk;
		}
	}

	public function _reports($page = '')
	{
		// Page admin signalements (Phase T6)
		return [$page];
	}
}
