<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 */

namespace NF\Widgets\Clock\Controllers;

use NF\NeoFrag\Loadables\Controller;

class Checker extends Controller
{
	public function index($settings = [])
	{
		return [
			'clock'    => in_array($settings['clock']    ?? '1', ['1', '0'], TRUE) ? ($settings['clock'] ?? '1')    : '1',
			'calendar' => in_array($settings['calendar'] ?? '1', ['1', '0'], TRUE) ? ($settings['calendar'] ?? '1') : '1',
			'birthday' => in_array($settings['birthday'] ?? '1', ['1', '0'], TRUE) ? ($settings['birthday'] ?? '1') : '1'
		];
	}
}
