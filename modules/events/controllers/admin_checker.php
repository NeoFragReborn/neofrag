<?php
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\Events\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module_Checker;

class Admin_Checker extends Module_Checker
{
	public function index($page = '')
	{
		return [$this->module->pagination->get_data($this->model()->get_events(), $page)];
	}

	public function _standards($page = '')
	{
		return [$this->module->pagination->get_data($this->model()->get_events('filter', 'standards'), $page)];
	}

	public function _matches($page = '')
	{
		return [$this->module->pagination->get_data($this->model()->get_events('filter', 'matches'), $page)];
	}

	public function upcoming($page = '')
	{
		return [$this->module->pagination->get_data($this->model()->get_events('filter', 'upcoming'), $page)];
	}

	public function _event($event_id, $title)
	{
		if ($event = $this->model()->check_event($event_id, $title))
		{
			return $event;
		}
	}

	public function add()
	{
		if (!$this->is_authorized('add_event'))
		{
			$this->error->unauthorized();
		}

		return [];
	}

	public function _edit($event_id, $title)
	{
		if (!$this->is_authorized('modify_event'))
		{
			$this->error->unauthorized();
		}

		if ($event = $this->model()->check_event($event_id, $title))
		{
			// Array indexé explicite mappé sur la signature de _edit (publish_date en dernier),
			// les colonnes de rencontre (mode_id/webtv/website/mode_title) n'y figurent pas.
			return [
				$event['event_id'],
				$event['title'],
				$event['type_id'],
				$event['date'],
				$event['date_end'],
				$event['description'],
				$event['private_description'],
				$event['location'],
				$event['image_id'],
				$event['published'],
				$event['type'],
				$event['publish_date'] ?? ''
			];
		}
	}

	public function _delete($event_id, $title)
	{
		if (!$this->is_authorized('delete_event'))
		{
			$this->error->unauthorized();
		}

		$this->ajax();

		if ($event = $this->model()->check_event($event_id, $title))
		{
			return [$event_id, $event['title']];
		}
	}

	public function _types_add()
	{
		if (!$this->is_authorized('add_events_type'))
		{
			$this->error->unauthorized();
		}

		return [];
	}

	public function _types_edit($type_id, $name)
	{
		if (!$this->is_authorized('modify_events_type'))
		{
			$this->error->unauthorized();
		}

		if ($type = $this->model('types')->check_type($type_id, $name))
		{
			return $type;
		}
	}

	public function _types_delete($type_id, $name)
	{
		if (!$this->is_authorized('delete_events_type'))
		{
			$this->error->unauthorized();
		}

		$this->ajax();

		if ($type = $this->model('types')->check_type($type_id, $name))
		{
			return [$type_id, $type['title']];
		}
	}

	public function _round_delete($event_id, $title, $round_id)
	{
		if (!$this->is_authorized('modify_event'))
		{
			$this->error->unauthorized();
		}

		$this->ajax();

		if ($this->model()->check_event($event_id, $title) && $this->db->select('round_id')->from('nf_events_matches_rounds')->where('round_id', $round_id)->where('event_id', $event_id)->row())
		{
			return [$round_id];
		}
	}

	public function _opponents()
	{
		if (!$this->is_authorized('modify_event'))
		{
			$this->error->unauthorized();
		}

		return [];
	}

	public function _opponents_add()
	{
		if (!$this->is_authorized('modify_event'))
		{
			$this->error->unauthorized();
		}

		return [];
	}

	public function _opponents_edit($opponent_id, $name)
	{
		if (!$this->is_authorized('modify_event'))
		{
			$this->error->unauthorized();
		}

		if ($opponent = $this->model('matches')->check_opponent($opponent_id, $name))
		{
			return $opponent;
		}
	}

	public function _opponents_delete($opponent_id, $name)
	{
		if (!$this->is_authorized('delete_event'))
		{
			$this->error->unauthorized();
		}

		$this->ajax();

		if ($opponent = $this->model('matches')->check_opponent($opponent_id, $name))
		{
			return [$opponent_id, $opponent['title']];
		}
	}
}
