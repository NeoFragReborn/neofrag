<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\Recruits\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Index extends Controller_Module
{
	public function index($recruits)
	{
		$panels = $this->array;

		foreach ($recruits as $recruit)
		{
			if (($recruit['closed'] || ($recruit['candidacies_accepted'] >= $recruit['size']) || ($recruit['date_end'] && strtotime($recruit['date_end']) < time())) && !$this->config->recruits_hide_unavailable)
			{
				$panels->append($this	->panel()
										->heading($this->no_translate($recruit['title']), $recruit['icon'] ?: 'fas fa-bullhorn') // titre saisi en base
										->body($this->lang('Cette offre n\'est plus disponible actuellement.'))
										->color('info'));
			}
			else
			{
				if ($candidacy = $this->model()->postulated($this->user->id, $recruit['recruit_id'], $recruit['title']))
				{
					$footer = '<a href="'.url('recruits/candidacy/'.$candidacy['candidacy_id'].'/'.url_title($recruit['title'])).'" class="btn btn-primary">'.icon('fas fa-briefcase').' '.$this->lang('Voir ma candidature').'</a>';
				}
				else
				{
					// « Postuler » seulement pour qui en a la permission : le checker de `postulate` la
					// vérifie, et le bouton menait sinon à un refus.
					$footer = '<a href="'.url('recruits/'.$recruit['recruit_id'].'/'.url_title($recruit['title'])).'" class="btn btn-light">'.icon('far fa-eye').' '.$this->lang('En savoir plus').'</a>'
							.($this->access('recruits', 'recruit_postulate', $recruit['recruit_id']) ? ' <a href="'.url('recruits/postulate/'.$recruit['recruit_id'].'/'.url_title($recruit['title'])).'" class="btn btn-primary">'.icon('fas fa-briefcase').' '.$this->lang('Postuler').'</a>' : '');
				}

				$panels->append($this	->panel()
										->heading($this->no_translate($candidacy ? $recruit['title'].'<div class="float-end"><span class="badge text-bg-dark">'.$this->lang('J\'ai postulé !').'</span></div>' : $recruit['title']), $recruit['icon'] ?: 'fas fa-bullhorn', 'recruits/'.$recruit['recruit_id'].'/'.url_title($recruit['title'])) // titre saisi en base
										->body($this->view('index', [
											'recruit_id'   => $recruit['recruit_id'],
											'title'        => $recruit['title'],
											'image_id'     => $recruit['image_id'],
											'date'         => $recruit['date'],
											'team_id'      => $recruit['team_id'],
											'team_name'    => $recruit['team_name'],
											'role'         => $recruit['role'],
											'size'         => $recruit['size'] - $recruit['candidacies_accepted'],
											'date_end'     => $recruit['date_end'],
											'introduction' => bbcode($recruit['introduction'])
										]))
										->footer_if($footer, $footer, 'right'));
			}
		}

		if ($panels->empty())
		{
			$panels->append($this	->panel()
									->heading($this->lang('Recrutement'), 'fas fa-bullhorn')
									->body('<div class="text-center">'.$this->lang('Aucune offre n\'a été publiée pour le moment').'</div>')
									->color('info'));
		}
		else
		{
			$panels->append($this->module->pagination->panel());
		}

		return $panels;
	}

	public function _recruit($recruit_id, $title, $introduction, $description, $requierments, $date, $user_id, $size, $role, $icon, $date_end, $closed, $team_id, $image_id, $username, $avatar, $sex, $candidacies, $candidacies_pending, $candidacies_accepted, $candidacies_declined, $team_name)
	{
		// Une offre n'a pas de langue à elle : sa canonique est dans la langue première du site.
		nf_seo_sans_langue();

		$this->title($title);

		if (($this->access('recruits', 'recruit_postulate', $recruit_id)) && (!$date_end || strtotime($date_end) > time()))
		{
			if ($candidacy = $this->model()->postulated($this->user->id, $recruit_id, $title))
			{
				$href                  = '<a href="'.url('recruits/candidacy/'.$candidacy['candidacy_id'].'/'.url_title($candidacy['title'])).'" class="btn btn-success">'.icon('far fa-eye').' '.$this->lang('Voir ma candidature').'</a>';
				$recruit['postulated'] = TRUE;
			}
			else
			{
				// Les mêmes conditions que le checker de `postulate` : une offre fermée ou complète
				// affichait « Postuler », et le clic menait à un refus (403, trouvé par check-liens le
				// 2026-09-22). La permission et la date sont déjà vérifiées au-dessus.
				if (!$closed && $candidacies_accepted < $size)
				{
					$href = '<a href="'.url('recruits/postulate/'.$recruit_id.'/'.url_title($title)).'" class="btn btn-primary d-block w-100">'.icon('fas fa-briefcase').' '.$this->lang('Postuler').'</a>';
				}
				else
				{
					$href = NULL;
				}

				$recruit['postulated'] = FALSE;
			}

			$postulate_panel = $this->panel()
									->heading($recruit['postulated'] ? $this->lang('J\'ai postulé') : $this->lang('Postuler'), $recruit['postulated'] ? 'fas fa-check' : 'fab fa-black-tie')
									->body($recruit['postulated'] ? $this->view('recruit-postulate', [
														'postulated' => $recruit['postulated'],
														'status'     => $candidacy['status']
													]) : $this->lang('Vous n\'avez pas encore déposé de candidature pour cette offre de recrutement.'))
									->footer($href);
		}
		else
		{
			$postulate_panel = $this->panel()
									->heading($this->lang('Postuler'), 'fab fa-black-tie')
									->body($this->lang('Vous n\'êtes pas autorisé à déposer de candidature pour cette offre...'))
									->color('info');
		}

		return $this->array
					->append(
						$this->row(
							$this->col(
								$this	->panel()
										->heading($title, ($icon ? $icon : 'fas fa-bullhorn'))
										->body($this->view('recruit', [
											'recruit_id'   => $recruit_id,
											'title'        => $title,
											'introduction' => bbcode($introduction),
											'description'  => bbcode($description),
											'requierments' => bbcode($requierments),
											'date'         => $date,
											'user_id'      => $user_id,
											'size'         => $size - $candidacies_accepted,
											'role'         => $role,
											'icon'         => $icon,
											'date_end'     => $date_end,
											'closed'       => $closed,
											'team_id'      => $team_id,
											'image_id'     => $image_id
										]))
							)
						)
					)
					->append(
						$this->row(
							$this	->col(
										$this	->panel()
												->heading('Informations', 'fas fa-info')
												->body($this->view('recruit-infos', [
																			'role'      => $role,
																			'size'      => $size,
																			'date_end'  => $date_end,
																			'team_id'   => $team_id,
																			'team_name' => $team_name
																		]))
									)
									->size('col-12 col-lg-6'),
							$this	->col($postulate_panel)
									->size('col-12 col-lg-6')
						)
					);
	}

	public function _postulate($recruit_id, $title, $introduction, $description, $requierments, $date, $recruit_user_id, $size, $role, $icon, $date_end, $closed, $team_id, $image_id, $username, $avatar, $sex, $candidacies, $candidacies_pending, $candidacies_accepted, $candidacies_declined, $team_name)
	{
		if ($candidacy = $this->model()->postulated($this->user->id, $recruit_id, $title))
		{
			return $this->panel()
						->heading($this->lang('Déposer ma candidature'), 'fab fa-black-tie')
						->body($this->lang('Vous avez déjà déposé votre candidature pour cette offre le <b>%s</b> !', timetostr($this->lang('j M Y'), $candidacy['date'])))
						->footer('<a href="'.url('recruits/candidacy/'.$candidacy['candidacy_id'].'/'.url_title($candidacy['title'])).'" class="btn btn-primary">'.icon('far fa-eye').' '.$this->lang('Voir ma candidature').'</a>')
						->color('info');
		}
		else
		{
			if ($candidacies_accepted < $size && $closed == FALSE && (!$date_end || strtotime($date_end) > time()))
			{
				$custom_fields = $this->model()->get_fields($recruit_id);
				$custom_rules  = [];
				foreach ($custom_fields as $cf)
				{
					$custom_rules['custom_'.$cf['field_id']] = [
						'label' => $cf['label'],
						'type'  => $cf['type'] === 'textarea' ? 'textarea' : 'text',
						'rules' => $cf['required'] ? 'required' : ''
					];
				}

				$this	->form()
						->add_rules($rules = [
							'pseudo' => [
								'label' => $this->lang('Votre pseudo'),
								'value' => $this->user->username,
								'type'  => 'text',
								'rules' => 'required'
							],
							'email' => [
								'label' => $this->lang('Adresse email'),
								'value' => $this->user->email,
								'type'  => 'email',
								'rules' => 'required'
							],
							'date_of_birth' => [
								'label' => $this->lang('Date de naissance'),
								'value' => $this->user->date_of_birth,
								'type'  => 'date',
								'check' => function($value){
									if ($value && strtotime($value) > strtotime(date('Y-m-d')))
									{
										return $this->lang('Vraiment ?! 2.1 Gigowatt !');
									}
								},
								'rules' => 'required'
							],
							'presentation' => [
								'label' => $this->lang('Présentez-vous'),
								'type'  => 'editor'
							],
							'motivations' => [
								'label' => $this->lang('Vos motivations'),
								'type'  => 'editor'
							],
							'experiences' => [
								'label' => $this->lang('Expériences'),
								'type'  => 'editor'
							]
						])
						->add_rules($custom_rules)
						->add_captcha()
						->add_submit($this->lang('Envoyer ma candidature'), 'fas fa-paper-plane');

				if ($this->form()->is_valid($post))
				{
					$custom_answers = [];
					foreach ($custom_fields as $cf)
					{
						$custom_answers[] = ['label' => $cf['label'], 'value' => $post['custom_'.$cf['field_id']] ?? ''];
					}

					$candidacy_id = $this->model()->send_candidacy(	$recruit_id,
																	$this->user->id,
																	$this->user->username ?: $post['pseudo'],
																	$this->user->email    ?: $post['email'],
																	$post['date_of_birth'],
																	$post['presentation'],
																	$post['motivations'],
																	$post['experiences'],
																	$custom_answers ? json_encode($custom_answers) : NULL);

					if ($this->config->recruits_alert && $this->user->id)
					{
						$users =  $this->db	->select('*')
											->from('nf_user')
											->where('deleted', FALSE)
											->get();

						$recipients = [];
						foreach ($users as $user)
						{
							if ($this->access('recruits', 'candidacy_vote', 0, NULL, $user['id']) || $this->access('recruits', 'candidacy_reply', 0, NULL, $user['id']))
							{
								$recipients[] = $user;
							}
						}

						// Migration MP → Talks : notification aux décideurs via conversation direct.
						// Si candidat anonyme (pas de $this->user->id), on prend le premier décideur comme expéditeur (bot-style).
						if ($recipients && ($talks = $this->module('talks')))
						{
							try
							{
								$sender_id = (int)$this->user->id ?: (int)$recipients[0]['id'];
								$body      = '<div class="alert alert-info m-0"><b>'.$this->lang('Message automatique.').'</b><br />'
								           .$this->lang('Une nouvelle candidature vient d\'être déposée par %s.', nf_texte($this->user->id ? $this->user->username : $post['pseudo']))
								           .'<br /><br />'.$this->lang('Pour la visualiser, <a href="%s">cliquer ici</a>.', url('admin/recruits/candidacy/'.$candidacy_id.'/'.url_title($title))).'</div>';

								foreach ($recipients as $recipient)
								{
									if ((int)$recipient['id'] === $sender_id) continue;

									$talk_id = $talks->model()->create_conversation(
										$sender_id,
										'direct',
										(string) $this->lang('Candidature : %s', $title),
										'',
										[(int)$recipient['id']]
									);
									if ($talk_id)
									{
										$talks->model()->send_message($talk_id, $sender_id, $body);
									}
								}
							}
							catch (\Throwable $e) {}
						}
					}

					notify($this->lang('Candidature envoyée avec succès'));

					if ($this->user())
					{
						redirect('recruits/candidacy/'.$candidacy_id.'/'.url_title($title));
					}
					else
					{
						redirect('recruits');
					}
				}

				return $this->panel()
							->heading($this->lang('Déposer ma candidature'), 'fab fa-black-tie')
							->body($this->view('postulate', [
													'recruit_id'   => $recruit_id,
													'title'        => $title,
													'role'         => $role,
													'icon'         => $icon,
													'date_end'     => $date_end,
													'form'         => $this->form()->display()
												]));
			}
			else
			{
				return $this->panel()
							->heading($this->lang('Déposer ma candidature'), 'fab fa-black-tie')
							->body($this->lang('Oops... L\'offre <b>%s</b> n\'est plus disponible...', $title))
							->color('danger');
			}
		}
	}

	public function _candidacy($candidacy_id, $recruit_id, $date, $user_id, $pseudo, $email, $date_of_birth, $presentation, $motivations, $experiences, $status, $reply_text, $title, $icon, $role, $team_id, $team_name, $username, $avatar, $sex)
	{
		return $this->array
					->append($this	->panel()
									->heading('Statut de ma candidature', 'fas fa-reply')
									->body($this->view('candidacy-status', [
										'status'     => $status,
										'reply_text' => bbcode($reply_text)
									]))
					)
					->append($this	->panel()
									->heading('Ma candidature', 'fab fa-black-tie')
									->body($this->view('candidacy', [
										'candidacy_id'  => $candidacy_id,
										'custom'        => $this->model()->get_candidacy_custom($candidacy_id),
										'recruit_id'    => $recruit_id,
										'date'          => $date,
										'user_id'       => $user_id,
										'pseudo'        => $pseudo,
										'email'         => $email,
										'role'          => $role,
										'date_of_birth' => $date_of_birth,
										'presentation'  => bbcode($presentation),
										'motivations'   => bbcode($motivations),
										'experiences'   => bbcode($experiences),
										'reply'         => bbcode($reply_text),
										'title'         => $title,
										'icon'          => $icon,
										'username'      => $username,
										'avatar'        => $avatar,
										'sex'           => $sex,
										'team_id'       => $team_id,
										'team_name'     => $team_name
									]))
					)
					->append($this->panel_back());
	}
}
