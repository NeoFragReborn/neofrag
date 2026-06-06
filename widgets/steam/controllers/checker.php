<?php
/**
 * https://neofr.ag
 */

namespace NF\Widgets\Steam\Controllers;

use NF\NeoFrag\Loadables\Controller;

class Checker extends Controller
{
	public function index($settings = [])
	{
		$group = trim($settings['group'] ?? '');

		// Allow only safe characters: alphanumeric, dash, underscore (vanity URL or numeric ID)
		if ($group !== '' && !preg_match('/^[A-Za-z0-9_-]{2,64}$/', $group))
		{
			$group = '';
		}

		return [
			'group'        => $group,
			'show_avatar'  => in_array($settings['show_avatar']  ?? '1', ['0', '1'], TRUE) ? $settings['show_avatar']  : '1',
			'show_summary' => in_array($settings['show_summary'] ?? '0', ['0', '1'], TRUE) ? $settings['show_summary'] : '0',
			'display'      => in_array($settings['display']      ?? 'compact', ['compact', 'full'], TRUE) ? $settings['display'] : 'compact'
		];
	}
}
