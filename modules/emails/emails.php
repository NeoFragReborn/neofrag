<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * NeoFrag — Module Emails (R2.0, 2026-05-06)
 *
 * Centralise les templates emails du site dans nf_email_templates / nf_email_template_translations.
 * Admin UI : list + édition (TinyMCE) + preview + envoi de test.
 *
 * Pour envoyer : $this->email->template('user.registration', ['username' => ..., ...])->to(...)->send();
 */

namespace NF\Modules\Emails;

use NF\NeoFrag\Addons\Module;

class Emails extends Module
{
	protected function __info()
	{
		return [
			'title'       => $this->lang('Templates emails'),
			'description' => $this->lang('Gestion centralisée des templates d\'emails du site (validation compte, mentions, notifications, etc.).'),
			'icon'        => 'fas fa-envelope-open-text',
			'link'        => 'https://neofrag-reborn.xyz',
			'author'      => 'NeoFrag Reborn',
			'license'     => 'LGPLv3 <https://www.gnu.org/licenses/lgpl-3.0.html>',
			// Decouplage du paquet : cf. tools/check-addon-declarations.php.
			'core'        => TRUE,
			'presets'     => [],
			'requires'    => [],
			'version'     => '1.0',
			'admin'       => TRUE,
			'routes'      => [
				'admin{pages}'                          => 'index',
				'admin/edit/{id}/{url_title}'           => '_edit',
				'admin/preview/{id}/{url_title}'        => '_preview',
				'admin/test/{id}/{url_title}'           => '_test',
				'admin/toggle/{id}/{url_title}'       => '_toggle'
			]
		];
	}

	public function permissions()
	{
		return [
			'default' => [
				'access' => [
					[
						'title'  => $this->lang('Templates emails'),
						'icon'   => 'fas fa-envelope-open-text',
						'access' => [
							'manage_email_templates' => [
								'title' => $this->lang('Gérer les templates d\'emails'),
								'icon'  => 'fas fa-edit',
								'admin' => TRUE
							]
						]
					]
				]
			]
		];
	}
}
