<?php
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Widgets\Copyright;

use NF\NeoFrag\Addons\Widget;

class Copyright extends Widget
{
	protected function __info()
	{
		return [
			'title'       => 'Copyright',
			'description' => $this->lang('Pied de page copyright avec mots magiques ({name}, {year}, {neofrag}, {copyright}).'),
			'link'        => 'https://neofr.ag',
			'author'      => 'Michaël BILCOT & Jérémy VALENTIN <contact@neofrag.com>',
			'license'     => 'LGPLv3 <https://neofr.ag/license>',
			'version'     => '1.0',
			'depends'     => [
				'neofrag' => '0.2.0'
			]
		];
	}
}
