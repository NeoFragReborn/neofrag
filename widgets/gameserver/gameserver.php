<?php
/**
 * https://neofr.ag
 */

namespace NF\Widgets\Gameserver;

use NF\NeoFrag\Addons\Widget;

class Gameserver extends Widget
{
	protected function __info()
	{
		return [
			'title'       => $this->lang('Serveur de jeu'),
			'description' => $this->lang('Affiche le statut, le nombre de joueurs, la carte et un bouton "Rejoindre" pour un serveur de jeu (Minecraft Java, Minecraft Bedrock, Source / GoldSource — CS2, GMod, ARMA, Rust, etc.).'),
			'author'      => 'NeoFrag',
			'license'     => 'LGPLv3',
			'version'     => '1.0',
			'depends'     => [
				'neofrag' => '0.2.0'
			],
			'types'       => [
				'index' => $this->lang('Serveur de jeu')
			]
		];
	}
}
