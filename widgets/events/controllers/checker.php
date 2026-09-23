<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Widgets\Events\Controllers;

use NF\NeoFrag\Loadables\Controller;

class Checker extends Controller
{
	public function events($settings = [])
	{
		// Reglages absents : un widget peut etre pose sans passer par son formulaire (install()
		// d'un theme, ajout en Live Editor, disposition ancienne). Cf. tools/check-widget-reglages.php.
		$settings = (array) $settings + ['type_id' => ''];

		if (in_array($settings['type_id'], array_map(function($a){
			return $a['type_id'];
		}, $this->module('events')->model('types')->get_types())))
		{
			return [
				'type_id' => $settings['type_id']
			];
		}
	}

	public function event($settings = [])
	{
		// Reglages absents : un widget peut etre pose sans passer par son formulaire (install()
		// d'un theme, ajout en Live Editor, disposition ancienne). Cf. tools/check-widget-reglages.php.
		$settings = (array) $settings + ['event_id' => ''];

		if (in_array($settings['event_id'], array_map(function($a){
			return $a['event_id'];
		}, $this->model()->get_events())))
		{
			return [
				'event_id' => $settings['event_id']
			];
		}
	}
}
