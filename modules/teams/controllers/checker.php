<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\Teams\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module_Checker;

class Checker extends Module_Checker
{
	public function _team($team_id, $name)
	{
		// L'adresse porte le nom court de l'équipe, refait quand son titre change : vérifiée avec le VRAI, une ancienne
		// adresse mène à la nouvelle au lieu de répondre 404 (m06).
		$vrai = nf_titre_lu($this->db->select('name')->from('nf_teams')->where('team_id', (int) $team_id)->row());

		if ($vrai !== '' && ($team = $this->model()->check_team($team_id, $vrai)))
		{
			nf_bon_titre((string) $name, $vrai, 'teams/'.(int) $team_id);

			return $team;
		}
	}
}
