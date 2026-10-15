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

		// Le nom vit dans `nf_awards_lang` depuis m31 : le lire dans `nf_awards` rendait NULL, et le
		// plan du site tombait en avertissement PHP à chaque passage (trouvé par check-liens, 2026-10-15).
		foreach ($this->db	->select('a.award_id', 'al.name')
							->from('nf_awards a')
							->join_lang('nf_awards_lang al', 'award_id', 'a.award_id', NULL, 'LEFT')
							->order_by('a.date DESC')
							->get() as $distinction)
		{
			$adresses[] = ['adresse' => 'awards/'.$distinction['award_id'].'/'.url_title($distinction['name']), 'sans_langue' => TRUE];
		}

		// Une rubrique vide n'est pas annoncée aux moteurs : sa page ne dirait que « rien pour l'instant ».
		return count($adresses) > 1 ? $adresses : [];
	}
}
