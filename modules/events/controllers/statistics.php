<?php
/**
 * https://neofr.ag
 */

namespace NF\Modules\Events\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Statistics extends Controller_Module
{
	public function statistics()
	{
		return [
			'events' => [
				'title' => $this->lang('Événements'),
				'data'  => function(){
					$this->db->from('nf_events');

					return 'date';
				}
			]
		];
	}
}
