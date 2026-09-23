<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\Statistics\Models;

use NF\NeoFrag\Loadables\Model;

class Statistics extends Model
{
	public function get_statistics($filters = NULL)
	{
		$statistics = [];
		$colors     = ['#7cb5ec', '#434348', '#90ed7d', '#f7a35c', '#8085e9', '#f15c80', '#e4d354', '#2b908f', '#f45b5b', '#91e8e1'];

		$i = 0;

		foreach (NeoFrag()->model2('addon')->get('module') as $module)
		{
			if ($controller = @$module->controller('statistics'))
			{
				// `$module->name` vaut FALSE sur un addon chargé : le nom est dans `info()`. Toutes les
				// clés valaient donc « -comments », « -articles »… au lieu de « comments-comments ».
				// Rien ne se voyait — le formulaire et le filtre lisent les mêmes clés — mais deux
				// modules exposant une statistique du même nom s'écrasaient l'un l'autre en silence.
				$nom = $module->info()->name;

				foreach ($controller->statistics() as $name => $statistic)
				{
					if ($filters === NULL || in_array($nom.'-'.$name, $filters))
					{
						$statistics[$nom.'-'.$name] = array_merge($statistic, [
							'title' => $statistic['title'],
							'color' => $colors[$i % 10]
						]);
					}

					$i++;
				}
			}
		}

		return $statistics;
	}
}
