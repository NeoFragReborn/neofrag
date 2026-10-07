<?php
declare(strict_types=1);
/**
 * Le plan du site (carrefour `sitemap`) : la page des recrutements et chaque offre ouverte. Une
 * offre close garde sa page, mais n'a plus rien à proposer à qui la trouverait par un moteur. Monolingue.
 *
 * Appelé par Settings\Controllers\Ajax::sitemap() dans la langue du plan.
 */

namespace NF\Modules\Recruits\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Sitemap extends Controller_Module
{
	/** @return list<array{adresse: string, date?: int|string|null}> */
	public function sitemap(): array
	{
		$adresses = [['adresse' => 'recruits']];

		foreach ($this->db->select('recruit_id', 'title', 'date')->from('nf_recruits')->where('closed', '0')->order_by('date DESC')->get() as $offre)
		{
			$adresses[] = ['adresse' => 'recruits/'.$offre['recruit_id'].'/'.url_title($offre['title']), 'date' => $offre['date'], 'sans_langue' => TRUE];
		}

		// Une rubrique vide n'est pas annoncée aux moteurs : sa page ne dirait que « rien pour l'instant ».
		return count($adresses) > 1 ? $adresses : [];
	}
}
