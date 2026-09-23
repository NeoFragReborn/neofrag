<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\Recruits;

use NF\NeoFrag\Addons\Module;

class Recruits extends Module
{
	protected function __info()
	{
		return [
			'title'       => $this->lang('Recrutements'),
			'description' => $this->lang('Recrutement de joueurs avec candidatures et système de votes — module gaming.'),
			'icon'        => 'fas fa-bullhorn',
			'link'        => 'https://neofr.ag',
			'author'      => 'Michaël BILCOT & Jérémy VALENTIN <contact@neofrag.com>',
			'license'     => 'LGPLv3 <https://neofr.ag/license>',
			// Decouplage du paquet : cf. tools/check-addon-declarations.php.
			'core'        => FALSE,
			'presets'     => ['gaming'],
			'requires'    => ['teams', 'games'],
			'admin'       => TRUE,
			'version'     => '1.0',
			'depends'     => [
				'neofrag' => '0.2.0'
			],
			'routes'      => [
				//Index
				'{page}'                                  => 'index',
				'{id}/{url_title}'                        => '_recruit',
				'postulate/{id}/{url_title}'              => '_postulate',
				'candidacy/{id}/{url_title}'              => '_candidacy',
				//Admin
				'admin{pages}'                            => 'index',
				'admin/pending' => 'pending',
				'admin/fields/delete/{id}/{url_title}' => '_field_delete',
				'admin/fields/{id}/{url_title}'        => '_fields',
				'admin/{id}/{url_title}'                  => '_edit',
				'admin/delete/{id}/{url_title}'           => '_delete',
				'admin/candidacies/{id}/{url_title}'      => '_candidacies',
				'admin/candidacy/{id}/{url_title}'        => '_candidacies_edit',
				'admin/candidacy/delete/{id}/{url_title}' => '_candidacies_delete'
			],
			'settings'    => function(){
				return $this->form2()
							->legend($this->lang('Paramètres des offres'))
							->rule($this->form_number('recruits_per_page')
										->title($this->lang('Nombre d\'offre par page'))
										->value($this->config->recruits_per_page ?: '5')
							)
							->rule($this->form_checkbox('recruits_hide_unavailable')
										->data(['on' => $this->lang('Masquer les offres indisponibles')])
										->value([$this->config->recruits_hide_unavailable ? 'on' : NULL])
							)
							->rule($this->form_checkbox('recruits_alert')
										->data(['on' => $this->lang('Être avertis par message privé des nouvelles candidatures')])
										->value([$this->config->recruits_alert ? 'on' : NULL])
							)
							->legend($this->lang('Réponse aux candidats'))
							->rule($this->form_checkbox('recruits_send')
										->data([
											'mp'   => $this->lang('Par message privé'),
											'mail' => $this->lang('Par e-mail')
										])
										->value([
											$this->config->recruits_send_mp ? 'mp' : NULL,
											$this->config->recruits_send_mail ? 'mail' : NULL
										])
							)
							->success(function($data){
								$this	->config('recruits_per_page', $data['recruits_per_page'])
										->config('recruits_hide_unavailable', in_array('on', $data['recruits_hide_unavailable']))
										->config('recruits_alert', in_array('on', $data['recruits_alert']))
										->config('recruits_send_mp', in_array('mp', $data['recruits_send']))
										->config('recruits_send_mail', in_array('mail', $data['recruits_send']));
								notify($this->lang('Configuration modifiée'));
								refresh();
							});
			}
		];
	}

	public function permissions()
	{
		return [
			'default' => [
				'access'  => [
					[
						'title'  => $this->lang('Offres de recrutement'),
						'icon'   => 'fas fa-bullhorn',
						'access' => [
							'add_recruit' => [
								'title' => $this->lang('Ajouter'),
								'icon'  => 'fas fa-plus',
								'admin' => TRUE
							],
							'modify_recruit' => [
								'title' => $this->lang('Modifier'),
								'icon'  => 'fas fa-edit',
								'admin' => TRUE
							],
							'delete_recruit' => [
								'title' => $this->lang('Supprimer'),
								'icon'  => 'far fa-trash-alt',
								'admin' => TRUE
							]
						]
					],
					[
						'title'  => $this->lang('Candidatures'),
						'icon'   => 'fab fa-black-tie',
						'access' => [
							'candidacy_vote' => [
								'title' => $this->lang('Déposer son avis'),
								'icon'  => 'far fa-star',
								'admin' => TRUE
							],
							'candidacy_reply' => [
								'title' => $this->lang('Accepter / Refuser les candidats'),
								'icon'  => 'fas fa-lock',
								'admin' => TRUE
							],
							'candidacy_delete' => [
								'title' => $this->lang('Supprimer'),
								'icon'  => 'far fa-trash-alt',
								'admin' => TRUE
							]
						]
					]
				]
			],
			'recruit' => [
				'get_all' => function(){
					// Le libellé se compose en PHP, après la requête (même règle que modules/files/files.php).
					return array_map(fn($ligne) => [
						'recruit_id' => $ligne['recruit_id'],
						'title'      => (string) $this->lang('Offre %s', $ligne['title'])
					], NeoFrag()->db->select('recruit_id', 'title')->from('nf_recruits')->get());
				},
				'check' => function($recruit_id){
					if (($recruit = NeoFrag()->db->select('title')->from('nf_recruits')->where('recruit_id', $recruit_id)->row()) !== [])
					{
						return (string) $this->lang('Offre %s', $recruit);
					}
				},
				'init' => [
					'recruit_postulate' => [
						['visitors', FALSE]
					]
				],
				'access' => [
					[
						'title'  => $this->lang('Postulants'),
						'icon'   => 'fab fa-black-tie',
						'access' => [
							'recruit_postulate' => [
								'title' => $this->lang('Déposer une candidature'),
								'icon'  => 'fas fa-briefcase'
							]
						]
					]
				]
			]
		];
	}
}
