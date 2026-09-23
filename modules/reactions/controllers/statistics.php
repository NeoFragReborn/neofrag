<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 */

namespace NF\Modules\Reactions\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Statistics extends Controller_Module
{
	public function statistics()
	{
		return [
			'reactions' => [
				'title' => $this->lang('Réactions'),
				'data'  => function(){
					$this->db->from('nf_reactions');

					return 'created_at';
				}
			]
		];
	}
}
