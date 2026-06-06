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
			'title'       => $this->lang('Streamer Twitch'),
			'description' => $this->lang('Affiche le statut live d\'un streamer Twitch (jeu, viewers, titre, miniature) avec lecteur intégré dans une popup.'),
			'author'      => 'NeoFrag',
			'license'     => 'LGPLv3',
			'version'     => '2.0',
			'depends'     => [
				'neofrag' => '0.2.0'
			],
			'types'       => [
				'index' => $this->lang('Streamer Twitch')
			]
		];
	}
}
