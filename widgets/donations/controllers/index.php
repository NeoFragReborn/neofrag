<?php
/**
 * https://neofr.ag
 */

namespace NF\Widgets\Donations\Controllers;

use NF\NeoFrag\Loadables\Controllers\Widget as Controller_Widget;

class Index extends Controller_Widget
{
	public function index($config = [])
	{
		return $this->progress($config);
	}

	public function progress($settings = [])
	{
		$campaign = $this->_campaign($settings);
		if (!$campaign) return $this->_no_config();

		$top   = $this->module('donations')->model()->get_top_donors($campaign['id'], 1);
		$donate = $this->_paypal_url($campaign);
		$show_top   = ($settings['show_top']   ?? '1') === '1';
		$show_recent = ($settings['show_recent'] ?? '1') === '1';
		$recent = $show_recent ? $this->module('donations')->model()->get_donations($campaign['id'], TRUE) : [];

		$this->css('donations');

		return $this->panel()
					->heading($campaign['title'], 'fas fa-hand-holding-heart')
					->body($this->view('progress', [
						'c'           => $campaign,
						'top_donor'   => $show_top && !empty($top) ? $top[0] : NULL,
						'recent'      => array_slice($recent, 0, 3),
						'donate_url'  => $donate,
						'campaign_url'=> url('donations/'.$campaign['name'])
					]), FALSE);
	}

	public function top($settings = [])
	{
		$campaign = $this->_campaign($settings);
		if (!$campaign) return $this->_no_config();

		$limit = max(1, min(20, (int)($settings['limit'] ?? 5)));
		$top   = $this->module('donations')->model()->get_top_donors($campaign['id'], $limit);

		$this->css('donations');

		return $this->panel()
					->heading($this->lang('Top donateurs'), 'fas fa-trophy')
					->body($this->view('top', [
						'top'         => $top,
						'campaign'    => $campaign,
						'campaign_url'=> url('donations/'.$campaign['name'])
					]), FALSE);
	}

	private function _campaign($settings)
	{
		$model = $this->module('donations')->model();
		$cid = (int)($settings['campaign_id'] ?? 0);
		$c   = $cid ? $model->get_campaign($cid) : NULL;
		if (!$c)
		{
			// Fallback: pick the first active campaign
			$active = $model->get_active_campaigns();
			$c = $active[0] ?? NULL;
		}
		if (!$c) return NULL;

		$totals = $model->get_total($c['id']);
		$c['raised']     = $totals['total'];
		$c['count']      = $totals['count'];
		$c['percentage'] = $c['goal_amount'] > 0 ? min(100, round($c['raised'] / $c['goal_amount'] * 100, 1)) : 0;
		return $c;
	}

	private function _paypal_url($c)
	{
		if (!empty($c['paypal_button_id']))
		{
			return 'https://www.paypal.com/donate?hosted_button_id='.urlencode($c['paypal_button_id']);
		}
		$email = $c['paypal_email'] ?: $this->config->nf_donations_paypal_email;
		if (!$email) return NULL;
		return 'https://www.paypal.com/donate?'.http_build_query([
			'business'      => $email,
			'item_name'     => $c['title'].' — '.$this->config->nf_name,
			'currency_code' => $c['currency'] ?: 'EUR',
			'no_recurring'  => '0',
			'no_shipping'   => '1',
			'cmd'           => '_donations'
		]);
	}

	private function _no_config()
	{
		return $this->panel()
					->heading($this->lang('Dons'), 'fas fa-hand-holding-heart')
					->body('<div class="text-center text-muted py-3"><small>'.$this->lang('Aucune campagne configurée.').'</small></div>', FALSE);
	}
}
