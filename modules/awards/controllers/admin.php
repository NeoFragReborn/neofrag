<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\Awards\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Admin extends Controller_Module
{
	public function index($awards)
	{
		$this->css('awards');

		if (empty($awards)) {
			$body = $this->admin_empty('fas fa-trophy', $this->lang('Aucun palmarès'));
		} else {
			$body = '<div class="nf-card-grid">';
			foreach ($awards as $a) {
				$slug = url_title($a['name']);
				$rank = (int)$a['ranking'];
				$rank_class = $rank == 1 ? 'trophy-gold' : ($rank == 2 ? 'trophy-silver' : ($rank == 3 ? 'trophy-bronze' : ''));
				// L'ordinal ne se traduit pas en ajoutant un suffixe (« 2nd », « 3rd », « 21st » en anglais) :
				// le podium a ses trois textes, les autres rangs s'affichent en chiffre, expliqués par l'infobulle.
				$rank_label = [1 => $this->lang('1er'), 2 => $this->lang('2e'), 3 => $this->lang('3e')][$rank] ?? (string) $rank;

				$body .= '<div class="nf-content-card">';
				$body .= '<div class="nf-content-card-head">';
				$body .= '<div class="nf-content-card-title">'.htmlspecialchars((string) ($a['name'])).'</div>';
				$body .= '<span class="nf-content-card-status published" title="'.$this->lang('Rang %d sur %d équipe|Rang %d sur %d équipes', (int)$a['participants'], $rank, (int)$a['participants']).'"><i class="fas fa-trophy '.$rank_class.'"></i> '.$rank_label.'</span>';
				$body .= '</div>';
				$body .= '<div class="nf-content-card-meta">';
				if (!empty($a['date']))       $body .= '<span><i class="far fa-calendar"></i> '.timetostr(NeoFrag()->lang('d/m/Y'), $a['date']).'</span>';
				if (!empty($a['team_title'])) $body .= '<span><i class="fas fa-users"></i> '.htmlspecialchars((string) ($a['team_title'])).'</span>';
				if (!empty($a['game_title'])) $body .= '<span><i class="fas fa-gamepad"></i> '.htmlspecialchars((string) ($a['game_title'])).'</span>';
				if (!empty($a['platform']))   $body .= '<span><i class="fas fa-tv"></i> '.htmlspecialchars((string) ($a['platform'])).'</span>';
				if (!empty($a['location']))   $body .= '<span><i class="fas fa-map-marker-alt"></i> '.htmlspecialchars((string) ($a['location'])).'</span>';
				$body .= '</div>';
				$body .= '<div class="nf-content-card-foot">';
				$body .= '<span class="nf-content-card-spacer"></span>';
				if ($this->is_authorized('modify_awards')) $body .= '<a class="btn btn-sm btn-outline-primary" href="'.url('admin/awards/'.$a['award_id'].'/'.$slug).'" title="'.$this->lang('Éditer').'"><i class="fas fa-pen"></i></a>';
				if ($this->is_authorized('delete_awards')) $body .= '<a class="btn btn-sm btn-outline-danger" href="'.url('admin/awards/delete/'.$a['award_id'].'/'.$slug).'" data-confirm="'.htmlspecialchars((string) ($this->lang('Supprimer ?')), ENT_QUOTES).'" title="'.$this->lang('Supprimer').'"><i class="far fa-trash-alt"></i></a>';
				$body .= '</div>';
				$body .= '</div>';
			}
			$body .= '</div>';
		}

		$actions = $this->is_authorized('add_awards')
			? '<a class="btn btn-sm btn-primary" href="'.url('admin/awards/add').'">'.icon('fas fa-plus').' '.$this->lang('Ajouter').'</a>'
			: '';
		return $this->admin_card('fas fa-trophy', $this->lang('Palmarès'), $body, $this->lang('%d palmarès|%d palmarès', count($awards), count($awards)), $actions);
	}

	public function add()
	{
		$this	->subtitle($this->lang('Ajouter un palmarès'))
				->form()
				->add_rules('awards', [
					'teams' => $this->model()->get_teams_list(),
					'games' => $this->model()->get_games_list()
				])
				->add_submit($this->lang('Ajouter'), 'fas fa-plus')
				->add_back('admin/awards');

		if ($this->form()->is_valid($post))
		{
			$this->model()->add_awards(	$post['date'],
										$post['team'],
										$post['game'],
										$post['platform'],
										$post['location'],
										$post['name'],
										$post['ranking'],
										$post['participants'],
										$post['description'],
										$post['image']);

			notify($this->lang('Palmarès ajouté avec succès'));

			redirect_back('admin/awards');
		}

		return $this->admin_card('fas fa-trophy', $this->lang('Nouveau palmarès'), $this->form()->display());
	}

	public function _edit($award_id, $team_id, $date, $location, $name, $platform, $game_id, $ranking, $participants, $description, $image_id, $team_name, $team_title, $game_name, $game_title)
	{
		$this	->subtitle($this->lang('Équipe %s', $team_title))
				->form()
				->add_rules('awards', [
					'award_id'     => $award_id,
					'date'         => $date,
					'team_id'      => $team_id,
					'teams'        => $this->model()->get_teams_list(),
					'game_id'      => $game_id,
					'games'        => $this->model()->get_games_list(),
					'platform'     => $platform,
					'location'     => $location,
					'name'         => $name,
					'ranking'      => $ranking,
					'participants' => $participants,
					'description'  => $description,
					'image'        => $image_id
				])
				->add_submit($this->lang('Éditer'))
				->add_back('admin/awards');

		if ($this->form()->is_valid($post))
		{
			$this->model()->edit_awards($award_id,
										$post['date'],
										$post['team'],
										$post['game'],
										$post['platform'],
										$post['location'],
										$post['name'],
										$post['ranking'],
										$post['participants'],
										$post['description'],
										$post['image']);

			notify($this->lang('Palmarès édité avec succès'));

			redirect_back('admin/awards');
		}

		return $this->admin_card('fas fa-trophy', $this->lang('Édition du palmarès').' — '.$name, $this->form()->display());
	}

	public function _delete($award_id, $name)
	{
		$this	->title($this->lang('Palmarès'))
				->subtitle($name)
				->form()
				->confirm_deletion($this->lang('Confirmation de suppression'), $this->lang('Êtes-vous sûr de vouloir supprimer le palmarès <b>%s</b> ?', $name));

		if ($this->form()->is_valid())
		{
			$this->model()->delete_awards($award_id);

			return 'OK';
		}

		return $this->form()->display();
	}
}
