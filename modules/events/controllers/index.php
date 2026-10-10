<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\Events\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

// couplage(games): les cartes des manches, lues seulement quand l'événement a un match (Matches::possibles(), m17)
class Index extends Controller_Module
{
	public function index($events)
	{
		// La liste est faite des événements, qui n'ont pas de langue à eux : sa canonique est dans la langue
		// première du site (et celle de ses filtres, qui passent tous par ici).
		nf_seo_sans_langue();

		$panels = $this->_filters();

		$types = $this->model('types')->get_types();

		foreach ($events as $event)
		{
			// La vérification d'accès passe EN PREMIER.
			//
			// `get_types()` ne rend que les types visibles par le visiteur : un type auquel il n'a
			// pas droit n'y figure pas du tout. Le code déréférençait pourtant `$types[$type_id]`
			// avant de vérifier quoi que ce soit — d'où, à chaque affichage de la liste,
			// `Undefined array key 1` puis `Trying to access array offset on null`, trois fois par
			// événement. Constaté le 2026-09-16 dans le journal du site de démonstration, où les
			// types créés en SQL n'avaient reçu aucune permission.
			//
			// Le second garde-fou (`$type` absent) couvre le cas inverse : un droit accordé sur un
			// type que `get_types()` ne rend pas. L'événement est alors ignoré, pas rendu à moitié.
			if (!$this->access('events', 'access_events_type', $event['type_id']))
			{
				continue;
			}

			if (($type = $types[$event['type_id']] ?? NULL) === NULL)
			{
				continue;
			}

			$icon = $type['type'] == 1 ? 'fas fa-crosshairs' : 'far fa-calendar';//Matches

			$data = [
				'type'         => $type,
				'participants' => $this->model('participants')->count_participants($event['event_id'])
			];

			if ($data['type']['type'] == 1 && ($match = $this->model('matches')->get_match_info($event['event_id'])))//Matches
			{
				$data['match'] = $match;
			}

			$panels->append($this	->panel()
									->heading('<a href="'.url('events/'.$event['event_id'].'/'.url_title($event['title'])).'">'.$event['title'].'</a>'.(!empty($data['match']) ? '<div class="float-end">'.($data['match']['game']['icon_id'] ? '<img src="'.NeoFrag()->model2('file', $data['match']['game']['icon_id'])->path().'" class="img-icon" alt="" />' : icon('fas fa-gamepad')).' '.$data['match']['game']['title'].'</div>' : ''), $icon)
									->body($this->view('event', array_merge($event, $data)), FALSE));
		}

		if (!$events)
		{
			$panels->append($this	->panel()
									->heading()
									->body('<div class="text-center">'.$this->lang('Aucun événement n\'a été publié pour le moment').'</div>')
									->color('info'));
		}
		else
		{
			$panels->append($this->module->pagination->panel());
		}

		return $panels;
	}

	public function standards($events)
	{
		return $this->index($events);
	}

	public function matches($events)
	{
		return $this->index($events);
	}

	public function upcoming($events)
	{
		return $this->index($events);
	}

	public function _type($events)
	{
		return $this->index($events);
	}

	public function _team($events)
	{
		return $this->index($events);
	}

	public function _filters()
	{
		$type = '';

		if (isset($this->url->segments[1]) && $this->url->segments[1] == 'type')
		{
			$type = $this->model('types')->check_type($this->url->segments[2], $this->url->segments[3]);
		}

		return $this->array
					->append($this	->panel()
									->body($this->view('filters', [
										'type' => $type
									]))
					);
	}

	public function _event($event_id, $title, $type_id, $date, $date_end, $description, $private_description, $location, $image_id, $published, $type, $mode_id, $webtv, $website, $mode_title)
	{
		// Un événement n'a pas de langue à lui : sa canonique est dans la langue première du site.
		nf_seo_sans_langue();

		$this	->title($title)
				->breadcrumb($title)
				->table()
				->add_columns([
					[
						'content' => function($data){
							return $this->module('user')->model2('user', $data['user_id'])->avatar();
						},
						'size'    => TRUE
					],
					[
						'content' => function($data){
							// La présence suit le choix du membre (Models\User::montre('statut')), comme son profil : elle se
							// montrait à tous ; et ces mots s'écrivaient en français dans toutes les langues (2026-10-09).
							$membre   = $this->module('user')->model2('user', $data['user_id']);
							$presence = $membre instanceof \NF\NeoFrag\Models\User && $membre->montre('statut')
								? ' · '.icon('fas fa-circle '.($data['online'] ? 'text-green' : 'text-gray')).' '.($data['online'] ? $this->lang('En ligne') : $this->lang('Hors ligne'))
								: '';

							return '<div>'.$this->user->link($data['user_id'], $data['username']).'</div><small>'.($data['admin'] ? $this->lang('Administrateur') : $this->lang('Membre')).$presence.'</small>';
						}
					],
					[
						'align'   => 'right',
						'content' => function($data) use ($event_id, $title){
							return $data['user_id'] == $this->user->id ? $this->model('participants')->buttons_status($event_id, $title, $data['status']) : $this->model('participants')->label_status($data['status']);
						}
					]
				])
				->add_columns_if($this->access->effective_admin(), [[
						'content' => function($data) use ($event_id, $title){
							return $this->button_delete('events/participant/delete/'.$event_id.'/'.url_title($title).'/'.$data['user_id']);
						},
						'size'    => TRUE
					]
				])
				->data($this->model('participants')->get_participants($event_id))
				->no_data($this->lang('Aucun participant pour cet événement'));

		$match = $type == 1 ? $this->model('matches')->get_match_info($event_id) : NULL;

		// Les manches d'un match, et leurs cartes : seulement quand il y a un match (m17 — sans le module Jeux, la table
		// des cartes est absente).
		$rounds = $match ? $this->db	->select('r.round_id', 'm.image_id', 'm.title', 'r.score1', 'r.score2')
										->from('nf_events_matches_rounds r')
										->join('nf_games_maps m', 'm.map_id = r.map_id')
										->where('r.event_id', $event_id)
										->order_by('r.round_id')
										->get() : [];

		if ($this->access->effective_admin())
		{
			$this->js('participants');

			$participants = $this->db	->select('user_id')
										->from('nf_events_participants')
										->where('event_id', $event_id)
										->get();

			$users = [];

			foreach ($this->db->select('id', 'username')->from('nf_user')->where_if($participants, 'id NOT', $participants)->where('deleted', FALSE)->get() as $user)
			{
				if ($this->access('events', 'access_events_type', $type_id, NULL, $user['id']))
				{
					$users[$user['id']] = $user['username'];
				}
			}

			array_natsort($users);

			$this	->form()
					->add_rules([
						'users' => [
							'type'   => 'checkbox',
							'values' => $users,
							'rules'  => 'required'
						]
					]);

			if ($this->form()->is_valid($post))
			{
				$this->model('participants')->invite($event_id, $title, array_unique($post['users']));

				notify($this->lang('Invitations envoyées'));

				refresh();
			}

			$modal = $this	->modal('Inviter des membres', 'fas fa-user-plus')
							->body($this->view('participants', [
								'users'   => $users,
								'form_id' => $this->form()->token()
							]))
							->submit($this->lang('Inviter'))
							->cancel()
							->set_id('c2dac90bb0731401a293d27ee036757a')
							->callback(function(){});
		}

		return $this->_filters()
					->append($this	->panel()
									->heading('<a href="'.url('events/'.$event_id.'/'.url_title($title)).'">'.$title.'</a>'.(!empty($match) ? '<div class="float-end ms-auto ps-3">'.($match['game']['icon_id'] ? '<img src="'.NeoFrag()->model2('file', $match['game']['icon_id'])->path().'" class="img-icon" alt="" />' : icon('fas fa-gamepad')).' '.$match['game']['title'].'</div>' : ''), $type == 1 ? 'fas fa-crosshairs' : 'far fa-calendar')
									->body($this->view('event', [
										'event_id'             => $event_id,
										'title'                => $title,
										'date'                 => $date,
										'date_end'             => $date_end,
										'description'          => $description,
										'private_description'  => $private_description,
										'location'             => $location,
										'image_id'             => $image_id,
										'match'                => $match,
										'webtv'                => $webtv,
										'website'              => $website,
										'mode'                 => $mode_title,
										'rounds'               => $rounds,
										'type'                 => $this->model('types')->get_types()[$type_id],
										'participants'         => $this->model('participants')->count_participants($event_id),
										'list_participants'    => $this->model('participants')->get_participants($event_id),
										'show_details'         => TRUE
									]), FALSE)
					)
					->append_if($this->user(), $this	->panel()
														->heading('<a name="participants"></a>'.$this->lang('Participants').(isset($modal) ? '<div class="float-end ms-auto ps-3">'.$this->button()->title('Invitations')->icon('fas fa-user-plus')->modal($modal).'</div>' : ''), 'fas fa-users')
														->body($this->table()->display())
					)
					->append_if(($comments = $this->module('comments')) && $comments->is_enabled(), function() use (&$comments, $event_id){
						return $comments('events', $event_id);
					})
					->append($this->button_back());
	}

	public function _participant_add($event_id, $title, $status)
	{
		$this->check_csrf('events/'.$event_id.'/'.$title);

		$this->db	->where('event_id', $event_id)
					->where('user_id', $this->user->id)
					->update('nf_events_participants', [
						'status' => $status
					]);

		notify($this->lang('Disponibilité ajoutée'));

		redirect('events/'.$event_id.'/'.$title.'#participants');
	}

	public function _participant_delete($event_id, $user_id)
	{
		$this	->title($this->lang('Suppression participant'))
				->form()
				->confirm_deletion($this->lang('Confirmation de suppression'), $this->lang('Êtes-vous sûr(e) de vouloir supprimer cet invité ?'));

		if ($this->form()->is_valid())
		{
			$this->db	->where('event_id', $event_id)
						->where('user_id', $user_id)
						->delete('nf_events_participants');

			return 'OK';
		}

		return $this->form()->display();
	}
}
