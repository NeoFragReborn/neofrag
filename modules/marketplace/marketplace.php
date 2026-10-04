<?php
declare(strict_types=1);
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
			'description' => $this->lang('Catalogue public des modules, widgets et thèmes téléchargeables.'),
			'icon'        => 'fas fa-store',
			'link'        => 'https://neofrag-reborn.xyz',
			'author'      => 'NeoFrag Reborn',
			'license'     => 'LGPLv3 <https://www.gnu.org/licenses/lgpl-3.0.html>',
			// Decouplage du paquet : cf. tools/check-addon-declarations.php.
			'core'        => TRUE,
			'presets'     => [],
			'requires'    => [],
			'admin'       => FALSE,
			'version'     => '1.0',
			'depends'     => ['neofrag' => '0.2.0'],
			'routes'      => [
				'' => 'index',
			]
		];
	}
}
