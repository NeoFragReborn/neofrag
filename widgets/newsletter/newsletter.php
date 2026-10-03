<?php
declare(strict_types=1);
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
			'icon'        => 'far fa-envelope',
			'author'      => 'NeoFrag',
			'license'     => 'LGPLv3',
			// Decouplage du paquet : cf. tools/check-addon-declarations.php.
			'core'        => FALSE,
			'presets'     => ['association'],
			'requires'    => [],
			'version'     => '1.0',
			'depends'     => ['neofrag' => '0.2.0'],
			'types'       => ['signup' => $this->lang('Inscription newsletter')]
		];
	}
}
