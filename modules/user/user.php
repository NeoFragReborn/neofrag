<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\User;

use NF\NeoFrag\Addons\Module;

class User extends Module
{
	protected function __info()
	{
		return [
			'title'       => $this->lang('Utilisateur'),
			'description' => $this->lang('Gestion des utilisateurs : inscription, profil, sécurité, 2FA, RGPD.'),
			'icon'        => 'fas fa-user',
			'link'        => 'https://neofr.ag',
			'author'      => 'Michaël BILCOT & Jérémy VALENTIN <contact@neofrag.com>',
			'license'     => 'LGPLv3 <https://neofr.ag/license>',
			// Decouplage du paquet : cf. tools/check-addon-declarations.php.
			// Infrastructure : le site ne tourne pas sans lui, l'administration ne propose donc
			// pas de l'eteindre. Reprend a l'identique l'ancien Module/Widget/Theme::$core.
			'deactivatable' => FALSE,
			'core'        => TRUE,
			'presets'     => [],
			'requires'    => [],
			'version'     => '1.0',
			'admin'       => TRUE,
			'routes'      => [
				//Index
				'sessions{pages}'                            => 'sessions',
				'security'                                   => 'security',
				'security/setup'                             => 'security_setup',
				'security/codes'                             => 'security_codes',
				'security/disable'                           => 'security_disable',
				'security/export'                            => 'security_export',
				'security/delete'                            => 'security_delete',
				'auth{pages}'                                => '_auth',
				'auth/unlink/{id}'                           => '_auth_unlink',
				'sessions/delete/{key_id}'                   => '_session_delete',
				'{id}/{url_title}'                           => '_member',
				'ajax/{id}/{url_title}'                      => '_member',
				'ajax/lost-password/{url_title}'             => '_lost_password',

				//Admin
				'admin{pages}'                                   => 'index',
				'admin/audit-log{pages}'                         => '_audit_log',
				'admin/export/(csv|json)'                        => '_export',
				'admin/groups/add'                               => '_groups_add',
				'admin/groups/edit/(admins|members|visitors)'    => '_groups_edit',
				'admin/groups/edit/{url_title}-{id}/{url_title}' => '_groups_edit',
				'admin/groups/edit/{id}/{url_title}'             => '_groups_edit',
				'admin/groups/delete/{id}/{url_title}'           => '_groups_delete',
				'admin/ajax/groups/sort'                         => '_groups_sort',
				'admin/fields'                                   => '_fields',
				'admin/fields/add'                               => '_fields_add',
				'admin/fields/edit/{id}/{url_title}'             => '_fields_edit',
				'admin/fields/delete/{id}/{url_title}'           => '_fields_delete',
				'admin/ajax/fields/sort'                         => '_fields_sort',
				'admin/sessions{pages}'                          => '_sessions',
				'admin/sessions/delete/{url_title}'              => '_sessions_delete',
				'admin/totp-reset/{id}/{url_title}'              => '_totp_reset',
				'admin/delete/{id}/{url_title}'               => '_delete',
				'admin/{id}/{url_title}'                       => '_edit'
			]
		];
	}

	public function __init()
	{
		// Migration MP → Talks unifié : le listener legacy `mp.reply.created` a été retiré.
		// Les notifications email pour les MP user-to-user sont maintenant gérées par
		// modules/talks/talks.php (listener `talks.message.created` filtré sur type=direct/group).

		// Le fuseau horaire du membre s'applique à l'ouverture de la session (core/session.php), et
		// non plus ici : ce __init() ne s'exécute que sur les pages du module user.
	}
}
