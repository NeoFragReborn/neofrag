<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Widgets\Recruits\Controllers;

use NF\NeoFrag\Loadables\Controller;

class Checker extends Controller
{
	public function recruit($settings = [])
	{
		// Reglages absents : un widget peut etre pose sans passer par son formulaire (install()
		// d'un theme, ajout en Live Editor, disposition ancienne). Cf. tools/check-widget-reglages.php.
		$settings = (array) $settings + ['recruit_id' => ''];

		if (in_array($settings['recruit_id'], array_map(function($a){
			return $a['recruit_id'];
		}, $this->model()->get_recruits())))
		{
			return [
				'recruit_id' => $settings['recruit_id']
			];
		}
	}
}
