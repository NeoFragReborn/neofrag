<?php
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\Settings\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Admin_Ajax extends Controller_Module
{
	public function maintenance()
	{
		$this->config('nf_maintenance', (bool)post('closed'), 'bool');

		return $this->json([
			'status' => $this->config->nf_maintenance
		]);
	}

	public function registration()
	{
		// nf_registration_status: 1 = open, 0 = closed. closed=1 means status=0.
		$this->config('nf_registration_status', (int)!post('closed'), 'int');

		return $this->json([
			'status' => !$this->config->nf_registration_status
		]);
	}
}
