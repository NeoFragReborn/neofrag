<?php
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
						'title'  => 'Fichiers',
						'icon'   => 'far fa-folder-open',
						'access' => [
							'add_files' => [
								'title' => 'Ajouter',
								'icon'  => 'fas fa-plus',
								'admin' => TRUE
							],
							'modify_files' => [
								'title' => 'Modifier',
								'icon'  => 'fas fa-edit',
								'admin' => TRUE
							],
							'delete_files' => [
								'title' => 'Supprimer',
								'icon'  => 'far fa-trash-alt',
								'admin' => TRUE
							]
						]
					]
				]
			],
			'directory' => [
				'get_all' => function(){
					return NeoFrag()->db->select('directory_id', 'CONCAT_WS(" ", "Dossier", path)')->from('nf_files_directories')->order_by('path')->get();
				},
				'check' => function($directory_id){
					if (($path = NeoFrag()->db->select('path')->from('nf_files_directories')->where('directory_id', (int)$directory_id)->row()) !== [])
					{
						return 'Dossier '.$path;
					}
				},
				'init' => [
					'read_directory' => [
						['visitors', TRUE]
					]
				],
				'access' => [
					[
						'title'  => 'Dossiers',
						'icon'   => 'far fa-folder',
						'access' => [
							'read_directory' => [
								'title' => 'Lecture',
								'icon'  => 'far fa-eye'
							]
						]
					]
				]
			],
			'file' => [
				'get_all' => function(){
					return NeoFrag()->db->select('id', 'CONCAT_WS(" ", "Fichier", name)')->from('nf_file')->where('path LIKE', 'upload/files/%')->order_by('name')->get();
				},
				'check' => function($file_id){
					if (($name = NeoFrag()->db->select('name')->from('nf_file')->where('id', (int)$file_id)->where('path LIKE', 'upload/files/%')->row()) !== [])
					{
						return 'Fichier '.$name;
					}
				},
				'init' => [
					'read_file' => [
						['visitors', TRUE]
					]
				],
				'access' => [
					[
						'title'  => 'Fichiers',
						'icon'   => 'far fa-file',
						'access' => [
							'read_file' => [
								'title' => 'Lecture',
								'icon'  => 'far fa-eye'
							]
						]
					]
				]
			]
		];
	}
}
