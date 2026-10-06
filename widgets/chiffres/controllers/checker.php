<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 */

namespace NF\Widgets\Chiffres\Controllers;

use NF\NeoFrag\Loadables\Controller;

class Checker extends Controller
{
	public function index($settings = [])
	{
		// Les nombres connus, dans leur ordre, quatre au plus ; aucun coché : ceux par défaut.
		$nombres = array_slice(array_values(array_intersect(Index::NOMBRES, (array) ($settings['nombres'] ?? []))), 0, 4);

		return [
			'nombres'       => $nombres ?: Index::PAR_DEFAUT,
			'display_panel' => in_array($settings['display_panel'] ?? 'oui', ['oui', 'non'], TRUE) ? ($settings['display_panel'] ?? 'oui') : 'oui'
		];
	}
}
