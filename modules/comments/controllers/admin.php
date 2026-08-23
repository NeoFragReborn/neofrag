<?php
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\Comments\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Admin extends Controller_Module
{
	public function index($comments)
	{
		$this->title($this->lang('Commentaires'))->icon('far fa-comments');

		// Suppression en masse (POST) puis refresh.
		if (!empty($_POST['bulk_delete']) && !empty($_POST['selected']) && is_array($_POST['selected']))
		{
			$this->check_csrf('admin/comments');

			$ids = array_values(array_filter(array_map('intval', $_POST['selected'])));

			if ($ids)
			{
				NeoFrag()->db->where('id', $ids)->update('nf_comment', [
					'deleted_at' => date('Y-m-d H:i:s'),
					'deleted_by' => $this->user() ? (int)$this->user->id : NULL
				]);
				notify($this->lang('%d commentaire supprimé.|%d commentaires supprimés.', count($ids), count($ids)));
				refresh();
			}
		}

		$body = '<div class="nf-card-grid">';
		$count = 0;
		foreach ($comments->get() as $comment)
		{
			if ($comment->deleted_at)
			{
				continue; // en corbeille → visible dans admin/trash, pas ici
			}

			$count++;
			$module_name = preg_replace('/_.*$/', '', $comment->module);
			$module_info = $this->module($module_name) ? $this->module($module_name)->info() : NULL;
			$module_label = $module_info ? '<i class="'.$module_info->icon.'"></i> '.htmlspecialchars($module_info->title) : htmlspecialchars($module_name);

			$body .= '<div class="nf-content-card">';
			$body .= '<div class="nf-content-card-head">';
			$body .= '<div class="nf-content-card-title"><input type="checkbox" name="selected[]" value="'.(int)$comment->id.'" class="nf-bulk-cb" style="margin-right:6px;vertical-align:middle;">'.$comment->user->link().'</div>';
			$body .= '<span class="nf-content-card-status published"><i class="far fa-comments"></i> '.$module_label.'</span>';
			$body .= '</div>';
			$body .= '<div class="nf-content-card-desc">'.htmlspecialchars(strip_tags((string)$comment->content)).'</div>';
			$body .= '<div class="nf-content-card-meta">';
			$body .= '<span><i class="far fa-clock"></i> '.htmlspecialchars((string)$comment->date).'</span>';
			$body .= '</div>';
			$body .= '<div class="nf-content-card-foot">';
			$body .= '<span class="nf-content-card-spacer"></span>';
			$body .= '<a class="btn btn-sm btn-outline-danger" href="'.url('ajax/comments/delete/'.$comment->id).'" data-confirm="'.htmlspecialchars($this->lang('Supprimer ce commentaire ?'), ENT_QUOTES).'" title="'.$this->lang('Supprimer').'"><i class="far fa-trash-alt"></i></a>';
			$body .= '</div>';
			$body .= '</div>';
		}
		$body .= '</div>';

		if ($count === 0)
		{
			$body = $this->admin_empty('far fa-comments', $this->lang('Aucun commentaire'));
		}
		else
		{
			$bulk_bar = '<div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-bottom:12px;">'
				.'<label style="display:flex;align-items:center;gap:6px;font-size:13px;margin:0;cursor:pointer;"><input type="checkbox" id="nf-bulk-all"> '.$this->lang('Tout sélectionner').'</label>'
				.'<button type="submit" name="bulk_delete" value="1" class="btn btn-sm btn-outline-danger" data-confirm="'.htmlspecialchars($this->lang('Supprimer les commentaires sélectionnés ?'), ENT_QUOTES).'"><i class="far fa-trash-alt"></i> '.$this->lang('Supprimer la sélection').'</button>'
				.'</div>';

			$body = '<form method="post" action="'.htmlspecialchars(url($this->url->request), ENT_QUOTES).'"><input type="hidden" name="_" value="'.$this->csrf_token().'">'.$bulk_bar.$body.'</form>'
				.'<script>(function(){var a=document.getElementById("nf-bulk-all");if(a){a.addEventListener("change",function(){document.querySelectorAll(".nf-bulk-cb").forEach(function(c){c.checked=a.checked;});});}})();</script>';
		}

		return $this->admin_card('far fa-comments', $this->lang('Commentaires'), $body, $count.' '.$this->lang('commentaire|commentaires', $count));
	}
}
