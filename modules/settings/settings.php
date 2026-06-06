<?php
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
			'version'     => '1.0',
			'admin'       => FALSE
		];
	}
}
