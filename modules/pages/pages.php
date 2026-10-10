<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\Pages;

use NF\NeoFrag\Addons\Module;

class Pages extends Module
{
	/**
	 * Descripteurs de contenu — cf. Module::content_types(). Sans réaction, abonnement ni révision : la
	 * déclaration sert au référencement de chaque page (nf_seo_meta) et à son adresse publique.
	 */
	public function declare_content_types()
	{
		return [
			'pages' => ['table' => 'nf_pages', 'pk' => 'page_id', 'author' => NULL],
		];
	}

	/** L'adresse publique d'une page : son nom, à la racine du site. */
	public function content_url($type, $id)
	{
		$nom = $type === 'pages' ? $this->db->select('name')->from('nf_pages')->where('page_id', (int) $id)->row() : NULL;

		return is_string($nom) && $nom !== '' ? url($nom) : '';
	}

	protected function __info()
	{
		return [
			'title'       => $this->lang('Pages'),
			'description' => $this->lang('Pages CMS statiques : à propos, mentions légales, conditions, etc.'),
			'icon'        => 'far fa-file',
			'link'        => 'https://neofrag-reborn.xyz',
			'author'      => 'Michaël BILCOT & Jérémy VALENTIN <contact@neofrag.com>',
			'license'     => 'LGPLv3 <https://www.gnu.org/licenses/lgpl-3.0.html>',
			// Decouplage du paquet : cf. tools/check-addon-declarations.php.
			'core'        => TRUE,
			'presets'     => [],
			'requires'    => [],
			'version'     => '1.0',
			'admin'       => TRUE,
			'routes'      => [
				//Index
				''                        => 'index',
				'{url_title}'             => '_index',

				//Admin
				'admin{pages}'                   => 'index',
				'admin/add' => 'add',
				'admin/{id}/{url_title*}'        => '_edit',
				'admin/delete/{id}/{url_title*}' => '_delete'
			]
		];
	}

	public function permissions()
	{
		return [
			'default' => [
				'access'  => [
					[
						'title'  => $this->lang('Pages'),
						'icon'   => 'far fa-file',
						'access' => [
							'add_pages' => [
								'title' => $this->lang('Ajouter'),
								'icon'  => 'fas fa-plus',
								'admin' => TRUE
							],
							'modify_pages' => [
								'title' => $this->lang('Modifier'),
								'icon'  => 'fas fa-edit',
								'admin' => TRUE
							],
							'delete_pages' => [
								'title' => $this->lang('Supprimer'),
								'icon'  => 'far fa-trash-alt',
								'admin' => TRUE
							]
						]
					]
				]
			],
			'page' => [
				'get_all' => function(){
					// Le libellé se compose en PHP, après la requête (même règle que modules/files/files.php).
					return array_map(fn($ligne) => [
						'page_id' => $ligne['page_id'],
						'title'   => (string) $this->lang('Page %s', $ligne['title'])
					], NeoFrag()->db->select('p.page_id', 'pl.title')->from('nf_pages p')->join('nf_pages_lang pl', 'p.page_id = pl.page_id')->where('pl.lang', $this->config->lang->info()->name)->get());
				},
				'check'   => function($page_id){
					if (($page = NeoFrag()->db->select('title')->from('nf_pages_lang')->where('page_id', $page_id)->where('lang', $this->config->lang->info()->name)->row()) !== [])
					{
						return $this->lang('Page %s', $page);
					}
				},
				'init'    => [
					'access_page' => []
				],
				'access'  => [
					[
						'title'  => $this->lang('Pages'),
						'icon'   => 'far fa-file',
						'access' => [
							'access_page' => [
								'title' => $this->lang('Accès au contenu'),
								'icon'  => 'far fa-eye'
							]
						]
					]
				]
			]
		];
	}
}
