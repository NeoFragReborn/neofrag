<?php
declare(strict_types=1);
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
			'title'       => $this->lang('Tableau de bord'),
			'description' => $this->lang('Tableau de bord et panneau d\'administration central.'),
			'icon'        => 'fas fa-tachometer-alt',
			'link'        => 'https://neofr.ag',
			'author'      => 'Michaël BILCOT & Jérémy VALENTIN <contact@neofrag.com>',
			'license'     => 'LGPLv3 <https://www.gnu.org/licenses/lgpl-3.0.html>',
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

	public function is_authorized()
	{
		return $this->access->effective_admin();
	}
}
