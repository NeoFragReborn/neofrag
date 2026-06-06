<?php
/**
 * https://neofr.ag
 */

namespace NF\Modules\News\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Statistics extends Controller_Module
{
	public function statistics()
	{
		return [
			'news' => [
				'title' => $this->lang('Actualités'),
				'data'  => function(){
					$this->db	->from('nf_news')
								->where('published', '1')
								->where('deleted_at IS NULL');

					return 'date';
				}
			]
		];
	}
}
