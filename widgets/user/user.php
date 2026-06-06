<?php
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Widgets\User;

use NF\NeoFrag\Addons\Widget;

class User extends Widget
{
	protected function __info()
	{
		return [
			'title'       => $this->lang('Espace membre'),
			'description' => $this->lang('Espace membre : connexion, lien vers le profil ou inscription.'),
			'link'        => 'https://neofr.ag',
			'author'      => 'Michaël BILCOT & Jérémy VALENTIN <contact@neofrag.com>',
			'license'     => 'LGPLv3 <https://neofr.ag/license>',
			'version'     => '1.0',
			'types'       => [
				'index'          => $this->lang('Espace membre'),
				'index_mini'     => $this->lang('Espace membre (mini)'),
				'messages_inbox' => $this->lang('Messagerie')
			]
		];
	}
}
