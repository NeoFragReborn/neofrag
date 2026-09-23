<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\Pages\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module_Checker;

class Checker extends Module_Checker
{
	/**
	 * Ce checker est le ROUTEUR DE REPLI du produit.
	 *
	 * Toute adresse qui n'appartient à aucun module aboutit ici, et n'y trouve pas de page : c'est
	 * un 404 ordinaire, pas une anomalie. Sans cette déclaration, chaque visiteur qui se trompe
	 * d'adresse et chaque robot qui sonde écrivait une ligne d'erreur dans le journal de
	 * production. Le diagnostic reste rendu à l'écran en mode débogage.
	 */
	public function refus_ordinaire(): bool
	{
		return TRUE;
	}

	public function _index($name)
	{
		$modele = $this->model('pages');

		if ($this->url->segments[0] != 'pages' && $modele instanceof \NF\Modules\Pages\Models\Pages && ($content = $modele->page_publique((string) $name)))
		{
			if ($this->access('pages', 'access_page', $content['page_id']))
			{
				return $content;
			}

			return $this->error->unauthorized();
		}
	}
}
