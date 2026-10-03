<?php
declare(strict_types=1);
/**
 * Le plan du site (carrefour `sitemap`) : le palmarès et la page de chaque distinction.
 * Monolingue, sans droit de lecture.
 *
 * Appelé par Settings\Controllers\Ajax::sitemap() dans la langue du plan.
 */

namespace NF\Modules\Awards\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Sitemap extends Controller_Module
{
	/** @return list<array{adresse: string, date?: int|string|null}> */
	public function sitemap(): array
	{
		$adresses = [['adresse' => 'awards']];

		foreach ($this->db->select('award_id', 'name')->from('nf_awards')->order_by('date DESC')->get() as $distinction)
		{
			$adresses[] = ['adresse' => 'awards/'.$distinction['award_id'].'/'.url_title($distinction['name'])];
		}

		return $adresses;
	}
}
