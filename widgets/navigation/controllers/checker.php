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

		if (!empty($settings['menu']) && ($liens = $this->liens_du_menu((string) $settings['menu'])) !== NULL)
		{
			// Le NOM du menu est gardé dans les réglages (2026-10-15). Il en était retiré : le widget n'en gardait
			// qu'une COPIE des liens, prise à l'enregistrement — un lien ajouté, renommé ou traduit ensuite n'arrivait
			// jamais dans la navigation, et l'écran de réglage ne savait plus quel menu était choisi. L'affichage le
			// relit désormais (Index::_display) ; la copie ne sert que si le menu disparaît.
			return ['menu' => (string) $settings['menu'], 'links' => $liens];
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

	/** Les liens d'un menu géré, dans la langue de la page ; NULL si ce menu n'existe pas (ou plus). */
	public function liens_du_menu(string $nom): ?array
	{
		$menu = NeoFrag()->db->select('menu_id')->from('nf_menus')->where('name', $nom)->row(FALSE);

		return is_array($menu) && !empty($menu['menu_id']) ? $this->_menu_links((int) $menu['menu_id']) : NULL;
	}

	protected function _menu_links($menu_id)
	{
		$roots    = [];
		$children = [];

		// Le titre d'un lien vit dans `nf_menus_items_lang` depuis m32 (2026-10-15) : la meilleure langue.
		foreach (NeoFrag()->db	->select('i.item_id', 'i.parent_id', 'il.title', 'i.url', 'i.icon')
								->from('nf_menus_items i')
								->join_lang('nf_menus_items_lang il', 'item_id', 'i.item_id', NULL, 'LEFT')
								->where('i.menu_id', $menu_id)
								->order_by('i.position ASC, i.item_id ASC')
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
