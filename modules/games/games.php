<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\Games;

use NF\NeoFrag\Addons\Module;

class Games extends Module
{
	protected function __info()
	{
		return [
			'title'       => $this->lang('Jeux / Cartes'),
			'description' => $this->lang('La liste des jeux pratiqués, avec bannière, icône, cartes et modes de jeu ; elle sert de base aux équipes, aux matchs, au palmarès et au recrutement.'),
			'icon'        => 'fas fa-gamepad',
			'link'        => 'https://neofr.ag',
			'author'      => 'Michaël BILCOT & Jérémy VALENTIN <contact@neofrag.com>',
			'license'     => 'LGPLv3 <https://www.gnu.org/licenses/lgpl-3.0.html>',
			// Decouplage du paquet : cf. tools/check-addon-declarations.php.
			'core'        => FALSE,
			'presets'     => ['gaming'],
			'requires'    => [],
			'admin'       => TRUE,
			'version'     => '1.0',
			'depends'     => [
				'neofrag' => '0.2.0'
			],
			'routes'      => [
				//Admin
				'admin{pages}'                  => 'index',
				'admin/{id}/{url_title}{pages}' => '_edit',
				'admin/delete/{id}/{url_title}' => '_delete',

				//Maps
				'admin/maps/add(?:/{id}/{url_title})?' => '_maps_add',
				'admin/maps/edit/{id}/{url_title}'     => '_maps_edit',
				'admin/maps/delete/{id}/{url_title}'   => '_maps_delete',

				//Modes
				'admin/modes/add/{id}/{url_title}'    => '_modes_add',
				'admin/modes/edit/{id}/{url_title}'   => '_modes_edit',
				'admin/modes/delete/{id}/{url_title}' => '_modes_delete'
			]
		];
	}

	public function permissions()
	{
		return [
			'default' => [
				'access'  => [
					[
						'title'  => $this->lang('Jeux'),
						'icon'   => 'fas fa-gamepad',
						'access' => [
							'add_games' => [
								'title' => $this->lang('Ajouter'),
								'icon'  => 'fas fa-plus',
								'admin' => TRUE
							],
							'modify_games' => [
								'title' => $this->lang('Modifier'),
								'icon'  => 'fas fa-edit',
								'admin' => TRUE
							],
							'delete_games' => [
								'title' => $this->lang('Supprimer'),
								'icon'  => 'far fa-trash-alt',
								'admin' => TRUE
							]
						]
					],
					[
						'title'  => $this->lang('Cartes'),
						'icon'   => 'far fa-map',
						'access' => [
							'add_games_maps' => [
								'title' => $this->lang('Ajouter une carte'),
								'icon'  => 'fas fa-plus',
								'admin' => TRUE
							],
							'modify_games_maps' => [
								'title' => $this->lang('Modifier une carte'),
								'icon'  => 'fas fa-edit',
								'admin' => TRUE
							],
							'delete_games_maps' => [
								'title' => $this->lang('Supprimer une carte'),
								'icon'  => 'far fa-trash-alt',
								'admin' => TRUE
							]
						]
					]
				]
			]
		];
	}
}
