<?php
declare(strict_types=1);
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
			'icon'        => 'fab fa-teamspeak',
			'author'      => 'NeoFrag',
			'license'     => 'LGPLv3',
			// Decouplage du paquet : cf. tools/check-addon-declarations.php.
			'core'        => FALSE,
			'presets'     => ['gaming'],
			'requires'    => [],
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
