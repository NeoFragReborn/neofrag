<?php
declare(strict_types=1);
/**
 * Le plan du site (carrefour `sitemap`) : l'accueil de la galerie, ses albums parus et les
 * catégories qui en ont. Seulement ce qu'un VISITEUR voit — `gallery.gallery_see`, dont la portée est
 * l'album, et que l'installation n'accorde pas d'office — et seulement les albums présentés dans la
 * langue du plan : les autres y sont servis en repli, canoniques dans la leur.
 *
 * Appelé par Settings\Controllers\Ajax::sitemap().
 */

namespace NF\Modules\Gallery\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Sitemap extends Controller_Module
{
	/** @return list<array{adresse: string, date?: int|string|null}> */
	public function sitemap(): array
	{
		$adresses   = [];
		$categories = [];

		foreach ($this->db	->select('g.gallery_id', 'g.category_id', 'g.name', 'g.date')
							->from('nf_gallery g')
							->join('nf_gallery_lang gl', 'gl.gallery_id = g.gallery_id')
							->where('gl.lang', $this->config->lang->info()->name)
							->where('g.published', '1')
							->where('g.deleted_at', NULL)
							->where('g.date <=', date('Y-m-d H:i:s'))
							->order_by('g.date DESC')
							->get() as $album)
		{
			if (!$this->access('gallery', 'gallery_see', (int) $album['gallery_id'], 'visitors'))
			{
				continue;
			}

			$adresses[] = ['adresse' => 'gallery/album/'.$album['gallery_id'].'/'.url_title($album['name']), 'date' => $album['date']];

			if ($album['category_id'])
			{
				$categories[(int) $album['category_id']] = max($categories[(int) $album['category_id']] ?? '', (string) $album['date']);
			}
		}

		if ($categories)
		{
			foreach ($this->db->select('category_id', 'name')->from('nf_gallery_categories')->where('category_id', array_keys($categories))->get() as $categorie)
			{
				$adresses[] = ['adresse' => 'gallery/'.$categorie['category_id'].'/'.url_title($categorie['name']), 'date' => $categories[(int) $categorie['category_id']]];
			}
		}

		// Une rubrique vide n'est pas annoncée aux moteurs : sa page ne dirait que « rien pour l'instant ».
		if (!$adresses)
		{
			return [];
		}

		array_unshift($adresses, ['adresse' => 'gallery', 'date' => $adresses[0]['date'] ?? NULL]);

		return $adresses;
	}
}
