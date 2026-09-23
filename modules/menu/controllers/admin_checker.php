<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 */

namespace NF\Modules\Menu\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module_Checker;

class Admin_Checker extends Module_Checker
{
	public function index($page = '')
	{
		$menus = NeoFrag()->db	->select('m.menu_id', 'm.name', 'm.title', 'COUNT(i.item_id) AS nb')
								->from('nf_menus m')
								->join('nf_menus_items i', 'm.menu_id = i.menu_id', 'LEFT')
								->group_by('m.menu_id')
								->order_by('m.title ASC')
								->get();

		return [$menus];
	}

	public function _menu_add() { return [NULL]; }

	public function _menu_edit($id, $title)
	{
		$m = NeoFrag()->db->select('*')->from('nf_menus')->where('menu_id', $id)->row();
		return $m ? [$m] : NULL;
	}

	public function _menu_delete($id, $title)
	{
		$m = NeoFrag()->db->select('menu_id', 'title')->from('nf_menus')->where('menu_id', $id)->row();
		return $m ? [$m] : NULL;
	}

	public function _item_add($id)
	{
		$m = NeoFrag()->db->select('*')->from('nf_menus')->where('menu_id', $id)->row();
		return $m ? [$m] : NULL;
	}

	public function _item_edit($id, $title)
	{
		$it = NeoFrag()->db->select('*')->from('nf_menus_items')->where('item_id', $id)->row();
		return $it ? [$it] : NULL;
	}

	public function _item_delete($id, $title)
	{
		$it = NeoFrag()->db->select('*')->from('nf_menus_items')->where('item_id', $id)->row();
		return $it ? [$it] : NULL;
	}
}
