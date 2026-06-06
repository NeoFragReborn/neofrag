<?php
/**
 * https://neofr.ag
 */

namespace NF\Modules\Donations\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Index extends Controller_Module
{
	public function index()
	{
		$this	->title($this->lang('Dons'))
				->icon('fas fa-hand-holding-heart')
				->css('donations');

		$campaigns = $this->model()->get_active_campaigns();

		// Si exactement 1 campagne, on redirige vers son URL canonique
		// (et non un appel direct à _campaign qui exigerait segments[1]).
		if (count($campaigns) === 1 && !empty($campaigns[0]['name']))
		{
			redirect('donations/'.url_title($campaigns[0]['name']));
		}

		return $this->view('index', [
			'campaigns' => array_map([$this, '_enrich'], $campaigns)
		]);
	}

	public function _campaign()
	{
		// Route returns the campaign columns as args; just take the slug from URL segments.
		$name = $this->url->segments[1] ?? '';
		$campaign = $this->model()->get_campaign($name);

		if (!$campaign)
		{
			$this->error(404);
			return;
		}

		$this	->title($campaign['title'])
				->icon('fas fa-hand-holding-heart')
				->css('donations');
		$this->breadcrumb($this->lang('Dons'), 'donations');
		$this->breadcrumb($campaign['title']);

		$campaign  = $this->_enrich($campaign);
		$donations = $this->model()->get_donations($campaign['id'], TRUE);

		return $this->view('campaign', [
			'c'         => $campaign,
			'donations' => $donations,
			'donate_url'=> $this->_paypal_url($campaign)
		]);
	}

	private function _enrich($c)
	{
		$totals = $this->model()->get_total($c['id']);
		$c['raised']     = $totals['total'];
		$c['count']      = $totals['count'];
		$c['percentage'] = $c['goal_amount'] > 0 ? min(100, round($c['raised'] / $c['goal_amount'] * 100, 1)) : 0;
		return $c;
	}

	private function _paypal_url($campaign)
	{
		// Hosted button takes priority if set
		if (!empty($campaign['paypal_button_id']))
		{
			return 'https://www.paypal.com/donate?hosted_button_id='.urlencode($campaign['paypal_button_id']);
		}
		$email = $campaign['paypal_email'] ?: $this->config->nf_donations_paypal_email;
		if (!$email) return NULL;
		return 'https://www.paypal.com/donate?'.http_build_query([
			'business'      => $email,
			'item_name'     => $campaign['title'].' — '.$this->config->nf_name,
			'currency_code' => $campaign['currency'] ?: 'EUR',
			'no_recurring'  => '0',
			'no_shipping'   => '1',
			'cmd'           => '_donations'
		]);
	}
}
