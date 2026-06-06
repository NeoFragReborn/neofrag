<?php
/**
 * https://neofr.ag
 * Auth checker pour /ajax/notifications/* — nécessite d'être connecté.
 */

namespace NF\Modules\Notifications\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module_Checker;

class Ajax_Checker extends Module_Checker
{
	public function _read($id)
	{
		if (!$this->user())
		{
			$this->error->unauthorized();
			return;
		}

		$this->ajax();

		return [(int)$id];
	}

	public function _read_all()
	{
		if (!$this->user())
		{
			$this->error->unauthorized();
			return;
		}

		$this->ajax();

		return [];
	}

	public function _subscribe($type, $id)
	{
		if (!$this->user())
		{
			$this->error->unauthorized();
			return;
		}

		$this->ajax();

		return [$type, (int)$id];
	}
}
