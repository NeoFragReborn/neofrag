<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\Gallery;

use NF\NeoFrag\Addons\Module;

class Gallery extends Module
{

	/** Corbeille : type restaurable declare par le module lui-meme (cf. Trash::types()). */
	public function trash_types()
	{
		return [
			'gallery' => [
				'label'   => 'Galerie', 'table' => 'nf_gallery',
				'pk'      => 'gallery_id', 'lang' => 'nf_gallery_lang', 'title' => 'title',
				'restore' => 'restore_gallery', 'purge' => 'purge_gallery', 'url' => 'gallery/album/%d/%s',
			],
		];
	}
	protected function __info()
	{
		return [
			'title'       => $this->lang('Galeries'),
			'description' => $this->lang('Galerie photos en catégories et albums ; chaque album a ses droits de consultation et de publication. Images avec titre, description et commentaires.'),
			'icon'        => 'far fa-image',
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
				'{id}/{url_title}'                         => '_category',
				'album/{id}/{url_title}{page}'             => '_gallery',
				// La page d'une image. Le widget « Image aléatoire », le diaporama de la galerie et le
				// lien d'un commentaire posé sur une image y renvoient ; la route manquait depuis
				// l'origine du fork, et chacun de ces liens aboutissait à une page introuvable
				// (trouvé le 2026-09-23 en cherchant l'adresse d'un album).
				'image/{id}/{url_title}'                   => '_image',
				//Admin
				'admin{pages}'                             => 'index',
				'admin/add' => 'add',
				'admin/{id}/{url_title}'                   => '_edit',
				'admin/delete/{id}/{url_title}'            => '_delete',
				'admin/categories/add'                     => '_categories_add',
				'admin/categories/{id}/{url_title}'        => '_categories_edit',
				'admin/categories/delete/{id}/{url_title}' => '_categories_delete',
				'admin/ajax/image/add/{id}/{url_title}'    => '_image_add',
				'admin/image/{id}/{url_title}'             => '_image_edit',
				'admin/image/delete/{id}/{url_title}'      => '_image_delete'
			],
			'settings'    => function(){
				return $this->form2()
							->rule($this->form_number('images_per_page')
										->title($this->lang('Images par page'))
										->value($this->config->images_per_page)
							)
							->success(function($data){
								$this->config('images_per_page', $data['images_per_page']);
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
						'title'  => $this->lang('Albums photos'),
						'icon'   => 'far fa-image',
						'access' => [
							'add_gallery' => [
								'title' => $this->lang('Créer'),
								'icon'  => 'fas fa-plus',
								'admin' => TRUE
							],
							'modify_gallery' => [
								'title' => $this->lang('Modifier'),
								'icon'  => 'fas fa-edit',
								'admin' => TRUE
							],
							'delete_gallery' => [
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
							'add_gallery_category' => [
								'title' => $this->lang('Ajouter une catégorie'),
								'icon'  => 'fas fa-plus',
								'admin' => TRUE
							],
							'modify_gallery_category' => [
								'title' => $this->lang('Modifier une catégorie'),
								'icon'  => 'fas fa-edit',
								'admin' => TRUE
							],
							'delete_gallery_category' => [
								'title' => $this->lang('Supprimer une catégorie'),
								'icon'  => 'far fa-trash-alt',
								'admin' => TRUE
							]
						]
					]
				]
			],
			'gallery' => [
				'get_all' => function(){
					return NeoFrag()->db->select('gallery_id', 'title')->from('nf_gallery_lang')->where('lang', $this->config->lang->info()->name)->get();
				},
				'check'   => function($gallery_id){
					if (($gallery = NeoFrag()->db->select('title')->from('nf_gallery_lang')->where('gallery_id', $gallery_id)->where('lang', $this->config->lang->info()->name)->row()) !== [])
					{
						return $gallery;
					}
				},
				'init'    => [
					'gallery_see'     => [
					],
					'gallery_post'    => [
						['admins', TRUE]
					]
				],
				'access'  => [
					[
						'title'  => $this->lang('Galeries'),
						'icon'   => 'far fa-image',
						'access' => [
							'gallery_see' => [
								'title' => $this->lang('Voir l\'album'),
								'icon'  => 'far fa-eye'
							],
							'gallery_post' => [
								'title' => $this->lang('Poster une photo'),
								'icon'  => 'fas fa-pencil-alt'
							]
						]
					]
				]
			]
		];
	}

	public function comments($image_id)
	{
		$image = $this->db	->select('title')
							->from('nf_gallery_images')
							->where('image_id', $image_id)
							->row();

		if ($image)
		{
			return [
				'title' => $image,
				'url'   => 'gallery/image/'.$image_id.'/'.url_title($image)
			];
		}
	}
}
