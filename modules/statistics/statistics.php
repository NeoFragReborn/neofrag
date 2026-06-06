<?php
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\Statistics;

use NF\NeoFrag\Addons\Module;

class Statistics extends Module
{
	protected function __info()
	{
		return [
			'title'       => $this->lang('Statistiques'),
			'description' => $this->lang('Statistiques de visite et de trafic du site.'),
			'icon'        => 'far fa-chart-bar',
			'link'        => 'https://neofr.ag',
			'author'      => 'Michaël BILCOT & Jérémy VALENTIN <contact@neofrag.com>',
			'license'     => 'LGPLv3 <https://neofr.ag/license>',
			'version'     => '1.0',
			'admin'       => FALSE
		];
	}
}
