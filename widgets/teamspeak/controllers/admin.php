<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 */

namespace NF\Widgets\Teamspeak\Controllers;

use NF\NeoFrag\Loadables\Controller;

class Admin extends Controller
{
	public function index($settings = [])
	{
		return $this->view('admin', [
			'mode'       => $settings['mode']       ?? 'simple',
			'host'       => $settings['host']       ?? '',
			'voice_port' => $settings['voice_port'] ?? '9987',
			'query_port' => $settings['query_port'] ?? '10011',
			'query_user' => $settings['query_user'] ?? '',
			'query_pass' => $settings['query_pass'] ?? '',
			'label'      => $settings['label']      ?? ''
		]);
	}
}
