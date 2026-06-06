<?php
/**
 * https://neofr.ag
 * Module Wiki — pages collaboratives avec historique des révisions.
 */

namespace NF\Modules\Wiki;

use NF\NeoFrag\Addons\Module;

class Wiki extends Module
{
	protected function __info()
	{
		return [
			'title'       => $this->lang('Wiki'),
			'description' => $this->lang('Pages collaboratives avec historique de révisions automatique.'),
			'icon'        => 'fas fa-book',
			'link'        => 'https://neofr.ag',
			'author'      => 'NeoFrag Reborn',
			'license'     => 'LGPLv3 <https://neofr.ag/license>',
			'admin'       => TRUE,
			'version'     => '1.0',
			'depends'     => ['neofrag' => '0.2.0'],
			'routes'      => [
				''                                  => 'index',
				'history/{url_title}'               => '_history',
				'revision/{id}'                     => '_revision',
				'diff/{id}/{id}'                    => '_diff',
				'diff/{id}'                         => '_diff',
				'{url_title}'                       => '_page',
				'admin{pages}'                      => 'index',
				'admin/add'                         => '_add',
				'admin/edit/{url_title}'            => '_edit',
				'admin/delete/{url_title}'          => '_delete'
			]
		];
	}

	public function permissions()
	{
		return [
			'default' => [
				'access' => [
					[
						'title'  => 'Wiki',
						'icon'   => 'fas fa-book',
						'access' => [
							'manage' => ['title' => 'Gérer pages wiki', 'icon' => 'fas fa-edit', 'admin' => TRUE]
						]
					]
				]
			]
		];
	}
}
