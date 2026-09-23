<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\Monitoring\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module_Checker;

class Admin_Ajax_Checker extends Module_Checker
{
	public function index()
	{
		if ($check = post_check('refresh'))
		{
			$this->extension('json');
			return [$check['refresh']];
		}
	}

	public function backup()
	{
		// Pas d'extension .json sur l'URL : le backup streame du text/event-stream (pas du JSON), et
		// surtout l'URL en .json se faisait avaler par nginx/Plesk (« smart static ») → 404 côté prod.
		// On route donc comme /…/phpinfo (sans extension) ; le contrôleur backup() pose ses propres headers.
		return [];
	}

	public function update()
	{
		$this->extension('json');
		return [];
	}
}
