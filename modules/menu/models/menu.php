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

	/*
	 * Le titre d'un lien se traduit (m32, 2026-10-15) : la navigation sert la meilleure traduction (la langue
	 * demandée, sinon le français, sinon la première écrite) ; l'écran d'édition, le titre TEL QU'IL EST ÉCRIT dans
	 * la langue de l'administration (`titre_dans`).
	 */
	public function get_items($menu_id)
	{
		return $this->db	->select('i.*', 'il.title')
							->from('nf_menus_items i')
							->join_lang('nf_menus_items_lang il', 'item_id', 'i.item_id', NULL, 'LEFT')
							->where('i.menu_id', $menu_id)
							->order_by('i.position ASC, i.item_id ASC')
							->get();
	}

	/** Un lien, avec son titre dans la meilleure langue (NULL s'il n'existe pas). */
	public function get_item($item_id)
	{
		return $this->db	->select('i.*', 'il.title')
							->from('nf_menus_items i')
							->join_lang('nf_menus_items_lang il', 'item_id', 'i.item_id', NULL, 'LEFT')
							->where('i.item_id', (int) $item_id)
							->row() ?: NULL;
	}

	/** Le titre TEL QU'IL EST ÉCRIT dans une langue, sans repli : c'est ce que l'administration édite. */
	public function titre_dans(int $item_id, string $langue): string
	{
		$titre = $this->db	->select('title')
							->from('nf_menus_items_lang')
							->where('item_id', $item_id)
							->where('lang', $langue)
							->row();

		return is_string($titre) ? $titre : '';
	}

	/** `ecrire_lang` CRÉE la ligne quand elle manque (leçon de m30). */
	public function ecrire_titre(int $item_id, string $titre, string $langue): void
	{
		$this->db->ecrire_lang('nf_menus_items_lang', ['item_id' => $item_id, 'lang' => $langue], ['title' => $titre]);
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
