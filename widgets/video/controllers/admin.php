<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * Widget Vidéo — panneau de réglages (Live Editor).
 */

namespace NF\Widgets\Video\Controllers;

use NF\NeoFrag\Loadables\Controller;

class Admin extends Controller
{
	public function index($settings = [])
	{
		return $this->view('admin', array_merge(['count' => 5], $settings));
	}
}
