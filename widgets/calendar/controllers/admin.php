<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 */

namespace NF\Widgets\Calendar\Controllers;

use NF\NeoFrag\Loadables\Controller;

class Admin extends Controller
{
	public function semaine($settings = [])
	{
		// Ni nombre ni liste : sept jours, toujours. Seul le panneau se règle.
		return $this->view('admin', array_merge([
			'count'         => NULL,
			'display_panel' => 'oui'
		], $settings));
	}

	public function upcoming($settings = [])
	{
		return $this->view('admin', array_merge([
			'count'         => 5,
			'display_panel' => 'oui'
		], $settings));
	}
}
