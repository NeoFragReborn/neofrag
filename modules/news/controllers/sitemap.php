<?php
declare(strict_types=1);
/**
 * Le plan du site (carrefour `sitemap`) : les actualités parues et leurs catégories qui en
 * ont, dans la langue du plan. Une actualité rédigée dans une autre langue n'y figure pas : servie ici
 * en repli, elle se déclare canonique dans la sienne.
 *
 * Appelé par Settings\Controllers\Ajax::sitemap().
 */

namespace NF\Modules\News\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Sitemap extends Controller_Module
{
	/** @return list<array{adresse: string, date?: int|string|null}> */
	public function sitemap(): array
	{
		$adresses   = [];
		$categories = [];

		foreach ($this->db	->select('n.news_id', 'n.category_id', 'nl.title', 'n.date')
							->from('nf_news n')
							->join('nf_news_lang nl', 'nl.news_id = n.news_id')
							->where('nl.lang', $this->config->lang->info()->name)
							->where('n.published', '1')
							->where('n.deleted_at', NULL)
							->where('n.date <=', date('Y-m-d H:i:s'))
							->order_by('n.date DESC')
							->get() as $actualite)
		{
			$adresses[] = ['adresse' => 'news/'.$actualite['news_id'].'/'.url_title($actualite['title']), 'date' => $actualite['date']];

			if ($actualite['category_id'])
			{
				$categories[(int) $actualite['category_id']] = max($categories[(int) $actualite['category_id']] ?? '', (string) $actualite['date']);
			}
		}

		if ($categories)
		{
			foreach ($this->db->select('category_id', 'name')->from('nf_news_categories')->where('category_id', array_keys($categories))->get() as $categorie)
			{
				$adresses[] = ['adresse' => 'news/category/'.$categorie['category_id'].'/'.url_title($categorie['name']), 'date' => $categories[(int) $categorie['category_id']]];
			}
		}

		array_unshift($adresses, ['adresse' => 'news', 'date' => $adresses[0]['date'] ?? NULL]);

		return $adresses;
	}
}
