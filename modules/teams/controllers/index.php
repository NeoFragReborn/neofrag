<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 *
 * couplage(recruits): la fiche d'une equipe affiche ses recrutements ouverts, mais
 * _check_team_recruits() sort AVANT toute requete si le module n'est pas la
 * (`module('recruits')` + `table_exists`). Le controle etait a l'origine place APRES la requete,
 * ce qui ne protegeait rien : corrige le 2026-09-15.
 */

namespace NF\Modules\Teams\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Index extends Controller_Module
{
	public function index()
	{
		$panels = $this->array;

		foreach ($this->model()->get_teams() as $team)
		{
			$panel = $this	->panel()
							->body($this->view('index', [
								'team_id'    => $team['team_id'],
								'name'       => $team['name'],
								'title'      => $team['title'],
								'icon_id'    => $team['icon_id'],
								'game_icon'  => $team['game_icon'],
								'game_title' => $team['game_title'],
								'image_id'   => $team['image_id'],
								'players'    => $this->model()->get_players($team['team_id']),
								'events'     => $this->_get_team_events($team['team_id'])
							]), FALSE)
							->footer_if($this->_check_team_recruits($team['team_id']), '<div class="text-center">'.$this->_team_recruits_link($team['team_id']).'</div>');

			$panels->append($panel);
		}

		if ($panels->empty())
		{
			$panels->append($this	->panel()
									->heading($this->lang('Équipe'), 'fas fa-headset')
									->body('<div class="text-center">'.$this->lang('Aucune équipe n\'a été créée pour le moment').'</div>')
									->color('info'));
		}

		return $panels;
	}

	public function _team($team_id, $name, $title, $image_id, $icon_id, $description, $game_id, $game, $game_icon)
	{
		$this	->title($title)
				->breadcrumb($title);

		$team_events = NULL;
		$matches     = '';

		if ($this->config->teams_display_matches && ($team_events = $this->_get_team_events($team_id)))
		{
			$matches = $this->table()
							->add_columns([
								[
									'title'   => $this->lang('Date'),
									'content' => function($data){
										return timetostr($this->lang('d/m/Y'), $data['date']);
									},
									'size'    => TRUE,
									'class'   => 'align-middle'
								],
								[
									'content' => function($data){
										if ($data['match']['opponent']['image_id'])
										{
											return '<img src="'.NeoFrag()->model2('file', $data['match']['opponent']['image_id'])->path().'" style="max-height: 35px; max-width: 50px;" alt="" />';
										}
										else
										{
											return '';
										}
									},
									'class'   => 'col-1 text-center align-middle'
								],
								[
									'title'   => $this->lang('Adversaire'),
									'content' => function($data){
										if ($data['match']['opponent']['country'])
										{
											$opponent = '<img src="'.url('images/flags/'.$data['match']['opponent']['country'].'.png').'" data-bs-toggle="tooltip" title="'.country_name($data['match']['opponent']['country']).'" style="margin-right: 8px;" alt="" />';
										}

										$opponent .= $data['match']['opponent']['title'];

										return $opponent;
									},
									'class'   => 'align-middle'
								],
								[
									'title'   => $this->lang('Événement'),
									'content' => function($data){
										return '<a href="'.url('events/'.$data['event_id'].'/'.url_title($data['title'])).'">'.$data['title'].'</a>';
									},
									'class'   => 'align-middle'
								],
								[
									'title'   => '<div class="text-center">'.$this->lang('Score').'</div>',
									'content' => function($data){
										return $this->module('events')->model('matches')->display_scores($data['match']['scores'], $color).'<span class="'.$color.'">'.$data['match']['scores'][0].':'.$data['match']['scores'][1].'</span>';
									},
									'class'   => 'text-center align-middle'
								]
							])
							->data(array_slice($team_events, 0, 10))
							->no_data($this->lang('Aucun match disputé...'))
							->display();
		}

		return $this->array()
					->append(
						$this	->panel()
								->body($this->view('team', [
									'team_id'     => $team_id,
									'name'        => $name,
									'title'       => $title,
									'icon_id'     => $icon_id,
									'image_id'    => $image_id,
									'game'        => $game,
									'description' => bbcode($description),
									'players'     => $this->model()->get_players($team_id)
								]), FALSE)
								->footer_if($this->_check_team_recruits($team_id), '<div class="text-center">'.$this->_team_recruits_link($team_id).'</div>')
					)
					->append_if(!empty($team_events), function() use ($team_events, $matches, $team_id, $name){
						return $this->panel()
									->heading($this->lang('Derniers résultats'), 'fas fa-crosshairs')
									->body($matches)
									->footer_if(count($team_events) > 10, '<a href="'.url('events/team/'.$team_id.'/'.url_title($name)).'">'.icon('far fa-arrow-alt-circle-right').' '.$this->lang('Voir tous les matchs de cette équipe').'</a>', 'right');
					})
					->append($this->panel_back('teams'));
	}

	public function _get_team_events($team_id)
	{
		// Sans le module Événements, pas de matchs : la boucle parcourait NULL et écrivait un avertissement
		// au journal à chaque page d'équipe (relevé le 2026-10-04).
		if (!$this->module('events'))
		{
			return NULL;
		}

		$events = $this->module('events')->model()->get_events('team', $team_id);

		$team_matches = [];
		foreach (is_array($events) ? $events : [] as $key => $event)
		{
			if ($event['nb_rounds'] > 0)
			{
				$team_matches[$key] = $event;
				$team_matches[$key]['match'] = $this->module('events')->model('matches')->get_match_info($event['event_id']);
			}
		}

		return $team_matches ?: NULL;
	}

	public function _check_team_recruits($team_id)
	{
		// Le controle de presence du module etait place APRES la requete (cf. le `if` plus bas) :
		// sans `recruits` installe, nf_recruits n'existe pas et la page d'une equipe fatalisait
		// avant meme d'atteindre la garde. Corrige le 2026-09-15 — on sort d'abord.
		if (!$this->module('recruits') || !$this->db->table_exists('nf_recruits'))
		{
			return NULL;
		}

		$recruits = $this->db	->select('r.*', 'COUNT(DISTINCT rc.candidacy_id) as candidacies', 'COUNT(DISTINCT CASE WHEN rc.status = \'1\' THEN rc.candidacy_id END) as candidacies_pending', 'COUNT(DISTINCT CASE WHEN rc.status = \'2\' THEN rc.candidacy_id END) as candidacies_accepted', 'COUNT(DISTINCT CASE WHEN rc.status = \'3\' THEN rc.candidacy_id END) as candidacies_declined')
								->from('nf_recruits r')
								// LEFT : voir modules/recruits/models/recruits.php.
								->join('nf_recruits_candidacies rc', 'rc.recruit_id = r.recruit_id', 'LEFT')
								->group_by('r.recruit_id')
								->where('r.closed', FALSE)
								->where('r.team_id', $team_id)
								->get();

		if ($recruits)
		{
			foreach ($recruits as $recruit)
			{
				if ($recruit['closed'] || ($recruit['candidacies_accepted'] >= $recruit['size']) || ($recruit['date_end'] && strtotime($recruit['date_end']) < time()))
				{
					continue;
				}
				else
				{
					return $recruit;
				}
			}
		}

		return FALSE;
	}

	private function _team_recruits_link($team_id)
	{
		$recruit = $this->_check_team_recruits($team_id);
		if (!$recruit)
		{
			return '';
		}

		$url = url('recruits/'.$recruit['recruit_id'].'/'.url_title($recruit['title']));
		return '<a href="'.$url.'" class="btn btn-primary btn-sm">'.icon('fas fa-bullhorn').' '.$this->lang('Cette équipe recrute — Postuler').'</a>';
	}
}
