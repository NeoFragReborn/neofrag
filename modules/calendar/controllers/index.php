<?php
declare(strict_types=1);
namespace NF\Modules\Calendar\Controllers;
use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;
use NF\Modules\Calendar\Calendar;

class Index extends Controller_Module
{
	public function index($events)
	{
		return $this->_render_list($events, $this->lang('À venir'), 'index');
	}

	public function _past($events)
	{
		return $this->_render_list($events, $this->lang('Passés'), 'past');
	}

	protected function _render_list($events, $tab_title, $tab)
	{
		$this->title($this->lang('Calendrier — %s', $tab_title))->icon('far fa-calendar')->breadcrumb();

		$tabs = '<ul class="nav nav-tabs mb-3">'
			.'<li class="nav-item"><a class="nav-link '.($tab === 'index' ? 'active' : '').'" href="'.url('calendar').'">'.$this->lang('À venir').'</a></li>'
			.'<li class="nav-item"><a class="nav-link '.($tab === 'past' ? 'active' : '').'" href="'.url('calendar/past').'">'.$this->lang('Passés').'</a></li>'
			.'<li class="nav-item ms-auto"><a class="nav-link" href="'.url('calendar/ical').'"><i class="fas fa-calendar-alt"></i> '.$this->lang('Export iCal').'</a></li>'
			.'</ul>';

		if (empty($events))
		{
			$empty = $tab === 'past' ? $this->lang('Aucun événement passé.') : $this->lang('Aucun événement à venir.');
			$body = $tabs.'<div class="alert alert-info text-center">'.$empty.'</div>';
		}
		else
		{
			$body = $tabs.'<div class="list-group">';
			foreach ($events as $e)
			{
				$slug = url_title($e['title']);
				$color = $e['color'] ? $e['color'] : '#03c1a2';
				$body .= '<a href="'.url('calendar/'.$e['id'].'/'.$slug).'" class="list-group-item list-group-item-action" style="border-left:4px solid '.htmlspecialchars((string) ($color)).'">';
				$body .= '<div class="d-flex justify-content-between mb-1">';
				$body .= '<strong>'.htmlspecialchars((string) ($e['title'])).'</strong>';
				$body .= '<small class="text-muted">'.Calendar::format_dt($e['start_at'], (bool)$e['all_day'], $e['end_at']).'</small>';
				$body .= '</div>';
				if (!empty($e['location']))
				{
					$body .= '<small class="text-muted"><i class="fas fa-map-marker-alt"></i> '.htmlspecialchars((string) ($e['location'])).'</small>';
				}
				$body .= '</a>';
			}
			$body .= '</div>';
		}

		return $this->panel()->title($this->lang('Calendrier'), 'far fa-calendar')->body($body);
	}

	public function _event($e)
	{
		$this->title($e['title'])->icon('far fa-calendar')->breadcrumb();

		$body = '<div class="mb-3"><h2>'.htmlspecialchars((string) ($e['title'])).'</h2>';
		$body .= '<p class="text-muted"><i class="far fa-clock"></i> '.Calendar::format_dt($e['start_at'], (bool)$e['all_day'], $e['end_at']).'</p>';
		if (!empty($e['location']))
		{
			$body .= '<p><i class="fas fa-map-marker-alt"></i> '.htmlspecialchars((string) ($e['location'])).'</p>';
		}
		if ($e['user_id'])
		{
			$body .= '<p class="text-muted"><i class="far fa-user"></i> '.$this->lang('Organisé par %s', $this->user->link($e['user_id'], $e['username'])).'</p>';
		}
		// Suivre l'événement : c'est l'abonnement qui décide qui recevra le rappel.
		/** @var \NF\Modules\Notifications\Notifications|null $notifications */
		$notifications = $this->module('notifications');

		if ($notifications && ($suivre = $notifications->follow_button('calendar-event', (int) $e['id'])))
		{
			$body .= '<p>'.$suivre.'</p>';
		}

		$body .= '</div>';

		if (!empty($e['description']))
		{
			$body .= '<div class="card"><div class="card-body">'.nl2br(htmlspecialchars((string) ($e['description']))).'</div></div>';
		}

		$body .= '<div class="mt-3"><a class="btn btn-secondary btn-sm" href="'.url('calendar').'"><i class="fas fa-arrow-left"></i> '.$this->lang('Retour au calendrier').'</a></div>';

		return $this->panel()->title($e['title'], 'far fa-calendar')->body($body);
	}

	public function _ical()
	{
		$events = NeoFrag()->db->select('*')->from('nf_calendar_events')->where('published', '1')->where('start_at >=', date('Y-m-d 00:00:00', strtotime('-1 year')))->order_by('start_at ASC')->get();

		$ical  = "BEGIN:VCALENDAR\r\n";
		$ical .= "VERSION:2.0\r\n";
		$ical .= "PRODID:-//".$this->config->nf_name."//Calendar//FR\r\n";
		$ical .= "CALSCALE:GREGORIAN\r\n";
		$ical .= "METHOD:PUBLISH\r\n";
		$ical .= "X-WR-CALNAME:".$this->config->nf_name."\r\n";

		foreach ($events as $e)
		{
			$start = strtotime($e['start_at']);
			$end   = $e['end_at'] ? strtotime($e['end_at']) : $start + 3600;

			$fmt_d = function($ts, $all_day) {
				return $all_day ? date('Ymd', $ts) : gmdate('Ymd\THis\Z', $ts);
			};

			$ical .= "BEGIN:VEVENT\r\n";
			$ical .= "UID:event-".$e['id']."@".parse_url(url('//'), PHP_URL_HOST)."\r\n";
			$ical .= "DTSTAMP:".gmdate('Ymd\THis\Z')."\r\n";
			$ical .= "DTSTART".($e['all_day'] ? ';VALUE=DATE' : '').":".$fmt_d($start, (bool)$e['all_day'])."\r\n";
			$ical .= "DTEND".($e['all_day'] ? ';VALUE=DATE' : '').":".$fmt_d($end, (bool)$e['all_day'])."\r\n";
			$ical .= "SUMMARY:".$this->_ical_escape($e['title'])."\r\n";
			if (!empty($e['description']))
			{
				$ical .= "DESCRIPTION:".$this->_ical_escape($e['description'])."\r\n";
			}
			if (!empty($e['location']))
			{
				$ical .= "LOCATION:".$this->_ical_escape($e['location'])."\r\n";
			}
			$ical .= "END:VEVENT\r\n";
		}

		$ical .= "END:VCALENDAR\r\n";

		header('Content-Type: text/calendar; charset=utf-8');
		header('Content-Disposition: attachment; filename="calendar.ics"');
		echo $ical;
		exit;
	}

	private function _ical_escape($str)
	{
		$str = str_replace(["\\", "\r\n", "\n", ",", ";"], ["\\\\", "\\n", "\\n", "\\,", "\\;"], $str);
		return $str;
	}
}
