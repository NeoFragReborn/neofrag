<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 */

namespace NF\Widgets\Teamspeak\Controllers;

use NF\NeoFrag\Loadables\Controller;

class Checker extends Controller
{
	public function index($settings = [])
	{
		$host = trim($settings['host'] ?? '');
		if ($host !== '' && !preg_match('/^[a-zA-Z0-9.\-]{3,253}$/', $host)) $host = '';

		$voice_port = (int)($settings['voice_port'] ?? 9987);
		if ($voice_port < 1 || $voice_port > 65535) $voice_port = 9987;

		$query_port = (int)($settings['query_port'] ?? 10011);
		if ($query_port < 1 || $query_port > 65535) $query_port = 10011;

		$label = trim($settings['label'] ?? '');
		if (mb_strlen($label) > 60) $label = mb_substr($label, 0, 60);

		// query_user / query_pass left as-is, framework handles encoding
		$query_user = trim($settings['query_user'] ?? '');
		$query_pass = trim($settings['query_pass'] ?? '');

		return [
			'mode'       => in_array($settings['mode'] ?? 'simple', ['simple', 'tree'], TRUE) ? ($settings['mode'] ?? 'simple') : 'simple',
			'host'       => $host,
			'voice_port' => (string)$voice_port,
			'query_port' => (string)$query_port,
			'query_user' => $query_user,
			'query_pass' => $query_pass,
			'label'      => $label
		];
	}
}
