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
					.'<div>'.htmlspecialchars((string) ($n['title'])).'</div>'
					.'<small class="text-muted">'.htmlspecialchars((string) ($n['created_at'])).($n['actor'] ? ' · '.htmlspecialchars((string) ($n['actor'])) : '').'</small>'
					.'</a>';
			}
			$body .= '</div>';
		}

		return $this->panel()->title($this->lang('Notifications'), 'far fa-bell')->body($body);
	}
}
