<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Widgets\Teams;

use NF\NeoFrag\Addons\Widget;

class Teams extends Widget
{
	protected function __info()
	{
		return [
			'title'       => $this->lang('Équipes'),
			'description' => $this->lang('Les équipes du site en bannières cliquables, chacune menant à sa page : jeu, présentation, joueurs. Seules les équipes qui ont une bannière y figurent.'),
			'icon'        => 'fas fa-headset',
			'link'        => 'https://neofrag-reborn.xyz',
			'author'      => 'Michaël BILCOT & Jérémy VALENTIN <contact@neofrag.com>',
			'license'     => 'LGPLv3 <https://www.gnu.org/licenses/lgpl-3.0.html>',
			// Decouplage du paquet : cf. tools/check-addon-declarations.php.
			'core'        => FALSE,
			'presets'     => ['gaming'],
			'requires'    => ['teams'],
			'version'     => '1.0',
			'depends'     => [
				'neofrag' => '0.2.0'
			]
		];
	}
}
