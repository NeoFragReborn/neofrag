<?php
/**
 * https://neofr.ag
 */

namespace NF\Widgets\Donations\Controllers;

use NF\NeoFrag\Loadables\Controller;

class Admin extends Controller
{
	public function progress($settings = [])
	{
		return $this->view('admin', [
			'type'        => 'progress',
			'campaign_id' => $settings['campaign_id'] ?? '',
			'show_top'    => $settings['show_top']    ?? '1',
			'show_recent' => $settings['show_recent'] ?? '1',
			'limit'       => $settings['limit']       ?? '5',
			'campaigns'   => $this->module('donations')->model()->get_campaigns()
		]);
	}

	public function top($settings = [])
	{
		return $this->view('admin', [
			'type'        => 'top',
			'campaign_id' => $settings['campaign_id'] ?? '',
			'show_top'    => $settings['show_top']    ?? '1',
			'show_recent' => $settings['show_recent'] ?? '1',
			'limit'       => $settings['limit']       ?? '5',
			'campaigns'   => $this->module('donations')->model()->get_campaigns()
		]);
	}
}
