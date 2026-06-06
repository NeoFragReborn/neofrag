<?php
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Widgets\Events;

use NF\NeoFrag\Addons\Widget;

class Events extends Widget
{
	protected function __info()
	{
		return [
			'title'       => $this->lang('Événements'),
			'description' => $this->lang('Calendrier ou liste des événements à venir — module gaming.'),
			'link'        => 'https://neofr.ag',
			'author'      => 'Michaël BILCOT & Jérémy VALENTIN <contact@neofrag.com>',
			'license'     => 'LGPLv3 <https://neofr.ag/license>',
			'version'     => '1.0',
			'depends'     => [
				'neofrag' => '0.2.0'
			],
			'types'       => [
				'index'       => $this->lang('Calendrier des événements'),
				'types'       => $this->lang('Liste des types d\'événements'),
				'events'      => $this->lang('Liste des événements'),
				'event'       => $this->lang('Un événement en détail'),
				'matches'     => $this->lang('Derniers résultats'),
				'upcoming'    => $this->lang('Prochains matchs')
			]
		];
	}
}
