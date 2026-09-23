<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Widgets\News;

use NF\NeoFrag\Addons\Widget;

class News extends Widget
{
	protected function __info()
	{
		return [
			'title'       => $this->lang('Actualités'),
			'description' => $this->lang('Liste des dernières actualités publiées.'),
			'icon'        => 'far fa-file-alt',
			'link'        => 'https://neofr.ag',
			'author'      => 'Michaël BILCOT & Jérémy VALENTIN <contact@neofrag.com>',
			'license'     => 'LGPLv3 <https://neofr.ag/license>',
			// Decouplage du paquet : cf. tools/check-addon-declarations.php.
			'core'        => FALSE,
			'presets'     => ['communaute', 'gaming'],
			'requires'    => [],
			'version'     => '1.0',
			'depends'     => [
				'neofrag' => '0.2.0'
			],
			'types'       => [
				'index'      => $this->lang('Actualités récentes'),
				'categories' => $this->lang('Catégories'),
				'tags'       => $this->lang('Tags')
			]
		];
	}
}
