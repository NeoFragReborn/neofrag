<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\Recruits\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Admin extends Controller_Module
{
	public function index($recruits)
	{
		$total_candidacies = 0;
		$total_pending     = 0;
		$total_accepted    = 0;
		$total_declined    = 0;

		foreach ($recruits as $recruit)
		{
			$total_candidacies += $recruit['candidacies'];
			$total_pending     += $recruit['candidacies_pending'];
			$total_accepted    += $recruit['candidacies_accepted'];
			$total_declined    += $recruit['candidacies_declined'];
		}

		// Cards offres
		if (empty($recruits)) {
			$recruits_body = $this->admin_empty('fas fa-bullhorn', $this->lang('Il n\'y a pas encore d\'offre de recrutement'));
		} else {
			$recruits_body = '<div class="nf-card-grid">';
			foreach ($recruits as $r) {
				$slug    = url_title($r['title']);
				$is_full = $r['candidacies_accepted'] >= $r['size'];
				$is_closed = $r['closed'] || $is_full || ($r['date_end'] && strtotime($r['date_end']) < time());
				$status_text = $is_closed ? $this->lang('Clôturée') : $this->lang('Active');
				$status_class = $is_closed ? 'draft' : 'published';

				$recruits_body .= '<div class="nf-content-card">';
				$recruits_body .= '<div class="nf-content-card-head">';
				$recruits_body .= '<div class="nf-content-card-title"><a href="'.url('recruits/'.$r['recruit_id'].'/'.$slug).'">'.nf_texte($r['title']).'</a></div>';
				$recruits_body .= '<span class="nf-content-card-status '.$status_class.'"><i class="fas '.($is_closed ? 'fa-lock' : 'fa-check').'"></i> '.$status_text.'</span>';
				$recruits_body .= '</div>';
				$recruits_body .= '<div class="nf-content-card-meta">';
				$recruits_body .= '<span title="'.$this->lang('Postes').'"><i class="fas fa-briefcase"></i> '.(int)$r['size'].'</span>';
				$recruits_body .= '<span title="'.$this->lang('En attente').'"><i class="far fa-clock text-muted"></i> '.(int)$r['candidacies_pending'].'</span>';
				$recruits_body .= '<span title="'.$this->lang('Acceptées').'"><i class="fas fa-check text-success"></i> '.(int)$r['candidacies_accepted'].'</span>';
				$recruits_body .= '<span title="'.$this->lang('Refusées').'"><i class="fas fa-ban text-danger"></i> '.(int)$r['candidacies_declined'].'</span>';
				$recruits_body .= '</div>';
				$recruits_body .= '<div class="nf-content-card-foot">';
				$recruits_body .= '<span class="nf-content-card-spacer"></span>';
				if ($this->access->effective_admin()) $recruits_body .= (string)$this->button_access($r['recruit_id'], 'recruit');
				if ($this->is_authorized('modify_recruit')) $recruits_body .= '<a class="btn btn-sm btn-outline-secondary" href="'.url('admin/recruits/'.$r['recruit_id'].'/'.$slug).'" title="'.$this->lang('Éditer').'"><i class="fas fa-pen"></i></a>';
				if ($this->is_authorized('delete_recruit')) $recruits_body .= '<a class="btn btn-sm btn-outline-danger" href="'.url('admin/recruits/delete/'.$r['recruit_id'].'/'.$slug).'" data-confirm="'.nf_texte($this->lang('Supprimer ?')).'" title="'.$this->lang('Supprimer').'"><i class="far fa-trash-alt"></i></a>';
				$recruits_body .= '</div>';
				$recruits_body .= '</div>';
			}
			$recruits_body .= '</div>';
		}

		$candidacies_body = $this->view('admin-candidacies', [
			'total_candidacies' => $total_candidacies,
			'total_pending'     => $total_pending,
			'total_accepted'    => $total_accepted,
			'total_declined'    => $total_declined
		]);

		$add_btn = $this->is_authorized('add_recruit') ? '<a class="btn btn-sm btn-primary" href="'.url('admin/recruits/add').'"><i class="fas fa-plus"></i> '.$this->lang('Créer une offre').'</a>' : '';

		// La liste est découpée par pages de 10 (le checker) : sans ces liens, les suivantes étaient
		// inatteignables, et le compte ne disait que la page affichée.
		$pagination = (string) $this->module->pagination->get_pagination();
		$total      = $pagination !== '' ? (int) $this->module->pagination->count() : count($recruits);

		if ($pagination !== '')
		{
			$recruits_body .= '<div class="d-flex justify-content-center mt-3">'.$pagination.'</div>';
		}

		return '<div class="nf-list-layout">'
			.'<div class="nf-list-aside">'.$this->admin_card('fab fa-black-tie', $this->lang('Candidatures'), $candidacies_body).'</div>'
			.'<div class="nf-list-main">'.$this->admin_card('fas fa-bullhorn', $this->lang('Offres de recrutement'), $recruits_body, $this->lang('%d offre|%d offres', $total, $total), $add_btn).'</div>'
			.'</div>';
	}

	public function add()
	{
		$this	->subtitle($this->lang('Créer une offre'))
				->form()
				->add_rules('recruit', [
					'teams' => $this->model()->get_teams_list()
				])
				->add_submit($this->lang('Ajouter'), 'fas fa-plus')
				->add_back('admin/recruits');

		if ($this->form()->is_valid($post))
		{
			$this->model()->add_recruits(	$post['title'],
											$post['introduction'],
											$post['description'],
											$post['requierments'],
											($post['size'] <= 0 ? 1 : $post['size']),
											$post['role'],
											$post['icon'],
											$post['date_end'],
											in_array('on', $post['closed']),
											$post['team_id'],
											$post['image']);

			notify($this->lang('Offre de recrutement ajoutée avec succès'));

			redirect_back('admin/recruits');
		}

		return $this->panel()
					->heading($this->lang('Créer une offre de recrutement'), 'fas fa-bullhorn')
					->body($this->form()->display());
	}

	public function _edit($recruit_id, $title, $introduction, $description, $requierments, $date, $user_id, $size, $role, $icon, $date_end, $closed, $team_id, $image_id, $username, $avatar, $sex, $total_candidacies, $candidacies_pending, $candidacies_accepted, $candidacies_declined, $team_name)
	{
		$this	->subtitle($title)
				->js('knob')
				->form()
				->add_rules('recruit', [
					'teams'        => $this->model()->get_teams_list(),
					'title'        => $title,
					'introduction' => $introduction,
					'description'  => $description,
					'requierments' => $requierments,
					'size'         => $size,
					'role'         => $role,
					'icon'         => $icon,
					'date_end'     => $date_end,
					'closed'       => $closed,
					'team_id'      => $team_id,
					'image_id'     => $image_id
				])
				->add_submit($this->lang('Enregistrer'))
				->add_back('admin/recruits');

		if ($this->form()->is_valid($post))
		{
			$this->model()->edit_recruits(	$recruit_id,
											$post['title'],
											$post['introduction'],
											$post['description'],
											$post['requierments'],
											($post['size'] <= 0 ? 1 : $post['size']),
											$post['role'],
											$post['icon'],
											$post['date_end'],
											in_array('on', $post['closed']),
											$post['team_id'],
											$post['image']);

			notify($this->lang('Offre de recrutement modifiée avec succès'));

			redirect_back('admin/recruits');
		}

		return $this->row(
			$this->col(
				$this	->panel()
						->heading($title.' <span class="ms-2">'.(string)$this->button_access($recruit_id, 'recruit').'</span>', 'fas fa-briefcase')
						->body($this->form()->display())
						->size('col-12 col-lg-8')
			),
			$this->col(
				$this	->panel()
						->heading($this->lang('Candidatures déposées'), 'fab fa-black-tie')
						->body($this->view('admin-recruit-status', [
												'size'                 => $size,
												'available'            => $size - $candidacies_accepted,
												'total_candidacies'    => $total_candidacies,
												'candidacies_pending'  => $candidacies_pending,
												'candidacies_accepted' => $candidacies_accepted,
												'candidacies_declined' => $candidacies_declined
											]))
						->footer(($this->is_authorized('candidacy_vote') || $this->is_authorized('candidacy_reply')) ? '<a href="'.url('admin/recruits/candidacies/'.$recruit_id.'/'.url_title($title)).'" class="btn btn-outline-info">'.$this->lang('Voir les candidatures').'</a>' : '<span class="text-red">'.$this->lang('Vous n\'êtes pas autorisé à gérer les candidatures...').'</span>')
						->size('col-12 col-lg-4'),
				$this	->panel()
						->heading('Formulaire', 'fas fa-tasks')
						->body($this->view('admin-custom-form', ['fields' => $this->model()->get_fields($recruit_id)]))
						->footer('<a href="'.url('admin/recruits/fields/'.$recruit_id.'/'.url_title($title)).'" class="btn btn-outline-info">'.icon('fas fa-sliders-h').' '.$this->lang('Personnaliser le formulaire').'</a>')
						->size('col-12 col-lg-4')
			)
		);
	}

	public function _delete($recruit_id, $title)
	{
		$this	->title($this->lang('Suppression offre de recrutement'))
				->subtitle($title)
				->form()
				->confirm_deletion($this->lang('Confirmation de suppression'), $this->lang('Êtes-vous sûr(e) de vouloir supprimer l\'offre <b>%s</b> ?<br />Toutes les candidatures associées à cette offre seront aussi supprimées.', $title));

		if ($this->form()->is_valid())
		{
			$this->model()->delete_recruit($recruit_id);

			return 'OK';
		}

		return $this->form()->display();
	}

	public function pending()
	{
		if(!$this->is_authorized('candidacy_vote') && !$this->is_authorized('candidacy_reply'))
		{
			$this->error->unauthorized();
		}

		$this->subtitle($this->lang('Candidatures en attentes'));

		$candidacies_pending = $this->table()
									->add_columns([
										[
											'content' => function($data){
												return '<a href="mailto:'.$data['email'].'" data-bs-toggle="tooltip" title="'.$data['email'].'">'.icon('far fa-envelope').'</a>';
											},
											'sort'    => function($data){
												return $data['email'];
											},
											'search'  => function($data){
												return $data['email'];
											},
											'size'    => TRUE
										],
										[
											'title'   => $this->lang('Candidat'),
											'content' => function($data){
												if ($data['user_id'])
												{
													return $this->user->link($data['user_id'], $data['username']);
												}
												else
												{
													return $data['pseudo'];
												}
											},
											'sort'    => function($data){
												return $data['pseudo'];
											},
											'search'  => function($data){
												return $data['pseudo'];
											}
										],
										[
											'title'   => $this->lang('Date'),
											'content' => function($data){
												return '<span data-bs-toggle="tooltip" title="'.timetostr($this->lang('l j F Y, H:i'), $data['date']).'">'.time_span($data['date']).'</span>';
											},
											'sort'    => function($data){
												return $data['date'];
											}
										],
										[
											'title'   => $this->lang('Offre'),
											'content' => function($data){
												return $data['title'];
											},
											'sort'    => function($data){
												return $data['title'];
											},
											'search'  => function($data){
												return $data['title'];
											}
										],
										[
											'content' => [
												function($data){
													return ($this->is_authorized('candidacy_vote') || $this->is_authorized('candidacy_reply')) ? $this->button_update('admin/recruits/candidacy/'.$data['candidacy_id'].'/'.url_title($data['title'])) : NULL;
												},
												function($data){
													return $this->is_authorized('candidacy_delete') ? $this->button_delete('admin/recruits/candidacy/delete/'.$data['candidacy_id'].'/'.url_title($data['title'])) : NULL;
												}
											],
											'size'    => TRUE
										]
									])
									->data($this->model()->get_candidacies($recruit_id = '', 1))
									->no_data($this->lang('Aucune candidature en attente'))
									->display();

		return $this->array
					->append($this	->panel()
									->heading('Liste des candidatures en attentes', 'fab fa-black-tie')
									->body($candidacies_pending)
									->size('col-12 col-lg-8')
					)
					->append($this->panel_back());
	}

	public function _candidacies($recruit_id, $recruit_title)
	{
		$this->subtitle($recruit_title);

		$candidacies_pending = $this->table()
									->add_columns([
										[
											'title'   => $this->lang('Candidat'),
											'content' => function($data){
												if ($data['user_id'])
												{
													return $this->user->link($data['user_id'], $data['pseudo']);
												}
												else
												{
													return $data['pseudo'];
												}
											},
											'sort'    => function($data){
												return $data['pseudo'];
											},
											'search'  => function($data){
												return $data['pseudo'];
											}
										],
										[
											'title'   => $this->lang('Date'),
											'content' => function($data){
												return '<span data-bs-toggle="tooltip" title="'.timetostr($this->lang('l j F Y, H:i'), $data['date']).'">'.time_span($data['date']).'</span>';
											},
											'sort'    => function($data){
												return $data['date'];
											}
										],
										[
											'title'   => $this->lang('Adresse e-mail'),
											'content' => function($data){
												return '<a href="mailto:'.$data['email'].'">'.$data['email'].'</a>';
											},
											'sort'    => function($data){
												return $data['email'];
											},
											'search'  => function($data){
												return $data['email'];
											}
										],
										[
											'content' => [
												function($data){
													return ($this->is_authorized('candidacy_vote') || $this->is_authorized('candidacy_reply')) ? $this->button_update('admin/recruits/candidacy/'.$data['candidacy_id'].'/'.url_title($data['title'])) : NULL;
												},
												function($data){
													return $this->is_authorized('candidacy_delete') ? $this->button_delete('admin/recruits/candidacy/delete/'.$data['candidacy_id'].'/'.url_title($data['title'])) : NULL;
												}
											],
											'size'    => TRUE
										]
									])
									->data($this->model()->get_candidacies($recruit_id, 1))
									->no_data($this->lang('Aucune candidature en attente'))
									->display();

		$candidacies_accepted = $this->table()
									->add_columns([
										[
											'title'   => $this->lang('Candidat'),
											'content' => function($data){
												if ($data['user_id'])
												{
													return $this->user->link($data['user_id'], $data['username']);
												}
												else
												{
													return $data['pseudo'];
												}
											},
											'sort'    => function($data){
												return $data['pseudo'];
											},
											'search'  => function($data){
												return $data['pseudo'];
											}
										],
										[
											'title'   => $this->lang('Date'),
											'content' => function($data){
												return '<span data-bs-toggle="tooltip" title="'.timetostr($this->lang('l j F Y, H:i'), $data['date']).'">'.time_span($data['date']).'</span>';
											},
											'sort'    => function($data){
												return $data['date'];
											}
										],
										[
											'title'   => $this->lang('Adresse e-mail'),
											'content' => function($data){
												return '<a href="mailto:'.$data['email'].'">'.$data['email'].'</a>';
											},
											'sort'    => function($data){
												return $data['email'];
											},
											'search'  => function($data){
												return $data['email'];
											}
										],
										[
											'content' => [
												function($data){
													return ($this->is_authorized('candidacy_vote') || $this->is_authorized('candidacy_reply')) ? $this->button_update('admin/recruits/candidacy/'.$data['candidacy_id'].'/'.url_title($data['title'])) : NULL;
												},
												function($data){
													return $this->is_authorized('candidacy_delete') ? $this->button_delete('admin/recruits/candidacy/delete/'.$data['candidacy_id'].'/'.url_title($data['title'])) : NULL;
												}
											],
											'size'    => TRUE
										]
									])
									->data($this->model()->get_candidacies($recruit_id, 2))
									->no_data($this->lang('Aucune candidature acceptée'))
									->display();

		$candidacies_declined = $this->table()
									->add_columns([
										[
											'title'   => $this->lang('Candidat'),
											'content' => function($data){
												if ($data['user_id'])
												{
													return $this->user->link($data['user_id'], $data['username']);
												}
												else
												{
													return $data['pseudo'];
												}
											},
											'sort'    => function($data){
												return $data['pseudo'];
											},
											'search'  => function($data){
												return $data['pseudo'];
											}
										],
										[
											'title'   => $this->lang('Date'),
											'content' => function($data){
												return '<span data-bs-toggle="tooltip" title="'.timetostr($this->lang('l j F Y, H:i'), $data['date']).'">'.time_span($data['date']).'</span>';
											},
											'sort'    => function($data){
												return $data['date'];
											}
										],
										[
											'title'   => $this->lang('Adresse e-mail'),
											'content' => function($data){
												return '<a href="mailto:'.$data['email'].'">'.$data['email'].'</a>';
											},
											'sort'    => function($data){
												return $data['email'];
											},
											'search'  => function($data){
												return $data['email'];
											}
										],
										[
											'content' => [
												function($data){
													return ($this->is_authorized('candidacy_vote') || $this->is_authorized('candidacy_reply')) ? $this->button_update('admin/recruits/candidacy/'.$data['candidacy_id'].'/'.url_title($data['title'])) : NULL;
												},
												function($data){
													return $this->is_authorized('candidacy_delete') ? $this->button_delete('admin/recruits/candidacy/delete/'.$data['candidacy_id'].'/'.url_title($data['title'])) : NULL;
												}
											],
											'size'    => TRUE
										]
									])
									->data($this->model()->get_candidacies($recruit_id, 3))
									->no_data($this->lang('Aucune candidature refusée'))
									->display();

		return $this->array
					->append($this	->panel()
									->heading('Liste des candidatures', 'fas fa-briefcase')
									->body($this->view('admin-recruit-candidacies', [
														'table_pending'  => $candidacies_pending,
														'table_accepted' => $candidacies_accepted,
														'table_declined' => $candidacies_declined
													]))
									->size('col-12')
					)
					->append($this->panel_back());
	}

	public function _candidacies_edit($candidacy_id, $date, $user_id, $pseudo, $email, $date_of_birth, $presentation, $motivations, $experiences, $status, $reply, $recruit_id, $title, $icon, $role, $team_id, $team_name, $username, $avatar, $sex)
	{
		$this->subtitle($title);

		$reply_form = $this	->form()
							->add_rules([
								'reply' => [
									'label'  => $this->lang('Votre réponse'),
									'value'  => $reply,
									'type'   => 'editor',
									'rules'  => 'required'
								],
								'status' => [
									'label'  => $this->lang('Décision'),
									'value'  => $status,
									'values' => [
										'1' => $this->lang('En attente'),
										'2' => $this->lang('Acceptée'),
										'3' => $this->lang('Refusée')
									],
									'type'   => 'radio',
									'rules'  => 'required'
								]
							])
							->add_submit($this->lang('Envoyer la réponse'), 'fas fa-paper-plane')
							->save();

		if ($reply_form->is_valid($post))
		{
			$this->model()->update_candidacy(	$candidacy_id,
												$post['reply'],
												$post['status']);

			$this->contact_applicant($candidacy_id, $title, $post['reply'], $post['status']);

			if ($post['status'] == 2)
			{
				if ($team_id && $user_id && $this->model()->check_team($team_id, url_title($team_name)) && $status != 2 && $this->db->from('nf_teams_users')->where('user_id', $user_id)->where('team_id', $team_id)->empty())
				{
					if ($check_role = $this->model()->check_role($role))
					{
						$this->db->insert('nf_teams_users', [
							'team_id' => $team_id,
							'user_id' => $user_id,
							'role_id' => $check_role['role_id']
						]);
					}
					else
					{
						$role_id = $this->db->insert('nf_teams_roles', [
							'title' => $role
						]);

						$this->db->insert('nf_teams_users', [
							'team_id' => $team_id,
							'user_id' => $user_id,
							'role_id' => $role_id
						]);
					}
				}
			}

			notify($this->lang('Réponse envoyée avec succès'));

			redirect('admin/recruits/candidacy/'.$candidacy_id.'/'.url_title($title));
		}

		$total_votes = 0;
		$total_up = 0;
		$total_down = 0;

		foreach ($votes = $this->model()->get_votes($candidacy_id) as $vote)
		{
			if ($vote['vote'] == 1)
			{
				$total_up += 1;
			}
			else
			{
				$total_down += 1;
			}

			$total_votes += 1;
		}

		$user_vote = $this->db	->from('nf_recruits_candidacies_votes')
								->where('candidacy_id', $candidacy_id)
								->where('user_id', $this->user->id)
								->row();

		$vote_form = $this	->form()
							->add_rules([
								'vote' => [
									'label'  => $this->lang('Je suis'),
									'value'  => isset($user_vote['vote']) ? $user_vote['vote'] : NULL,
									'values' => [
										'1' => icon('far fa-thumbs-up').' <span class="text-green">'.$this->lang('Favorable').'</span>',
										'0' => icon('far fa-thumbs-down').' <span class="text-red">'.$this->lang('Défavorable').'</span>'
									],
									'type'   => 'radio',
									'rules'  => 'required'
								],
								'comment' => [
									'label'  => $this->lang('Commentaire'),
									'type'   => 'textarea',
									'value'  => isset($user_vote['comment']) ? $user_vote['comment'] : NULL,
									'rules'  => 'required'
								]
							])
							->add_submit($this->lang('Envoyer mon avis'), 'fas fa-paper-plane')
							->save();

		if ($vote_form->is_valid($post))
		{
			if ($user_vote)
			{
				$this->model()->update_vote($this->user->id,
											$candidacy_id,
											$post['vote'],
											$post['comment']);

				notify($this->lang('Vote modifié avec succès'));
			}
			else
			{
				$this->model()->send_vote(	$candidacy_id,
											$post['vote'],
											$post['comment']);

				notify($this->lang('Vote envoyé avec succès'));
			}

			refresh();
		}

		if ($status == 1)
		{
			$statut_heading = icon('fas fa-hourglass-end').' '.$this->lang('Candidature <b>en cours d\'examen</b>');
			$statut_color   = 'text-bg-secondary';
		}
		else if ($status == 2)
		{
			$statut_heading = icon('fas fa-check').' '.$this->lang('Candidature <b>acceptée</b>');
			$statut_color   = 'text-bg-success';
		}
		else
		{
			$statut_heading = icon('fas fa-times').' '.$this->lang('Candidature <b>refusée</b>');
			$statut_color   = 'text-bg-danger';
		}

		return $this->row(
			$this->col(
				$this	->panel_box()
						->heading($statut_heading, '', 'admin/recruits/candidacies/'.$recruit_id.'/'.url_title($title))
						->color($statut_color)
						->footer(icon('fas fa-arrow-circle-left').' '.$this->lang('Retour aux candidatures de cette offre')),
				$this	->panel()
						->heading($this->lang('Candidature de %s', '<b>'.nf_texte($pseudo).'</b>').' <a href="mailto:'.$email.'" class="btn btn-outline-secondary btn-sm ms-2" data-bs-toggle="tooltip" title="'.$this->lang('Contacter par e-mail').'">'.icon('far fa-envelope').'</a>', 'fab fa-black-tie')
						->body($this->view('candidacy', [
							'candidacy_id'  => $candidacy_id,
							'custom'        => $this->model()->get_candidacy_custom($candidacy_id),
							'date'          => $date,
							'user_id'       => $user_id,
							'pseudo'        => $pseudo,
							'email'         => $email,
							'role'          => $role,
							'date_of_birth' => $date_of_birth,
							'presentation'  => bbcode($presentation),
							'motivations'   => bbcode($motivations),
							'experiences'   => bbcode($experiences),
							'status'        => $status,
							'reply'         => $reply,
							'title'         => $title,
							'icon'          => $icon,
							'username'      => $username,
							'avatar'        => $avatar,
							'sex'           => $sex,
							'team_id'       => $team_id,
							'team_name'     => $team_name
						])),
				$this	->panel()
						->heading($this->lang('Réponse au candidat'), 'fas fa-lock')
						->body($this->is_authorized('candidacy_reply') ? $reply_form->display() : '<span class="text-red">'.$this->lang('Vous n\'êtes pas autorisé à gérer le statut de la candidature.').'</span>')
						->size('col-12 col-lg-7'),
				$this->button_back()
			),
			$this->col(
				$this	->panel()
						->heading($this->lang('Tendance des votes').' <span class="ms-2 small text-muted">'.$total_up.' '.icon('far fa-thumbs-up text-success').' &middot; '.$total_down.' '.icon('far fa-thumbs-down text-danger').'</span>', 'far fa-comment-dots')
						->body($this->view('admin-candidacy-status', [
							'status' => $status,
							'votes'  => $votes
						])),
				$this	->panel()
						->heading('Mon avis sur la candidature', 'far fa-star')
						->body($this->is_authorized('candidacy_vote') ? $vote_form->display() : '<span class="text-red">'.$this->lang('Vous n\'êtes pas autorisé à déposer votre avis.').'</span>')
						->size('col-12 col-lg-5')
			)
		);
	}

	public function _candidacies_delete($candidacy_id, $pseudo, $title)
	{
		$this	->title($this->lang('Suppression candidature'))
				->subtitle($title)
				->form()
				->confirm_deletion($this->lang('Confirmation de suppression'), $this->lang('Êtes-vous sûr(e) de vouloir supprimer la candidature de <b>%s</b> ?<br />Tous les avis associés à cette candidature seront aussi supprimés.', $pseudo));

		if ($this->form()->is_valid())
		{
			$this->model()->delete_candidacy($candidacy_id);

			return 'OK';
		}

		return $this->form()->display();
	}

	public function contact_applicant($candidacy_id, $title, $reply, $status)
	{
		if ($candidacy = $this->model()->check_candidacy($candidacy_id, url_title($title)))
		{
			if ($this->config->recruits_send_mp && $candidacy['user_id'])
			{
				if ($status == 2)
				{
					$message = '<div class="alert alert-success">'.$this->lang('Votre candidature a été <b>acceptée</b>. Félicitations !').'</div>'.$reply;
				}
				else if ($status == 3)
				{
					$message = '<div class="alert alert-danger">'.$this->lang('Votre candidature a été <b>refusée</b>. Désolé !').'</div>'.$reply;
				}
				else
				{
					$message = $reply;
				}

				// Migration MP → Talks : réponse à candidature en conversation direct admin ↔ candidat.
				if ($candidacy['user_id'] && (int)$candidacy['user_id'] !== (int)$this->user->id && ($talks = $this->module('talks')))
				{
					try
					{
						$talk_id = $talks->model()->create_conversation(
							(int)$this->user->id,
							'direct',
							(string) $this->lang('Candidature : %s', $candidacy['title']),
							'',
							[(int)$candidacy['user_id']]
						);
						if ($talk_id)
						{
							$talks->model()->send_message($talk_id, (int)$this->user->id, $message);
						}
					}
					catch (\Throwable $e) {}
				}
			}

			if ($this->config->recruits_send_mail && $candidacy['email'])
			{
				$this	->email
						// Expéditeur : l'adresse du site (défaut de la bibliothèque). Le repli sur l'adresse personnelle
						// de l'administrateur usurpait son domaine et la divulguait au candidat.
						->to($candidacy['email'])
						->subject((string) $this->lang('Candidature : %s', $candidacy['title']))
						->message('default', [
							'content' => bbcode($reply).($this->user() ? '<br /><br /><br />'.$this->user->link() : '')
						])
						->send();
			}
		}
	}

	public function _fields($recruit_id, $title)
	{
		$this	->title($this->lang('Personnaliser le formulaire'))
				->subtitle($title)
				->form()
				->add_rules([
					'label'    => ['label' => $this->lang('Libellé de la question'), 'type' => 'text', 'rules' => 'required'],
					'type'     => ['label' => $this->lang('Type de champ'), 'type' => 'select', 'values' => ['text' => $this->lang('Texte court'), 'textarea' => $this->lang('Texte long')], 'value' => 'text'],
					'required' => ['label' => $this->lang('Obligatoire'), 'type' => 'checkbox', 'values' => ['1' => $this->lang('Réponse obligatoire')]]
				])
				->add_back('admin/recruits/'.$recruit_id.'/'.url_title($title))
				->add_submit($this->lang('Ajouter le champ'), 'fas fa-plus');

		if ($this->form()->is_valid($post))
		{
			$this->model()->add_field($recruit_id, $post['label'], $post['type'], in_array('1', $post['required'] ?? []));
			notify($this->lang('Champ ajouté.'));
			redirect('admin/recruits/fields/'.$recruit_id.'/'.url_title($title));
		}

		$fields = $this->model()->get_fields($recruit_id);

		if (empty($fields))
		{
			$list = '<p class="text-muted">'.$this->lang('Aucun champ personnalisé. Ajoutez-en avec le formulaire ci-dessous.').'</p>';
		}
		else
		{
			$list = '<ul class="list-group mb-3">';

			foreach ($fields as $f)
			{
				$meta  = $f['type'] === 'textarea' ? $this->lang('texte long') : $this->lang('texte court');
				$meta .= $f['required'] ? ', '.$this->lang('obligatoire') : '';

				$list .= '<li class="list-group-item d-flex justify-content-between align-items-center">'
						.'<span>'.nf_texte($f['label']).' <small class="text-muted">('.$meta.')</small></span>'
						.'<a href="'.url('admin/recruits/fields/delete/'.$f['field_id'].'/'.url_title($title)).'" class="btn btn-sm btn-outline-danger" data-confirm="'.nf_texte($this->lang('Supprimer ce champ ?')).'">'.icon('far fa-trash-alt').'</a>'
						.'</li>';
			}

			$list .= '</ul>';
		}

		return $this->panel()
					->heading($this->lang('Personnaliser le formulaire').' — '.nf_texte($title), 'fas fa-sliders-h')
					->body($list.'<hr />'.$this->form()->display())
					->size('col-12');
	}

	public function _field_delete($field_id, $recruit_id)
	{
		$this->model()->delete_field($field_id, $recruit_id);
		notify($this->lang('Champ supprimé.'));
		redirect_back('admin/recruits');
	}
}
