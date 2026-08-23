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
		// Rétro-compat : ancien réglage mono-chaîne `username` → pré-remplit la liste de chaînes.
		$channels = $settings['channels'] ?? '';
		if ($channels === '' && !empty($settings['username']))
		{
			$channels = 'twitch:'.$settings['username'];
		}

		return $this->view('admin', [
			'channels'      => $channels,
			'client_id'     => $settings['client_id']     ?? '',
			'client_secret' => $settings['client_secret'] ?? '',
			'api_key'       => $settings['api_key']        ?? '',
			'open_mode'     => $settings['open_mode']      ?? 'popup',
			'show_offline'  => $settings['show_offline']   ?? '1'
		]);
	}
}
