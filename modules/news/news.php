<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\News;

use NF\NeoFrag\Addons\Module;

class News extends Module
{

	/** Descripteurs de contenu — cf. Module::content_types(). */
	public function declare_content_types()
	{
		return [
			'news' => [
				'table' => 'nf_news', 'pk' => 'news_id', 'author' => 'user_id',
				'reactable' => TRUE, 'subscribable' => TRUE, 'revisable' => TRUE,
			],
			'news-category' => [
				'table' => 'nf_news_categories', 'pk' => 'category_id',
				'subscribable' => TRUE,
			],
		];
	}

	/** URL publique d'une actualite (titre lu dans la langue courante). */
	public function content_url($type, $id)
	{
		if ($type !== 'news')
		{
			return '';
		}

		$title = $this->db	->select('title')
							->from('nf_news_lang')
							->where('news_id', (int) $id)
							->where('lang', $this->config->lang->info()->name)
							->row();

		return $title ? 'news/'.(int) $id.'/'.url_title($title) : '';
	}

	/**
	 * Corbeille : ce module declare LUI-MEME son type restaurable (inversion du 2026-09-15).
	 * Avant, c'est `trash` qui tenait en dur la liste des tables des autres modules — il ne
	 * pouvait donc pas etre du coeur sans tirer news/articles/gallery/forum avec lui.
	 * Desormais le coeur collecte, il ne connait plus personne. Idiome repris de `groups()`.
	 */
	public function trash_types()
	{
		return [
			'news' => [
				'label'   => 'Actualité', 'table' => 'nf_news',
				'pk'      => 'news_id', 'lang' => 'nf_news_lang', 'title' => 'title',
				'restore' => 'restore_news', 'purge' => 'purge_news', 'url' => 'news/%d/%s',
			],
		];
	}
	protected function __info()
	{
		return [
			'title'       => $this->lang('Actualités'),
			'description' => $this->lang('Actualités classées par catégories et tags : publication programmée, commentaires, réactions, abonnement par catégorie et historique des modifications.'),
			'icon'        => 'far fa-file-alt',
			'link'        => 'https://neofr.ag',
			'author'      => 'Michaël BILCOT & Jérémy VALENTIN <contact@neofrag.com>',
			'license'     => 'LGPLv3 <https://www.gnu.org/licenses/lgpl-3.0.html>',
			// Decouplage du paquet : cf. tools/check-addon-declarations.php.
			'core'        => FALSE,
			'presets'     => ['communaute', 'association', 'gaming'],
			'requires'    => [],
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

	/**
	 * Les notifications que ce module envoie, pour les préférences de chaque membre (Notifications::types(),
	 * chantier A, étape A4).
	 *
	 * @return list<array<string, mixed>>
	 */
	public function types_de_notification(): array
	{
		return [
			['type' => 'news', 'titre' => (string) $this->lang('Une actualité dans une catégorie que je suis'), 'ordre' => 60],
		];
	}
}
