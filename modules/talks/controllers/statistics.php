<?php
/**
 * https://neofr.ag
 */

namespace NF\Modules\Talks\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Statistics extends Controller_Module
{
	public function statistics()
	{
		return [
			'talks_messages' => [
				'title' => $this->lang('Messages'),
				'data'  => function(){
					$this->db	->from('nf_talks_messages')
								->where('deleted_at IS NULL');

					return 'date';
				}
			]
		];
	}
}
