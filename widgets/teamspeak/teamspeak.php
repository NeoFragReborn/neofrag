<?php
/**
 * https://neofr.ag
 */

namespace NF\Widgets\Teamspeak;

use NF\NeoFrag\Addons\Widget;

class Teamspeak extends Widget
{
	protected function __info()
	{
		return [
			'title'       => $this->lang('Serveur TeamSpeak 3'),
			'description' => $this->lang('Affiche les channels et clients connectés à un serveur TeamSpeak 3, avec un bouton "Se connecter".'),
			'author'      => 'NeoFrag',
			'license'     => 'LGPLv3',
			'version'     => '3.0',
			'depends'     => [
				'neofrag' => '0.2.0'
			],
			'types'       => [
				'index' => $this->lang('Serveur TeamSpeak 3')
			]
		];
	}
}
