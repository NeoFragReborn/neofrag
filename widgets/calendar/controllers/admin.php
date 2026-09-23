<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 */

namespace NF\Widgets\Calendar\Controllers;

use NF\NeoFrag\Loadables\Controller;

class Admin extends Controller
{
	public function upcoming($settings = [])
	{
		return $this->view('admin', array_merge([
			'count'         => 5,
			'display_panel' => 'oui'
		], $settings));
	}
}
