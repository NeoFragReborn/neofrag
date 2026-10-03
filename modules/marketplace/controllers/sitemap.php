<?php
declare(strict_types=1);
/**
 * Le plan du site (carrefour `sitemap`) : la page publique du catalogue des modules, thèmes et
 * widgets — sur le site officiel, celle que cherche quelqu'un qui veut savoir ce que fait le produit.
 *
 * Appelé par Settings\Controllers\Ajax::sitemap() dans la langue du plan.
 */

namespace NF\Modules\Marketplace\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Sitemap extends Controller_Module
{
	/** @return list<array{adresse: string, date?: int|string|null}> */
	public function sitemap(): array
	{
		return [['adresse' => 'marketplace']];
	}
}
