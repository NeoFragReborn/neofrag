<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 */

namespace NF\Modules\Media\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Statistics extends Controller_Module
{
	public function statistics()
	{
		return [
			'media' => [
				'title' => $this->lang('Médias'),
				'data'  => function(){
					$this->db->from('nf_media');

					return 'created_at';
				}
			]
		];
	}
}
