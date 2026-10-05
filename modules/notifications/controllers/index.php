<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * Page frontend listant toutes les notifications du user courant (marquées lues à l'ouverture).
 */

namespace NF\Modules\Notifications\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Index extends Controller_Module
{
	public function index($page = '')
	{
		$this	->title($this->lang('Notifications'))
				->icon('far fa-bell')
				->breadcrumb();

		$items = $this->module->recent(50);
		$this->module->mark_all_read();

		if (!$items)
		{
			$body = '<div class="alert alert-info text-center">'.$this->lang('Aucune notification.').'</div>';
		}
		else
		{
			$body = '<div class="list-group">';
			foreach ($items as $n)
			{
				$body .= '<a class="list-group-item list-group-item-action'.(empty($n['is_read']) ? ' nf-notif-unread' : '').'" href="'.url($n['url'] ?: 'notifications').'">'
					.'<div>'.nf_texte($n['title']).'</div>'
					.'<small class="text-muted">'.nf_date_heure($n['created_at']).($n['actor'] ? ' · '.nf_texte($n['actor']) : '').'</small>'
					.'</a>';
			}
			$body .= '</div>';
		}

		// Hors du cadre de l'espace membre pour l'instant : les thèmes donnent à cette page leur colonne de
		// droite, où le cadre se trouvait coincé. Elle le rejoindra sous user/ avec ses préférences (étape A4).
		return $this->panel()->title($this->lang('Notifications'), 'far fa-bell')->body($body);
	}
}
