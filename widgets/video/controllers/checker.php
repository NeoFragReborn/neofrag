<?php
/**
 * https://neofr.ag
 * Widget Vidéo — normalisation des réglages.
 */

namespace NF\Widgets\Video\Controllers;

use NF\NeoFrag\Loadables\Controller;

class Checker extends Controller
{
	public function index($settings = [])
	{
		return [
			'count' => max(1, min(20, (int) ($settings['count'] ?? 5)))
		];
	}
}
