<?php
declare(strict_types=1);
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
			'description' => $this->lang('Liens vers les réseaux sociaux configurés (Facebook, Twitter, Instagram, etc.).'),
			'icon'        => 'fas fa-share-nodes',
			'link'        => 'https://neofrag-reborn.xyz',
			'author'      => 'Jérémy VALENTIN <jeremy.valentin@neofr.ag>',
			'license'     => 'LGPLv3 <https://www.gnu.org/licenses/lgpl-3.0.html>',
			// Decouplage du paquet : cf. tools/check-addon-declarations.php.
			'core'        => TRUE,
			'presets'     => [],
			'requires'    => [],
			'version'     => '1.0',
			'depends'     => [
				'neofrag' => '0.2.2'
			]
		];
	}
}
