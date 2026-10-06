<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 */

namespace NF\Widgets\Frise\Controllers;

use NF\NeoFrag\Loadables\Controller;

class Checker extends Controller
{
	public function index($settings = [])
	{
		return [
			'count'         => max(5, min(30, (int) ($settings['count'] ?? 12))),
			'mois'          => max(1, min(12, (int) ($settings['mois'] ?? 3))),
			'display_panel' => in_array($settings['display_panel'] ?? 'oui', ['oui', 'non'], TRUE) ? ($settings['display_panel'] ?? 'oui') : 'oui'
		];
	}
}
