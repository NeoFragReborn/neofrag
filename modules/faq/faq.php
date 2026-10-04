<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * Module FAQ — questions/réponses catégorisées.
 */

namespace NF\Modules\Faq;

use NF\NeoFrag\Addons\Module;

class Faq extends Module
{
	protected function __info()
	{
		return [
			'title'       => $this->lang('FAQ'),
			'description' => $this->lang('Foire aux questions catégorisée affichée en accordéon Bootstrap.'),
			'icon'        => 'far fa-question-circle',
			'link'        => 'https://neofrag-reborn.xyz',
			'author'      => 'NeoFrag Reborn',
			'license'     => 'LGPLv3 <https://www.gnu.org/licenses/lgpl-3.0.html>',
			// Decouplage du paquet : cf. tools/check-addon-declarations.php.
			'core'        => FALSE,
			'presets'     => ['association'],
			'requires'    => [],
			'admin'       => TRUE,
			'version'     => '1.0',
			'depends'     => ['neofrag' => '0.2.0'],
			'routes'      => [
				''                              => 'index',
				'admin{pages}'                  => 'index',
				'admin/q/add'                   => '_q_add',
				'admin/q/{id}/{url_title}'      => '_q_edit',
				'admin/q/delete/{id}/{url_title}' => '_q_delete',
				'admin/cat/add'                 => '_cat_add',
				'admin/cat/{id}/{url_title}'    => '_cat_edit',
				'admin/cat/delete/{id}/{url_title}' => '_cat_delete'
			]
		];
	}

	public function permissions()
	{
		return [
			'default' => [
				'access' => [
					[
						'title'  => $this->lang('FAQ'),
						'icon'   => 'far fa-question-circle',
						'access' => [
							'manage_questions'  => ['title' => $this->lang('Gérer les questions'),  'icon' => 'far fa-question-circle', 'admin' => TRUE],
							'manage_categories' => ['title' => $this->lang('Gérer les catégories'), 'icon' => 'far fa-folder',           'admin' => TRUE]
						]
					]
				]
			]
		];
	}
}
