<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 */

namespace NF\Widgets\Clock;

use NF\NeoFrag\Addons\Widget;

class Clock extends Widget
{
	protected function __info()
	{
		return [
			'title'       => $this->lang('Horloge & calendrier'),
			'description' => $this->lang('Affichage dynamique de l\'heure, de la date du jour et des anniversaires des membres.'),
			'icon'        => 'fas fa-clock',
			'author'      => 'ArkaNiX — portage NeoFrag Reborn',
			'license'     => 'LGPLv3 <https://www.gnu.org/licenses/lgpl-3.0.html>',
			// Decouplage du paquet : cf. tools/check-addon-declarations.php.
			'core'        => TRUE,
			'presets'     => [],
			'requires'    => [],
			'version'     => '3.0',
			'link'        => 'https://neofrag-reborn.xyz',
			'depends'     => [
				'neofrag' => '0.2.0'
			],
			'types'       => [
				'index' => $this->lang('Horloge')
			]
		];
	}
}
