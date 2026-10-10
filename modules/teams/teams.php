<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\Teams;

use NF\NeoFrag\Addons\Module;

class Teams extends Module
{
	protected function __info()
	{
		return [
			'title'       => $this->lang('Équipes'),
			'description' => $this->lang('Une page par équipe : jeu, présentation, joueurs et rôles, résultats et recrutement selon les modules installés. Pour une guilde ou une équipe eSport.'),
			'icon'        => 'fas fa-headset',
			'link'        => 'https://neofrag-reborn.xyz',
			'author'      => 'Michaël BILCOT & Jérémy VALENTIN <contact@neofrag.com>',
			'license'     => 'LGPLv3 <https://www.gnu.org/licenses/lgpl-3.0.html>',
			// Decouplage du paquet : cf. tools/check-addon-declarations.php.
			'core'        => FALSE,
			'presets'     => ['gaming'],
			'requires'    => ['games'],
			'admin'       => TRUE,
			'version'     => '1.0',
			'depends'     => [
				'neofrag' => '0.2.0'
			],
			'routes'      => [
				//Index
				'{id}/{url_title}'                           => '_team',

				//Admin
				'admin/{id}/{url_title*}'                    => '_edit',
				'admin/delete/{id}/{url_title*}'             => '_delete',
				'admin/roles/add'                            => '_roles_add',
				'admin/roles/{id}/{url_title*}'              => '_roles_edit',
				'admin/roles/delete/{id}/{url_title}'        => '_roles_delete',
				'admin/players/delete/{id}/{url_title}/{id}' => '_players_delete',
				'admin/ajax/roles/sort'                      => '_roles_sort'
			],
			'settings'    => function(){
				return $this->form2()
							->rule($this->form_checkbox('teams_display_matches')
										->data(['on' => $this->lang('Afficher les matchs réalisés')])
										->value([$this->config->teams_display_matches ? 'on' : NULL])
							)
							->success(function($data){
								// Une case à cocher rend un TABLEAU (les valeurs cochées) : enregistré tel quel, il
								// devenait le texte « Array », toujours vrai — l'option ne se décochait plus
								// (trouvé par check-reglages, 2026-10-04).
								$this->config('teams_display_matches', in_array('on', (array) $data['teams_display_matches'], TRUE));
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
						'title'  => $this->lang('Équipes'),
						'icon'   => 'fas fa-headset',
						'access' => [
							'add_teams' => [
								'title' => $this->lang('Ajouter'),
								'icon'  => 'fas fa-plus',
								'admin' => TRUE
							],
							'modify_teams' => [
								'title' => $this->lang('Modifier'),
								'icon'  => 'fas fa-edit',
								'admin' => TRUE
							],
							'delete_teams' => [
								'title' => $this->lang('Supprimer'),
								'icon'  => 'far fa-trash-alt',
								'admin' => TRUE
							]
						]
					],
					[
						'title'  => $this->lang('Rôles'),
						'icon'   => 'fas fa-sitemap',
						'access' => [
							'add_teams_roles' => [
								'title' => $this->lang('Ajouter un rôle'),
								'icon'  => 'fas fa-plus',
								'admin' => TRUE
							],
							'modify_teams_roles' => [
								'title' => $this->lang('Modifier un rôle'),
								'icon'  => 'fas fa-edit',
								'admin' => TRUE
							],
							'delete_teams_roles' => [
								'title' => $this->lang('Supprimer un rôle'),
								'icon'  => 'far fa-trash-alt',
								'admin' => TRUE
							]
						]
					]
				]
			]
		];
	}

	/**
	 * L'onglet « Équipes » du profil public d'un membre (User::onglets_profil(), chantier A, étape A2) : les équipes
	 * où il joue, son rôle et leur jeu. Aucune équipe : pas d'onglet.
	 *
	 * @return list<array<string, mixed>>
	 */
	public function profil_membre($membre): array
	{
		$equipes = (array) $this->db	->select('t.team_id', 't.name', 'tl.title', 't.icon_id', 'r.title AS role', 'gl.title AS jeu')
										->from('nf_teams_users tu')
										->join('nf_teams t', 't.team_id = tu.team_id', 'INNER')
										->join_lang('nf_teams_lang tl', 'team_id', 't.team_id')
										->join('nf_teams_roles r', 'r.role_id = tu.role_id')
										->join_lang('nf_games_lang gl', 'game_id', 't.game_id')
										->where('tu.user_id', (int) $membre->id)
										->order_by('t.order', 't.team_id')
										->get();

		if (!$equipes)
		{
			return [];
		}

		return [[
			'onglet'  => 'equipes',
			'titre'   => (string) $this->lang('Équipes'),
			'icone'   => 'fas fa-headset',
			'ordre'   => 30,
			'nombre'  => count($equipes),
			'contenu' => fn () => $this->view('profil-membre', ['equipes' => $equipes]),
		]];
	}

	public function groups()
	{
		$teams = NeoFrag()->db	->select('t.team_id', 't.name', 'tl.title')
										->from('nf_teams t')
										->join_lang('nf_teams_lang tl', 'team_id', 't.team_id')
										->get();

		$groups = [];

		foreach ($teams as $team)
		{
			$groups[$team['team_id']] = [
				'name'  => $team['name'],
				'title' => $team['title'],
				'users' => $this->db()->select('u.id')->from('nf_teams_users tu')->join('nf_user u', 'tu.user_id = u.id', 'INNER')->where('tu.team_id', $team['team_id'])->where('u.deleted', FALSE)->get()
			];
		}

		return $groups;
	}
}
