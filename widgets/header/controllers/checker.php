<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Widgets\Header\Controllers;

use NF\NeoFrag\Loadables\Controller;

class Checker extends Controller
{
	public function index($settings = [])
	{
		// Reglages absents : un widget peut etre pose sans passer par son formulaire (install()
		// d'un theme, ajout en Live Editor, disposition ancienne). Cf. tools/check-widget-reglages.php.
		$settings = (array) $settings + ['align' => '', 'color_description' => '', 'color_title' => '', 'description' => '', 'display' => '', 'title' => ''];

		return [
			'display'           => in_array($settings['display'], ['logo', 'title']) ? $settings['display'] : 'title',
			'align'             => in_array($settings['align'], ['text-start', 'text-end']) ? $settings['align'] : 'text-center',
			'title'             => utf8_htmlentities($settings['title']),
			'description'       => utf8_htmlentities($settings['description']),
			'color_title'       => preg_match($regex = '/^#([a-f0-9]{3}){1,2}$/i', $settings['color_title'])       ? $settings['color_title']       : '',
			'color_description' => preg_match($regex,                              $settings['color_description']) ? $settings['color_description'] : ''
		];
	}
}
