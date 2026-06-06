<?php
/**
 * https://neofr.ag
 */

namespace NF\Widgets\Latest_Comments;

use NF\NeoFrag\Addons\Widget;

class Latest_Comments extends Widget
{
	protected function __info()
	{
		return [
			'title'       => $this->lang('Derniers commentaires'),
			'description' => 'Affiche les commentaires les plus récents postés sur le site (toutes sections confondues).',
			'link'        => 'https://neofr.ag',
			'author'      => 'NeoFrag Fork',
			'license'     => 'LGPLv3 <https://neofr.ag/license>',
			'version'     => '1.0',
			'depends'     => [
				'neofrag' => '0.2.0'
			]
		];
	}
}
