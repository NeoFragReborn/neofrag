<?php
/**
 * https://neofr.ag
 */

namespace NF\Modules\Menu\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Admin extends Controller_Module
{
	public function index($menus)
	{
		$this->title($this->lang('Menus'))->icon('fas fa-bars');

		if (empty($menus))
		{
			$body = $this->admin_empty('fas fa-bars', $this->lang('Aucun menu. Créez-en un pour organiser votre navigation.'));
		}
		else
		{
			$body = '<table class="table table-hover" style="margin:0;"><thead><tr><th>'.$this->lang('Titre').'</th><th>'.$this->lang('Identifiant').'</th><th class="text-end">'.$this->lang('Items').'</th><th class="text-end"></th></tr></thead><tbody>';
			foreach ($menus as $m)
			{
				$slug  = url_title($m['title']);
				$body .= '<tr>'
					.'<td><strong>'.htmlspecialchars($m['title']).'</strong></td>'
					.'<td><code>'.htmlspecialchars($m['name']).'</code></td>'
					.'<td class="text-end">'.(int)$m['nb'].'</td>'
					.'<td class="text-end" style="white-space:nowrap;">'
					.'<a class="btn btn-sm btn-outline-primary" href="'.url('admin/menu/edit/'.$m['menu_id'].'/'.$slug).'" title="'.$this->lang('Éditer').'"><i class="fas fa-pen"></i></a> '
					.'<a class="btn btn-sm btn-outline-danger" href="'.$this->csrf_url('admin/menu/delete/'.$m['menu_id'].'/'.$slug).'" data-confirm="'.htmlspecialchars($this->lang('Supprimer ce menu et tous ses items ?'), ENT_QUOTES).'" title="'.$this->lang('Supprimer').'"><i class="far fa-trash-alt"></i></a>'
					.'</td></tr>';
			}
			$body .= '</tbody></table>';
		}

		$actions = '<a class="btn btn-primary btn-sm" href="'.url('admin/menu/add').'"><i class="fas fa-plus"></i> '.$this->lang('Nouveau menu').'</a>';

		return $this->admin_card('fas fa-bars', $this->lang('Menus'), $body, count($menus).' '.$this->lang('menu|menus', count($menus)), $actions);
	}

	public function _menu_add()    { return $this->_menu_form(NULL); }
	public function _menu_edit($menu) { return $this->_menu_form($menu); }
	public function _menu_delete($menu)
	{
		$this->check_csrf('admin/menu');

		NeoFrag()->db->where('menu_id', $menu['menu_id'])->delete('nf_menus'); // CASCADE → items supprimés
		notify($this->lang('Menu supprimé.'));
		redirect('admin/menu');
	}

	protected function _menu_form($menu)
	{
		$is_new = $menu === NULL;
		$this->title($is_new ? $this->lang('Nouveau menu') : $this->lang('Éditer : %s', $menu['title']))->icon('fas fa-bars')->breadcrumb();

		$this->form()
			 ->add_rules([
				'title' => ['label' => $this->lang('Titre'),              'type' => 'text', 'value' => $is_new ? '' : $menu['title'], 'rules' => 'required'],
				'name'  => ['label' => $this->lang('Identifiant (slug)'), 'type' => 'text', 'value' => $is_new ? '' : $menu['name'],  'rules' => 'required']
			 ])
			 ->add_submit($is_new ? $this->lang('Créer') : $this->lang('Enregistrer'));

		if ($this->form()->is_valid($post))
		{
			$data = ['title' => $post['title'], 'name' => url_title($post['name'])];

			if ($is_new)
			{
				$new_id = NeoFrag()->db->insert('nf_menus', $data);
				notify($this->lang('Menu créé. Ajoutez maintenant ses items.'));
				redirect('admin/menu/edit/'.$new_id.'/'.url_title($data['title']));
			}

			NeoFrag()->db->where('menu_id', $menu['menu_id'])->update('nf_menus', $data);
			notify($this->lang('Menu modifié.'));
			redirect('admin/menu/edit/'.$menu['menu_id'].'/'.url_title($data['title']));
		}

		$out = $this->admin_back('admin/menu', $this->lang('Menus'))
			 . $this->admin_card($is_new ? 'fas fa-plus' : 'fas fa-edit', $is_new ? $this->lang('Nouveau menu') : $this->lang('Réglages du menu'), $this->form()->display());

		if (!$is_new)
		{
			$out .= $this->_items_card($menu);
		}

		return $out;
	}

	protected function _items_card($menu)
	{
		$items    = $this->model()->get_items($menu['menu_id']);
		$children = [];
		$roots    = [];
		foreach ($items as $it)
		{
			if ($it['parent_id']) $children[$it['parent_id']][] = $it;
			else                  $roots[] = $it;
		}

		if (empty($items))
		{
			$body = $this->admin_empty('fas fa-list', $this->lang('Aucun item. Ajoutez le premier lien de ce menu.'));
		}
		else
		{
			$body  = '<table class="table table-hover" style="margin:0;"><tbody>';
			$row = function($it, $depth) use (&$row, &$children, &$body, $menu) {
				$slug   = url_title($it['title']);
				$pad    = $depth ? 'padding-left:'.(18 + $depth * 22).'px;' : '';
				$body  .= '<tr>'
					.'<td style="'.$pad.'">'.($depth ? '<i class="fas fa-level-up-alt fa-rotate-90 text-muted" style="margin-right:6px;"></i>' : '').($it['icon'] ? '<i class="'.htmlspecialchars($it['icon']).'" style="margin-right:6px;"></i>' : '').'<strong>'.htmlspecialchars($it['title']).'</strong>'
					.($it['url'] ? ' <small class="text-muted">'.htmlspecialchars($it['url']).'</small>' : '').'</td>'
					.'<td class="text-end" style="white-space:nowrap;">'
					.'<a class="btn btn-sm btn-outline-primary" href="'.url('admin/menu/item/edit/'.$it['item_id'].'/'.$slug).'" title="'.$this->lang('Éditer').'"><i class="fas fa-pen"></i></a> '
					.'<a class="btn btn-sm btn-outline-danger" href="'.$this->csrf_url('admin/menu/item/delete/'.$it['item_id'].'/'.$slug).'" data-confirm="'.htmlspecialchars($this->lang('Supprimer cet item (et ses sous-items) ?'), ENT_QUOTES).'" title="'.$this->lang('Supprimer').'"><i class="far fa-trash-alt"></i></a>'
					.'</td></tr>';
				foreach ($children[$it['item_id']] ?? [] as $child)
				{
					$row($child, $depth + 1);
				}
			};
			foreach ($roots as $it) { $row($it, 0); }
			$body .= '</tbody></table>';
		}

		$actions = '<a class="btn btn-primary btn-sm" href="'.url('admin/menu/item/add/'.$menu['menu_id']).'"><i class="fas fa-plus"></i> '.$this->lang('Ajouter un item').'</a>';

		return $this->admin_card('fas fa-list', $this->lang('Items du menu'), $body, count($items).' '.$this->lang('item|items', count($items)), $actions);
	}

	public function _item_add($menu)  { return $this->_item_form($menu, NULL); }
	public function _item_edit($item) { return $this->_item_form($this->model()->get_menu($item['menu_id']), $item); }
	public function _item_delete($item)
	{
		$this->check_csrf('admin/menu');

		// Supprime l'item et ses enfants directs (1 niveau de sous-menu).
		NeoFrag()->db->where('parent_id', $item['item_id'])->delete('nf_menus_items');
		NeoFrag()->db->where('item_id', $item['item_id'])->delete('nf_menus_items');
		notify($this->lang('Item supprimé.'));
		redirect('admin/menu/edit/'.$item['menu_id'].'/'.url_title($this->_menu_slug($item['menu_id'])));
	}

	protected function _item_form($menu, $item)
	{
		$is_new = $item === NULL;
		$this->title($is_new ? $this->lang('Nouvel item') : $this->lang('Éditer : %s', $item['title']))->icon('fas fa-list')->breadcrumb();

		// Parents possibles = items racine du menu (sauf l'item édité lui-même).
		$parents = ['' => $this->lang('— Racine —')];
		foreach ($this->model()->get_items($menu['menu_id']) as $it)
		{
			if (!$it['parent_id'] && (!$item || $it['item_id'] != $item['item_id']))
			{
				$parents[$it['item_id']] = $it['title'];
			}
		}

		$this->form()
			 ->add_rules([
				'title'     => ['label' => $this->lang('Titre'),       'type' => 'text',   'value' => $is_new ? '' : $item['title'], 'rules' => 'required'],
				'url'       => ['label' => $this->lang('Lien (URL ou route, ex. forum)'), 'type' => 'text', 'value' => $is_new ? '' : $item['url']],
				'icon'      => ['label' => $this->lang('Icône (classe Font Awesome, ex. fas fa-home)'), 'type' => 'text', 'value' => $is_new ? '' : $item['icon']],
				'parent_id' => ['label' => $this->lang('Sous-menu de'), 'type' => 'select', 'values' => $parents, 'value' => $is_new ? '' : (string)$item['parent_id']],
				'target'    => ['label' => $this->lang('Ouverture'),   'type' => 'select', 'values' => ['' => $this->lang('Même onglet'), '_blank' => $this->lang('Nouvel onglet')], 'value' => $is_new ? '' : $item['target']],
				'position'  => ['label' => $this->lang('Position'),    'type' => 'text',   'value' => $is_new ? '0' : $item['position']]
			 ])
			 ->add_submit($is_new ? $this->lang('Ajouter') : $this->lang('Enregistrer'));

		if ($this->form()->is_valid($post))
		{
			$data = [
				'menu_id'   => (int)$menu['menu_id'],
				'parent_id' => $post['parent_id'] !== '' ? (int)$post['parent_id'] : NULL,
				'title'     => $post['title'],
				'url'       => $post['url'] ?? '',
				'icon'      => $post['icon'] ?? '',
				'target'    => $post['target'] === '_blank' ? '_blank' : '',
				'position'  => (int)$post['position']
			];

			if ($is_new) NeoFrag()->db->insert('nf_menus_items', $data);
			else         NeoFrag()->db->where('item_id', $item['item_id'])->update('nf_menus_items', $data);

			notify($is_new ? $this->lang('Item ajouté.') : $this->lang('Item modifié.'));
			redirect('admin/menu/edit/'.$menu['menu_id'].'/'.url_title($menu['title']));
		}

		return $this->admin_back('admin/menu/edit/'.$menu['menu_id'].'/'.url_title($menu['title']), $this->lang('Menu : %s', $menu['title']))
			 . $this->admin_card($is_new ? 'fas fa-plus' : 'fas fa-edit', $is_new ? $this->lang('Nouvel item') : $this->lang('Éditer l\'item'), $this->form()->display());
	}

	private function _menu_slug($menu_id)
	{
		$menu = $this->model()->get_menu($menu_id);
		return $menu ? $menu['title'] : 'menu';
	}
}
