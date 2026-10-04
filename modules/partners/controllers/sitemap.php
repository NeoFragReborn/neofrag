<?php
declare(strict_types=1);
/**
 * Le plan du site (carrefour `sitemap`) : la page des partenaires. Ils n'ont pas de page à eux.
 *
 * Appelé par Settings\Controllers\Ajax::sitemap() dans la langue du plan.
 */

namespace NF\Modules\Partners\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Sitemap extends Controller_Module
{
	/** @return list<array{adresse: string, date?: int|string|null}> */
	public function sitemap(): array
	{
		// Une rubrique vide n'est pas annoncée aux moteurs : sa page ne dirait que « rien pour l'instant ».
		if (!(int) $this->db->select('COUNT(*)')->from('nf_partners')->row())
		{
			return [];
		}

		return [['adresse' => 'partners']];
	}
}
