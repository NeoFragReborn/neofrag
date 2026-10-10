<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\Search;

use NF\NeoFrag\Addons\Module;

class Search extends Module
{
	protected function __info()
	{
		return [
			'title'       => $this->lang('Rechercher'),
			'description' => $this->lang('Moteur de recherche transverse du site.'),
			'icon'        => 'fas fa-search',
			'link'        => 'https://neofrag-reborn.xyz',
			'author'      => 'Michaël BILCOT & Jérémy VALENTIN <contact@neofrag.com>',
			'license'     => 'LGPLv3 <https://www.gnu.org/licenses/lgpl-3.0.html>',
			// Decouplage du paquet : cf. tools/check-addon-declarations.php.
			'core'        => TRUE,
			'presets'     => [],
			'requires'    => [],
			'version'     => '1.0',
			'routes'      => [
				'(?:(.+?){pages})?' => 'index'
			]
		];
	}
}
