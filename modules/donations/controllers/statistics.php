<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 */

namespace NF\Modules\Donations\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Statistics extends Controller_Module
{
	public function statistics()
	{
		return [
			'donations' => [
				'title' => $this->lang('Dons'),
				'data'  => function(){
					$this->db->from('nf_donations');

					return 'created_at';
				}
			]
		];
	}
}
