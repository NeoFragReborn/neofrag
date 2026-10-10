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
			'license'     => 'LGPLv3 <https://www.gnu.org/licenses/lgpl-3.0.html>',
			'author'      => 'Michaël BILCOT & Jérémy VALENTIN <contact@neofrag.com>',
			'link'        => 'https://neofr.ag',
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
					// La bibliothèque OAuth (version 3) donne l'adresse de l'avatar toute faite, dans `pictureURL` : le
					// champ `avatar` d'avant n'existe plus, et aucun avatar Discord n'était repris. Un membre sans avatar
					// n'en a pas : on n'en enregistre aucun.
					'avatar'   => !empty($data->pictureURL) ? $data->pictureURL.'?size=512' : NULL
				];
			};
		}
	}
}
