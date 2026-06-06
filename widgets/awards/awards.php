<?php
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Widgets\Awards;

use NF\NeoFrag\Addons\Widget;

class Awards extends Widget
{
	protected function __info()
	{
		return [
			'title'       => $this->lang('Palmarès'),
			'description' => $this->lang('Affiche les dernières récompenses attribuées — module gaming.'),
			'link'        => 'https://neofr.ag',
			'author'      => 'Michaël BILCOT & Jérémy VALENTIN <contact@neofrag.com>',
			'license'     => 'LGPLv3 <https://neofr.ag/license>',
			'version'     => '1.0',
			'types'       => [
				'index'     => $this->lang('Derniers palmarès'),
				'best_team' => $this->lang('Équipe la plus récompensée'),
				'best_game' => $this->lang('Jeu le plus récompensé')
			]
		];
	}
}
