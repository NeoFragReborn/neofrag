<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\Settings\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module_Checker;

class Ajax_Checker extends Module_Checker
{
	/*
	 * Les fichiers texte de la racine déclarent leur extension AVANT de décider : un fichier absent est
	 * alors un 404 ordinaire. Déclarée seulement quand le fichier existait, l'extension faisait du refus
	 * une « extension d'URL refusée », écrite au journal de production à chaque robot qui sonde un
	 * humans.txt vide ou une clé IndexNow inconnue (relevé à la 1.2.22, 2026-10-03).
	 */
	public function humans()
	{
		$this->extension('txt');

		if ($this->url->request == 'humans.txt' && $this->config->nf_humans_txt)
		{
			return [];
		}
	}

	public function robots()
	{
		$this->extension('txt');

		if ($this->url->request == 'robots.txt' && $this->config->nf_robots_txt)
		{
			return [];
		}
	}

	public function favicon()
	{
		// Les navigateurs et certains robots demandent /favicon.ico à la racine quoi qu'annonce le gabarit.
		if ($this->url->request == 'favicon.ico')
		{
			$this->extension('ico');
			return [];
		}
	}

	public function manifest()
	{
		if ($this->url->request == 'manifest.webmanifest')
		{
			$this->extension('webmanifest');
			return [];
		}
	}

	public function service_worker()
	{
		// Uniquement à la racine : un worker servi depuis un sous-dossier n'aurait pas la portée
		// du site, et l'adresse n'aurait aucune raison d'exister ailleurs.
		if ($this->url->request == 'service-worker.js')
		{
			$this->extension('js');
			return [];
		}
	}

	public function sitemap()
	{
		if ($this->url->request == 'sitemap.xml')
		{
			$this->extension('xml');
			return [];
		}
	}

	public function indexnow()
	{
		// La clé IndexNow : celle du site seulement, à la racine, et quand IndexNow est allumé.
		$this->extension('txt');

		if (nf_indexnow_actif() && $this->url->request == nf_indexnow_cle().'.txt')
		{
			return [];
		}
	}
}
