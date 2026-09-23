<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\Comments\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Statistics extends Controller_Module
{
	public function statistics()
	{
		return [
			'comments' => [
				'title' => $this->lang('Commentaires'),
				'data'  => function(){
					$this->db->from('nf_comment')->where('deleted_at IS NULL');
					return 'date';
				}
			]
		];
	}
}
