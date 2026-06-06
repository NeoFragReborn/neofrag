<?php
/**
 * https://neofr.ag
 */

namespace NF\Widgets\Twitch\Controllers;

use NF\NeoFrag\Loadables\Controller;

class Checker extends Controller
{
	public function index($settings = [])
	{
		$username = strtolower(trim($settings['username'] ?? ''));
		// Twitch usernames: 4-25 chars, alphanumeric + underscore
		if ($username !== '' && !preg_match('/^[a-z0-9_]{4,25}$/', $username)) $username = '';

		$client_id     = trim($settings['client_id']     ?? '');
		$client_secret = trim($settings['client_secret'] ?? '');
		// Twitch client_id is 30 chars hex; allow 25-50 alphanum to be lenient
		if ($client_id !== '' && !preg_match('/^[a-z0-9]{25,50}$/i', $client_id)) $client_id = '';
		if ($client_secret !== '' && !preg_match('/^[a-z0-9]{25,80}$/i', $client_secret)) $client_secret = '';

		return [
			'username'      => $username,
			'client_id'     => $client_id,
			'client_secret' => $client_secret,
			'open_mode'     => in_array($settings['open_mode']    ?? 'popup', ['popup', 'newtab'], TRUE) ? $settings['open_mode']    : 'popup',
			'show_offline'  => in_array($settings['show_offline'] ?? '1', ['0', '1'], TRUE) ? $settings['show_offline'] : '1'
		];
	}
}
