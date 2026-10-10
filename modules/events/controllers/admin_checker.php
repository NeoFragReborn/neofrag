<?php
declare(strict_types=1);
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
		// Les matchs demandent les modules Jeux et Équipes (m17).
		if (!\NF\Modules\Events\Models\Matches::possibles())
		{
			return;
		}

		return [$this->module->pagination->get_data($this->model()->get_events('filter', 'matches'), $page)];
	}

	public function upcoming($page = '')
	{
		// Les matchs demandent les modules Jeux et Équipes (m17).
		if (!\NF\Modules\Events\Models\Matches::possibles())
		{
			return;
		}

		return [$this->module->pagination->get_data($this->model()->get_events('filter', 'upcoming'), $page)];
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
				$event['publish_date'] ?? '',
				$event['series_id'] ?? NULL
			];
		}
	}

	/**
	 * Suppression de TOUTE une série récurrente.
	 *
	 * Route distincte de `_delete`, et non une case à cocher ajoutée à sa confirmation : le
	 * formulaire de confirmation de suppression n'accepte QUE le champ `delete` (cf. Form::is_valid),
	 * et une case en plus ferait échouer la validation en silence. Deux gestes, deux routes, deux
	 * confirmations qui disent chacune ce qu'elles emportent.
	 *
	 * Le droit exigé est celui de la suppression d'un événement : supprimer douze occurrences reste
	 * la même action, répétée.
	 */
	public function _delete_series($event_id, $title)
	{
		if (!$this->is_authorized('delete_event'))
		{
			$this->error->unauthorized();
		}

		$this->ajax();

		if (($event = $this->model()->check_event($event_id, $title)) && !empty($event['series_id']))
		{
			return [$event_id, $event['title'], (int) $event['series_id']];
		}

		// Événement hors série : il n'y a pas de série à supprimer. On ne retombe pas sur la
		// suppression simple — l'opérateur a demandé autre chose que ce qu'on ferait.
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
		if (!\NF\Modules\Events\Models\Matches::possibles())
		{
			return;
		}

		if (!$this->is_authorized('modify_event'))
		{
			$this->error->unauthorized();
		}

		return [];
	}

	public function _opponents_add()
	{
		if (!\NF\Modules\Events\Models\Matches::possibles())
		{
			return;
		}

		if (!$this->is_authorized('modify_event'))
		{
			$this->error->unauthorized();
		}

		return [];
	}

	public function _opponents_edit($opponent_id, $name)
	{
		if (!\NF\Modules\Events\Models\Matches::possibles())
		{
			return;
		}

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
		if (!\NF\Modules\Events\Models\Matches::possibles())
		{
			return;
		}

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
