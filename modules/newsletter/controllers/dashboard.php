<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 */

namespace NF\Modules\Newsletter\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Dashboard extends Controller_Module
{
	public function dashboard()
	{
		$pending = (int)$this->db	->from('nf_newsletter_subscribers')
									->where('confirmed', FALSE)
									->count();

		if ($pending <= 0)
		{
			return [];
		}

		return [[
			'title'  => $pending.' '.$this->lang('inscription newsletter en attente|inscriptions newsletter en attente', $pending),
			'action' => $this->lang('Gérer la newsletter'),
			'url'    => 'admin/newsletter'
		]];
	}
}
