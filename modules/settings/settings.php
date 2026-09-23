<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\Settings;

use NF\NeoFrag\Addons\Module;

class Settings extends Module
{
	protected function __info()
	{
		return [
			'title'       => $this->lang('Paramètres'),
			'description' => $this->lang('Configuration générale du site : identité, maintenance, inscriptions, sociaux.'),
			'icon'        => 'fas fa-cogs',
			'link'        => 'https://neofr.ag',
			'author'      => 'Michaël BILCOT & Jérémy VALENTIN <contact@neofrag.com>',
			'license'     => 'LGPLv3 <https://neofr.ag/license>',
			// Decouplage du paquet : cf. tools/check-addon-declarations.php.
			// Infrastructure : le site ne tourne pas sans lui, l'administration ne propose donc
			// pas de l'eteindre. Reprend a l'identique l'ancien Module/Widget/Theme::$core.
			'deactivatable' => FALSE,
			'core'        => TRUE,
			'presets'     => [],
			'requires'    => [],
			'version'     => '1.0',
			'admin'       => FALSE
		];
	}
}
