<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 */

namespace NF\Widgets\Donations\Controllers;

use NF\NeoFrag\Loadables\Controller;

class Checker extends Controller
{
	public function progress($settings = [])
	{
		return $this->_check($settings);
	}

	public function top($settings = [])
	{
		return $this->_check($settings);
	}

	private function _check($settings)
	{
		$cid = (int)($settings['campaign_id'] ?? 0);
		if ($cid > 0)
		{
			$c = $this->module('donations')->model()->get_campaign($cid);
			if (!$c) $cid = 0;
		}

		$limit = (int)($settings['limit'] ?? 5);
		if ($limit < 1) $limit = 5;
		if ($limit > 20) $limit = 20;

		return [
			'campaign_id' => (string)$cid,
			'show_top'    => in_array($settings['show_top']    ?? '1', ['0', '1'], TRUE) ? ($settings['show_top'] ?? '1')    : '1',
			'show_recent' => in_array($settings['show_recent'] ?? '1', ['0', '1'], TRUE) ? ($settings['show_recent'] ?? '1') : '1',
			'limit'       => (string)$limit
		];
	}
}
