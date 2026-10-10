<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\Search\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Ajax extends Controller_Module
{
	/**
	 * Recherche instantanée (typeahead). Renvoie en JSON un petit échantillon de résultats
	 * (titre + lien) agrégés sur les modules exposant le carrefour `search` ET un `suggest()`.
	 * LIKE volontaire (rapide, sans index full-text) ; capé (5/module, 8 au total) pour rester léger.
	 * Respecte les filtres de publication/ACL posés par chaque `search()`.
	 */
	public function suggest()
	{
		$out = [];
		$q   = trim((string)($_GET['q'] ?? ''));

		if (mb_strlen($q) >= 2)
		{
			// Endpoint public + LIKE non indexé : rate-limit léger anti-amplification (60 req / 60 s par IP).
			$rl  = new \NF\NeoFrag\Libraries\Rate_Limit($this);
			$key = 'search_suggest:ip:'.\NF\NeoFrag\Libraries\Rate_Limit::bloc_ip();

			if (!$rl->check($key)['allowed'])
			{
				return $this->json([]);
			}

			$rl->hit($key, 60, 60, 60);

			$like = '%'.addcslashes($q, '%_').'%';

			foreach (NeoFrag()->model2('addon')->get('module') as $module)
			{
				if (!($sc = @$module->controller('search')) || !method_exists($sc, 'suggest') || !($columns = $sc->search()))
				{
					continue;
				}

				$args = [];

				foreach ($columns as $col)
				{
					array_push($args, $col.' LIKE', $like, 'OR');
				}

				call_user_func_array([$this->db, 'where'], $args);

				foreach ($this->db->limit(5)->get() as $row)
				{
					$s = $sc->suggest($row);

					if (!empty($s['title']) && !empty($s['url']))
					{
						$out[] = [
							'title'  => mb_strimwidth(trim(nf_texte_brut(strip_tags((string)$s['title']))), 0, 90, '…'),
							'url'    => $s['url'],
							'module' => $module->info()->title,
							'icon'   => $module->info()->icon
						];

						if (count($out) >= 8)
						{
							break 2;
						}
					}
				}
			}
		}

		return $this->json($out);
	}
}
