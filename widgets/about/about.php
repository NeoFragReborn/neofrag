<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Widgets\About;

use NF\NeoFrag\Addons\Widget;

class About extends Widget
{
	protected function __info()
	{
		return [
			'title'       => $this->lang('À propos'),
			'description' => $this->lang('Présente la structure saisie dans « Notre structure » : nom, type, date de création, logo et biographie, chacun affiché ou masqué, aligné et coloré au choix.'),
			'icon'        => 'fas fa-address-card',
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
			]
		];
	}
}
