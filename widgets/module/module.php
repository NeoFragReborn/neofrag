<?php
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Widgets\Module;

use NF\NeoFrag\Addons\Widget;

class Module extends Widget
{
	protected function __info()
	{
		return [
			'title'       => $this->lang('Module'),
			'description' => $this->lang('Insère un module complet à l\'intérieur d\'une zone du thème.'),
			'link'        => 'https://neofr.ag',
			'author'      => 'Michaël BILCOT & Jérémy VALENTIN <contact@neofrag.com>',
			'license'     => 'LGPLv3 <https://neofr.ag/license>',
			'version'     => '1.0',
		];
	}
}
