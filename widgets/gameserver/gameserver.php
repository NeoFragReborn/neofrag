<?php
declare(strict_types=1);
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
			'icon'        => 'fas fa-server',
			'author'      => 'NeoFrag Reborn',
			'license'     => 'LGPLv3 <https://www.gnu.org/licenses/lgpl-3.0.html>',
			// Decouplage du paquet : cf. tools/check-addon-declarations.php.
			'core'        => FALSE,
			'presets'     => ['gaming'],
			'requires'    => [],
			'version'     => '1.0',
			'link'        => 'https://neofrag-reborn.xyz',
			'depends'     => [
				'neofrag' => '0.2.0'
			],
			'types'       => [
				'index' => $this->lang('Serveur de jeu')
			]
		];
	}
}
