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
			'description' => $this->lang('Cinq affichages de la galerie au choix : ses catégories, les albums d\'une catégorie, une image tirée au hasard, le diaporama d\'un album ou les dernières photos en mosaïque.'),
			'icon'        => 'far fa-image',
			'link'        => 'https://neofrag-reborn.xyz',
			'author'      => 'Michaël BILCOT & Jérémy VALENTIN <contact@neofrag.com>',
			'license'     => 'LGPLv3 <https://www.gnu.org/licenses/lgpl-3.0.html>',
			// Decouplage du paquet : cf. tools/check-addon-declarations.php.
			'core'        => FALSE,
			'presets'     => ['communaute', 'association', 'gaming'],
			'requires'    => ['gallery'],
			'version'     => '1.1',
			'depends'     => [
				'neofrag' => '0.2.0'
			],
			'types'       => [
				'index'  => $this->lang('Liste des catégories'),
				'albums' => $this->lang('Albums d\'une catégorie'),
				'image'  => $this->lang('Image aléatoire'),
				'slider' => $this->lang('Slider d\'un album'),
				'grille' => $this->lang('Les dernières photos')
			]
		];
	}
}
