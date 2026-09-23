<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Widgets\Recruits;

use NF\NeoFrag\Addons\Widget;

class Recruits extends Widget
{
	protected function __info()
	{
		return [
			'title'       => $this->lang('Recrutement'),
			'description' => $this->lang('Postes actuellement ouverts au recrutement — widget gaming.'),
			'icon'        => 'fas fa-bullhorn',
			'link'        => 'https://neofr.ag',
			'author'      => 'Michaël BILCOT & Jérémy VALENTIN <contact@neofrag.com>',
			'license'     => 'LGPLv3 <https://neofr.ag/license>',
			// Decouplage du paquet : cf. tools/check-addon-declarations.php.
			'core'        => FALSE,
			'presets'     => ['gaming'],
			'requires'    => ['teams'],
			'version'     => '1.0',
			'depends'     => [
				'neofrag' => '0.2.0'
			],
			'types'       => [
				'index'   => $this->lang('Dernières annonces'),
				'recruit' => $this->lang('Une annonce en détail')
			]
		];
	}
}
