<?php
/**
 * https://neofr.ag
 * Voir ses notifications nécessite d'être connecté.
 */

namespace NF\Modules\Notifications\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module_Checker;

class Checker extends Module_Checker
{
	public function index($page = '')
	{
		if (!$this->user())
		{
			$this->error->unconnected();
			$this->error->unauthorized();
		}

		return [$page];
	}
}
