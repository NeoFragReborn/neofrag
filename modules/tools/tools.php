<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\Tools;

use NF\NeoFrag\Addons\Module;

class Tools extends Module
{
	protected function __info()
	{
		return [
			'title'       => $this->lang('Outils'),
			'description' => $this->lang('Outils techniques d\'administration : cache, SCSS, base de données.'),
			'icon'        => 'fas fa-wrench',
			'link'        => 'https://neofr.ag',
			'author'      => 'Michaël BILCOT & Jérémy VALENTIN <contact@neofrag.com>',
			'license'     => 'LGPLv3 <https://www.gnu.org/licenses/lgpl-3.0.html>',
			// Decouplage du paquet : cf. tools/check-addon-declarations.php.
			'core'        => TRUE,
			'presets'     => [],
			'requires'    => [],
			'version'     => '1.0',
		];
	}
}
