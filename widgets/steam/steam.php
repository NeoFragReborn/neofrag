<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 */

namespace NF\Widgets\Steam;

use NF\NeoFrag\Addons\Widget;

class Steam extends Widget
{
	protected function __info()
	{
		return [
			'title'       => $this->lang('Groupe Steam'),
			'description' => $this->lang('Affiche le nombre de membres, la présence en ligne et l\'activité d\'un groupe Steam.'),
			'icon'        => 'fab fa-steam',
			'author'      => 'Michaël BILCOT & Jérémy VALENTIN <contact@neofrag.com>',
			'license'     => 'LGPLv3 <https://www.gnu.org/licenses/lgpl-3.0.html>',
			// Decouplage du paquet : cf. tools/check-addon-declarations.php.
			'core'        => FALSE,
			'presets'     => ['gaming'],
			'requires'    => [],
			'version'     => '2.0',
			'link'        => 'https://neofr.ag',
			'depends'     => [
				'neofrag' => '0.2.0'
			],
			'types'       => [
				'index' => $this->lang('Groupe Steam')
			]
		];
	}
}
