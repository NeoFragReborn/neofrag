<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 */

namespace NF\Widgets\Newsletter;

use NF\NeoFrag\Addons\Widget;

class Newsletter extends Widget
{
	protected function __info()
	{
		return [
			'title'       => $this->lang('Newsletter'),
			'description' => $this->lang('Invite à s\'abonner à la newsletter depuis n\'importe quelle page et affiche le nombre d\'abonnés ; l\'inscription se valide par un e-mail de confirmation.'),
			'icon'        => 'far fa-envelope',
			'author'      => 'NeoFrag Reborn',
			'license'     => 'LGPLv3 <https://www.gnu.org/licenses/lgpl-3.0.html>',
			// Decouplage du paquet : cf. tools/check-addon-declarations.php.
			'core'        => FALSE,
			'presets'     => ['association'],
			'requires'    => ['newsletter'],
			'version'     => '1.0',
			'link'        => 'https://neofrag-reborn.xyz',
			'depends'     => ['neofrag' => '0.2.0'],
			'types'       => ['signup' => $this->lang('Inscription newsletter')]
		];
	}
}
