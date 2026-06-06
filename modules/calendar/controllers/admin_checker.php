<?php
namespace NF\Modules\Calendar\Controllers;
use NF\NeoFrag\Loadables\Controllers\Module_Checker;

class Admin_Checker extends Module_Checker
{
	public function index()
	{
		$events = NeoFrag()->db	->select('e.*', 'u.username')
								->from('nf_calendar_events e')
								->join('nf_user u', 'e.user_id = u.id', 'LEFT')
								->order_by('e.start_at DESC')
								->get();
		return [$events];
	}

	public function _add() { return [NULL]; }
	public function _edit($id, $title)
	{
		$e = NeoFrag()->db->select('*')->from('nf_calendar_events')->where('id', $id)->row();
		return $e ? [$e] : NULL;
	}
	public function _delete($id, $title)
	{
		$e = NeoFrag()->db->select('id', 'title')->from('nf_calendar_events')->where('id', $id)->row();
		return $e ? [$e] : NULL;
	}
}
