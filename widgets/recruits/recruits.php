<?php
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
			'title'       => 'Recrutement',
			'description' => 'Postes actuellement ouverts au recrutement — widget gaming.',
			'link'        => 'https://neofr.ag',
			'author'      => 'Michaël BILCOT & Jérémy VALENTIN <contact@neofrag.com>',
			'license'     => 'LGPLv3 <https://neofr.ag/license>',
			'version'     => '1.0',
			'depends'     => [
				'neofrag' => '0.2.0'
			],
			'types'       => [
				'index'   => 'Dernières annonces',
				'recruit' => 'Une annonce en détail'
			]
		];
	}
}
