<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Widgets\Navigation;

use NF\NeoFrag\Addons\Widget;

class Navigation extends Widget
{
	protected function __info()
	{
		return [
			'title'       => $this->lang('Navigation'),
			'description' => $this->lang('Menu de navigation horizontal ou vertical configurable.'),
			'icon'        => 'fas fa-bars',
			'link'        => 'https://neofr.ag',
			'author'      => 'Michaël BILCOT & Jérémy VALENTIN <contact@neofrag.com>',
			'license'     => 'LGPLv3 <https://neofr.ag/license>',
			// Decouplage du paquet : cf. tools/check-addon-declarations.php.
			'core'        => TRUE,
			'presets'     => [],
			'requires'    => [],
			'version'     => '1.0',
			'depends'     => [
				'neofrag' => '0.2.0'
			],
			'types'       => [
				'index'      => $this->lang('Horizontal'),
				'vertical'   => $this->lang('Vertical')
			]
		];
	}
}
