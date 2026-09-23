<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 */

namespace NF\Widgets\Clock\Controllers;

use NF\NeoFrag\Loadables\Controller;

class Admin extends Controller
{
	public function index($settings = [])
	{
		return $this->view('admin', [
			'clock'    => isset($settings['clock'])    ? $settings['clock']    : '1',
			'calendar' => isset($settings['calendar']) ? $settings['calendar'] : '1',
			'birthday' => isset($settings['birthday']) ? $settings['birthday'] : '1'
		]);
	}
}
