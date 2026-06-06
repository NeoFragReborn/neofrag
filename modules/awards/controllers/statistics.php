<?php
/**
 * https://neofr.ag
 */

namespace NF\Modules\Awards\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Statistics extends Controller_Module
{
	public function statistics()
	{
		return [
			'awards' => [
				'title' => $this->lang('Récompenses'),
				'data'  => function(){
					$this->db->from('nf_awards');

					return 'date';
				}
			]
		];
	}
}
