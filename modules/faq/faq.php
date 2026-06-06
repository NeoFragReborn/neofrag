<?php
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
			'description' => 'Foire aux questions catégorisée affichée en accordéon Bootstrap.',
			'icon'        => 'far fa-question-circle',
			'link'        => 'https://neofr.ag',
			'author'      => 'NeoFrag Reborn',
			'license'     => 'LGPLv3 <https://neofr.ag/license>',
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
						'title'  => 'FAQ',
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
