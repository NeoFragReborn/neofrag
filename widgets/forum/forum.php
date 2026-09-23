<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Widgets\Forum;

use NF\NeoFrag\Addons\Widget;

class Forum extends Widget
{
	protected function __info()
	{
		return [
			'title'       => $this->lang('Forum'),
			'description' => $this->lang('Derniers sujets ou messages du forum.'),
			'icon'        => 'fas fa-comments',
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
				'index'      => $this->lang('Derniers messages'),
				'topics'     => $this->lang('Derniers sujets'),
				'statistics' => $this->lang('Statistiques'),
				'activity'   => $this->lang('Activité')
			]
		];
	}
}
