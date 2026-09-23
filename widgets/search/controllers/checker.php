<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Widgets\Search\Controllers;

use NF\NeoFrag\Loadables\Controller;

class Checker extends Controller
{
	public function index($settings = [])
	{
		return [
			'align' => !empty($settings['align']) && in_array($settings['align'], ['float-start', 'float-end']) ? $settings['align'] : 'float-end'
		];
	}
}
