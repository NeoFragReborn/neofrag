<?php
declare(strict_types=1);
/**
 * Le plan du site (carrefour `sitemap`) : le calendrier et ses événements publiés, passés
 * compris — chacun garde sa page. Le module n'a ni droit de lecture ni traduction.
 *
 * Appelé par Settings\Controllers\Ajax::sitemap() dans la langue du plan.
 */

namespace NF\Modules\Calendar\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Sitemap extends Controller_Module
{
	/** @return list<array{adresse: string, date?: int|string|null}> */
	public function sitemap(): array
	{
		$adresses = [['adresse' => 'calendar']];

		foreach ($this->db->select('id', 'title', 'created_at')->from('nf_calendar_events')->where('published', '1')->order_by('start_at DESC')->get() as $evenement)
		{
			$adresses[] = ['adresse' => 'calendar/'.$evenement['id'].'/'.url_title($evenement['title']), 'date' => $evenement['created_at']];
		}

		// Une rubrique vide n'est pas annoncée aux moteurs : sa page ne dirait que « rien pour l'instant ».
		return count($adresses) > 1 ? $adresses : [];
	}
}
