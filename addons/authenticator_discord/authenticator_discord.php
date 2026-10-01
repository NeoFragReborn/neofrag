<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Addons\Authenticator_Discord;

use NF\NeoFrag\Addons\Authenticator;

class Authenticator_Discord extends Authenticator
{
	protected function __info()
	{
		return [
			'title'       => 'Discord',
			'description' => $this->lang('Connexion par un compte Discord. Demande une application déclarée chez Discord ; portée demandée : identify.'),
			'icon'        => 'fab fa-discord',
			'color'       => '#5865F2',
			'help'        => 'https://discordapp.com/developers/applications/me#top',
			'version'     => '1.0',
			'depends'     => [
				'addon/authenticator' => '1.0'
			]
		];
	}

	public function config()
	{
		return array_merge(parent::config(), [
			'scope' => ['identify']
		]);
	}

	public function data(&$params = [])
	{
		if (!empty($_GET['code']) && !empty($_GET['state']))
		{
			$params = $_GET;

			return function($data){
				return [
					'id'       => $data->id,
					'username' => $data->username,
					// Un membre Discord sans avatar n'a pas d'empreinte : l'adresse construite quand même
					// (`…/<id>/.png`) donnait une image cassée. Sans avatar, on n'en enregistre aucun.
					'avatar'   => !empty($data->avatar) ? 'https://cdn.discordapp.com/avatars/'.$data->id.'/'.$data->avatar.'.png?size=512' : NULL
				];
			};
		}
	}
}
