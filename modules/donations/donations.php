<?php
declare(strict_types=1);
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
			'author'      => 'HiddenBlob (Donation v3), d’après majiid — portage NeoFrag Reborn',
			'license'     => 'LGPLv3 <https://www.gnu.org/licenses/lgpl-3.0.html>',
			// Decouplage du paquet : cf. tools/check-addon-declarations.php.
			'core'        => FALSE,
			'presets'     => ['association'],
			'requires'    => [],
			'admin'       => TRUE,
			'version'     => '1.0',
			'link'        => 'https://neofrag-reborn.xyz',
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
					'title'  => $this->lang('Campagnes'),
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
