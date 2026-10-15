<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * Module Collectifs — des équipes privées, chacune avec son espace (m15, bot Discord v3, 2026-10-16).
 *
 * Un collectif réunit des membres autour d'un projet : une rédaction, une équipe de modération, un groupe de
 * créateurs. Ce n'est pas une équipe de jeu (le module `teams`, qui demande un jeu et se montre au public) : un
 * collectif est privé, et ce qu'il crée ensemble lui est réservé.
 *
 * Créé par un administrateur, un collectif reçoit d'un coup :
 * - une SECTION PRIVÉE du forum (une catégorie et son forum), que seuls ses membres lisent et écrivent ;
 * - deux rôles du site, posés par le module : celui des membres (la section) et celui de sa modération ;
 * - un GROUPE (`collectives-<id>`), que le bot Discord relie à un rôle, et — si on le veut — un salon privé sur le
 *   serveur, que seul ce rôle voit (fonctionnalité « Collectifs » du bot).
 *
 * Il a un CHEF, que l'administrateur nomme, et des rangs : administrateur et modérateur du collectif, membre. Le chef
 * et les administrateurs du collectif gèrent ses membres sans être administrateurs du site ; les modérateurs
 * modèrent sa section du forum. Les règles des rangs vivent dans le modèle (Models\Collectives).
 */

namespace NF\Modules\Collectives;

use NF\NeoFrag\Addons\Module;

class Collectives extends Module
{
	protected function __info()
	{
		return [
			'title'       => $this->lang('Collectifs'),
			'description' => $this->lang('Des équipes privées : chacune a sa section du forum, son rôle et son salon sur Discord, et un chef qui gère ses membres sans être administrateur.'),
			'icon'        => 'fas fa-people-group',
			'link'        => 'https://neofrag-reborn.xyz',
			'author'      => 'NeoFrag Reborn',
			'license'     => 'LGPLv3 <https://www.gnu.org/licenses/lgpl-3.0.html>',
			// Decouplage du paquet : cf. tools/check-addon-declarations.php. Le forum et Discord sont des
			// dépendances molles : sans le forum, un collectif n'a pas de section ; sans Discord, pas de salon.
			'core'        => FALSE,
			'presets'     => ['communaute', 'association'],
			'requires'    => [],
			'admin'       => TRUE,
			'version'     => '1.0',
			'depends'     => ['neofrag' => '0.2.0'],
			'routes'      => [
				''                                          => 'index',
				'{id}/{url_title}'                          => '_collective',
				'{id}/{url_title}/add'                      => '_add_member',
				'{id}/{url_title}/rank/{id}'                => '_rank',
				'{id}/{url_title}/remove/{id}'              => '_remove',
				'{id}/{url_title}/leave'                    => '_leave',
				'admin{pages}'                              => 'index',
				'admin/add'                                 => '_add',
				'admin/{id}/{url_title}'                    => '_edit',
				'admin/delete/{id}/{url_title}'             => '_delete'
			]
		];
	}

	public function permissions()
	{
		return [
			'default' => [
				'access' => [
					[
						'title'  => $this->lang('Collectifs'),
						'icon'   => 'fas fa-people-group',
						'access' => [
							'manage_collectives' => ['title' => $this->lang('Créer, modifier et supprimer les collectifs'), 'icon' => 'fas fa-people-group', 'admin' => TRUE]
						]
					]
				]
			]
		];
	}

	/**
	 * Un groupe par collectif (`collectives-<id>`) : son nom sur les profils, et sa correspondance avec un rôle sur
	 * Discord — le bot donne ce rôle à ses membres reliés, et le retire à qui en sort.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function groups()
	{
		$groupes = [];

		foreach ((array) NeoFrag()->db->select('collective_id', 'name')->from('nf_collectives')->order_by('name')->get() as $c)
		{
			$groupes[(int) $c['collective_id']] = [
				'name'   => $c['name'],
				'title'  => $c['name'],
				'icon'   => 'fas fa-people-group',
				// Un collectif est privé : son badge ne se montre pas sur le profil public.
				'hidden' => TRUE,
				'users'  => array_map('intval', array_column((array) $this->db()	->select('m.user_id')
																					->from('nf_collectives_members m')
																					->join('nf_user u', 'u.id = m.user_id AND u.deleted = "0"', 'INNER')
																					->where('m.collective_id', (int) $c['collective_id'])
																					->get(FALSE), 'user_id'))
			];
		}

		return $groupes;
	}

	/** « Mes collectifs », dans le menu de l'espace membre — pour qui en a. */
	public function espace_membre($user): array
	{
		$nombre = (int) $this->db->select('COUNT(*)')->from('nf_collectives_members')->where('user_id', (int) $user->id)->row();

		return $nombre ? [['url' => 'collectives', 'titre' => (string) $this->lang('Mes collectifs'), 'icone' => 'fas fa-people-group', 'ordre' => 45]] : [];
	}

	/** Les notifications que ce module envoie, pour les préférences de chaque membre. */
	public function types_de_notification(): array
	{
		return [
			['type' => 'collectives_added', 'titre' => (string) $this->lang('On m’ajoute à un collectif'), 'email' => FALSE, 'ordre' => 80],
		];
	}

	/**
	 * À la désinstallation, les rôles du site que les collectifs avaient posés partent avec eux, et leurs
	 * correspondances Discord : sans quoi un rôle orphelin ouvrirait encore leurs sections du forum. Les sections
	 * elles-mêmes restent, réservées aux administrateurs : ce qui s'y est écrit ne se perd pas.
	 */
	public function uninstall($remove = TRUE)
	{
		if ($remove && !nf_demo() && $this->db->table_exists('nf_collectives'))
		{
			/** @var \NF\Modules\Collectives\Models\Collectives $modele */
			$modele = $this->model();

			foreach ((array) $this->db->select('collective_id')->from('nf_collectives')->get() as $id)
			{
				$modele->oublier_discord((int) $id);
				$modele->retirer_roles((int) $id);
			}
		}

		return parent::uninstall($remove);
	}
}
