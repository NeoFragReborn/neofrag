<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 */

namespace NF\Widgets\Discord\Controllers;

use NF\NeoFrag\Loadables\Controller;

class Checker extends Controller
{
	public function index($settings = [])
	{
		$server_id = trim($settings['server_id'] ?? '');
		if ($server_id !== '' && !preg_match('/^\d{15,25}$/', $server_id))
		{
			$server_id = '';
		}

		$invite = trim($settings['invite'] ?? '');
		if ($invite !== '' && !preg_match('#^https?://(discord\.gg|discord\.com/invite|discordapp\.com/invite)/[A-Za-z0-9-]+/?$#', $invite))
		{
			$invite = '';
		}

		$height = (int)($settings['height'] ?? 400);
		$height = max(150, min(1000, $height));

		return [
			'server_id' => $server_id,
			'mode'      => in_array($settings['mode']  ?? 'native', ['native', 'iframe'], TRUE) ? ($settings['mode'] ?? 'native')  : 'native',
			'theme'     => in_array($settings['theme'] ?? 'dark',   ['dark', 'light'],     TRUE) ? ($settings['theme'] ?? 'dark') : 'dark',
			'height'    => (string)$height,
			'invite'    => $invite
		];
	}
}
