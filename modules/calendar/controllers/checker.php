<?php
namespace NF\Modules\Calendar\Controllers;
use NF\NeoFrag\Loadables\Controllers\Module_Checker;

class Checker extends Module_Checker
{
	public function index()
	{
		$events = NeoFrag()->db	->select('e.*', 'u.username', 'u.id AS user_id')
								->from('nf_calendar_events e')
								->join('nf_user u', 'e.user_id = u.id', 'LEFT')
								->where('e.published', '1')
								->where('e.start_at >=', date('Y-m-d 00:00:00'))
								->order_by('e.start_at ASC')
								->get();
		return [$events];
	}

	public function _past()
	{
		$events = NeoFrag()->db	->select('e.*', 'u.username', 'u.id AS user_id')
								->from('nf_calendar_events e')
								->join('nf_user u', 'e.user_id = u.id', 'LEFT')
								->where('e.published', '1')
								->where('e.start_at <', date('Y-m-d 00:00:00'))
								->order_by('e.start_at DESC')
								->limit(50)
								->get();
		return [$events];
	}

	public function _event($event_id, $title)
	{
		$e = NeoFrag()->db	->select('e.*', 'u.username', 'u.id AS user_id')
							->from('nf_calendar_events e')
							->join('nf_user u', 'e.user_id = u.id', 'LEFT')
							->where('e.id', $event_id)
							->where('e.published', '1')
							->row();
		return $e ? [$e] : NULL;
	}

	public function _ical()
	{
		return [];
	}
}
