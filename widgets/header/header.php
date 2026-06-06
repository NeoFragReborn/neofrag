<?php
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Widgets\Header;

use NF\NeoFrag\Addons\Widget;

class Header extends Widget
{
	protected function __info()
	{
		return [
			'title'       => $this->lang('Header'),
			'description' => $this->lang('En-tête du site avec titre, slogan et logo configurables.'),
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
