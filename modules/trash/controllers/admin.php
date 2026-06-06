<?php
/**
 * https://neofr.ag
 * Corbeille admin : liste le contenu soft-deleted, filtre par type, restauration / purge en masse.
 */

namespace NF\Modules\Trash\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;
use NF\Modules\Trash\Trash;

class Admin extends Controller_Module
{
	public function index($page = '')
	{
		$this->title($this->lang('Corbeille'))->icon('fas fa-trash-restore');

		$types = Trash::TYPES;

		// Action en masse (POST) : restaurer / purger la sélection.
		if (!empty($_POST['bulk_action']) && !empty($_POST['selected']) && is_array($_POST['selected']))
		{
			$action = (string)$_POST['bulk_action'];
			$done   = 0;

			foreach ($_POST['selected'] as $item)
			{
				list($type, $id) = array_pad(explode(':', (string)$item, 2), 2, '');
				$id = (int)$id;

				if (!$id || !isset($types[$type]))
				{
					continue;
				}

				$cfg = $types[$type];

				if ($action === 'restore' && $this->is_authorized('restore'))
				{
					$this->module($cfg['module'])->model()->{$cfg['restore']}($id);
					$done++;
				}
				else if ($action === 'purge' && $this->is_authorized('purge'))
				{
					$this->module($cfg['module'])->model()->{$cfg['purge']}($id);
					$done++;
				}
			}

			if ($done)
			{
				notify($this->lang($action === 'purge' ? '%d élément(s) purgé(s) définitivement.' : '%d élément(s) restauré(s).', $done));
			}

			redirect('admin/trash');
		}

		// Filtre par type.
		$filter = isset($_GET['type']) && isset($types[$_GET['type']]) ? (string)$_GET['type'] : '';
		$lang   = $this->config->lang->info()->name;
		$items  = [];

		foreach ($types as $type => $cfg)
		{
			if ($filter !== '' && $filter !== $type)
			{
				continue;
			}

			if (isset($cfg['lang']))
			{
				$rows = NeoFrag()->db
					->select('t.'.$cfg['pk'].' AS id', 'l.'.$cfg['title'].' AS title', 't.deleted_at', 'u.username AS deleted_by')
					->from($cfg['table'].' t')
					->join($cfg['lang'].' l', 'l.'.$cfg['pk'].' = t.'.$cfg['pk'])
					->join('nf_user u', 'u.id = t.deleted_by', 'LEFT')
					->where('l.lang', $lang)
					->where('t.deleted_at IS NOT NULL')
					->order_by('t.deleted_at DESC')
					->get(FALSE);
			}
			else
			{
				// Type sans table de langue (commentaires) : le titre vient d'une colonne texte.
				$rows = NeoFrag()->db
					->select('t.'.$cfg['pk'].' AS id', 't.'.$cfg['content'].' AS title', 't.deleted_at', 'u.username AS deleted_by')
					->from($cfg['table'].' t')
					->join('nf_user u', 'u.id = t.deleted_by', 'LEFT')
					->where('t.deleted_at IS NOT NULL')
					->order_by('t.deleted_at DESC')
					->get(FALSE);
			}

			foreach ($rows as $row)
			{
				$row['type']  = $type;
				$row['label'] = $cfg['label'];
				$items[]      = $row;
			}
		}

		usort($items, function($a, $b){ return strcmp((string)$b['deleted_at'], (string)$a['deleted_at']); });

		return $this->admin_card('fas fa-trash-restore', $this->lang('Corbeille'), $this->_render($items, $types, $filter));
	}

	private function _render(array $items, array $types, $filter)
	{
		// Barre de filtre par type.
		$toolbar = '<form method="get" action="'.url('admin/trash').'" style="margin-bottom:12px;">'
			.'<select name="type" class="form-control form-control-sm" style="width:auto;display:inline-block;" onchange="this.form.submit()">'
			.'<option value="">'.$this->lang('Tous les types').'</option>';
		foreach ($types as $type => $cfg)
		{
			$toolbar .= '<option value="'.$type.'"'.($filter === $type ? ' selected' : '').'>'.htmlspecialchars($cfg['label']).'</option>';
		}
		$toolbar .= '</select></form>';

		if (!$items)
		{
			return $toolbar.'<div class="alert alert-info text-center">'.$this->lang('La corbeille est vide.').'</div>';
		}

		$rows = '';
		foreach ($items as $it)
		{
			$rows .= '<tr>'
				.'<td><input type="checkbox" name="selected[]" value="'.htmlspecialchars($it['type'].':'.(int)$it['id']).'" class="nf-trash-cb"></td>'
				.'<td><span class="badge badge-secondary">'.htmlspecialchars($it['label']).'</span></td>'
				.'<td>'.htmlspecialchars(str_shortener(trim(strip_tags((string)$it['title'])), 80, '…')).'</td>'
				.'<td><small>'.htmlspecialchars((string)$it['deleted_at']).'</small></td>'
				.'<td><small>'.($it['deleted_by'] ? htmlspecialchars((string)$it['deleted_by']) : '—').'</small></td>'
				.'</tr>';
		}

		$confirm_purge = htmlspecialchars($this->lang('Purger définitivement la sélection ? Action irréversible.'), ENT_QUOTES);

		return $toolbar
			.'<form method="post" action="'.url('admin/trash').'">'
			.'<div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-bottom:12px;">'
				.'<label style="display:flex;align-items:center;gap:6px;font-size:13px;margin:0;cursor:pointer;"><input type="checkbox" id="nf-trash-all"> '.$this->lang('Tout sélectionner').'</label>'
				.'<button type="submit" name="bulk_action" value="restore" class="btn btn-sm btn-success">'.icon('fas fa-trash-restore').' '.$this->lang('Restaurer').'</button>'
				.'<button type="submit" name="bulk_action" value="purge" class="btn btn-sm btn-danger" onclick="return confirm(\''.$confirm_purge.'\');">'.icon('fas fa-times').' '.$this->lang('Purger').'</button>'
			.'</div>'
			.'<div class="table-responsive"><table class="table table-sm table-hover"><thead><tr>'
			.'<th></th><th>'.$this->lang('Type').'</th><th>'.$this->lang('Titre').'</th><th>'.$this->lang('Supprimé le').'</th><th>'.$this->lang('Par').'</th>'
			.'</tr></thead><tbody>'.$rows.'</tbody></table></div>'
			.'</form>'
			.'<script>(function(){var a=document.getElementById("nf-trash-all");if(a){a.addEventListener("change",function(){document.querySelectorAll(".nf-trash-cb").forEach(function(c){c.checked=a.checked;});});}})();</script>';
	}
}
