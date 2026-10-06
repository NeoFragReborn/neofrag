<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 */

namespace NF\Widgets\Frise\Controllers;

use NF\NeoFrag\Loadables\Controller;

class Admin extends Controller
{
	public function index($settings = [])
	{
		return $this->view('admin', array_merge([
			'count'         => 12,
			'mois'          => 3,
			'display_panel' => 'oui'
		], $settings));
	}
}
