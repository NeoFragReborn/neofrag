<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Widgets\Members;

use NF\NeoFrag\Addons\Widget;

class Members extends Widget
{
	protected function __info()
	{
		return [
			'title'       => $this->lang('Membres'),
			'description' => $this->lang('Derniers membres inscrits ou membres connectés.'),
			'icon'        => 'fas fa-users',
			'link'        => 'https://neofr.ag',
			'author'      => 'Michaël BILCOT & Jérémy VALENTIN <contact@neofrag.com>',
			'license'     => 'LGPLv3 <https://www.gnu.org/licenses/lgpl-3.0.html>',
			// Decouplage du paquet : cf. tools/check-addon-declarations.php.
			'core'        => TRUE,
			'presets'     => [],
			'requires'    => [],
			'version'     => '1.0',
			'depends'     => [
				'neofrag' => '0.2.0'
			],
			'types'       => [
				'index'       => $this->lang('Derniers membres'),
				'online'      => $this->lang('Qui est en ligne ?'),
				'online_mini' => $this->lang('Qui est en ligne ? (mini)')
			]
		];
	}
}
