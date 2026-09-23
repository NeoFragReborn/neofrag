<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\Talks\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Admin extends Controller_Module
{
	public function index($talks)
	{
		$this	->table()
				->add_columns([
					[
						'title'   => $this->lang('Discussion'),
						'content' => function($data){
							return $data['name'];
						},
						'sort'    => function($data){
							return $data['name'];
						},
						'search'  => function($data){
							return $data['name'];
						}
					],
					[
						'content' => [
							function($data){
								if ($data['talk_id'] > 1)
								{
									return $this->button_access($data['talk_id'], 'talk');
								}
							},
							function($data){
								if ($data['talk_id'] > 1)
								{
									return $this->button_update('admin/talks/'.$data['talk_id'].'/'.url_title($data['name']));
								}
							},
							function($data){
								if ($data['talk_id'] > 1)
								{
									return $this->button_delete('admin/talks/delete/'.$data['talk_id'].'/'.url_title($data['name']));
								}
							}
						],
						'size'    => TRUE
					]
				])
				->data($talks)
				->no_data($this->lang('Il n\'y a pas encore de discussion'));

		$actions = '<a class="btn btn-sm btn-primary" href="'.url('admin/talks/add').'">'.icon('fas fa-plus').' '.$this->lang('Créer').'</a>';

		return $this->admin_card('far fa-comment', $this->lang('Liste des discussions'), $this->table()->display(), '', $actions);
		// Note privacy : seuls les salons publics sont listés ici. Les conversations privées
		// (direct/group) sont volontairement masquées du panel admin. La gestion des signalements
		// (talks + MP legacy) sera intégrée au module Signalements unifié de la refonte modération
		// étendue site-wide (voir task #27). En attendant, /admin/talks/reports reste accessible
		// par URL directe pour les admins ayant besoin de consulter l'historique.
	}

	public function _reports($page = '')
	{
		$this->subtitle($this->lang('Signalements de messages'));

		// Récupère les signalements depuis nf_audit_log (action talks.message.reported uniquement)
		$reports = $this->db->select('a.id', 'a.user_id as reporter_id', 'a.action', 'a.details', 'UNIX_TIMESTAMP(a.created_at) as date', 'u.username as reporter_username')
							->from('nf_audit_log a')
							->join('nf_user u', 'u.id = a.user_id', 'LEFT')
							->where('a.action', 'talks.message.reported')
							->order_by('a.created_at DESC')
							->limit(100)
							->get();

		// Enrichir avec les détails du message signalé
		foreach ($reports as &$r)
		{
			$data = json_decode($r['details'] ?? '', TRUE) ?: [];
			$r['talk_id']    = $data['talk_id'] ?? 0;
			$r['message_id'] = $data['message_id'] ?? 0;

			if ($r['message_id'])
			{
				$msg = $this->db->select('m.message', 'm.user_id', 't.name as talk_name', 'u.username as author')
								->from('nf_talks_messages m')
								->join('nf_talks t', 't.talk_id = m.talk_id')
								->join('nf_user u',  'u.id = m.user_id', 'LEFT')
								->where('m.message_id', (int)$r['message_id'])
								->row();
				$r['message_text']    = is_array($msg) ? $msg['message']    : '';
				$r['message_author']  = is_array($msg) ? $msg['author']     : '';
				$r['talk_name']       = is_array($msg) ? $msg['talk_name']  : '';
			}
		}
		unset($r);

		return $this->admin_card('fas fa-flag', $this->lang('Signalements de messages'), $this->view('admin/reports', ['reports' => $reports]));
	}

	public function add()
	{
		$this	->subtitle($this->lang('Ajouter une discussion'))
				->form()
				->add_rules('talks')
				->add_submit($this->lang('Ajouter'), 'fas fa-plus')
				->add_back('admin/talks');

		if ($this->form()->is_valid($post))
		{
			$this->model()->add_talk($post['title']);

			notify($this->lang('Discussion ajoutée avec succès'));

			redirect_back('admin/talks');
		}

		return $this->admin_card('far fa-comment', $this->lang('Ajouter une discussion'), $this->form()->display());
	}

	public function _edit($talk_id, $title)
	{
		$this	->subtitle($title)
				->form()
				->add_rules('talks', [
					'title' => $title
				])
				->add_submit($this->lang('Éditer'))
				->add_back('admin/talks');

		if ($this->form()->is_valid($post))
		{
			$this->model()->edit_talk($talk_id, $post['title']);

			notify($this->lang('Discussion éditée avec succès'));

			redirect_back('admin/talks');
		}

		return $this->admin_card('far fa-comment', $this->lang('Édition de la discussion').' — '.$title, $this->form()->display());
	}

	public function _admin_delete($talk_id, $title)
	{
		$this	->title($this->lang('Suppression d\'une discussion'))
				->subtitle($title)
				->form()
				->confirm_deletion($this->lang('Confirmation de suppression'), $this->lang('Êtes-vous sûr(e) de vouloir supprimer la discussion <b>%s</b> ?', $title));

		if ($this->form()->is_valid())
		{
			$this->model()->delete_talk($talk_id);

			return 'OK';
		}

		return $this->form()->display();
	}
}
