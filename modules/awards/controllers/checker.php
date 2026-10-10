<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\Awards\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module_Checker;

class Checker extends Module_Checker
{
	public function _award($award_id, $name)
	{
		if ($award = $this->model()->check_awards($award_id, $name))
		{
			// Le titre de l'adresse n'est pas le bon : 301 vers la bonne (elle répondait 200 à n'importe lequel).
			nf_bon_titre((string) $name, (string) $award['name'], 'awards/'.(int) $award['award_id']);

			return [
				$award['award_id'],
				$award['team_id'],
				$award['date'],
				$award['location'],
				$award['name'],
				$award['platform'],
				$award['game_id'],
				$award['ranking'],
				$award['participants'],
				$award['description'],
				$award['image_id'],
				$award['team_name'],
				$award['team_title'],
				$award['game_name'],
				$award['game_title']
			];
		}
	}
}
