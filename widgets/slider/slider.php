<?php
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Widgets\Slider;

use NF\NeoFrag\Addons\Widget;

class Slider extends Widget
{
	const MAX_SLIDES = 5;

	protected function __info()
	{
		return [
			'title'       => $this->lang('Slider'),
			'description' => $this->lang('Diaporama d\'images plein largeur, slides éditables depuis l\'admin.'),
			'link'        => 'https://neofr.ag',
			'author'      => 'Michaël BILCOT & Jérémy VALENTIN <contact@neofrag.com>',
			'license'     => 'LGPLv3 <https://neofr.ag/license>',
			'version'     => '2.0',
			'depends'     => [
				'neofrag' => '0.2.0'
			]
		];
	}

	public function default_slides()
	{
		return [
			[
				'title'   => $this->lang('Bienvenue !'),
				'caption' => $this->lang('Configure le slider depuis le Live Editor pour ajouter tes propres slides.'),
				'file_id' => 0,
				'link'    => '',
				'active'  => 1
			]
		];
	}
}
