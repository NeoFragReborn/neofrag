<?php
/**
 * https://neofr.ag
 */

namespace NF\Widgets\Twitch\Controllers;

use NF\NeoFrag\Loadables\Controller;

class Admin extends Controller
{
	public function index($settings = [])
	{
		return $this->view('admin', [
			'username'      => $settings['username']      ?? '',
			'client_id'     => $settings['client_id']     ?? '',
			'client_secret' => $settings['client_secret'] ?? '',
			'open_mode'     => $settings['open_mode']     ?? 'popup',
			'show_offline'  => $settings['show_offline']  ?? '1'
		]);
	}
}
