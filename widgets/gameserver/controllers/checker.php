<?php
/**
 * https://neofr.ag
 */

namespace NF\Widgets\Gameserver\Controllers;

use NF\NeoFrag\Loadables\Controller;

class Checker extends Controller
{
	public function index($settings = [])
	{
		$engines = ['mc-java', 'mc-bedrock', 'source', 'goldsource'];
		$engine  = in_array($settings['engine'] ?? '', $engines, TRUE) ? $settings['engine'] : 'mc-java';

		$host = trim($settings['host'] ?? '');
		// Allow domain names, IPs, with optional dashes/dots/digits
		if ($host !== '' && !preg_match('/^[a-zA-Z0-9.\-]{3,253}$/', $host))
		{
			$host = '';
		}

		$port = (int)($settings['port'] ?? 0);
		if ($port < 1 || $port > 65535) $port = 0;

		$label = trim($settings['label'] ?? '');
		if (mb_strlen($label) > 60) $label = mb_substr($label, 0, 60);

		return [
			'engine' => $engine,
			'host'   => $host,
			'port'   => (string)$port,
			'label'  => $label
		];
	}
}
