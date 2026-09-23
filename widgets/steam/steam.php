<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 */

namespace NF\Widgets\Steam;

use NF\NeoFrag\Addons\Widget;

class Steam extends Widget
{
	protected function __info()
	{
		return [
			'title'       => $this->lang('Groupe Steam'),
			'description' => $this->lang('Affiche le nombre de membres, la présence en ligne et l\'activité d\'un groupe Steam.'),
			'icon'        => 'fab fa-steam',
			'author'      => 'NeoFrag',
			'license'     => 'LGPLv3',
			// Decouplage du paquet : cf. tools/check-addon-declarations.php.
			'core'        => FALSE,
			'presets'     => ['gaming'],
			'requires'    => [],
			'version'     => '2.0',
			'depends'     => [
				'neofrag' => '0.2.0'
			],
			'types'       => [
				'index' => $this->lang('Groupe Steam')
			]
		];
	}
}
