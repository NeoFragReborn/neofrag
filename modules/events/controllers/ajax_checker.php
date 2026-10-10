<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\Events\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module_Checker;

class Ajax_Checker extends Module_Checker
{
	public function index()
	{
		$this->extension('json');

		return [];
	}

	public function _event($event_id, $title)
	{
		if ($event = $this->model()->check_event($event_id, $title))
		{
			// Dans l'ordre de la signature d'Ajax::_event(), colonne par colonne : la ligne entière, passée telle quelle,
			// donnait la date de publication à `$type` (2026-10-10).
			return [
				$event['event_id'],
				$event['title'],
				$event['type_id'],
				$event['date'],
				$event['date_end'],
				$event['description'],
				$event['private_description'],
				$event['location'],
				$event['image_id'],
				$event['published'],
				$event['type'],
			];
		}
	}
}
