<?php
/**
 * https://neofr.ag
 */

namespace NF\Widgets\Latest_Comments\Controllers;

use NF\NeoFrag\Loadables\Controller;

class Checker extends Controller
{
	public function index($settings = [])
	{
		return [
			'count'         => max(1, min(20, (int)($settings['count'] ?? 5))),
			'display_panel' => in_array($settings['display_panel'] ?? 'oui', ['oui', 'non'], TRUE) ? $settings['display_panel'] : 'oui'
		];
	}
}
