<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Widgets\Events\Models;

use NF\NeoFrag\Loadables\Model;

class Events extends Model
{
	public function check_event($event_id)
	{
		// Le texte vient de la table des traductions (m31) : la langue de la page, sinon le français.
		$this->db	->select('e.event_id', 'el.title', 'e.type_id', 'e.date', 'e.date_end', 'el.description', 'el.private_description', 'el.location', 'e.image_id', 'e.published', 't.type', 'm.webtv', 'm.website')
					->from('nf_events e')
					->join_lang('nf_events_lang el', 'event_id', 'e.event_id', NULL, 'LEFT')
					->join('nf_events_types t',        'e.type_id = t.type_id')
					->join('nf_events_participants p', 'e.event_id = p.event_id')
					->join('nf_events_matches m', 'e.event_id = m.event_id')
					->where('e.event_id', $event_id)
					->group_by('e.event_id');

		if (!$this->url->admin)
		{
			// Publication programmée, comme le module : une publish_date future masque l'événement.
			// Le widget ne lisait que `published`, et montrait l'événement avant son heure.
			$this->db->where('e.published', TRUE)->where('(e.publish_date IS NULL OR e.publish_date <= NOW())');
		}

		$event = $this->db->row();

		if ($event && $this->access('events', 'access_events_type', $event['type_id']))
		{
			return $event;
		}
	}

	public function get_events($filter = '', $filter_data = '')
	{
		$types = array_keys($this->module('events')->model('types')->get_types());

		// Le nom d'un type vit dans nf_events_types_lang depuis m32 : la meilleure traduction.
		$this->db	->select('e.event_id', 'el.title', 'e.type_id', 'tl.title as type_title', 't.type', 't.color', 't.icon', 'e.date', 'e.date_end', 'el.description', 'el.private_description', 'el.location', 'e.image_id', 'e.published', 'u.id as user_id', 'u.username', 'COUNT(mr.round_id) as nb_rounds', 'm.webtv', 'm.website')
					->from('nf_events e')
					->join_lang('nf_events_lang el', 'event_id', 'e.event_id', NULL, 'LEFT')
					->join('nf_events_types t',           'e.type_id = t.type_id')
					->join_lang('nf_events_types_lang tl', 'type_id', 't.type_id', NULL, 'LEFT')
					->join('nf_events_participants p',    'e.event_id = p.event_id')
					->join('nf_user u',                   'u.id = e.user_id')
					->join('nf_events_matches m',         'e.event_id = m.event_id')
					->join('nf_events_matches_rounds mr', 'e.event_id = mr.event_id');

		if (!empty($filter) && !empty($filter_data))
		{
			if ($filter == 'filter')
			{
				if ($filter_data == 'standards')
				{
					$this->db->where('t.type', 0);
				}
				else if ($filter_data == 'matches')
				{
					$this->db	->where('t.type', 1)
								->having('nb_rounds > 0');
				}
				else if ($filter_data == 'upcoming')
				{
					$this->db	->where('t.type', 1)
								->having('nb_rounds = 0');
				}
			}
			else if ($filter == 'type')
			{
				$this->db->where('t.type_id', $filter_data);
			}
			else if ($filter == 'team')
			{
				$this->db->where('m.team_id', $filter_data);
			}
		}
		else
		{
			$this->db->where('t.type_id', $types, 'OR', 'p.user_id', $this->user->id);
		}

		if (!$this->url->admin)
		{
			// Publication programmée, comme le module (cf. check_event).
			$this->db->where('e.published', TRUE)->where('(e.publish_date IS NULL OR e.publish_date <= NOW())');
		}

		return $this->db->group_by('e.event_id')
						->order_by('date DESC')
						->get();
	}

	public function get_types()
	{
		static $types;

		if ($types === NULL)
		{
			$types = [];

			// Le nom d'un type vit dans nf_events_types_lang depuis m32 : la meilleure traduction. Trié par t.title, le
			// bloc des types tombait en erreur SQL sur tout site en 1.2.50 (trouvé avant sa sortie, 2026-10-15).
			foreach (	$this->db	->select('t.*', 'tl.title', 'COUNT(e.event_id) as nb_events')
									->from('nf_events_types t')
									->join_lang('nf_events_types_lang tl', 'type_id', 't.type_id', NULL, 'LEFT')
									->join('nf_events e', 't.type_id = e.type_id')
									->where('e.published', TRUE)
									->where('(e.publish_date IS NULL OR e.publish_date <= NOW())')
									->order_by('tl.title')
									->group_by('t.type_id')
									->get()
			as $type)
			{
				if ($this->access('events', 'access_events_type', $type['type_id']))
				{
					$types[$type['type_id']] = $type;
				}
			}
		}

		return $types;
	}
}
