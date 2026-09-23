<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Widgets\Partners\Controllers;

use NF\NeoFrag\Loadables\Controller;

class Checker extends Controller
{
	/**
	 * Le checker est le point de CONTRAT du widget : il doit rendre des réglages utilisables même
	 * quand on ne lui en donne aucun.
	 *
	 * Un widget peut parfaitement arriver sans réglages : posé par l'`install()` d'un thème, ajouté
	 * en Live Editor avant d'ouvrir son formulaire, ou restauré depuis une disposition ancienne.
	 * Les quatre clés étaient lues sans valeur de repli — quatre avertissements « Undefined array
	 * key », puis `display_number` valant 0 et `ceil($total / 0)` en **division par zéro** dans
	 * `controllers/index.php`. Constaté le 2026-09-16 dans le journal du site de démonstration,
	 * juste après avoir donné leur mise en page par défaut aux thèmes.
	 *
	 * `display_number` ne peut pas valoir 0 : c'est un diviseur.
	 */
	public function index($settings = [])
	{
		$style  = $settings['display_style'] ?? '';
		$nombre = (int) ($settings['display_number'] ?? 0);

		return [
			'display_style'  => in_array($style, ['light', 'dark'], TRUE) ? $style : 'light',
			'display_number' => max(1, $nombre),
			'display_height' => (int) ($settings['display_height'] ?? 0) ?: 140,
			'id'             => $settings['id'] ?? 0
		];
	}

	public function column($settings = [])
	{
		$style = $settings['display_style'] ?? '';

		return [
			'display_style' => in_array($style, ['light', 'dark'], TRUE) ? $style : 'light'
		];
	}
}
