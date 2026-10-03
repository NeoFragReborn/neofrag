<?php
declare(strict_types=1);
/**
 * Le plan du site (carrefour `sitemap`) : la page d'inscription à la newsletter.
 *
 * Appelé par Settings\Controllers\Ajax::sitemap() dans la langue du plan.
 */

namespace NF\Modules\Newsletter\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Sitemap extends Controller_Module
{
	/** @return list<array{adresse: string, date?: int|string|null}> */
	public function sitemap(): array
	{
		return [['adresse' => 'newsletter']];
	}
}
