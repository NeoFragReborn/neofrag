<?php
declare(strict_types=1);
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
			'icon'        => 'fab fa-twitch',
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
				'index' => $this->lang('Statut live')
			]
		];
	}
}
