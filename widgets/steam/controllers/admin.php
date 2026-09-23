<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 */

namespace NF\Widgets\Steam\Controllers;

use NF\NeoFrag\Loadables\Controller;

class Admin extends Controller
{
	public function index($settings = [])
	{
		return $this->view('admin', [
			'group'        => $settings['group']        ?? '',
			'show_avatar'  => $settings['show_avatar']  ?? '1',
			'show_summary' => $settings['show_summary'] ?? '0',
			'display'      => $settings['display']      ?? 'compact'
		]);
	}
}
