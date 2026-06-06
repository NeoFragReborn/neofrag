<?php
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Addons\Authenticator;

use NF\NeoFrag\Loadables\Addon;

class Authenticator extends Addon
{
	protected function __info()
	{
		return [
			'title'   => 'Authentificateur',
			'icon'    => 'fas fa-sign-in-alt',
			'version' => '1.0',
			'depends' => [
				'neofrag' => '0.2.0'
			]
		];
	}
}
