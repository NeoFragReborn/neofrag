<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Widgets\Partners;

use NF\NeoFrag\Addons\Widget;

class Partners extends Widget
{
	protected function __info()
	{
		return [
			'title'       => $this->lang('Partenaires'),
			'description' => $this->lang('Les logos des partenaires et sponsors, en bandeau défilant ou en colonne, version claire ou foncée selon le fond. Pour un club, une association ou une équipe.'),
			'icon'        => 'far fa-handshake',
			'link'        => 'https://neofr.ag',
			'author'      => 'Michaël BILCOT & Jérémy VALENTIN <contact@neofrag.com>',
			'license'     => 'LGPLv3 <https://www.gnu.org/licenses/lgpl-3.0.html>',
			// Decouplage du paquet : cf. tools/check-addon-declarations.php.
			'core'        => FALSE,
			'presets'     => ['gaming'],
			'requires'    => [],
			'version'     => '1.0',
			'depends'     => [
				'neofrag' => '0.2.0'
			],
			'types'       => [
				'index'  => 'Affichage horizontal en slider',
				'column' => 'Affichage simple en colonne'
			]
		];
	}
}
