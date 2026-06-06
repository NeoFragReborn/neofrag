<?php
/**
 * https://neofr.ag
 * Module Marketplace — showcase public des addons téléchargeables (à la
 * addons.neofr.ag). Lit marketplace/catalog.json (généré par tools/package-addons.php)
 * et affiche modules / widgets / thèmes / connecteurs avec fiche + téléchargement .zip.
 * Install ensuite via « Ajouter » (ZIP) de l'admin.
 */

namespace NF\Modules\Marketplace;

use NF\NeoFrag\Addons\Module;

class Marketplace extends Module
{
	protected function __info()
	{
		return [
			'title'       => $this->lang('Marketplace'),
			'description' => 'Catalogue public des modules, widgets et thèmes téléchargeables (showcase).',
			'icon'        => 'fas fa-store',
			'link'        => 'https://neofr.ag',
			'author'      => 'NeoFrag Reborn',
			'license'     => 'LGPLv3 <https://neofr.ag/license>',
			'admin'       => FALSE,
			'version'     => '1.0',
			'depends'     => ['neofrag' => '0.2.0'],
			'routes'      => [
				'' => 'index',
			]
		];
	}
}
