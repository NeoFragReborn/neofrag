<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Widgets\Navigation\Controllers;

use NF\NeoFrag\Loadables\Controller;

class Checker extends Controller
{
	public function index($settings = [])
	{
		return $this->_resolve($settings);
	}

	public function vertical($settings = [])
	{
		return $this->_resolve($settings);
	}

	/** Soit un menu nommé (module menu), soit la saisie manuelle de liens (comportement historique). */
	protected function _resolve($settings)
	{
		// Reglages absents : un widget peut etre pose sans passer par son formulaire (install()
		// d'un theme, ajout en Live Editor, disposition ancienne). Cf. tools/check-widget-reglages.php.
		$settings = (array) $settings + ['menu' => ''];

		if (!empty($settings['menu']))
		{
			$menu = NeoFrag()->db->select('menu_id')->from('nf_menus')->where('name', $settings['menu'])->row(FALSE);

			if (is_array($menu) && !empty($menu['menu_id']))
			{
				return ['links' => $this->_menu_links((int)$menu['menu_id'])];
			}
		}

		$links = [];

		foreach ($settings as $key => $values)
		{
			if (in_array($key, ['title', 'url', 'target']) && is_array($values))
			{
				foreach ($values as $i => $value)
				{
					$links[$i][$key] = utf8_htmlentities($value);
				}
			}
		}

		return ['links' => $links];
	}

	protected function _menu_links($menu_id)
	{
		$roots    = [];
		$children = [];

		foreach (NeoFrag()->db	->select('item_id', 'parent_id', 'title', 'url', 'icon')
								->from('nf_menus_items')
								->where('menu_id', $menu_id)
								->order_by('position ASC, item_id ASC')
								->get() as $it)
		{
			if ($it['parent_id']) $children[$it['parent_id']][] = $it;
			else                  $roots[] = $it;
		}

		$links = [];

		foreach ($roots as $it)
		{
			$kids = $children[$it['item_id']] ?? [];

			if ($kids)
			{
				$sub = [];
				foreach ($kids as $k)
				{
					$sub[] = ['title' => utf8_htmlentities($k['title']), 'url' => $k['url'], 'icon' => $k['icon']];
				}
				$links[] = ['title' => utf8_htmlentities($it['title']), 'url' => $sub, 'icon' => $it['icon']];
			}
			else
			{
				$links[] = ['title' => utf8_htmlentities($it['title']), 'url' => $it['url'], 'icon' => $it['icon']];
			}
		}

		return $links;
	}
}
