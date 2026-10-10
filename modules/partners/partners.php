<?php
declare(strict_types=1);
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
			'description' => $this->lang('Page des partenaires et sponsors : logo clair ou foncé, site, réseaux sociaux, code promo et présentation. Pour un club, une association ou une équipe.'),
			'icon'        => 'far fa-handshake',
			'link'        => 'https://neofrag-reborn.xyz',
			'author'      => 'Michaël BILCOT & Jérémy VALENTIN <contact@neofrag.com>',
			'license'     => 'LGPLv3 <https://www.gnu.org/licenses/lgpl-3.0.html>',
			// Decouplage du paquet : cf. tools/check-addon-declarations.php.
			'core'        => FALSE,
			'presets'     => ['gaming'],
			'requires'    => [],
			'admin'       => TRUE,
			'version'     => '1.0',
			'depends'     => [
				'neofrag' => '0.2.0'
			],
			'routes'      => [
				// La visite d'un partenaire : le checker `_partner` compte le clic, puis renvoie vers
				// son site. Retirée le 2026-06-01 (d0e8b1fa) comme « déclarée non implémentée » — à
				// tort : il n'y a pas de méthode de contrôleur parce que le checker redirige lui-même.
				// Le widget pointait toujours ici : ses logos menaient à un 404, et la colonne
				// « Visites » de l'administration ne bougeait plus.
				'{id}/{url_title}'               => '_partner',

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
