<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Widgets\Recruits;

use NF\NeoFrag\Addons\Widget;

class Recruits extends Widget
{
	protected function __info()
	{
		return [
			'title'       => $this->lang('Recrutement'),
			'description' => $this->lang('Les dernières offres de recrutement, avec leur équipe et les postes restant à pourvoir, ou une offre en détail : rôle proposé, places libres, date limite.'),
			'icon'        => 'fas fa-bullhorn',
			'link'        => 'https://neofrag-reborn.xyz',
			'author'      => 'Michaël BILCOT & Jérémy VALENTIN <contact@neofrag.com>',
			'license'     => 'LGPLv3 <https://www.gnu.org/licenses/lgpl-3.0.html>',
			// Decouplage du paquet : cf. tools/check-addon-declarations.php.
			'core'        => FALSE,
			'presets'     => ['gaming'],
			'requires'    => ['recruits', 'teams'],
			'version'     => '1.0',
			'depends'     => [
				'neofrag' => '0.2.0'
			],
			'types'       => [
				'index'   => $this->lang('Dernières annonces'),
				'recruit' => $this->lang('Une annonce en détail')
			]
		];
	}
}
