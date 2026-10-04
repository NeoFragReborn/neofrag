<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 *
 * Module Slider — gestion admin des slides du widget slider.
 * La config se fait dans /admin/slider, et le widget /widgets/slider/ lit la
 * table nf_slider_slides à l'affichage.
 */

namespace NF\Modules\Slider;

use NF\NeoFrag\Addons\Module;

class Slider extends Module
{
	protected function __info()
	{
		return [
			'title'       => $this->lang('Slider'),
			'description' => $this->lang('Gestion des slides du widget slider en page d\'accueil. Ajoute/modifie images, titres, sous-titres, liens et ordre.'),
			'icon'        => 'fas fa-images',
			'author'      => 'NeoFrag Reborn',
			'license'     => 'LGPLv3 <https://www.gnu.org/licenses/lgpl-3.0.html>',
			// Decouplage du paquet : cf. tools/check-addon-declarations.php.
			'core'        => TRUE,
			'presets'     => [],
			'requires'    => [],
			'admin'       => TRUE,
			'version'     => '1.0',
			'link'        => 'https://neofrag-reborn.xyz',
			'depends'     => ['neofrag' => '0.2.0'],
			'routes'      => [
				'admin'              => 'index',
				'admin/add'          => '_add',
				'admin/edit/{id}'    => '_edit',
				'admin/delete/{id}'  => '_delete',
				'admin/toggle/{id}'  => '_toggle',
				'admin/move'         => '_move'
			]
		];
	}

	public function permissions()
	{
		return [
			'default' => [
				'access' => [[
					'title'  => $this->lang('Slider'),
					'icon'   => 'fas fa-images',
					'access' => [
						'manage_slides' => [
							'title' => $this->lang('Gérer les slides'),
							'icon'  => 'fas fa-edit',
							'admin' => TRUE
						]
					]
				]]
			]
		];
	}
}
