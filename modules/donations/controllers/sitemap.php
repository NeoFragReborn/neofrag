<?php
declare(strict_types=1);
/**
 * Le plan du site (carrefour `sitemap`) : les campagnes de dons en cours, et la page qui les
 * réunit — sauf quand il n'y en a qu'une : la page redirige alors vers elle, et une redirection n'a
 * rien à faire dans un plan. Monolingue.
 *
 * Appelé par Settings\Controllers\Ajax::sitemap() dans la langue du plan.
 */

namespace NF\Modules\Donations\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Sitemap extends Controller_Module
{
	/** @return list<array{adresse: string, date?: int|string|null}> */
	public function sitemap(): array
	{
		$adresses = [];

		foreach ($this->db->select('name', 'updated_at')->from('nf_donations_campaigns')->where('status', 'active')->get() as $campagne)
		{
			$adresses[] = ['adresse' => 'donations/'.$campagne['name'], 'date' => $campagne['updated_at'], 'sans_langue' => TRUE];
		}

		// Une campagne seule : la page des dons y redirige. Aucune : elle ne dirait que « rien pour
		// l'instant », une page vide (« soft 404 ») pour Google — la vitrine en annonçait six (2026-10-07).
		if (count($adresses) > 1)
		{
			array_unshift($adresses, ['adresse' => 'donations']);
		}

		return $adresses;
	}
}
