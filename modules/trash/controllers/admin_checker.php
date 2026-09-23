<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * Accès corbeille : réservé à l'admin (gating effective_admin + is_authorized au niveau output).
 */

namespace NF\Modules\Trash\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module_Checker;

class Admin_Checker extends Module_Checker
{
	public function index($page = '')
	{
		return [$page];
	}
}
