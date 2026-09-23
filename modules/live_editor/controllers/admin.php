<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\Live_Editor\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Admin extends Controller_Module
{
	public function index()
	{
		$this	->css('fonts/open-sans')
				->css('live-editor')
				->js('live-editor')
				->js('sortable.lib.min');

		$modules = $pages = [];

		foreach (NeoFrag()->model2('addon')->get('module') as $module)
		{
			if (@$module->controller('index') && !in_array($module->info()->name, ['settings']))
			{
				$modules[] = $module;
			}
		}

		array_natsort($modules, function($a){
			return $a->info()->title;
		});

		$pages = array_merge([
			'index' => NeoFrag()->lang('Accueil')
		], $pages);

		foreach ($modules as $module)
		{
			$name = $module->info()->name;

			if ($name == 'pages')
			{
				foreach ($module->model()->get_pages() as $page)
				{
					if ($page['published'])
					{
						$pages[$page['name']] = (string) $this->lang('Page : %s', str_shortener($page['title'], 35));
					}
				}
			}
			else if ($this->_a_une_page_publique($module))
			{
				$pages[$name] = $module->info()->title;
			}
		}

		$theme = $this->theme($this->config->nf_default_theme);

		return $this->view('index', [
			'modules'       => $pages,
			'styles_row'    => $theme->styles_row(),
			'styles_widget' => $theme->styles_widget()
		]);
	}

	/**
	 * Le module a-t-il une page PUBLIQUE ?
	 *
	 * La liste de navigation de l'éditeur retenait tout module doté d'un contrôleur `index` — ce qui
	 * inclut `files`, `monitoring` ou `ads`, dont l'index n'existe que côté administration. Le menu
	 * proposait donc `/files`, `/monitoring` et `/ads`, qui répondent 404.
	 *
	 * Le critère factuel : le module déclare-t-il une route qui répond à son adresse RACINE ? Une
	 * simple route publique ne suffit pas — `files` déclare `{url_title}`, `ads` déclare
	 * `click/{id}`, et ni `/files` ni `/ads` n'existent pour autant. Seule une route vide, ou
	 * réduite à une pagination, répond à `/<module>`.
	 */
	private function _a_une_page_publique($module): bool
	{
		$racines = ['', '{page}', '{pages}', '/{page}', '/{pages}'];

		foreach (array_keys((array) ($module->info()->routes ?? [])) as $route)
		{
			if (in_array((string) $route, $racines, TRUE))
			{
				return TRUE;
			}
		}

		return FALSE;
	}
}
