<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Addons\Authenticator;

use NF\NeoFrag\Loadables\Addon;

class Authenticator extends Addon
{
	protected function __info()
	{
		return [
			'title'       => $this->lang('Authentificateur'),
			'description' => $this->lang('Socle commun des connexions externes. Il ne se connecte à rien tout seul : les connecteurs Discord, GitHub et Google s’appuient dessus.'),
			'icon'        => 'fas fa-sign-in-alt',
			'version'     => '1.0',
			'license'     => 'LGPLv3 <https://www.gnu.org/licenses/lgpl-3.0.html>',
			'author'      => 'Michaël BILCOT & Jérémy VALENTIN <contact@neofrag.com>',
			'link'        => 'https://neofr.ag',
			'depends'     => [
				'neofrag' => '0.2.0'
			]
		];
	}
}
