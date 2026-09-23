<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: HiddenCMS — porté sur NeoFrag Reborn
 */

namespace NF\Modules\Files;

use NF\NeoFrag\Addons\Module;

class Files extends Module
{
	protected function __info()
	{
		return [
			'title'       => $this->lang('Fichiers'),
			'description' => $this->lang('Gestionnaire de fichiers : arborescence, upload, dossiers et permissions de lecture par fichier/dossier.'),
			'icon'        => 'far fa-folder-open',
			'link'        => 'https://neofr.ag',
			'author'      => 'HiddenCMS',
			'license'     => 'LGPLv3 <https://neofr.ag/license>',
			// Decouplage du paquet : cf. tools/check-addon-declarations.php.
			'core'        => TRUE,
			'presets'     => [],
			'requires'    => [],
			'admin'       => TRUE,
			'front'       => TRUE,
			'version'     => '1.0',
			'depends'     => [
				'neofrag' => '1.0.0'
			],
			'routes'      => [
				'{url_title}'  => '_file',
				'admin{pages}' => 'index'
			]
		];
	}

	public function permissions()
	{
		return [
			'default' => [
				'access'  => [
					[
						'title'  => $this->lang('Fichiers'),
						'icon'   => 'far fa-folder-open',
						'access' => [
							'add_files' => [
								'title' => $this->lang('Ajouter'),
								'icon'  => 'fas fa-plus',
								'admin' => TRUE
							],
							'modify_files' => [
								'title' => $this->lang('Modifier'),
								'icon'  => 'fas fa-edit',
								'admin' => TRUE
							],
							'delete_files' => [
								'title' => $this->lang('Supprimer'),
								'icon'  => 'far fa-trash-alt',
								'admin' => TRUE
							]
						]
					]
				]
			],
			'directory' => [
				'get_all' => function(){
					// Le libellé se compose en PHP, après la requête : écrit dans le SQL (`CONCAT_WS(" ", "Dossier", path)`),
					// le mot « Dossier » restait en français dans toutes les langues.
					return array_map(fn($ligne) => [
						'directory_id' => $ligne['directory_id'],
						'title'        => (string) $this->lang('Dossier %s', $ligne['path'])
					], NeoFrag()->db->select('directory_id', 'path')->from('nf_files_directories')->order_by('path')->get());
				},
				'check' => function($directory_id){
					if (($path = NeoFrag()->db->select('path')->from('nf_files_directories')->where('directory_id', (int)$directory_id)->row()) !== [])
					{
						return (string) $this->lang('Dossier %s', $path);
					}
				},
				'init' => [
					'read_directory' => [
						['visitors', TRUE]
					]
				],
				'access' => [
					[
						'title'  => $this->lang('Dossiers'),
						'icon'   => 'far fa-folder',
						'access' => [
							'read_directory' => [
								'title' => $this->lang('Lecture'),
								'icon'  => 'far fa-eye'
							]
						]
					]
				]
			],
			'file' => [
				'get_all' => function(){
					// Même raison que pour les dossiers : le mot « Fichier » se traduit en PHP, pas dans le SQL.
					return array_map(fn($ligne) => [
						'id'    => $ligne['id'],
						'title' => (string) $this->lang('Fichier %s', $ligne['name'])
					], NeoFrag()->db->select('id', 'name')->from('nf_file')->where('path LIKE', 'upload/files/%')->order_by('name')->get());
				},
				'check' => function($file_id){
					if (($name = NeoFrag()->db->select('name')->from('nf_file')->where('id', (int)$file_id)->where('path LIKE', 'upload/files/%')->row()) !== [])
					{
						return (string) $this->lang('Fichier %s', $name);
					}
				},
				'init' => [
					'read_file' => [
						['visitors', TRUE]
					]
				],
				'access' => [
					[
						'title'  => $this->lang('Fichiers'),
						'icon'   => 'far fa-file',
						'access' => [
							'read_file' => [
								'title' => $this->lang('Lecture'),
								'icon'  => 'far fa-eye'
							]
						]
					]
				]
			]
		];
	}
}
