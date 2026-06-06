<?php
/**
 * https://neofr.ag
 */

namespace NF\Widgets\Discord\Controllers;

use NF\NeoFrag\Loadables\Controller;

class Admin extends Controller
{
	public function index($settings = [])
	{
		return $this->view('admin', [
			'server_id' => $settings['server_id'] ?? '',
			'mode'      => $settings['mode']      ?? 'native',
			'theme'     => $settings['theme']     ?? 'dark',
			'height'    => $settings['height']    ?? 400,
			'invite'    => $settings['invite']    ?? ''
		]);
	}
}
