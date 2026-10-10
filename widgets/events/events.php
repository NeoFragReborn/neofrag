<?php
declare(strict_types=1);
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
			'description' => $this->lang('Six affichages des événements au choix : calendrier, liste par type, un événement en détail, types d\'événements, derniers résultats ou prochains matchs.'),
			'icon'        => 'fas fa-calendar-check',
			'link'        => 'https://neofrag-reborn.xyz',
			'author'      => 'Michaël BILCOT & Jérémy VALENTIN <contact@neofrag.com>',
			'license'     => 'LGPLv3 <https://www.gnu.org/licenses/lgpl-3.0.html>',
			// Decouplage du paquet : cf. tools/check-addon-declarations.php.
			'core'        => FALSE,
			'presets'     => ['gaming'],
			'requires'    => ['events'],
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
