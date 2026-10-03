<?php
declare(strict_types=1);
/**
 * Le plan du site (carrefour `sitemap`) : les pages publiées, à leur adresse publique — la
 * racine du site, `/fr/<nom>` —, parues, rédigées dans la langue du plan et lisibles par un visiteur
 * (`pages.access_page`, que l'installation n'accorde pas d'office). Le plan d'avant les annonçait
 * toutes, dans chaque langue, sans regarder qui pouvait les lire.
 *
 * Appelé par Settings\Controllers\Ajax::sitemap() dans la langue du plan.
 */

namespace NF\Modules\Pages\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Sitemap extends Controller_Module
{
	/** @return list<array{adresse: string, date?: int|string|null}> */
	public function sitemap(): array
	{
		$adresses = [];

		foreach ($this->db	->select('p.page_id', 'p.name', 'p.date')
							->from('nf_pages p')
							->join('nf_pages_lang pl', 'pl.page_id = p.page_id')
							->where('pl.lang', $this->config->lang->info()->name)
							->where('p.published', '1')
							->get() as $page)
		{
			// Une page datée de demain n'est pas encore parue ; une page sans date l'est.
			$parue = empty($page['date']) || strtotime((string) $page['date']) <= time();

			if ($parue && $this->access('pages', 'access_page', (int) $page['page_id'], 'visitors'))
			{
				$adresses[] = ['adresse' => $page['name'], 'date' => $page['date']];
			}
		}

		// La liste des pages (`/fr/pages`), quand elle en a à montrer.
		if ($adresses)
		{
			array_unshift($adresses, ['adresse' => 'pages']);
		}

		return $adresses;
	}
}
