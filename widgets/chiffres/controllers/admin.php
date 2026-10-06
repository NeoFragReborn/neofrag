<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 */

namespace NF\Widgets\Chiffres\Controllers;

use NF\NeoFrag\Loadables\Controller;

class Admin extends Controller
{
	public function index($settings = [])
	{
		return $this->view('admin', array_merge([
			'nombres'       => Index::PAR_DEFAUT,
			'display_panel' => 'oui'
		], $settings));
	}
}
