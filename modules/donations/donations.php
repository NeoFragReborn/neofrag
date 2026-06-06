<?php
/**
 * https://neofr.ag
 */

namespace NF\Modules\Donations;

use NF\NeoFrag\Addons\Module;

class Donations extends Module
{
	protected function __info()
	{
		return [
			'title'       => $this->lang('Dons'),
			'description' => $this->lang('Système de campagnes de dons avec objectif, barre de progression, liste de donateurs et bouton PayPal.'),
			'icon'        => 'fas fa-hand-holding-heart',
			'author'      => 'NeoFrag',
			'license'     => 'LGPLv3',
			'admin'       => TRUE,
			'version'     => '1.0',
			'depends'     => ['neofrag' => '0.2.0'],
			'routes'      => [
				''                              => 'index',
				'{url_title}'                   => '_campaign',
				'admin'                         => 'index',
				'admin/edit/{id}'               => '_edit',
				'admin/new'                     => '_new',
				'admin/delete/{id}'             => '_delete',
				'admin/{id}/donations'          => '_donations',
				'admin/{id}/donation/add'       => '_donation_add',
				'admin/donation/edit/{id}'      => '_donation_edit',
				'admin/donation/delete/{id}'    => '_donation_delete'
			]
		];
	}

	public function permissions()
	{
		return [
			'default' => [
				'access' => [[
					'title'  => 'Campagnes',
					'icon'   => 'fas fa-bullseye',
					'access' => [
						'manage_campaigns' => ['title' => $this->lang('Gérer les campagnes'), 'admin' => TRUE],
						'manage_donations' => ['title' => $this->lang('Gérer les dons'),     'admin' => TRUE]
					]
				]]
			]
		];
	}
}
