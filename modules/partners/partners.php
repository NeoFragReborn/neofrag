<?php
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\Partners;

use NF\NeoFrag\Addons\Module;

class Partners extends Module
{
	protected function __info()
	{
		return [
			'title'       => $this->lang('Partenaires'),
			'description' => $this->lang('Partenaires et sponsors — module gaming.'),
			'icon'        => 'far fa-handshake',
			'link'        => 'https://neofr.ag',
			'author'      => 'Michaël BILCOT & Jérémy VALENTIN <contact@neofrag.com>',
			'license'     => 'LGPLv3 <https://neofr.ag/license>',
			'admin'       => TRUE,
			'version'     => '1.0',
			'depends'     => [
				'neofrag' => '0.2.0'
			],
			'routes'      => [

				//Admin
				'admin/{id}/{url_title*}'        => '_edit',
				'admin/delete/{id}/{url_title*}' => '_delete'
			],
			'settings'    => function(){
				return $this->form2()
							->rule($this->form_radio('partners_logo_display')
										->title($this->lang('Logo'))
										->info($this->lang('Utilisez les logos clairs s\'ils sont affichés sur un fond foncé'))
										->value($this->config->partners_logo_display)
										->data([
											'logo_dark'  => $this->lang('Foncé'),
											'logo_light' => $this->lang('Clair')
										])
							)
							->success(function($data){
								$this->config('partners_logo_display', $data['partners_logo_display']);
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
						'title'  => $this->lang('Partenaires'),
						'icon'   => 'far fa-star',
						'access' => [
							'add_partners' => [
								'title' => $this->lang('Ajouter'),
								'icon'  => 'fas fa-plus',
								'admin' => TRUE
							],
							'modify_partners' => [
								'title' => $this->lang('Modifier'),
								'icon'  => 'fas fa-edit',
								'admin' => TRUE
							],
							'delete_partners' => [
								'title' => $this->lang('Supprimer'),
								'icon'  => 'far fa-trash-alt',
								'admin' => TRUE
							]
						]
					]
				]
			]
		];
	}
}
