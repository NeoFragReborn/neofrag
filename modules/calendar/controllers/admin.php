<?php
declare(strict_types=1);
namespace NF\Modules\Calendar\Controllers;
use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;
use NF\Modules\Calendar\Calendar;

class Admin extends Controller_Module
{
	public function index($events)
	{
		$this->title($this->lang('Calendrier'))->icon('far fa-calendar');

		$pub = $drafts = 0;
		foreach ($events as $e) { if (!empty($e['published'])) $pub++; else $drafts++; }

		if (empty($events)) {
			$body = $this->admin_empty('far fa-calendar', $this->lang('Aucun événement programmé.'));
		} else {
			$body = '<div class="nf-card-grid">';
			foreach ($events as $e) {
				$slug      = url_title($e['title']);
				$published = !empty($e['published']);

				$body .= '<div class="nf-content-card">';
				$body .= '<div class="nf-content-card-head">';
				$body .= '<div class="nf-content-card-title">'.nf_texte($e['title']).'</div>';
				$body .= '<span class="nf-content-card-status '.($published ? 'published' : 'draft').'">';
				$body .= '<i class="fas '.($published ? 'fa-check' : 'fa-clock').'"></i> '.($published ? $this->lang('Publié') : $this->lang('Brouillon'));
				$body .= '</span>';
				$body .= '</div>';
				$body .= '<div class="nf-content-card-meta">';
				$body .= '<span><i class="far fa-calendar-alt"></i> '.Calendar::format_dt($e['start_at'], (bool)$e['all_day'], $e['end_at']).'</span>';
				if (!empty($e['location'])) $body .= '<span><i class="fas fa-map-marker-alt"></i> '.nf_texte($e['location']).'</span>';
				if (!empty($e['username']))  $body .= '<span><i class="fas fa-user"></i> '.nf_texte($e['username']).'</span>';
				$body .= '</div>';
				$body .= '<div class="nf-content-card-foot">';
				$body .= '<span class="nf-content-card-spacer"></span>';
				$body .= '<a class="btn btn-sm btn-outline-secondary" href="'.url('admin/calendar/'.$e['id'].'/'.$slug).'" title="'.$this->lang('Éditer').'"><i class="fas fa-pen"></i></a>';
				$body .= '<a class="btn btn-sm btn-outline-danger" href="'.$this->csrf_url('admin/calendar/delete/'.$e['id'].'/'.$slug).'" data-confirm="'.nf_texte($this->lang('Supprimer ?')).'" title="'.$this->lang('Supprimer').'"><i class="far fa-trash-alt"></i></a>';
				$body .= '</div>';
				$body .= '</div>';
			}
			$body .= '</div>';
		}

		$subtitle = $pub.' '.$this->lang('publié|publiés', $pub).($drafts > 0 ? ' · '.$drafts.' '.$this->lang('brouillon|brouillons', $drafts) : '');
		$actions  = '<a class="btn btn-primary btn-sm" href="'.url('admin/calendar/add').'"><i class="fas fa-plus"></i> '.$this->lang('Nouvel événement').'</a>';

		return $this->admin_card('far fa-calendar', $this->lang('Événements'), $body, $subtitle, $actions);
	}

	public function _add()    { return $this->_form(NULL); }
	public function _edit($e) { return $this->_form($e); }
	public function _delete($e)
	{
		$this->check_csrf('admin/calendar');

		NeoFrag()->db->where('id', $e['id'])->delete('nf_calendar_events');
		notify($this->lang('Événement supprimé.'));
		redirect('admin/calendar');
	}

	protected function _form($e)
	{
		$is_new = $e === NULL;
		$this->title($is_new ? $this->lang('Nouvel événement') : $this->lang('Éditer événement'))->icon('far fa-calendar')->breadcrumb();

		$this->form()
			 ->add_rules([
				'title'       => ['label' => $this->lang('Titre'), 'type' => 'text', 'value' => $is_new ? '' : $e['title'], 'rules' => 'required'],
				'description' => ['label' => $this->lang('Description'), 'type' => 'editor', 'value' => $is_new ? '' : $e['description']],
				'location'    => ['label' => $this->lang('Lieu'), 'type' => 'text', 'value' => $is_new ? '' : $e['location']],
				// Des champs date et heure (sélecteur, format de la langue, fuseau de celui qui saisit) : c'étaient
				// des champs texte au format « YYYY-MM-DD HH:MM:SS », lus à l'heure du serveur.
				'start_at'    => ['label' => $this->lang('Début'), 'type' => 'datetime', 'value' => $is_new ? nf_heure_saisie((new \DateTime('today 18:00', nf_fuseau()))->format('Y-m-d H:i:s')) : $this->_heure_du_formulaire($e['start_at'], !empty($e['all_day'])), 'rules' => 'required'],
				'end_at'      => ['label' => $this->lang('Fin (optionnel)'), 'type' => 'datetime', 'value' => $is_new ? '' : $this->_heure_du_formulaire($e['end_at'] ?? '', !empty($e['all_day']))],
				'all_day'     => ['label' => $this->lang('Toute la journée'), 'type' => 'checkbox', 'value' => ['1'], 'values' => ['1' => $this->lang('Événement sur toute la journée')], 'checked' => ['1' => (!$is_new && !empty($e['all_day']))]],
				'color'       => ['label' => $this->lang('Couleur (#hex, optionnel)'), 'type' => 'text', 'value' => $is_new ? '#03c1a2' : ($e['color'] ?? '')],
				'published'   => ['label' => $this->lang('Publier'), 'type' => 'checkbox', 'value' => ['1'], 'values' => ['1' => $this->lang('Événement visible')], 'checked' => ['1' => ($is_new || !empty($e['published']))]]
			 ])
			 ->add_submit($is_new ? $this->lang('Créer') : $this->lang('Enregistrer'), $is_new ? 'fas fa-plus' : 'fas fa-check');

		if ($this->form()->is_valid($post))
		{
			// Un événement sur toute la journée porte une date de calendrier, pas un instant : le champ a
			// converti la saisie vers le fuseau du serveur, on garde la date telle qu'elle a été choisie.
			$journee = in_array('1', $post['all_day'] ?? []);

			$data = [
				'title'       => $post['title'],
				'description' => $post['description'] ?? '',
				'location'    => $post['location'] ?? '',
				'start_at'    => $journee ? nf_heure_affichee($post['start_at']) : $post['start_at'],
				'end_at'      => !empty($post['end_at']) ? ($journee ? nf_heure_affichee($post['end_at']) : $post['end_at']) : NULL,
				'all_day'     => $journee ? 1 : 0,
				'color'       => $post['color'] ?? NULL,
				'published'   => in_array('1', $post['published'] ?? []) ? 1 : 0
			];

			if ($is_new)
			{
				$data['user_id'] = $this->user->id;
				NeoFrag()->db->insert('nf_calendar_events', $data);
			}
			else
			{
				NeoFrag()->db->where('id', $e['id'])->update('nf_calendar_events', $data);
			}

			notify($is_new ? $this->lang('Événement créé.') : $this->lang('Événement modifié.'));
			redirect('admin/calendar');
		}

		return $this->admin_card($is_new ? 'fas fa-plus' : 'fas fa-edit', $is_new ? $this->lang('Nouvel événement') : $this->lang('Éditer : %s', $e['title']), $this->form()->display());
	}

	/**
	 * La valeur d'un champ date et heure, qui l'affichera dans le fuseau de celui qui regarde. Une
	 * journée entière est enregistrée telle qu'elle a été choisie : on la présente au champ comme une
	 * saisie, pour qu'il la rende à l'identique.
	 */
	private function _heure_du_formulaire($valeur, bool $journee)
	{
		return $journee ? nf_heure_saisie($valeur) : $valeur;
	}
}
