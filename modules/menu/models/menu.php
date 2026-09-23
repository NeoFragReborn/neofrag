<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 */

namespace NF\Modules\Menu\Models;

use NF\NeoFrag\Loadables\Model;

class Menu extends Model
{
	public function get_menus()
	{
		return $this->db	->select('m.menu_id', 'm.name', 'm.title', 'COUNT(i.item_id) AS nb')
							->from('nf_menus m')
							->join('nf_menus_items i', 'm.menu_id = i.menu_id', 'LEFT')
							->group_by('m.menu_id')
							->order_by('m.title ASC')
							->get();
	}

	public function get_menu($menu_id)
	{
		return $this->db->select('*')->from('nf_menus')->where('menu_id', $menu_id)->row();
	}

	public function get_menu_by_name($name)
	{
		return $this->db->select('*')->from('nf_menus')->where('name', $name)->row();
	}

	public function get_menu_choices()
	{
		$out = [];
		foreach ($this->db->select('menu_id', 'title')->from('nf_menus')->order_by('title ASC')->get() as $m)
		{
			$out[$m['menu_id']] = $m['title'];
		}
		return $out;
	}

	public function get_items($menu_id)
	{
		return $this->db	->select('*')
							->from('nf_menus_items')
							->where('menu_id', $menu_id)
							->order_by('position ASC, item_id ASC')
							->get();
	}

	/** Items mis en forme pour le widget navigation : [['title','url','icon'], ...] avec sous-menu (url = tableau). */
	public function get_links($menu_id)
	{
		$roots    = [];
		$children = [];

		foreach ($this->get_items($menu_id) as $item)
		{
			if ($item['parent_id'])
			{
				$children[$item['parent_id']][] = $item;
			}
			else
			{
				$roots[] = $item;
			}
		}

		$links = [];

		foreach ($roots as $item)
		{
			$kids = $children[$item['item_id']] ?? [];

			if ($kids)
			{
				$sub = [];
				foreach ($kids as $k)
				{
					$sub[] = ['title' => $k['title'], 'url' => $k['url'], 'icon' => $k['icon']];
				}
				$links[] = ['title' => $item['title'], 'url' => $sub, 'icon' => $item['icon']];
			}
			else
			{
				$links[] = ['title' => $item['title'], 'url' => $item['url'], 'icon' => $item['icon']];
			}
		}

		return $links;
	}
}
