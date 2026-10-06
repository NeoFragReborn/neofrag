<?php
declare(strict_types=1);
namespace NF\Widgets\Calendar;
use NF\NeoFrag\Addons\Widget;

class Calendar extends Widget
{
	protected function __info()
	{
		return [
			'title'   => $this->lang('Calendrier'),
			'description' => $this->lang('Les prochains événements du calendrier, avec leur date et leur couleur, ou la semaine en cours, ses jours d\'événement marqués et le prochain rendez-vous ; un lien mène au calendrier complet.'),
			'icon'        => 'far fa-calendar',
			'author'  => 'NeoFrag Reborn',
			'license' => 'LGPLv3 <https://www.gnu.org/licenses/lgpl-3.0.html>',
			// Decouplage du paquet : cf. tools/check-addon-declarations.php.
			'core'        => FALSE,
			'presets'     => ['association', 'gaming'],
			'requires'    => [],
			'version' => '1.1',
			'link'        => 'https://neofrag-reborn.xyz',
			'depends' => ['neofrag' => '0.2.0'],
			'types'   => [
				'upcoming' => $this->lang('Prochains événements'),
				'semaine'  => $this->lang('La semaine')
			]
		];
	}
}
