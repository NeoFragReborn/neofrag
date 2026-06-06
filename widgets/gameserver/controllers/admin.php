<?php
/**
 * https://neofr.ag
 */

namespace NF\Widgets\Gameserver\Controllers;

use NF\NeoFrag\Loadables\Controller;

class Admin extends Controller
{
	public function index($settings = [])
	{
		return $this->view('admin', [
			'engine' => $settings['engine'] ?? 'mc-java',
			'host'   => $settings['host']   ?? '',
			'port'   => $settings['port']   ?? '',
			'label'  => $settings['label']  ?? ''
		]);
	}
}
