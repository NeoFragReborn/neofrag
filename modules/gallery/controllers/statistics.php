<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 */

namespace NF\Modules\Gallery\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Statistics extends Controller_Module
{
	public function statistics()
	{
		return [
			'gallery_images' => [
				'title' => $this->lang('Images galerie'),
				'data'  => function(){
					$this->db->from('nf_gallery_images');

					return 'date';
				}
			]
		];
	}
}
