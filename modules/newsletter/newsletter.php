<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * Module Newsletter — inscription publique + envoi via email transactionnel.
 */

namespace NF\Modules\Newsletter;

use NF\NeoFrag\Addons\Module;

class Newsletter extends Module
{
	protected function __info()
	{
		return [
			'title'       => $this->lang('Newsletter'),
			'description' => $this->lang('Inscription à la newsletter (double opt-in) et envoi de campagnes.'),
			'icon'        => 'far fa-envelope',
			'link'        => 'https://neofr.ag',
			'author'      => 'NeoFrag Reborn',
			'license'     => 'LGPLv3 <https://neofr.ag/license>',
			// Decouplage du paquet : cf. tools/check-addon-declarations.php.
			'core'        => FALSE,
			'presets'     => [],
			'requires'    => [],
			'admin'       => TRUE,
			'version'     => '1.0',
			'depends'     => [
				'neofrag' => '0.2.0'
			],
			'routes'      => [
				''                              => 'index',
				'confirm/{url_title}'           => '_confirm',
				'unsubscribe/{url_title}'       => '_unsubscribe',
				'track/{url_title}'             => '_track',
				'admin{pages}'                  => 'index',
				'admin/campaigns'                 => '_campaigns',
				'admin/campaigns/send/{id}'       => '_campaign_send',
				'admin/campaigns/cancel/{id}'     => '_campaign_cancel',
				'admin/compose'                   => '_compose',
				'admin/templates'                 => '_templates',
				'admin/templates/add'             => '_template_add',
				'admin/templates/edit/{id}'       => '_template_edit',
				'admin/templates/delete/{id}'     => '_template_delete',
				'admin/subscribers'               => '_subscribers',
				'admin/subscribers/delete/{id}'   => '_subscriber_delete'
			]
		];
	}

	public function permissions()
	{
		return [
			'default' => [
				'access' => [
					[
						'title'  => $this->lang('Newsletter'),
						'icon'   => 'far fa-envelope',
						'access' => [
							'manage_subscribers' => ['title' => $this->lang('Gérer abonnés'), 'icon' => 'fas fa-users', 'admin' => TRUE],
							'send_campaigns'     => ['title' => $this->lang('Envoyer campagnes'), 'icon' => 'fas fa-paper-plane', 'admin' => TRUE]
						]
					]
				]
			]
		];
	}
}
