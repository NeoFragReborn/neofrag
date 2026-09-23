<?php
declare(strict_types=1);
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
			'description' => $this->lang('Affiche les commentaires les plus récents postés sur le site (toutes sections confondues).'),
			'icon'        => 'far fa-comments',
			'link'        => 'https://neofr.ag',
			'author'      => 'NeoFrag Fork',
			'license'     => 'LGPLv3 <https://neofr.ag/license>',
			// Decouplage du paquet : cf. tools/check-addon-declarations.php.
			'core'        => TRUE,
			'presets'     => [],
			'requires'    => [],
			'version'     => '1.0',
			'depends'     => [
				'neofrag' => '0.2.0'
			]
		];
	}
}
