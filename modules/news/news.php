<?php
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\News;

use NF\NeoFrag\Addons\Module;

class News extends Module
{
	protected function __info()
	{
		return [
			'title'       => $this->lang('Actualités'),
			'description' => $this->lang('Actualités du site avec catégories et commentaires.'),
			'icon'        => 'far fa-file-alt',
			'link'        => 'https://neofr.ag',
			'author'      => 'Michaël BILCOT & Jérémy VALENTIN <contact@neofrag.com>',
			'license'     => 'LGPLv3 <https://neofr.ag/license>',
			'admin'       => TRUE,
			'version'     => '1.0',
			'depends'     => [
				'neofrag' => '0.2.0'
			],
			'routes'      => [
				//Index
				'{page}'                                   => 'index',
				'{id}/{url_title}'                         => '_news',
				'tag/{url_title}{pages}'                   => '_tag',
				'category/{id}/{url_title}{pages}'         => '_category',

				//Admin
				'admin{pages}'                             => 'index',
				'admin/add' => 'add',
				'admin/history/{id}/{url_title}'           => '_history',
				'admin/revision/restore/{id}/{url_title}/{id}' => '_revision_restore',
				'admin/{id}/{url_title}'                   => '_edit',
				'admin/delete/{id}/{url_title}'            => '_delete',
				'admin/categories/add'                     => '_categories_add',
				'admin/categories/{id}/{url_title}'        => '_categories_edit',
				'admin/categories/delete/{id}/{url_title}' => '_categories_delete'
			],
			'settings'    => function(){
				return $this->form2()
							->rule($this->form_number('news_per_page')
										->title($this->lang('Actualités par page'))
										->value($this->config->news_per_page)
							)
							->success(function($data){
								$this->config('news_per_page', $data['news_per_page']);
								notify($this->lang('Configuration modifiée'));
								refresh();
							});
			}
		];
	}

	public function permissions()
	{
		return [
			'default' => [
				'access'  => [
					[
						'title'  => $this->lang('Actualités'),
						'icon'   => 'far fa-file-alt',
						'access' => [
							'add_news' => [
								'title' => $this->lang('Ajouter'),
								'icon'  => 'fas fa-plus',
								'admin' => TRUE
							],
							'modify_news' => [
								'title' => $this->lang('Modifier'),
								'icon'  => 'fas fa-edit',
								'admin' => TRUE
							],
							'delete_news' => [
								'title' => $this->lang('Supprimer'),
								'icon'  => 'far fa-trash-alt',
								'admin' => TRUE
							]
						]
					],
					[
						'title'  => $this->lang('Catégories'),
						'icon'   => 'fas fa-align-left',
						'access' => [
							'add_news_category' => [
								'title' => $this->lang('Ajouter une catégorie'),
								'icon'  => 'fas fa-plus',
								'admin' => TRUE
							],
							'modify_news_category' => [
								'title' => $this->lang('Modifier une catégorie'),
								'icon'  => 'fas fa-edit',
								'admin' => TRUE
							],
							'delete_news_category' => [
								'title' => $this->lang('Supprimer une catégorie'),
								'icon'  => 'far fa-trash-alt',
								'admin' => TRUE
							]
						]
					]
				]
			]
		];
	}

	public function comments($news_id)
	{
		$news = $this->db	->select('title')
							->from('nf_news_lang')
							->where('news_id', $news_id)
							->where('lang', $this->config->lang->info()->name)
							->row();

		if ($news)
		{
			return [
				'title' => $news,
				'url'   => 'news/'.$news_id.'/'.url_title($news)
			];
		}
	}
}
