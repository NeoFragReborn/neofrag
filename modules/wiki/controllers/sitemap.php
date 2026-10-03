<?php
declare(strict_types=1);
/**
 * Le plan du site (carrefour `sitemap`) : l'accueil du wiki et chacune de ses pages publiées —
 * les guides de NeoFrag Reborn, sur le site officiel, et le journal des versions. Le plan d'avant n'en
 * annonçait aucune.
 *
 * Appelé par Settings\Controllers\Ajax::sitemap() dans la langue du plan : des chemins comme ceux que
 * prend url(), et seulement ce qu'un visiteur peut lire. Le wiki n'a ni droit de lecture ni traduction.
 */

namespace NF\Modules\Wiki\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Sitemap extends Controller_Module
{
	/** @return list<array{adresse: string, date?: int|string|null}> */
	public function sitemap(): array
	{
		$adresses = [];
		$derniere = NULL;

		foreach ($this->db->select('slug', 'updated_at')->from('nf_wiki_pages')->where('published', '1')->order_by('sort_order', 'id')->get() as $page)
		{
			$adresses[] = ['adresse' => 'wiki/'.$page['slug'], 'date' => $page['updated_at']];
			$derniere   = max($derniere, (string) $page['updated_at']);
		}

		array_unshift($adresses, ['adresse' => 'wiki', 'date' => $derniere]);

		return $adresses;
	}
}
