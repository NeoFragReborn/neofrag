<?php
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\Admin;

use NF\NeoFrag\Addons\Module;

class Admin extends Module
{
	protected function __info()
	{
		return [
			'title'       => 'Tableau de bord',
			'description' => 'Tableau de bord et panneau d\'administration central.',
			'icon'        => 'fas fa-tachometer-alt',
			'link'        => 'https://neofr.ag',
			'author'      => 'Michaël BILCOT & Jérémy VALENTIN <contact@neofrag.com>',
			'license'     => 'LGPLv3 <https://neofr.ag/license>',
			'version'     => '1.0',
			'admin'       => FALSE
		];
	}

	public function is_authorized()
	{
		return $this->access->effective_admin();
	}
}
