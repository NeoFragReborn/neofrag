<?php
/**
 * https://neofr.ag
 * @author: Jérémy VALENTIN <jeremy.valentin@neofr.ag>
 */

namespace NF\Widgets\Socials;

use NF\NeoFrag\Addons\Widget;

class Socials extends Widget
{
	protected function __info()
	{
		return [
			'title'       => $this->lang('Réseaux sociaux'),
			'description' => 'Liens vers les réseaux sociaux configurés (Facebook, Twitter, Instagram, etc.).',
			'link'        => 'https://neofr.ag',
			'author'      => 'Jérémy VALENTIN <jeremy.valentin@neofr.ag>',
			'license'     => 'LGPLv3 <https://neofr.ag/license>',
			'version'     => '1.0',
			'depends'     => [
				'neofrag' => '0.2.2'
			]
		];
	}
}
