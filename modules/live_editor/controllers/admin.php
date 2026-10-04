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

		/*
		 * Le menu « Navigation », en sous-menus : les pages du site d'abord, puis les modules rangés
		 * dans les rubriques de la barre latérale de l'administration (nf_rubriques_admin(), une seule
		 * liste pour les deux). Il alignait jusqu'ici plus de quarante entrées à la suite (relevé le
		 * 2026-10-02).
		 */
		$rubriques = nf_rubriques_admin();
		$rangement = [];

		foreach ($rubriques as $cle => $rubrique)
		{
			foreach ($rubrique['modules'] as $nom)
			{
				$rangement[$nom] = $cle;
			}
		}

		$groupes = [
			'pages' => ['title' => (string) $this->lang('Pages du site'), 'icon' => 'far fa-file-alt', 'pages' => ['index' => (string) NeoFrag()->lang('Accueil')]],
		] + array_map(static fn (array $r): array => ['title' => $r['title'], 'icon' => $r['icon'], 'pages' => []], $rubriques);

		foreach ($modules as $module)
		{
			$name = $module->info()->name;

			if ($name == 'pages')
			{
				foreach ($module->model()->get_pages() as $page)
				{
					if ($page['published'])
					{
						$groupes['pages']['pages'][$page['name']] = str_shortener($page['title'], 35);
					}
				}
			}
			else if ($this->_a_une_page_publique($module))
			{
				$groupes[$rangement[$name] ?? 'autres']['pages'][$name] = (string) $module->info()->title;
			}
		}

		$theme = $this->theme($this->config->nf_default_theme);

		return $this->view('index', [
			'groupes'       => array_filter($groupes, static fn (array $g): bool => $g['pages'] !== []),
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
	 *
	 * Ou une méthode `index()` dans le contrôleur public : c'est elle qui répond à `/<module>` quand
	 * aucune route ne le fait. Sans ce second critère, le Forum, les Galeries, les Équipes, le Contact
	 * et le Palmarès manquaient au menu (relevé le 2026-10-02) ; les modules sans page publique —
	 * fichiers, publicités, API, Discord, Monitoring — n'en ont pas.
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

		$controleur = @$module->controller('index');

		return is_object($controleur) && method_exists($controleur, 'index') && (new \ReflectionMethod($controleur, 'index'))->isPublic();
	}
}
