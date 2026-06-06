<?php
/**
 * https://neofr.ag
 */

namespace NF\Widgets\Newsletter;

use NF\NeoFrag\Addons\Widget;

class Newsletter extends Widget
{
	protected function __info()
	{
		return [
			'title'       => $this->lang('Newsletter'),
			'description' => $this->lang('Formulaire d\'inscription à la newsletter.'),
			'author'      => 'NeoFrag',
			'license'     => 'LGPLv3',
			'version'     => '1.0',
			'depends'     => ['neofrag' => '0.2.0'],
			'types'       => ['signup' => $this->lang('Inscription newsletter')]
		];
	}
}
