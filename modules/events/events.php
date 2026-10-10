<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\Events;

use NF\NeoFrag\Addons\Module;

class Events extends Module
{
	protected function __info()
	{
		return [
			'title'       => $this->lang('Événements'),
			'description' => $this->lang('Événements et matchs d\'une guilde ou d\'une équipe eSport : invitations avec réponse présent, absent ou peut-être, scores par manche, récurrence, rappels.'),
			'icon'        => 'fas fa-trophy',
			'link'        => 'https://neofrag-reborn.xyz',
			'author'      => 'Michaël BILCOT & Jérémy VALENTIN <contact@neofrag.com>',
			'license'     => 'LGPLv3 <https://www.gnu.org/licenses/lgpl-3.0.html>',
			// Decouplage du paquet : cf. tools/check-addon-declarations.php.
			'core'        => FALSE,
			'presets'     => ['gaming'],
			'requires'    => ['games', 'teams'],
			'admin'       => TRUE,
			'version'     => '1.0',
			'depends'     => [
				'neofrag' => '0.2.0'
			],
			'routes'      => [
				//Index
				'{page}'                                    => 'index',
				'upcoming{page}'                            => 'upcoming',
				// La cible d'une route nomme la methode du CONTROLEUR, pas celle du checker : le
				// checker est toujours cherche avec un tiret bas devant. Cote public le contrôleur
				// ecrit `standards()`, cote administration `_standards()` — d'ou l'asymetrie des
				// deux blocs, qui est voulue. Verifie dans les deux sens : mettre le tiret bas ici
				// fait rendre 404.
				'standards{page}'                           => 'standards',
				'matches{page}'                             => 'matches',
				'{id}/{url_title}'                          => '_event',
				'type/{id}/{url_title}{page}'               => '_type',
				'team/{id}/{url_title}{page}'               => '_team',
				'participant/{id}/{url_title}/{id}'         => '_participant_add',
				'participant/delete/{id}/{url_title}/{id}'  => '_participant_delete',

				//Ajax
				'ajax/{id}/{url_title}'                     => '_event',

				//Admin
				// Les trois onglets de filtre — « Standards », « Résultats », « Matchs à jouer » —
				// sont rendus par la MÊME vue côté public et côté administration, et pointent donc
				// vers `admin/events/standards` et compagnie. Ces routes n'existaient pas : les
				// trois onglets menaient à un 404. Les méthodes, elles, étaient déjà écrites dans le
				// contrôleur ET dans son checker. Déclarées AVANT `admin{pages}`, qui sinon les
				// avale.
				// Les cibles suivent les noms RÉELS des méthodes du checker — `_standards()`,
				// `_matches()`, mais `upcoming()` sans tiret bas — et non une symétrie supposée.
				'admin/standards{page}'                     => '_standards',
				'admin/matches{page}'                       => '_matches',
				'admin/upcoming{page}'                      => 'upcoming',
				'admin{pages}'                              => 'index',
				'admin/{id}/{url_title}'                    => '_edit',
				'admin/delete/{id}/{url_title}'             => '_delete',
				'admin/delete-series/{id}/{url_title}'      => '_delete_series',
				'admin/types/add'                           => '_types_add',
				'admin/types/{id}/{url_title}'              => '_types_edit',
				'admin/types/delete/{id}/{url_title}'       => '_types_delete',
				'admin/opponents'                           => '_opponents',
				'admin/opponents/add'                       => '_opponents_add',
				'admin/opponents/{id}/{url_title}'          => '_opponents_edit',
				'admin/opponents/delete/{id}/{url_title}'   => '_opponents_delete',
				'admin/rounds/delete/{id}/{url_title}/{id}' => '_round_delete'
			],
			'settings'    => function(){
				return $this->form2()
							->rule($this->form_number('events_per_page')
										->title($this->lang('Nombre d\'événement par page'))
										->value($this->config->events_per_page ?: '10')
							)
							->rule($this->form_checkbox('events_alert_mp')
										->data([
											'on' => $this->lang('Être averti par message privé des invitations')
										])
										->value([$this->config->events_alert_mp ? 'on' : NULL])
							)
							->rule($this->form_number('events_reminder_hours')
										->title($this->lang('Rappel automatique avant un événement (heures, 0 = désactivé)'))
										->value($this->config->events_reminder_hours === FALSE ? '24' : (string)$this->config->events_reminder_hours)
							)
							->success(function($data){
								$this	->config('events_per_page', $data['events_per_page'])
										->config('events_alert_mp', in_array('on', $data['events_alert_mp']))
										->config('events_reminder_hours', max(0, (int)$data['events_reminder_hours']));
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
						'title'  => $this->lang('Événements'),
						'icon'   => 'fas fa-calendar-alt',
						'access' => [
							'add_event' => [
								'title' => $this->lang('Ajouter'),
								'icon'  => 'fas fa-plus',
								'admin' => TRUE
							],
							'modify_event' => [
								'title' => $this->lang('Modifier'),
								'icon'  => 'fas fa-edit',
								'admin' => TRUE
							],
							'delete_event' => [
								'title' => $this->lang('Supprimer'),
								'icon'  => 'far fa-trash-alt',
								'admin' => TRUE
							]
						]
					],
					[
						'title'  => $this->lang('Types'),
						'icon'   => 'far fa-bookmark',
						'access' => [
							'add_events_type' => [
								'title' => $this->lang('Ajouter un type'),
								'icon'  => 'fas fa-plus',
								'admin' => TRUE
							],
							'modify_events_type' => [
								'title' => $this->lang('Modifier un type'),
								'icon'  => 'fas fa-edit',
								'admin' => TRUE
							],
							'delete_events_type' => [
								'title' => $this->lang('Supprimer un type'),
								'icon'  => 'far fa-trash-alt',
								'admin' => TRUE
							]
						]
					]
				]
			],
			'type' => [
				'get_all' => function(){
					// Le libellé se compose en PHP, après la requête : écrit dans le SQL, le mot restait en
					// français dans toutes les langues (même règle que modules/files/files.php).
					return array_map(fn($ligne) => [
						'type_id' => $ligne['type_id'],
						'title'   => (string) $this->lang('Type %s', $ligne['title'])
					], NeoFrag()->db->select('type_id', 'title')->from('nf_events_types')->get());
				},
				'check'   => function($type_id){
					if (($type = NeoFrag()->db->select('title')->from('nf_events_types')->where('type_id', $type_id)->row()) !== [])
					{
						return (string) $this->lang('Type %s', $type);
					}
				},
				'init'    => [
					'access_events_type' => []
				],
				'access'  => [
					[
						'title'  => $this->lang('Types'),
						'icon'   => 'far fa-bookmark',
						'access' => [
							'access_events_type' => [
								'title' => $this->lang('Visibilité'),
								'icon'  => 'far fa-eye'
							]
						]
					]
				]
			]
		];
	}

	/**
	 * Un événement se montre s'il est publié, paru, et de l'un des types que celui qui regarde peut voir (la règle de
	 * check_event()) — pour qui le cite hors de sa page (Module::content_visible_of()).
	 */
	public function contenu_visible(string $type, int $id): bool
	{
		$type_id = $type === 'events'
			? $this->db->select('type_id')->from('nf_events')->where('event_id', $id)->where('published', TRUE)->where('(publish_date IS NULL OR publish_date <= NOW())')->row()
			: NULL;

		return is_numeric($type_id) && $this->access('events', 'access_events_type', (int) $type_id);
	}

	public function comments($event_id)
	{
		$event = $this->db	->select('title')
							->from('nf_events')
							->where('event_id', $event_id)
							->row();

		if ($event)
		{
			return [
				'title' => $event,
				'url'   => 'events/'.$event_id.'/'.url_title($event)
			];
		}
	}

	/**
	 * Les notifications que ce module envoie, pour les préférences de chaque membre (Notifications::types(),
	 * chantier A, étape A4).
	 *
	 * @return list<array<string, mixed>>
	 */
	public function types_de_notification(): array
	{
		return [
			['type' => 'event-invite',   'titre' => (string) $this->lang('Une invitation à un événement'), 'ordre' => 40],
			['type' => 'event-reminder', 'titre' => (string) $this->lang('Le rappel d’un événement où je participe'), 'ordre' => 41],
		];
	}
}
