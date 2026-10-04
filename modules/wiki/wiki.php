<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * Module Wiki — pages collaboratives avec historique des révisions.
 */

namespace NF\Modules\Wiki;

use NF\NeoFrag\Addons\Module;

class Wiki extends Module
{
	/**
	 * Descripteurs de contenu — cf. Module::content_types(). Sans réaction, abonnement ni révision (le
	 * wiki tient son propre historique) : la déclaration sert au référencement de chaque page
	 * (nf_seo_meta) et à son adresse publique.
	 */
	public function declare_content_types()
	{
		return [
			'wiki' => ['table' => 'nf_wiki_pages', 'pk' => 'id'],
		];
	}

	/** L'adresse publique d'une page du wiki. */
	public function content_url($type, $id)
	{
		$slug = $type === 'wiki' ? $this->db->select('slug')->from('nf_wiki_pages')->where('id', (int) $id)->row() : NULL;

		return is_string($slug) && $slug !== '' ? url('wiki/'.$slug) : '';
	}

	protected function __info()
	{
		return [
			'title'       => $this->lang('Wiki'),
			'description' => $this->lang('Pages collaboratives avec historique de révisions automatique.'),
			'icon'        => 'fas fa-book',
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
						'title'  => $this->lang('Wiki'),
						'icon'   => 'fas fa-book',
						'access' => [
							'manage' => ['title' => $this->lang('Gérer les pages du wiki'), 'icon' => 'fas fa-edit', 'admin' => TRUE]
						]
					]
				]
			]
		];
	}
}
