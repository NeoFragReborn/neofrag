<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 *
 * couplage(forum): la section privée d'un collectif — chaque lecture est gardée par table_exists('nf_forum_categories') :
 * sans le forum, un collectif n'a simplement pas de section.
 * couplage(discord): la correspondance groupe ↔ rôle et le salon — gardés par table_exists('nf_discord_roles') :
 * sans le module Discord, un collectif n'a rien sur Discord.
 */

namespace NF\Modules\Collectives\Models;

use NF\NeoFrag\Loadables\Model;

class Collectives extends Model
{
	/**
	 * Les rangs, du plus haut au plus bas. Le CHEF est nommé par un administrateur du site, et lui seul le change :
	 * un collectif ne se fait pas prendre par l'un des siens. Le chef et les administrateurs du collectif gèrent ses
	 * membres — chacun ceux qui sont en dessous de lui —, les modérateurs modèrent sa section du forum.
	 */
	const RANGS = ['chef' => 4, 'admin' => 3, 'moderateur' => 2, 'membre' => 1];

	/** Les droits du rôle de modération, dans la section : ceux d'un modérateur de catégorie du forum, sans le déplacement (qui sortirait un sujet de la section). */
	const DROITS_DE_MODERATION = ['category_modify', 'category_delete', 'category_announce', 'category_lock'];

	// ── Lire ──────────────────────────────────────────────────────────────────

	/** @return list<array<string, mixed>> Tous les collectifs, avec leur chef et leur nombre de membres. */
	public function liste(): array
	{
		return array_values((array) $this->db	->select('c.collective_id', 'c.name', 'c.description', 'c.category_id', 'c.discord', 'c.discord_channel_id', 'c.created_at',
													'(SELECT COUNT(*) FROM nf_collectives_members m JOIN nf_user um ON um.id = m.user_id AND um.deleted = "0" WHERE m.collective_id = c.collective_id) AS nombre',
													'(SELECT u.username FROM nf_collectives_members m JOIN nf_user u ON u.id = m.user_id WHERE m.collective_id = c.collective_id AND m.rank = "chef" LIMIT 1) AS chef')
												->from('nf_collectives c')
												->order_by('c.name')
												->get());
	}

	/** @return array<string, mixed>|null */
	public function un(int $id): ?array
	{
		$c = $this->db->select('*')->from('nf_collectives')->where('collective_id', $id)->row(FALSE);

		return is_array($c) && $c !== [] ? $c : NULL;
	}

	/** @return list<array<string, mixed>> Les collectifs d'un membre, et son rang dans chacun. */
	public function du_membre(int $user_id): array
	{
		return array_values((array) $this->db	->select('c.collective_id', 'c.name', 'c.description', 'm.rank',
													'(SELECT COUNT(*) FROM nf_collectives_members x JOIN nf_user ux ON ux.id = x.user_id AND ux.deleted = "0" WHERE x.collective_id = c.collective_id) AS nombre')
												->from('nf_collectives_members m')
												->join('nf_collectives c', 'c.collective_id = m.collective_id', 'INNER')
												->where('m.user_id', $user_id)
												->order_by('c.name')
												->get());
	}

	/** @return list<array<string, mixed>> Les membres d'un collectif, du chef aux membres, puis par pseudo. */
	public function membres(int $id): array
	{
		$membres = array_values((array) $this->db	->select('m.user_id', 'm.rank', 'm.joined_at', 'u.username')
													->from('nf_collectives_members m')
													->join('nf_user u', 'u.id = m.user_id AND u.deleted = "0"', 'INNER')
													->where('m.collective_id', $id)
													->get());

		usort($membres, fn (array $a, array $b): int => [self::poids((string) $b['rank']), mb_strtolower((string) $a['username'])] <=> [self::poids((string) $a['rank']), mb_strtolower((string) $b['username'])]);

		return $membres;
	}

	/** Le rang d'un membre dans un collectif, ou NULL s'il n'en est pas. */
	public function rang(int $id, int $user_id): ?string
	{
		$rang = $this->db->select('rank')->from('nf_collectives_members')->where('collective_id', $id)->where('user_id', $user_id)->row();

		return is_string($rang) && isset(self::RANGS[$rang]) ? $rang : NULL;
	}

	public static function poids(string $rang): int
	{
		return self::RANGS[$rang] ?? 0;
	}

	/** La clé de son groupe : celle que la correspondance des rôles Discord retient. */
	public function cle_de_groupe(int $id): string
	{
		return url_title('collectives_'.$id);
	}

	/** Le forum de sa section, s'il en a une : `forum_id` et `title`. */
	public function forum_de(array $collectif): ?array
	{
		if (empty($collectif['category_id']) || !$this->db->table_exists('nf_forum'))
		{
			return NULL;
		}

		$forum = $this->db	->select('forum_id', 'title')
							->from('nf_forum')
							->where('parent_id', (int) $collectif['category_id'])
							->where('is_subforum', FALSE)
							->order_by('forum_id')
							->limit(1)
							->row(FALSE);

		return is_array($forum) && $forum !== [] ? $forum : NULL;
	}

	// ── Qui peut quoi ─────────────────────────────────────────────────────────

	/** Gère-t-il les membres de ce collectif ? Son chef et ses administrateurs, et qui gère les collectifs sur le site. */
	public function gere(int $id, int $user_id, bool $gestionnaire_du_site): bool
	{
		return $gestionnaire_du_site || in_array($this->rang($id, $user_id), ['chef', 'admin'], TRUE);
	}

	/**
	 * Les rangs qu'il peut donner : ceux qui sont SOUS le sien (un administrateur du collectif ne fait pas d'autres
	 * administrateurs, le chef si). Le rang de chef ne se donne jamais ici : un administrateur du site le change,
	 * dans l'administration.
	 *
	 * @return list<string>
	 */
	public function rangs_donnables(int $id, int $acteur, bool $gestionnaire_du_site): array
	{
		$plafond = $gestionnaire_du_site ? self::RANGS['chef'] : self::poids((string) $this->rang($id, $acteur));

		return array_values(array_filter(['admin', 'moderateur', 'membre'], static fn (string $r): bool => self::poids($r) < $plafond));
	}

	/** Peut-il changer le rang de ce membre, ou le retirer ? Seulement un membre placé sous lui ; jamais le chef. */
	public function peut_toucher(int $id, int $acteur, int $cible, bool $gestionnaire_du_site): bool
	{
		$rang_cible = $this->rang($id, $cible);

		if ($rang_cible === NULL || $rang_cible === 'chef' || $acteur === $cible)
		{
			return FALSE;
		}

		return $gestionnaire_du_site || ($this->gere($id, $acteur, FALSE) && self::poids((string) $this->rang($id, $acteur)) > self::poids($rang_cible));
	}

	// ── Créer, modifier, supprimer (l'administration) ─────────────────────────

	public function creer(string $nom, string $description, int $chef, bool $discord): int
	{
		$id = (int) $this->db->insert('nf_collectives', [
			'name'        => $nom,
			'description' => $description,
			'discord'     => $discord ? 1 : 0,
		]);

		[$role, $role_moderation] = $this->_poser_roles($id, $nom);

		$this->db->where('collective_id', $id)->update('nf_collectives', [
			'role_id'            => $role,
			'role_moderation_id' => $role_moderation,
			'category_id'        => $this->_creer_section($nom, $role, $role_moderation),
		]);

		$this->ajouter($id, $chef, 'chef', NULL);
		$this->_discord_a_change();

		return $id;
	}

	public function modifier(int $id, string $nom, string $description, int $chef, bool $discord): void
	{
		$avant = $this->un($id);

		if ($avant === NULL)
		{
			return;
		}

		$this->db->where('collective_id', $id)->update('nf_collectives', ['name' => $nom, 'description' => $description, 'discord' => $discord ? 1 : 0]);

		foreach ([(int) $avant['role_id'] => $this->lang('Collectif « %s »', $nom), (int) $avant['role_moderation_id'] => $this->lang('Collectif « %s » — modération', $nom)] as $role => $titre)
		{
			if ($role)
			{
				$this->db->where('role_id', $role)->update('nf_roles', ['title' => mb_substr((string) $titre, 0, 100)]);
			}
		}

		if (!empty($avant['category_id']) && $this->db->table_exists('nf_forum_categories'))
		{
			$this->db->where('category_id', (int) $avant['category_id'])->update('nf_forum_categories', ['title' => $nom]);
		}

		// Un nouveau chef : l'ancien devient administrateur du collectif, il n'en sort pas.
		foreach ($this->membres($id) as $m)
		{
			if ($m['rank'] === 'chef' && (int) $m['user_id'] !== $chef)
			{
				$this->changer_rang($id, (int) $m['user_id'], 'admin');
			}
		}

		$this->rang($id, $chef) === NULL ? $this->ajouter($id, $chef, 'chef', NULL) : $this->changer_rang($id, $chef, 'chef');

		if (!$discord && !empty($avant['discord']))
		{
			$this->oublier_discord($id);
		}

		$this->_discord_a_change();
	}

	/**
	 * Un collectif supprimé emporte ses membres, ses rôles du site et son rôle sur Discord. Sa section du forum et son
	 * salon restent, réservés aux administrateurs : ce qui s'y est écrit ne se perd pas ; on les supprime à la main.
	 */
	public function supprimer(int $id): void
	{
		$membres = array_map('intval', array_column($this->membres($id), 'user_id'));

		$this->oublier_discord($id);
		$this->retirer_roles($id);
		$this->db->where('collective_id', $id)->delete('nf_collectives');
		$this->_groupes_changes($membres);
	}

	// ── Les membres ───────────────────────────────────────────────────────────

	public function ajouter(int $id, int $user_id, string $rang, ?int $par): bool
	{
		if (!isset(self::RANGS[$rang]) || $this->rang($id, $user_id) !== NULL)
		{
			return FALSE;
		}

		$this->db->insert('nf_collectives_members', ['collective_id' => $id, 'user_id' => $user_id, 'rank' => $rang, 'added_by' => $par]);
		$this->_droits_du_membre($id, $user_id, $rang);
		$this->_groupes_changes([$user_id]);

		return TRUE;
	}

	public function changer_rang(int $id, int $user_id, string $rang): void
	{
		if (!isset(self::RANGS[$rang]) || $this->rang($id, $user_id) === NULL)
		{
			return;
		}

		$this->db->where('collective_id', $id)->where('user_id', $user_id)->update('nf_collectives_members', ['rank' => $rang]);
		$this->_droits_du_membre($id, $user_id, $rang);
	}

	public function retirer(int $id, int $user_id): void
	{
		$this->db->where('collective_id', $id)->where('user_id', $user_id)->delete('nf_collectives_members');
		$this->_droits_du_membre($id, $user_id, NULL);
		$this->_groupes_changes([$user_id]);
	}

	/** Le membre que désigne ce pseudo, s'il existe et n'est pas effacé. */
	public function membre_par_pseudo(string $pseudo): ?int
	{
		$id = $this->db->select('id')->from('nf_user')->where('username', trim($pseudo))->where('deleted', '0')->row();

		return $id ? (int) $id : NULL;
	}

	// ── Les rôles du site ─────────────────────────────────────────────────────

	/** Les rôles du site d'un collectif partent : ses membres perdent sa section, ses droits sur elle s'effacent. */
	public function retirer_roles(int $id): void
	{
		$c = $this->un($id);

		foreach ($c ? array_filter([(int) $c['role_id'], (int) $c['role_moderation_id']]) : [] as $role)
		{
			// nf_users_roles et nf_role_permissions suivent (ON DELETE CASCADE).
			$this->db->where('role_id', $role)->delete('nf_roles');
		}

		$this->access->reload();
	}

	/** @return array{0: int, 1: int} Le rôle des membres et celui de la modération. */
	private function _poser_roles(int $id, string $nom): array
	{
		$roles = [];

		foreach (['collective_'.$id => $this->lang('Collectif « %s »', $nom), 'collective_'.$id.'_moderation' => $this->lang('Collectif « %s » — modération', $nom)] as $cle => $titre)
		{
			$roles[] = (int) $this->db->insert('nf_roles', [
				'name'        => $cle,
				'title'       => mb_substr((string) $titre, 0, 100),
				'description' => (string) $this->lang('Posé par le module Collectifs : il suit les membres du collectif, ne le donnez pas à la main.'),
				'color'       => 'secondary',
				'icon'        => 'fas fa-people-group',
				'order'       => 200,
			]);
		}

		return [$roles[0], $roles[1]];
	}

	/**
	 * La section privée : une catégorie du forum, et son forum. Personne ne la lit ni n'y écrit — ni un visiteur, ni un
	 * membre —, sauf par le rôle du collectif ; sa modération, par le rôle de modération. Un administrateur du site,
	 * lui, passe partout.
	 */
	private function _creer_section(string $nom, int $role, int $role_moderation): ?int
	{
		$forum = $this->module('forum');

		if (!$forum || !($modele = $forum->model('forum')) instanceof \NF\Modules\Forum\Models\Forum || !$this->db->table_exists('nf_forum_categories'))
		{
			return NULL;
		}

		$categorie = (int) $modele->add_category($nom);
		// En texte : le forum lit « f12 » comme un sous-forum du forum 12 (Forum::get_parent_id()).
		$modele->add_forum((string) $this->lang('Discussions'), (string) $categorie, (string) $this->lang('L’espace du collectif, réservé à ses membres.'), '');

		$base = array_map('intval', array_column((array) $this->db->select('role_id')->from('nf_roles')->where('name', ['member', 'visitor'])->get(FALSE), 'role_id'));

		foreach (['category_read', 'category_write'] as $action)
		{
			foreach ($base as $role_de_base)
			{
				$this->db->replace('nf_role_permissions', ['role_id' => $role_de_base, 'permission' => 'forum.'.$action, 'scope_id' => $categorie, 'authorized' => 'never']);
			}

			$this->db->replace('nf_role_permissions', ['role_id' => $role, 'permission' => 'forum.'.$action, 'scope_id' => $categorie, 'authorized' => 'allow']);
		}

		foreach (self::DROITS_DE_MODERATION as $action)
		{
			$this->db->replace('nf_role_permissions', ['role_id' => $role_moderation, 'permission' => 'forum.'.$action, 'scope_id' => $categorie, 'authorized' => 'allow']);
		}

		$this->access->reload();

		return $categorie;
	}

	/** Les rôles du site d'un membre suivent son rang : celui du collectif pour tous, celui de la modération à partir de modérateur. */
	private function _droits_du_membre(int $id, int $user_id, ?string $rang): void
	{
		$c = $this->un($id);

		if ($c === NULL)
		{
			return;
		}

		foreach ([(int) $c['role_id'] => $rang !== NULL, (int) $c['role_moderation_id'] => $rang !== NULL && self::poids($rang) >= self::RANGS['moderateur']] as $role => $voulu)
		{
			if (!$role)
			{
				continue;
			}

			$this->db->where('user_id', $user_id)->where('role_id', $role)->delete('nf_users_roles');

			if ($voulu)
			{
				$this->db->insert('nf_users_roles', ['user_id' => $user_id, 'role_id' => $role]);
			}
		}

		$this->access->invalidate_cache();
	}

	// ── Discord ───────────────────────────────────────────────────────────────

	/**
	 * Le collectif quitte Discord (supprimé, ou son salon décoché) : sa correspondance de rôle s'efface, et le bot
	 * apprend par le fil d'événements quel rôle supprimer. Son salon reste — sans le rôle, seuls les administrateurs
	 * du serveur le voient.
	 */
	public function oublier_discord(int $id): void
	{
		$c = $this->un($id);

		if ($c === NULL || !$this->db->table_exists('nf_discord_roles'))
		{
			return;
		}

		$cle  = $this->cle_de_groupe($id);
		$role = $this->db->select('role_id')->from('nf_discord_roles')->where('group_key', $cle)->row();

		$this->db->where('group_key', $cle)->delete('nf_discord_roles');
		$this->db->where('collective_id', $id)->update('nf_collectives', ['discord_channel_id' => NULL]);

		// couplage(api): facultatif — sans le module api, `Module::__load` rend NULL et rien n'est inscrit.
		if ((is_string($role) && $role !== '' || !empty($c['discord_channel_id'])) && ($api = \NF\NeoFrag\Addons\Module::__load(\NeoFrag(), ['api'])) instanceof \NF\Modules\Api\Api)
		{
			$api->consigner('discord.collective.removed', [
				'collective_id' => $id,
				'role_id'       => is_string($role) && $role !== '' ? $role : NULL,
				'channel_id'    => $c['discord_channel_id'] ?: NULL,
			]);
		}

		$this->_discord_a_change();
	}

	/** Le bot relit sa configuration : un collectif créé, renommé ou supprimé change ce qu'il doit tenir sur le serveur. */
	private function _discord_a_change(): void
	{
		if (($discord = $this->module('discord')) && ($modele = $discord->model('discord')) instanceof \NF\Modules\Discord\Models\Discord)
		{
			$modele->nouvelle_version();
		}
	}

	/** Les groupes de ces membres ont changé : le bot Discord met leurs rôles à jour. */
	private function _groupes_changes(array $user_ids): void
	{
		// couplage(api): facultatif — sans le module api, `Module::__load` rend NULL et rien n'est inscrit.
		if (($api = \NF\NeoFrag\Addons\Module::__load(\NeoFrag(), ['api'])) instanceof \NF\Modules\Api\Api)
		{
			foreach ($user_ids as $user_id)
			{
				$api->consigner('user.groups.changed', ['user_id' => (int) $user_id]);
			}
		}
	}
}
