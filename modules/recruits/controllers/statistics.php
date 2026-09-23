<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 */

namespace NF\Modules\Recruits\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Statistics extends Controller_Module
{
	public function statistics()
	{
		return [
			'recruits' => [
				'title' => $this->lang('Recrutements'),
				'data'  => function(){
					$this->db->from('nf_recruits');

					return 'date';
				}
			]
		];
	}
}
