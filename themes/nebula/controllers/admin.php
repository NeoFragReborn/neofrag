<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * Nebula — la page de configuration du thème : ses couleurs (accent, fond, texte), refusées quand elles ne se lisent
 * pas, avec l'aperçu sur une vraie page du site (d10, 2026-10-15). Le thème n'en avait aucune : sa palette était
 * écrite dans sa feuille.
 */

namespace NF\Themes\Nebula\Controllers;

use NF\NeoFrag\Loadables\Controller;

class Admin extends Controller
{
	/** Les couleurs d'origine du thème, celles de sa feuille. */
	const COULEURS = ['theme_color' => '#2dd4bf', 'background_color' => '#070a10', 'text_color' => '#e7eef6'];

	public function index()
	{
		return nf_reglage_couleurs($this, $this->__caller, self::COULEURS);
	}
}
