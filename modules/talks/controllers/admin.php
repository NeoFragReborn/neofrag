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

		// Les pièces jointes orphelines (ligne 0.36), montrées puis effacées sur demande — seulement celles qui le sont
		// encore. Elles viennent de conversations privées : leur nom d'origine n'est pas montré.
		if (!empty($_POST['purger_orphelins']) && is_array($_POST['purger_orphelins']))
		{
			notify($this->lang('%d fichier(s) orphelin(s) effacé(s).', nf_effacer_orphelins('talks', 'nf_talks_attachments', array_map('strval', $_POST['purger_orphelins']))));
			refresh();
		}

		$orphelins = nf_fichiers_orphelins('talks', 'nf_talks_attachments');

		return $this->admin_card('far fa-comment', $this->lang('Liste des discussions'), $this->table()->display(), '', $actions)
			.($orphelins ? $this->admin_card('fas fa-paperclip', $this->lang('Pièces jointes orphelines'), $this->view('admin/orphelins', ['orphelins' => $orphelins])) : '');
		// Note privacy : seuls les salons publics sont listés ici. Les conversations privées
		// (direct/group) sont volontairement masquées du panel admin. Un message se signale par la
		// modération (Modération → Signalements) ; l'ancienne page qui lisait le journal d'audit est
		// retirée (2026-10-09).
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
				->add_submit($this->lang('Enregistrer'))
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
