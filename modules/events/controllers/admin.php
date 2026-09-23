<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\Events\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Admin extends Controller_Module
{
	public function index($events)
	{
		$this	->css('admin')
				->js('fullcalendar.min')
				->js('fullcalendar-locales.min')
				->js('events');

		/**
		 * Les actions de la page vivent dans sa BARRE D'OUTILS, comme sur les autres ecrans.
		 *
		 * Deux défauts tenaient ici : la création d'un événement était proposée DEUX FOIS, dans
		 * l'en-tête du calendrier (« Créer un événement ») et dans celui de la liste (« Nouvel
		 * événement ») — deux boutons, deux libellés, une seule action ; et « Gérer les
		 * adversaires » était un bouton posé APRÈS la carte des types, hors de tout conteneur, qui
		 * flottait entre deux blocs sans alignement.
		 */
		if ($this->is_authorized('add_event'))
		{
			$this->add_action($this->button($this->lang('Nouvel événement'), 'fas fa-plus', 'primary')->url('admin/events/add'));
		}

		if ($this->is_authorized('modify_event'))
		{
			$this->add_action($this->button($this->lang('Adversaires'), 'fas fa-shield-alt', 'secondary')->url('admin/events/opponents'));
		}

		// Stat cards
		$now            = time();
		$week_end       = strtotime('+7 days');
		$total_events   = count($events);
		$published_count = $upcoming = $week_count = 0;
		foreach ($events as $e) {
			if (!empty($e['published'])) $published_count++;
			$ts = is_numeric($e['date']) ? (int)$e['date'] : strtotime($e['date']);
			if ($ts > $now) {
				$upcoming++;
				if ($ts <= $week_end) $week_count++;
			}
		}
		$types_count = 0;
		try { $types_count = (int)$this->db->from('nf_events_types')->count(); } catch (\Throwable $ex) {}

		$cards  = '<div class="nf-stats-grid">';
		$cards .= '<div class="nf-stat-card"><div class="nf-stat-label"><i class="fas fa-calendar"></i> '.$this->lang('Total événements').'</div><div class="nf-stat-value">'.number_format($total_events, 0, ',', ' ').'</div>';
		$cards .= '<div class="nf-stat-trend">'.$published_count.' '.$this->lang('publié|publiés', $published_count).'</div></div>';
		$cards .= '<div class="nf-stat-card"><div class="nf-stat-label"><i class="fas fa-hourglass-half"></i> '.$this->lang('À venir').'</div><div class="nf-stat-value">'.number_format($upcoming, 0, ',', ' ').'</div>';
		$cards .= '<div class="nf-stat-trend '.($upcoming > 0 ? 'up' : '').'">'.$this->lang('Événements futurs').'</div></div>';
		$cards .= '<div class="nf-stat-card"><div class="nf-stat-label"><i class="fas fa-bolt"></i> '.$this->lang('Cette semaine').'</div><div class="nf-stat-value">'.number_format($week_count, 0, ',', ' ').'</div>';
		$cards .= '<div class="nf-stat-trend '.($week_count > 0 ? 'warn' : '').'">'.$this->lang('Dans les 7 prochains jours').'</div></div>';
		$cards .= '<div class="nf-stat-card"><div class="nf-stat-label"><i class="far fa-bookmark"></i> '.$this->lang('Types').'</div><div class="nf-stat-value">'.number_format($types_count, 0, ',', ' ').'</div>';
		$cards .= '<div class="nf-stat-trend">'.$this->lang('Types d\'événement configurés').'</div></div>';
		$cards .= '</div>';

		// Calendar (will go in left col)
		$calendar_card = '<div class="card events-calendar-card">'
			.'<div class="nf-card-header">'
			.'<span><i class="far fa-calendar-alt"></i> '.$this->lang('Calendrier').'</span>'
			.'</div>'
			.'<div class="events-calendar-body"><div id="calendar"></div></div>'
			.'</div>';

		$types = $this	->table()
						->add_columns([
							[
								'content' => function($data){
									return $this->label($data['title'], $data['icon'], $data['color'], 'admin/events/types/'.$data['type_id'].'/'.url_title($data['title']));
								}
							],
							[
								'content' => [
									function($data){
										return $this->access->effective_admin() ? $this->button_access($data['type_id'], 'type') : NULL;
									},
									function($data){
										return $this->is_authorized('modify_events_type') ? $this->button_update('admin/events/types/'.$data['type_id'].'/'.url_title($data['title'])) : NULL;
									},
									function($data){
										return $this->is_authorized('delete_events_type') ? $this->button_delete('admin/events/types/delete/'.$data['type_id'].'/'.url_title($data['title'])) : NULL;
									}
								],
								'size'    => TRUE
							]
						])
						->pagination(FALSE)
						->data($this->model('types')->get_types())
						->no_data($this->lang('Aucun type'))
						->display();

		$events = $this	->table()
						->add_columns([
							[
								'content' => function($data){
									if (!$data['published'])
									{
										return '<i class="far fa-circle" data-bs-toggle="tooltip" title="'.$this->lang('En attente de publication').'" style="color: #535353;"></i>';
									}

									if (!empty($data['publish_date']) && strtotime($data['publish_date']) > time())
									{
										return '<i class="fas fa-calendar-alt" data-bs-toggle="tooltip" title="'.$this->lang('Programmé le %s', timetostr($this->lang('d/m/Y H:i'), $data['publish_date'])).'" style="color: #e0a32e;"></i>';
									}

									return '<i class="fas fa-circle" data-bs-toggle="tooltip" title="'.$this->lang('Publié').'" style="color: #7bbb17;"></i>';
								},
								'sort'    => function($data){
									return $data['published'];
								},
								'size'    => TRUE
							],
							[
								'title'   => $this->lang('Type'),
								'content' => function($data){
									return $this->label($data['type_title'], $data['icon'], $data['color'], 'admin/events/types/'.$data['type_id'].'/'.url_title($data['type_title']));
								},
								'sort'    => function($data){
									return $data['type_title'];
								},
								'search'  => function($data){
									return $data['type_title'];
								}
							],
							[
								'title'   => $this->lang('Titre'),
								'content' => function($data){
									$series = !empty($data['series_id']) ? ' <span class="badge text-bg-secondary" data-bs-toggle="tooltip" title="'.htmlspecialchars((string) ($this->lang('Occurrence d\'une série récurrente')), ENT_QUOTES).'"><i class="fas fa-repeat"></i></span>' : '';
									return '<a href="'.url('events/'.$data['event_id'].'/'.url_title($data['title'])).'">'.$data['title'].'</a>'.$series;
								},
								'sort'    => function($data){
									return $data['title'];
								},
								'search'  => function($data){
									return $data['title'];
								}
							],
							[
								'content' => function($data){
									if ($data['type'] == 1 && ($match = $this->model('matches')->get_match_info($data['event_id'])))//Matches
									{
										return  ($match['scores'] ? $this->model('matches')->label_global_scores($data['event_id']).'<span style="margin: 0 10px;"> vs </span>' : '<span style="margin-right: 10px;">'.$this->lang('Match à jouer').' vs </span>').
												($match['opponent']['country'] ? '<img src="'.url('images/flags/'.$match['opponent']['country'].'.png').'" data-bs-toggle="tooltip" title="'.country_name($match['opponent']['country']).'" style="margin-right: 10px;" alt="" />' : '').
												$match['opponent']['title'].' <i>('.$match['game']['title'].')</i>';
									}
								},
								'sort'    => function($data){
									if ($data['type'] == 1 && ($match = $this->model('matches')->get_match_info($data['event_id'])))//Matches
									{
										return $match['opponent']['title'].' '.$match['game']['title'].' '.implode(' - ', $match['scores']);
									}
								},
								'search'  => function($data){
									if ($data['type'] == 1 && ($match = $this->model('matches')->get_match_info($data['event_id'])))//Matches
									{
										return $match['opponent']['title'].' '.$match['game']['title'].' '.implode(' - ', $match['scores']);
									}
								}
							],
							[
								'title'   => $this->lang('Auteur'),
								'content' => function($data){
									return $data['user_id'] ? NeoFrag()->user->link($data['user_id'], $data['username']) : $this->lang('Visiteur');
								},
								'sort'    => function($data){
									return $data['username'];
								},
								'search'  => function($data){
									return $data['username'];
								}
							],
							[
								'title'   => $this->lang('Date'),
								'content' => function($data){
									return '<span data-bs-toggle="tooltip" title="'.timetostr(NeoFrag()->lang('l j F Y, H:i'), $data['date']).'">'.timetostr(NeoFrag()->lang('d/m/Y H:i'), $data['date']).($data['date_end'] ? '&nbsp;&nbsp;<i>'.icon('fas fa-hourglass-end').(ceil((strtotime($data['date_end']) - strtotime($data['date'])) / ( 60 * 60 ))).'h</i>' : '').'</span>';
								},
								'sort'    => function($data){
									return $data['date'];
								}
							],
							[
								'title'   => '<i class="fas fa-users" data-bs-toggle="tooltip" title="'.$this->lang('Participants').'"></i>',
								'content' => function($data){
									return '<a href="'.url('events/'.$data['event_id'].'/'.url_title($data['title']).'#participants').'">'.$this->model('participants')->count_participants($data['event_id']).'</a>';
								},
								'size'    => TRUE
							],
							[
								'title'   => '<i class="far fa-comments" data-bs-toggle="tooltip" title="'.$this->lang('Commentaires').'"></i>',
								'content' => function($data){
									return $this->module('comments')->admin('events', $data['event_id']);
								},
								'size'    => TRUE
							],
							[
								'content' => [
									function($data){
										return $this->is_authorized('modify_event') ? $this->button_update('admin/events/'.$data['event_id'].'/'.url_title($data['title'])) : NULL;
									},
									function($data){
										return $this->is_authorized('delete_event') ? $this->button_delete('admin/events/delete/'.$data['event_id'].'/'.url_title($data['title'])) : NULL;
									},
									// Second bouton, sur les seules occurrences d'une série. Même
									// style que la suppression simple — c'est la même nature de
									// geste — mais l'icône de récurrence, pour qu'on ne puisse pas
									// confondre « cette séance » et « les douze ».
									function($data){
										if (empty($data['series_id']) || !$this->is_authorized('delete_event'))
										{
											return NULL;
										}

										return $this	->button_delete(
															'admin/events/delete-series/'.$data['event_id'].'/'.url_title($data['title']),
															$this->lang('Supprimer toute la série')
														)
														->icon('fas fa-repeat');
									}
								],
								'size'    => TRUE
							]
						])
						->data($events)
						->no_data($this->lang('Il n\'y a pas encore d\'événement'))
						->display();

		// Types card
		$types_card = '<div class="card events-types-card">'
			.'<div class="nf-card-header">'
			.'<span><i class="far fa-bookmark"></i> '.$this->lang('Types').'</span>'
			.($this->is_authorized('add_events_type') ? '<a class="btn btn-secondary btn-sm" href="'.url('admin/events/types/add').'"><i class="fas fa-plus"></i> '.$this->lang('Nouveau').'</a>' : '')
			.'</div>'
			.$types
			.'</div>';

		// Events list card
		$events_card = '<div class="card events-list-card">'
			.'<div class="nf-card-header">'
			.'<span><i class="fas fa-list"></i> '.$this->lang('Liste des événements').'</span>'
			.'</div>'
			.'<div class="events-list-filters">'.$this->_filters().'</div>'
			.'<div class="events-list-body">'.$events.'</div>'
			.'</div>';

		// Layout: stat-cards → calendar (col-8) + types (col-4) → events list full width
		return $cards
			.'<div class="row">'
			.'<div class="col-12 col-lg-8">'.$calendar_card.'</div>'
			.'<div class="col-12 col-lg-4">'.$types_card.'</div>'
			.'</div>'
			.$events_card;
	}

	public function _standards($events)
	{
		return $this->index($events);
	}

	public function _matches($events)
	{
		return $this->index($events);
	}

	public function upcoming($events)
	{
		return $this->index($events);
	}

	public function _filters()
	{
		return $this->view('filters', ['type' => '']);
	}

	public function add()
	{
		$this	->subtitle($this->lang('Ajouter un événement'))
				->form()
				->add_rules('events')
				->add_rules([
					'recurrence' => [
						'label'  => $this->lang('Répéter'),
						'type'   => 'select',
						'value'  => '',
						'values' => [
							''        => $this->lang('Aucune'),
							'daily'   => $this->lang('Quotidienne'),
							'weekly'  => $this->lang('Hebdomadaire'),
							'monthly' => $this->lang('Mensuelle')
						],
						'size'   => 'col-3'
					],
					'occurrences' => [
						'label'       => $this->lang('Occurrences'),
						'type'        => 'number',
						'value'       => '1',
						'size'        => 'col-3',
						'description' => $this->lang('Récurrence : nombre total d\'occurrences (max %d).', \NF\Modules\Events\Lib\Recurrence::MAX_OCCURRENCES)
					]
				])
				->add_submit($this->lang('Ajouter'), 'fas fa-plus')
				->add_back('admin/events');

		if ($this->form()->is_valid($post))
		{
			$dates = \NF\Modules\Events\Lib\Recurrence::dates($post['date'], $post['date_end'], $post['recurrence'] ?? '', (int)($post['occurrences'] ?? 1));

			$ids = [];
			foreach ($dates as $occurrence)
			{
				$ids[] = (int)$this->model()->add($post['title'],
											$post['type'],
											$occurrence[0],
											$occurrence[1],
											$post['description'],
											$post['private_description'],
											$post['location'],
											$post['image'],
											in_array('on', $post['published']),
											$post['publish_date'] ?? '');
			}

			$event_id = $ids[0];

			if (count($ids) > 1)
			{
				$this->model()->set_series($ids);
				notify($this->lang('Série de %d événements créée', count($ids)));
			}
			else
			{
				notify($this->lang('Événement ajouté'));
			}

			if ($this->db->select('type')->from('nf_events_types')->where('type_id', $post['type'])->row())
			{
				redirect('admin/events/'.$event_id.'/'.url_title($post['title']));
			}
			else
			{
				redirect_back('admin/events');
			}
		}

		return $this->admin_card('fas fa-calendar-alt', $this->lang('Ajouter un événement'), $this->form()->display());
	}

	public function _edit($event_id, $title, $type_id, $date, $date_end, $description, $private_description, $location, $image_id, $published, $type, $publish_date = '', $series_id = NULL)
	{
		// Une série de moins de deux occurrences n'en est pas une : proposer « toute la série » sur
		// un événement seul offrirait un choix sans conséquence, ce qui fait douter de tous les autres.
		$occurrences = $this->model()->count_series($series_id);

		$formulaire = $this	->title($this->lang('Éditer l\'événement'))
							->subtitle($title)
							->form()
							->add_rules('events', [
								'title'               => $title,
								'type_id'             => $type_id,
								'image_id'            => $image_id,
								'description'         => $description,
								'private_description' => $private_description,
								'location'            => $location,
								'date'                => $date,
								'date_end'            => $date_end,
								'publish_date'        => $publish_date,
								'published'           => $published
							]);

		if ($occurrences > 1)
		{
			$formulaire->add_rules([
				'series' => [
					'type'        => 'checkbox',
					'checked'     => ['on' => FALSE],
					'values'      => ['on' => $this->lang('Appliquer à toutes les occurrences de la série (%d)', $occurrences)],
					'description' => $this->lang('Le titre, le type, les descriptions, le lieu, l\'image et la publication sont recopiés sur chaque occurrence. Les DATES ne le sont pas : ce sont elles qui distinguent une occurrence de la suivante.')
				]
			]);
		}

		$form_default = $formulaire	->add_submit($this->lang('Éditer'))
									->add_back('admin/events')
									->save();

		if ($type == 1)//Matches
		{
			$match = $this->db->from('nf_events_matches')->where('event_id', $event_id)->row();

			if (!empty($match['mode_id']))
			{
				$game_id = $this->db->select('game_id')->from('nf_games_modes')->where('mode_id', $match['mode_id'])->row();
				$this->db->where('game_id', $game_id);
			}

			$maps = [];

			foreach ($this->db->select('*')->from('nf_games_maps')->get() as $map)
			{
				$maps[$map['map_id']] = $map['title'];
			}

			$form_match = $this	->form()
								->add_rules([
									'team' => [
										'label'       => $this->lang('Équipe'),
										'value'       => isset($match['team_id']) ? $match['team_id'] : NULL,
										'values'      => $this->module('teams')->model()->get_teams_list(),
										'type'        => 'select',
										'rules'       => 'required'
									],
									'opponent' => [
										'label'       => $this->lang('Adversaire'),
										'value'       => isset($match['opponent_id']) ? $match['opponent_id'] : NULL,
										'values'      => $this->model('matches')->get_opponents_list(),
										'type'        => 'select',
										'rules'       => 'required'
									],
									'mode' => [
										'label'       => $this->lang('Mode'),
										'value'       => isset($match['mode_id']) ? $match['mode_id'] : NULL,
										'values'      => $this->module('games')->model('modes')->get_modes_list(),
										'type'        => 'select'
									],
									'webtv' => [
										'label'       => 'WebTv',
										'value'       => isset($match['webtv']) ? $match['webtv'] : NULL,
										'description' => $this->lang('Renseignez l\'url de votre chaine Twitch pour indiquer une retransmission en Live.'),
										'type'        => 'url'
									],
									'website' => [
										'label'       => $this->lang('Site web'),
										'value'       => isset($match['website']) ? $match['website'] : NULL,
										'description' => $this->lang('Renseignez un site qui parle de l\'événement'),
										'type'        => 'url'
									]
								])
								->add_submit($this->lang('Valider'))
								->save();

			$form_opponent = $this	->form()
									->add_rules('opponents')
									->add_submit($this->lang('Valider'))
									->save();

			$form_round = $this	->form()
								->add_rules([
									'map' => [
										'label'  => $this->lang('Carte'),
										'type'   => 'select',
										'values' => $maps,
										'size'   => 'col-5'
									],
									'score1' => [
										'label'  => $this->lang('Notre score'),
										'type'   => 'number',
										'rules'  => 'required',
										'size'   => 'col-3'
									],
									'score2' => [
										'label'  => $this->lang('Score adverse'),
										'type'   => 'number',
										'rules'  => 'required',
										'size'   => 'col-3'
									]
								])
								->add_submit($this->lang('Valider'))
								->save();

			if ($form_match->is_valid($post))
			{
				$this->db->replace('nf_events_matches', [
					'event_id'    => $event_id,
					'team_id'     => $post['team'],
					'opponent_id' => $post['opponent'],
					'mode_id'     => !empty($post['mode']) ? $post['mode'] : NULL,
					'webtv'       => $post['webtv'],
					'website'     => $post['website']
				]);

				notify($this->lang('Rencontre éditée'));

				redirect('admin/events/'.$event_id.'/'.url_title($title));
			}
			else if ($form_opponent->is_valid($post))
			{
				$this->model('matches')->add_opponent($post['title'], $post['image'], $post['country'], $post['website']);

				notify($this->lang('Adversaire ajouté'));

				redirect('admin/events/'.$event_id.'/'.url_title($title));
			}
			else if ($form_round->is_valid($post))
			{
				$this->db->insert('nf_events_matches_rounds', [
					'event_id' => $event_id,
					'map_id'   => !empty($post['map']) ? $post['map'] : NULL,
					'score1'   => $post['score1'],
					'score2'   => $post['score2']
				]);

				notify($this->lang('Manche ajoutée'));

				redirect('admin/events/'.$event_id.'/'.url_title($title));
			}
		}

		if ($form_default->is_valid($post))
		{
			$this->model()->edit(	$event_id,
									$post['title'],
									$post['type'],
									$post['date'],
									$post['date_end'],
									$post['description'],
									$post['private_description'],
									$post['location'],
									$post['image'],
									in_array('on', $post['published']),
									$post['publish_date'] ?? '');

			// La propagation vient APRÈS l'édition de l'occupation courante, et la recouvre sans
			// dommage : les deux écrivent les mêmes valeurs. L'inverse laisserait l'occurrence
			// éditée en désaccord avec ses sœurs si la propagation échouait.
			if ($occurrences > 1 && in_array('on', (array) ($post['series'] ?? [])))
			{
				$touchees = $this->model()->edit_series($series_id, [
					'title'               => $post['title'],
					'type_id'             => $post['type'],
					'description'         => $post['description'],
					'private_description' => $post['private_description'],
					'location'            => $post['location'],
					'image_id'            => $post['image'],
					'published'           => in_array('on', $post['published'])
				]);

				notify($this->lang('Série mise à jour : %d occurrences. Les dates de chacune sont conservées.', $touchees));
			}
			else
			{
				notify($this->lang('Événement édité'));
			}

			$new_type = $this->db->select('type')->from('nf_events_types')->where('type_id', $post['type'])->row();

			if ($new_type && $type != $new_type)
			{
				redirect('admin/events/'.$event_id.'/'.url_title($post['title']));
			}
			else
			{
				redirect_back('admin/events');
			}
		}

		$alert = '';

		if ($published && !$this->model('participants')->get_participants($event_id))
		{
			$alert = $this	->panel()
							->body('<div class="float-end"><a href="'.url('events/'.$event_id.'/'.url_title($title).'#participants').'" class="btn btn-info">'.$this->lang('Inviter des membres').'</a></div><i class="fas fa-info-circle"></i> <b>'.$this->lang('Pense-bête !').'</b><br />'.$this->lang('N\'oubliez pas d\'envoyer vos demandes de participation à vos membres !'))
							->color('info');
		}

		$panel = $this		->panel()
							->heading('Éditer l\'événement', 'fas fa-align-left')
							->body($form_default->display());

		if ($type == 1)//Matches
		{
			$this	->table()
					->add_columns([
						[
							'content' => function($data){
								return $this->model('matches')->label_scores($data['score1'], $data['score2']).($data['title'] ? ' '.icon('far fa-map ms-1').' '.$data['title'] : '');
							}
						],
						[
							'content' => [
								function($data) use ($event_id, $title){
									return $this->button_delete('admin/events/rounds/delete/'.$event_id.'/'.url_title($title).'/'.$data['round_id']);
								}
							],
							'size'    => TRUE
						]
					])
					->pagination(FALSE)
					->data($rounds = $this->db	->select('r.round_id', 'm.title', 'r.score1', 'r.score2')
												->from('nf_events_matches_rounds r')
												->join('nf_games_maps m', 'm.map_id = r.map_id')
												->where('r.event_id', $event_id)
												->order_by('r.round_id')
												->get())
					->no_data($this->lang('Aucune manche renseignée'));

			$modal_opponent = $this	->modal('Ajouter un adversaire', 'fas fa-plus')
									->body($form_opponent->display())
									->open_if($form_opponent->get_errors());

			$modal_round    = $this	->modal('Ajouter une manche', 'fas fa-plus')
									->body($form_round->display())
									->open_if($form_round->get_errors());

			return $this->row(
				$this	->col($alert, $panel)
						->size('col-12 col-lg-8'),
				$this	->col(
							$this	->panel()
									->heading($this->lang('Détails de la rencontre'), 'fas fa-info-circle')
									->body((string)$this->button()->title($this->lang('Ajouter un adversaire'))->icon('fas fa-plus')->color('outline-primary')->modal($modal_opponent).'<br>'.$form_match->display()),
							$this	->panel()
									->heading($this->lang('Manches jouées').(count($rounds) > 1 ? ' <small class="text-muted">— '.$this->lang('Global').' '.$this->model('matches')->label_global_scores($event_id).'</small>' : ''), 'fas fa-chess')
									->body($this->table()->display())
									->footer($this->button_create('#', $this->lang('Ajouter une manche'))->modal($modal_round))
						)
						->size('col-12 col-lg-4')
			);
		}
		else
		{
			return $panel;
		}
	}

	public function _delete($event_id, $title)
	{
		$this	->title($this->lang('Suppression événement'))
				->subtitle($title)
				->form()
				->confirm_deletion($this->lang('Confirmation de suppression'), $this->lang('Êtes-vous sûr(e) de vouloir supprimer l\'événement <b>%s</b> ?', $title));

		if ($this->form()->is_valid())
		{
			$this->model()->delete($event_id);

			return 'OK';
		}

		return $this->form()->display();
	}

	/**
	 * Supprime toutes les occurrences d'une série récurrente.
	 *
	 * Le modèle savait le faire depuis la livraison de la récurrence — `delete_series()` existait,
	 * et rien ne l'appelait. Créer douze séances d'un coup et devoir les supprimer une par une
	 * n'était pas une limite décidée, seulement une moitié de chantier.
	 *
	 * La confirmation ANNONCE LE NOMBRE. « Supprimer la série » sans dire combien, sur une série
	 * qu'on a créée il y a des mois, se clique à l'aveugle.
	 */
	public function _delete_series($event_id, $title, $series_id)
	{
		$occurrences = $this->model()->count_series($series_id);

		$this	->title($this->lang('Suppression de la série'))
				->subtitle($title)
				->form()
				->confirm_deletion(
					$this->lang('Confirmation de suppression'),
					$this->lang(
						'Supprimer <b>l\'unique occurrence</b> de la série dont fait partie <b>%2$s</b> ?|Supprimer <b>les %1$d occurrences</b> de la série dont fait partie <b>%2$s</b> ? Elles seront toutes effacées, avec leurs commentaires et leurs participations.',
						$occurrences,
						$occurrences,
						$title
					)
				);

		if ($this->form()->is_valid())
		{
			$this->model()->delete_series($series_id);

			return 'OK';
		}

		return $this->form()->display();
	}

	public function _types_add()
	{
		$this	->subtitle($this->lang('Ajouter un type d\'événement'))
				->form()
				->add_rules('types')
				->add_back('admin/events')
				->add_submit($this->lang('Ajouter'), 'fas fa-plus');

		if ($this->form()->is_valid($post))
		{
			$this->model('types')->add(	$post['type'],
										$post['title'],
										$post['color'],
										$post['icon']);

			notify($this->lang('Type d\'événement ajouté'));

			redirect_back('admin/events');
		}

		return $this->admin_card('far fa-bookmark', $this->lang('Ajouter un type d\'événement'), $this->form()->display());
	}

	public function _types_edit($type_id, $type, $title, $color, $icon)
	{
		$this	->subtitle($this->lang('Type %s', $title))
				->form()
				->add_rules('types', [
					'type'  => $type,
					'title' => $title,
					'color' => $color,
					'icon'  => $icon
				])
				->add_submit($this->lang('Éditer'))
				->add_back('admin/events');

		if ($this->form()->is_valid($post))
		{
			$this->model('types')->edit($type_id,
										$post['type'],
										$post['title'],
										$post['color'],
										$post['icon']);

			notify($this->lang('Type d\'événement édité'));

			redirect_back('admin/events');
		}

		return $this->admin_card('far fa-bookmark', $this->lang('Éditer le type d\'événement').' — '.$title, $this->form()->display());
	}

	public function _types_delete($type_id, $title)
	{
		$this	->title($this->lang('Suppression type d\'événement'))
				->subtitle($title)
				->form()
				->confirm_deletion($this->lang('Confirmation de suppression'), $this->lang('Êtes-vous sûr(e) de vouloir supprimer le type d\'événement <b>%s</b> ?<br />Tous les événements de ce type seront aussi supprimés.', $title));

		if ($this->form()->is_valid())
		{
			$this->model('types')->delete($type_id);

			return 'OK';
		}

		return $this->form()->display();
	}

	public function _round_delete($round_id)
	{
		$this	->title($this->lang('Suppression manche'))
				->form()
				->confirm_deletion($this->lang('Confirmation de suppression'), $this->lang('Êtes-vous sûr(e) de vouloir supprimer cette manche ?'));

		if ($this->form()->is_valid())
		{
			$this->db	->where('round_id', $round_id)
						->delete('nf_events_matches_rounds');

			return 'OK';
		}

		return $this->form()->display();
	}

	public function _opponents()
	{
		$opponents = $this	->table()
							->add_columns([
								[
									'content' => function($data){
										$flag = $data['country'] ? '<img src="'.url('images/flags/'.$data['country'].'.png').'" data-bs-toggle="tooltip" title="'.country_name($data['country']).'" style="margin-right: 10px;" alt="" />' : '';

										return $flag.'<a href="'.url('admin/events/opponents/'.$data['opponent_id'].'/'.url_title($data['title'])).'">'.$data['title'].'</a>';
									},
									'sort'    => function($data){ return $data['title']; },
									'search'  => function($data){ return $data['title']; }
								],
								[
									'title'   => $this->lang('Site web'),
									'content' => function($data){
										return $data['website'] ? '<a href="'.$data['website'].'" target="_blank" rel="noopener noreferrer">'.$data['website'].'</a>' : '';
									}
								],
								[
									'title'   => $this->lang('Rencontres'),
									'content' => function($data){
										return $this->model('matches')->count_matches($data['opponent_id']);
									},
									'size'    => TRUE
								],
								[
									'content' => [
										function($data){
											return $this->is_authorized('modify_event') ? $this->button_update('admin/events/opponents/'.$data['opponent_id'].'/'.url_title($data['title'])) : NULL;
										},
										function($data){
											return $this->is_authorized('delete_event') ? $this->button_delete('admin/events/opponents/delete/'.$data['opponent_id'].'/'.url_title($data['title'])) : NULL;
										}
									],
									'size'    => TRUE
								]
							])
							->pagination(FALSE)
							->data($this->model('matches')->get_opponents())
							->no_data($this->lang('Aucun adversaire'))
							->display();

		return $this->admin_card('fas fa-shield-alt', $this->lang('Adversaires'), $opponents, '', $this->is_authorized('modify_event') ? (string)$this->button_create('admin/events/opponents/add', $this->lang('Nouvel adversaire')) : '');
	}

	public function _opponents_add()
	{
		$this	->subtitle($this->lang('Ajouter un adversaire'))
				->form()
				->add_rules('opponents')
				->add_back('admin/events/opponents')
				->add_submit($this->lang('Ajouter'), 'fas fa-plus');

		if ($this->form()->is_valid($post))
		{
			$this->model('matches')->add_opponent($post['title'], $post['image'], $post['country'], $post['website']);

			notify($this->lang('Adversaire ajouté'));

			redirect_back('admin/events/opponents');
		}

		return $this->admin_back('admin/events/opponents', $this->lang('Adversaires')).$this->admin_card('fas fa-shield-alt', $this->lang('Ajouter un adversaire'), $this->form()->display());
	}

	public function _opponents_edit($opponent_id, $image_id, $title, $website, $country)
	{
		$this	->subtitle($this->lang('Adversaire %s', $title))
				->form()
				->add_rules('opponents', [
					'title'    => $title,
					'image_id' => $image_id,
					'country'  => $country,
					'website'  => $website
				])
				->add_back('admin/events/opponents')
				->add_submit($this->lang('Éditer'));

		if ($this->form()->is_valid($post))
		{
			$this->model('matches')->edit_opponent($opponent_id, $post['title'], $post['image'], $post['country'], $post['website']);

			notify($this->lang('Adversaire édité'));

			redirect_back('admin/events/opponents');
		}

		return $this->admin_back('admin/events/opponents', $this->lang('Adversaires')).$this->admin_card('fas fa-shield-alt', $this->lang('Éditer l\'adversaire').' — '.$title, $this->form()->display());
	}

	public function _opponents_delete($opponent_id, $title)
	{
		$nb = $this->model('matches')->count_matches($opponent_id);

		$message = $this->lang('Êtes-vous sûr(e) de vouloir supprimer l\'adversaire <b>%s</b> ?', $title);

		if ($nb)
		{
			$message .= '<br />'.$this->lang('%d rencontre(s) associée(s) seront aussi supprimée(s).', $nb);
		}

		$this	->title($this->lang('Suppression adversaire'))
				->subtitle($title)
				->form()
				->confirm_deletion($this->lang('Confirmation de suppression'), $message);

		if ($this->form()->is_valid())
		{
			$this->model('matches')->delete_opponent($opponent_id);

			return 'OK';
		}

		return $this->form()->display();
	}
}
