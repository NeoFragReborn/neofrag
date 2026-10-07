<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Widgets\Talks\Controllers;

use NF\NeoFrag\Loadables\Controller;

class Checker extends Controller
{
	public function index($settings = [])
	{
		// Reglages absents : un widget peut etre pose sans passer par son formulaire (install()
		// d'un theme, ajout en Live Editor, disposition ancienne). Cf. tools/check-widget-reglages.php.
		$settings = (array) $settings + ['talk_id' => ''];

		$talks = $this->db->select('talk_id')->from('nf_talks')->get();

		return [
			'talk_id' => in_array($settings['talk_id'], $talks) ? $settings['talk_id'] : current($talks)
		];
	}

	public function salon($settings = [])
	{
		$settings = (array) $settings + ['talk_id' => ''];

		// Un salon public ouvert à tous les membres ; à défaut de celui qu'on a choisi, le premier par son nom.
		$salons = $this->db	->select('talk_id')
							->from('nf_talks')
							->where('type', 'public')
							->where('audience', 'all')
							->where('deleted_at', NULL)
							->order_by('name')
							->get();

		return [
			'talk_id' => in_array($settings['talk_id'], $salons) ? $settings['talk_id'] : (current($salons) ?: '')
		];
	}
}
