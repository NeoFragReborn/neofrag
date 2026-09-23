<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 */

namespace NF\Widgets\Discord;

use NF\NeoFrag\Addons\Widget;

class Discord extends Widget
{
	protected function __info()
	{
		return [
			'title'       => $this->lang('Serveur Discord'),
			'description' => $this->lang('Affiche les membres en ligne, les salons vocaux et un lien d\'invitation vers votre serveur Discord.'),
			'icon'        => 'fab fa-discord',
			'author'      => 'NeoFrag',
			'license'     => 'LGPLv3',
			// Decouplage du paquet : cf. tools/check-addon-declarations.php.
			'core'        => TRUE,
			'presets'     => [],
			'requires'    => [],
			'version'     => '2.0',
			'depends'     => [
				'neofrag' => '0.2.0'
			],
			'types'       => [
				'index' => $this->lang('Serveur Discord')
			]
		];
	}
}
