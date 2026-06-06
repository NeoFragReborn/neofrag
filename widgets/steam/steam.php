<?php
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
			'author'      => 'NeoFrag',
			'license'     => 'LGPLv3',
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
