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
			'link'        => 'https://neofrag-reborn.xyz',
			'author'      => 'NeoFrag Reborn',
			'license'     => 'LGPLv3 <https://www.gnu.org/licenses/lgpl-3.0.html>',
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
