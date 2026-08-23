<?php
/**
 * https://neofr.ag
 */

namespace NF\Widgets\Twitch;

use NF\NeoFrag\Addons\Widget;

class Twitch extends Widget
{
	protected function __info()
	{
		return [
			'title'       => $this->lang('Statut live'),
			'description' => $this->lang('Statut en direct de plusieurs chaînes Twitch / YouTube (jeu, viewers, titre, miniature) avec lecteur intégré.'),
			'author'      => 'NeoFrag',
			'license'     => 'LGPLv3',
			'version'     => '3.0',
			'depends'     => [
				'neofrag' => '0.2.0'
			],
			'types'       => [
				'index' => $this->lang('Statut live')
			]
		];
	}
}
