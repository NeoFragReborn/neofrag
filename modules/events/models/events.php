<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\Events\Models;

use NF\NeoFrag\Loadables\Model;

class Events extends Model
{
	/**
	 * Les seules colonnes qu'une édition « toute la série » recopie sur chaque occurrence.
	 *
	 * Constante, et non liste locale, pour qu'un test puisse l'inspecter : la propriété qui compte
	 * ici — *aucune colonne de date n'y figure* — est de celles dont la violation ne se voit pas à
	 * la relecture et ne se rattrape pas après coup. Propager `date` ferait tenir les douze séances
	 * d'une série le même soir, en perdant définitivement les dates d'origine.
	 *
	 * @var list<string>
	 */
	const SERIES_FIELDS = ['title', 'type_id', 'description', 'private_description', 'location', 'image_id', 'published'];

	public function check_event($event_id, $title)
	{
		$this->db	->select('e.event_id', 'e.title', 'e.type_id', 'e.date', 'e.date_end', 'e.description', 'e.private_description', 'e.location', 'e.image_id', 'e.published', 'e.publish_date', 'e.series_id', 't.type', 'm.mode_id', 'm.webtv', 'm.website', 'gm.title as mode_title')
					->from('nf_events e')
					->join('nf_events_types t',        'e.type_id = t.type_id')
					->join('nf_events_participants p', 'e.event_id = p.event_id', 'LEFT')
					->join('nf_events_matches m', 'e.event_id = m.event_id', 'LEFT')
					->join('nf_games_modes gm', 'm.mode_id = gm.mode_id', 'LEFT')
					->where('e.event_id', $event_id)
					->group_by('e.event_id');

		if (!$this->url->admin)
		{
			// Publication programmée : une publish_date future masque l'événement (NULL = visible dès publié).
			$this->db->where('e.published', TRUE)->where('(e.publish_date IS NULL OR e.publish_date <= NOW())');
		}

		$event = $this->db->row();

		if ($event && $title == url_title($event['title']) && $this->access('events', 'access_events_type', $event['type_id']))
		{
			return $event;
		}
	}

	public function get_events($filter = '', $filter_data = '')
	{
		$types = array_keys($this->model('types')->get_types());

		$this->db	->select('e.event_id', 'e.title', 'e.type_id', 't.title as type_title', 't.type', 't.color', 't.icon', 'e.date', 'e.date_end', 'e.description', 'e.private_description', 'e.location', 'e.image_id', 'e.published', 'e.publish_date', 'e.series_id', 'u.id as user_id', 'u.username', 'COUNT(mr.round_id) as nb_rounds', 'm.webtv', 'm.website')
					->from('nf_events e')
					->join('nf_events_types t',           'e.type_id = t.type_id')
					->join('nf_events_participants p',    'e.event_id = p.event_id', 'LEFT')
					->join('nf_user u',                   'u.id = e.user_id', 'LEFT')
					->join('nf_events_matches m',         'e.event_id = m.event_id', 'LEFT')
					->join('nf_events_matches_rounds mr', 'e.event_id = mr.event_id', 'LEFT');

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
			// Publication programmée : une publish_date future masque l'événement (NULL = visible dès publié).
			$this->db->where('e.published', TRUE)->where('(e.publish_date IS NULL OR e.publish_date <= NOW())');
		}

		return $this->db->group_by('e.event_id')
						->order_by('date DESC')
						->get();
	}

	public function add($title, $type_id, $date, $date_end, $description, $private_description, $location, $image_id, $published, $publish_date = '')
	{
		return $this->db->insert('nf_events', [
			'user_id'             => $this->user->id,
			'title'               => $title,
			'type_id'             => $type_id,
			'date'                => $date,
			'date_end'            => $date_end ?: NULL,
			'description'         => $description,
			'private_description' => $private_description,
			'location'            => $location,
			'image_id'            => $image_id,
			'published'           => $published,
			'publish_date'        => $publish_date ?: NULL
		]);
	}

	public function edit($event_id, $title, $type_id, $date, $date_end, $description, $private_description, $location, $image_id, $published, $publish_date = '')
	{
		$this->db	->where('event_id', $event_id)
					->update('nf_events', [
						'title'               => $title,
						'type_id'             => $type_id,
						'date'                => $date,
						'date_end'            => $date_end ?: NULL,
						'description'         => $description,
						'private_description' => $private_description,
						'location'            => $location,
						'image_id'            => $image_id,
						'published'           => $published,
						'publish_date'        => $publish_date ?: NULL
					]);
	}

	public function delete($event_id)
	{
		NeoFrag()->model2('file', $this->db->select('image_id')->from('nf_events')->where('event_id', $event_id)->row())->delete();

		if ($comments = $this->module('comments'))
		{
			$comments->delete('events', $event_id);
		}

		$this->db	->where('event_id', $event_id)
					->delete('nf_events');
	}

	/** Relie une liste d'occurrences en série (series_id = la 1re). No-op si < 2 occurrences. */
	public function set_series(array $event_ids)
	{
		if (count($event_ids) < 2)
		{
			return;
		}

		$this->db	->where('event_id', array_map('intval', $event_ids))
					->update('nf_events', ['series_id' => (int) $event_ids[0]]);
	}

	/** Supprime toutes les occurrences d'une série (réutilise delete() : image + commentaires + ligne). */
	public function delete_series($series_id)
	{
		foreach ($this->db->select('event_id')->from('nf_events')->where('series_id', (int) $series_id)->get() as $event_id)
		{
			$this->delete((int) $event_id);
		}
	}

	/** Nombre d'occurrences d'une série. 0 si l'identifiant est vide — un événement isolé n'en a pas. */
	public function count_series($series_id)
	{
		return $series_id ? (int) $this->db->from('nf_events')->where('series_id', (int) $series_id)->count() : 0;
	}

	/**
	 * Applique à TOUTES les occurrences d'une série ce qui ne dépend pas de la date.
	 *
	 * Le filtrage des champs n'est pas une précaution de style : les DATES doivent rester propres à
	 * chaque occurrence, puisque c'est la seule chose qui distingue une séance de la suivante. Les
	 * propager ferait tenir les douze séances d'une série le même soir — et sans retour possible,
	 * les dates d'origine étant alors perdues. `publish_date` suit la même règle, pour la même
	 * raison.
	 *
	 * @param  array<string,mixed> $champs colonnes candidates ; celles hors liste sont ignorées
	 * @return int nombre d'occurrences touchées
	 */
	public function edit_series($series_id, array $champs)
	{
		if (!($series_id && ($champs = array_intersect_key($champs, array_flip(self::SERIES_FIELDS)))))
		{
			return 0;
		}

		$this->db->where('series_id', (int) $series_id)->update('nf_events', $champs);

		return $this->count_series($series_id);
	}

	/**
	 * Rappels : notifie les participants (tout sauf « Absent ») des événements publiés qui débutent dans
	 * la fenêtre `events_reminder_hours` (défaut 24 h ; 0 = désactivé). Appelé par l'endpoint cron.
	 * Idempotent : `reminder_sent_at` est posé atomiquement avant l'envoi → un seul rappel par événement.
	 * @return int événements rappelés
	 */
	public function send_due_reminders($limit = 50)
	{
		$raw   = $this->config->events_reminder_hours;
		$hours = ($raw === FALSE || $raw === NULL || $raw === '') ? 24 : (int)$raw; // FALSE = setting absent (cf. Config::__get)

		if ($hours <= 0)
		{
			return 0;
		}

		$now      = date('Y-m-d H:i:s');
		$deadline = date('Y-m-d H:i:s', strtotime('+'.$hours.' hours'));
		$count    = 0;

		foreach ($this->db	->select('event_id', 'title')
							->from('nf_events')
							->where('published', '1')
							->where('reminder_sent_at', NULL)
							->where('date >', $now)
							->where('date <=', $deadline)
							->order_by('date ASC')
							->limit($limit)
							->get(FALSE) as $event)
		{
			// Claim atomique : seul le 1er passage qui pose reminder_sent_at envoie les notifs.
			$claimed = (int)$this->db	->where('event_id', (int)$event['event_id'])
										->where('reminder_sent_at', NULL)
										->update('nf_events', ['reminder_sent_at' => $now]);

			if ($claimed < 1)
			{
				continue;
			}

			$this->_remind_participants((int)$event['event_id'], (string)$event['title']);
			$count++;
		}

		return $count;
	}

	// Notif cloche à chaque participant encore concerné. push_unique = anti-doublon.
	protected function _remind_participants($event_id, $title)
	{
		if (!($notifications = $this->module('notifications')))
		{
			return;
		}

		$url = 'events/'.$event_id.'/'.url_title($title);

		foreach ($this->reminder_recipients($event_id) as $user_id)
		{
			$notifications->push_unique((int)$user_id, 'event-reminder', $this->lang('Rappel : l\'événement « %s » commence bientôt', $title), $url, NULL);
		}
	}

	/** Destinataires d'un rappel : tous les participants SAUF les absents (status 2). */
	public function reminder_recipients($event_id)
	{
		return $this->db	->select('user_id')
							->from('nf_events_participants')
							->where('event_id', $event_id)
							->where('status <>', 2)
							->get();
	}
}
