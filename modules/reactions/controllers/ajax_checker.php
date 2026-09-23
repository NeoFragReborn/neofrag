<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * Auth checker pour /ajax/reactions/* — réagir nécessite d'être connecté.
 */

namespace NF\Modules\Reactions\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module_Checker;

class Ajax_Checker extends Module_Checker
{
	public function _toggle($type, $id)
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
