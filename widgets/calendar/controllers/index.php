<?php
declare(strict_types=1);
namespace NF\Widgets\Calendar\Controllers;
use NF\NeoFrag\Loadables\Controllers\Widget as Controller_Widget;

class Index extends Controller_Widget
{
	public function index($config = [])
	{
		return $this->upcoming($config);
	}

	public function upcoming($config = [])
	{
		$count = max(1, min(20, (int)($config['count'] ?? 5)));

		$events = NeoFrag()->db	->select('id', 'title', 'start_at', 'all_day', 'color')
								->from('nf_calendar_events')
								->where('published', '1')
								->where('start_at >=', date('Y-m-d 00:00:00'))
								->order_by('start_at ASC')
								->limit($count)
								->get();

		$body = '';
		if (empty($events))
		{
			$body = '<div class="text-center text-muted py-2"><small>'.$this->lang('Aucun événement à venir').'</small></div>';
		}
		else
		{
			$body = '<ul class="list-unstyled mb-0">';
			foreach ($events as $e)
			{
				$ts = strtotime($e['start_at']);
				$color = $e['color'] ? $e['color'] : '#03c1a2';
				$body .= '<li class="py-1 border-bottom" style="border-left:3px solid '.htmlspecialchars((string) ($color)).';padding-left:0.5rem">';
				$body .= '<a href="'.url('calendar/'.$e['id'].'/'.url_title($e['title'])).'"><i class="far fa-calendar me-1"></i>'.htmlspecialchars((string) ($e['title'])).'</a>';
				$body .= '<br><small class="text-muted">'.($e['all_day'] ? date('j M Y', $ts) : date('j M Y H:i', $ts)).'</small>';
				$body .= '</li>';
			}
			$body .= '</ul>';
		}

		if (($config['display_panel'] ?? 'oui') === 'non')
		{
			return $body;
		}

		return $this->panel()
					->heading($this->lang('Prochains événements'), 'far fa-calendar')
					->body($body)
					->footer('<a href="'.url('calendar').'">'.icon('far fa-arrow-alt-circle-right').' '.$this->lang('Voir le calendrier').'</a>', 'right');
	}
}
