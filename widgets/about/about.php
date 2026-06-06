<?php
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Widgets\About;

use NF\NeoFrag\Addons\Widget;

class About extends Widget
{
	protected function __info()
	{
		return [
			'title'       => $this->lang('À propos'),
			'description' => 'Bloc de présentation libre éditable en HTML.',
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
