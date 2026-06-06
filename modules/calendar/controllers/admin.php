<?php
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
				$body .= '<div class="nf-content-card-title">'.htmlspecialchars($e['title']).'</div>';
				$body .= '<span class="nf-content-card-status '.($published ? 'published' : 'draft').'">';
				$body .= '<i class="fas '.($published ? 'fa-check' : 'fa-clock').'"></i> '.($published ? $this->lang('Publié') : $this->lang('Brouillon'));
				$body .= '</span>';
				$body .= '</div>';
				$body .= '<div class="nf-content-card-meta">';
				$body .= '<span><i class="far fa-calendar-alt"></i> '.Calendar::format_dt($e['start_at'], (bool)$e['all_day'], $e['end_at']).'</span>';
				if (!empty($e['location'])) $body .= '<span><i class="fas fa-map-marker-alt"></i> '.htmlspecialchars($e['location']).'</span>';
				if (!empty($e['username']))  $body .= '<span><i class="fas fa-user"></i> '.htmlspecialchars($e['username']).'</span>';
				$body .= '</div>';
				$body .= '<div class="nf-content-card-foot">';
				$body .= '<span class="nf-content-card-spacer"></span>';
				$body .= '<a class="btn btn-sm btn-outline-primary" href="'.url('admin/calendar/'.$e['id'].'/'.$slug).'" title="'.$this->lang('Éditer').'"><i class="fas fa-pen"></i></a>';
				$body .= '<a class="btn btn-sm btn-outline-danger" href="'.url('admin/calendar/delete/'.$e['id'].'/'.$slug).'" data-confirm="'.htmlspecialchars($this->lang('Supprimer ?'), ENT_QUOTES).'" title="'.$this->lang('Supprimer').'"><i class="far fa-trash-alt"></i></a>';
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
				'start_at'    => ['label' => $this->lang('Début (YYYY-MM-DD HH:MM:SS)'), 'type' => 'text', 'value' => $is_new ? date('Y-m-d 18:00:00') : $e['start_at'], 'rules' => 'required'],
				'end_at'      => ['label' => $this->lang('Fin (YYYY-MM-DD HH:MM:SS, optionnel)'), 'type' => 'text', 'value' => $is_new ? '' : ($e['end_at'] ?? '')],
				'all_day'     => ['label' => $this->lang('Toute la journée'), 'type' => 'checkbox', 'value' => ['1'], 'values' => ['1' => $this->lang('Événement sur toute la journée')], 'checked' => ['1' => (!$is_new && !empty($e['all_day']))]],
				'color'       => ['label' => $this->lang('Couleur (#hex, optionnel)'), 'type' => 'text', 'value' => $is_new ? '#03c1a2' : ($e['color'] ?? '')],
				'published'   => ['label' => $this->lang('Publier'), 'type' => 'checkbox', 'value' => ['1'], 'values' => ['1' => $this->lang('Événement visible')], 'checked' => ['1' => ($is_new || !empty($e['published']))]]
			 ])
			 ->add_submit($is_new ? $this->lang('Créer') : $this->lang('Enregistrer'));

		if ($this->form()->is_valid($post))
		{
			$data = [
				'title'       => $post['title'],
				'description' => $post['description'] ?? '',
				'location'    => $post['location'] ?? '',
				'start_at'    => $post['start_at'],
				'end_at'      => !empty($post['end_at']) ? $post['end_at'] : NULL,
				'all_day'     => in_array('1', $post['all_day'] ?? []) ? 1 : 0,
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

		return $this->admin_back('admin/calendar', $this->lang('Calendrier')).$this->admin_card($is_new ? 'fas fa-plus' : 'fas fa-edit', $is_new ? $this->lang('Nouvel événement') : $this->lang('Éditer : %s', $e['title']), $this->form()->display());
	}
}
