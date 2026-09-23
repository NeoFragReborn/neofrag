<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Jérémy VALENTIN <jeremy.valentin@neofr.ag>
 */

namespace NF\Widgets\Socials\Controllers;

use NF\NeoFrag\Loadables\Controller;

class Checker extends Controller
{
	public function index($settings = [])
	{
		// Reglages absents : un widget peut etre pose sans passer par son formulaire (install()
		// d'un theme, ajout en Live Editor, disposition ancienne). Cf. tools/check-widget-reglages.php.
		$settings = (array) $settings + ['content_display' => '', 'display_panel' => '', 'icon_size' => '', 'margin_bottom' => '', 'margin_left' => '', 'margin_right' => '', 'margin_top' => '', 'padding_bottom' => '', 'padding_left' => '', 'padding_right' => '', 'padding_top' => '', 'social_display' => '', 'social_style' => ''];

		return [
			'display_panel'   => in_array($settings['display_panel'], ['oui', 'non']) ? $settings['display_panel'] : 'non',
			'social_display'  => in_array($settings['social_display'], ['col-12', 'col-6', 'col-4', 'col-3', 'col-2', 'col-1', 'col', 'ul-inline', 'ul']) ? $settings['social_display'] : 'col-12',
			'social_style'    => in_array($settings['social_style'], ['btn btn-social btn-sm', 'btn btn-social', 'btn btn-social btn-lg', 'btn btn-link']) ? $settings['social_style'] : 'btn btn-social',
			'content_display' => in_array($settings['content_display'], ['all', 'icon', 'legend']) ? $settings['content_display'] : 'all',
			'icon_size'       => in_array($settings['icon_size'], ['fa-1x', 'fa-2x', 'fa-3x', 'fa-4x']) ? $settings['icon_size'] : 'fa-1x',
			'padding_top'     => intval($settings['padding_top']),
			'padding_right'   => intval($settings['padding_right']),
			'padding_bottom'  => intval($settings['padding_bottom']),
			'padding_left'    => intval($settings['padding_left']),
			'margin_top'      => intval($settings['margin_top']),
			'margin_right'    => intval($settings['margin_right']),
			'margin_bottom'   => intval($settings['margin_bottom']),
			'margin_left'     => intval($settings['margin_left'])
		];
	}
}
