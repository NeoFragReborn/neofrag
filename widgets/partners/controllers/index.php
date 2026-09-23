<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Widgets\Partners\Controllers;

use NF\NeoFrag\Loadables\Controllers\Widget as Controller_Widget;

class Index extends Controller_Widget
{
	public function index($settings = [])
	{
		$this->js('partners');

		$partners = $this->module('partners')->model()->get_partners();

		if (!empty($partners))
		{
			$total_partners = count($partners);

			// Un widget doit se rendre même avec des réglages qu'il n'a pas écrits lui-même : posé
			// par l'`install()` d'un thème, ajouté en Live Editor avant d'ouvrir son formulaire, ou
			// restauré depuis une disposition ancienne. Ici les quatre clés étaient lues sans repli,
			// et `display_number` absent donnait `ceil($total / NULL)` — une **division par zéro**,
			// constatée le 2026-09-16 dans le journal du site de démonstration. `display_number` est
			// un diviseur : il ne peut jamais valoir 0.
			$par_slide = max(1, (int) ($settings['display_number'] ?? 0));

			return $this->panel()->body($this->view('index', [
				'partners'       => $partners,
				'total_partners' => $total_partners,
				'total_slides'   => (int) ceil($total_partners / $par_slide),
				'display_style'  => $settings['display_style'] ?? 'light',
				'display_number' => $par_slide,
				'display_height' => (int) ($settings['display_height'] ?? 0) ?: 140,
				'id'             => $settings['id'] ?? 0
			]), FALSE);
		}
	}

	public function column($settings = [])
	{
		$this	->css('partners')
				->js('partners');

		$partners = $this->module('partners')->model()->get_partners();

		if (!empty($partners))
		{
			return $this->panel()
						->heading('Partenaires')
						->body($this->view('column', [
							'partners'      => $partners,
							'display_style' => $settings['display_style'] ?? 'light'
						]));
		}
	}
}
