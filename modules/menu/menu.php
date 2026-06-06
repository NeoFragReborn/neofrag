<?php
/**
 * https://neofr.ag
 * Module Menu — constructeur de menus nommés réutilisables (items hiérarchiques).
 */

namespace NF\Modules\Menu;

use NF\NeoFrag\Addons\Module;

class Menu extends Module
{
	protected function __info()
	{
		return [
			'title'       => $this->lang('Menus'),
			'description' => $this->lang('Constructeur de menus nommés réutilisables (items hiérarchiques) pour la navigation.'),
			'icon'        => 'fas fa-bars',
			'link'        => 'https://neofr.ag',
			'author'      => 'NeoFrag Reborn',
			'license'     => 'LGPLv3 <https://neofr.ag/license>',
			'admin'       => TRUE,
			'version'     => '1.0',
			'depends'     => ['neofrag' => '0.2.0'],
			'routes'      => [
				'admin{pages}'                       => 'index',
				'admin/add'                          => '_menu_add',
				'admin/edit/{id}/{url_title}'        => '_menu_edit',
				'admin/delete/{id}/{url_title}'      => '_menu_delete',
				'admin/item/add/{id}'                => '_item_add',
				'admin/item/edit/{id}/{url_title}'   => '_item_edit',
				'admin/item/delete/{id}/{url_title}' => '_item_delete'
			]
		];
	}

	public function permissions()
	{
		return [
			'default' => [
				'access' => [
					[
						'title'  => 'Menus',
						'icon'   => 'fas fa-bars',
						'access' => [
							'manage' => ['title' => $this->lang('Gérer les menus'), 'icon' => 'fas fa-edit', 'admin' => TRUE]
						]
					]
				]
			]
		];
	}
}
