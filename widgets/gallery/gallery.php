<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Widgets\Gallery;

use NF\NeoFrag\Addons\Widget;

class Gallery extends Widget
{
	protected function __info()
	{
		return [
			'title'       => $this->lang('Galeries'),
			'description' => $this->lang('Aperçu de la galerie photos avec dernières images.'),
			'icon'        => 'far fa-image',
			'link'        => 'https://neofr.ag',
			'author'      => 'Michaël BILCOT & Jérémy VALENTIN <contact@neofrag.com>',
			'license'     => 'LGPLv3 <https://www.gnu.org/licenses/lgpl-3.0.html>',
			// Decouplage du paquet : cf. tools/check-addon-declarations.php.
			'core'        => FALSE,
			'presets'     => ['communaute', 'association', 'gaming'],
			'requires'    => [],
			'version'     => '1.0',
			'depends'     => [
				'neofrag' => '0.2.0'
			],
			'types'       => [
				'index'  => $this->lang('Liste des catégories'),
				'albums' => $this->lang('Albums d\'une catégorie'),
				'image'  => $this->lang('Image aléatoire'),
				'slider' => $this->lang('Slider d\'un album')
			]
		];
	}
}
