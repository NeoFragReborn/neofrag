<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 */

namespace NF\Widgets\Calendar\Controllers;

use NF\NeoFrag\Loadables\Controller;

class Checker extends Controller
{
	public function upcoming($settings = [])
	{
		return [
			'count'         => max(1, min(20, (int)($settings['count'] ?? 5))),
			'display_panel' => in_array($settings['display_panel'] ?? 'oui', ['oui', 'non'], TRUE) ? ($settings['display_panel'] ?? 'oui') : 'oui'
		];
	}
}
