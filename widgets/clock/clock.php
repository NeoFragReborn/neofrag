<?php
/**
 * https://neofr.ag
 */

namespace NF\Widgets\Clock;

use NF\NeoFrag\Addons\Widget;

class Clock extends Widget
{
	protected function __info()
	{
		return [
			'title'       => $this->lang('Horloge & calendrier'),
			'description' => $this->lang('Affichage dynamique de l\'heure, de la date du jour et des anniversaires des membres.'),
			'author'      => 'NeoFrag',
			'license'     => 'LGPLv3',
			'version'     => '3.0',
			'depends'     => [
				'neofrag' => '0.2.0'
			],
			'types'       => [
				'index' => $this->lang('Horloge')
			]
		];
	}
}
