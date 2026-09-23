<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 */

namespace NF\Modules\Statistics\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module_Checker;

class Admin_Checker extends Module_Checker
{
	public function index()
	{
		// Le module statistics n'a pas de permission dédiée → réservé aux admins effectifs.
		if (!$this->user() || !$this->access->effective_admin())
		{
			$this->error->unauthorized();
		}

		return [];
	}
}
