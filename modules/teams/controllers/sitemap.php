<?php
declare(strict_types=1);
/**
 * Le plan du site (carrefour `sitemap`) : la liste des équipes et la page de celles qui sont
 * présentées dans la langue du plan — les autres y sont servies en repli, canoniques dans la leur.
 *
 * Appelé par Settings\Controllers\Ajax::sitemap().
 */

namespace NF\Modules\Teams\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Sitemap extends Controller_Module
{
	/** @return list<array{adresse: string, date?: int|string|null}> */
	public function sitemap(): array
	{
		$adresses = [['adresse' => 'teams']];

		foreach ($this->db	->select('t.team_id', 't.name')
							->from('nf_teams t')
							->join('nf_teams_lang tl', 'tl.team_id = t.team_id')
							->where('tl.lang', $this->config->lang->info()->name)
							->order_by('t.team_id')
							->get() as $equipe)
		{
			$adresses[] = ['adresse' => 'teams/'.$equipe['team_id'].'/'.url_title($equipe['name'])];
		}

		// Une rubrique vide n'est pas annoncée aux moteurs : sa page ne dirait que « rien pour l'instant ».
		return count($adresses) > 1 ? $adresses : [];
	}
}
