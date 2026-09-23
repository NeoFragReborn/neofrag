<?php
declare(strict_types=1);
namespace NF\Modules\Marketplace\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Index extends Controller_Module
{
	public function index($addons, $base_url = '')
	{
		$this	->title($this->lang('Marketplace'))
				->icon('fas fa-store')
				->breadcrumb();

		return $this->css('marketplace')->view('index', [
			'addons'   => is_array($addons) ? $addons : [],
			'base_url' => (string) $base_url,
		]);
	}
}
