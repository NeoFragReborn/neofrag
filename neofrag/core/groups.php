<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\NeoFrag\Core;

use NF\NeoFrag\Core;

class Groups extends Core
{
	private $_groups = [];

	public function __construct()
	{
		$users = $this->db->select('id', 'admin')->from('nf_user')->where('deleted', FALSE)->get();

		$this->_groups = [
			'admins' => [
				'name'   => 'admins',
				'title'  => NeoFrag()->lang('Administrateurs'),
				'color'  => 'danger',
				'icon'   => 'fas fa-rocket',
				'hidden' => FALSE,
				'users'  => array_map('intval', array_map(function($a){return intval($a['id']);}, array_filter($users, function($a){return $a['admin'];}))),
				'auto'   => 'neofrag'
			],
			'members' => [
				'name'   => 'members',
				'title'  => NeoFrag()->lang('Membres'),
				'color'  => 'success',
				'icon'   => 'fas fa-user',
				'hidden' => FALSE,
				'users'  => array_map('intval', array_map(function($a){return intval($a['id']);}, array_filter($users, function($a){return !$a['admin'];}))),
				'auto'   => 'neofrag'
			],
			'visitors' => [
				'name'   => 'visitors',
				'title'  => NeoFrag()->lang('Visiteurs'),
				'color'  => 'info',
				'icon'   => '',
				'hidden' => FALSE,
				'users'  => NULL,
				'auto'   => 'neofrag'
			]
		];

		// Le titre dans la langue du site, sinon dans une autre (le français d'abord), sinon le nom.
		// La jointure était INTERNE, filtrée sur la langue : un groupe créé en français n'a qu'une ligne
		// de titre, et il DISPARAISSAIT de la liste — de ses droits d'accès compris — dès que le site
		// s'affichait dans une autre langue (relevé le 2026-09-23).
		$langue = preg_match('/^[a-z]{2}$/', $code = (string) $this->config->lang->info()->name) ? $code : 'fr';

		$groups = $this->db	->select('g.group_id', 'g.name', 'g.color', 'g.icon', 'g.hidden', 'COALESCE(gl.title, (SELECT gx.title FROM nf_groups_lang gx WHERE gx.group_id = g.group_id ORDER BY gx.lang = "fr" DESC, gx.lang LIMIT 1), g.name) AS title', 'g.auto')
							->from('nf_groups g')
							->join('nf_groups_lang gl', 'gl.group_id = g.group_id AND gl.lang = "'.$langue.'"', 'LEFT')
							->order_by('g.order')
							->get();

		$order = 1;

		foreach ($groups as $group)
		{
			if ($group['auto'])
			{
				if (!isset($this->_groups[$group['name']]))
				{
					$this->_groups[$group['name']]['order'] = $order++;
				}

				$this->_groups[$group['name']]['id']     = $group['group_id'];
				$this->_groups[$group['name']]['color']  = $group['color'];
				$this->_groups[$group['name']]['icon']   = $group['icon'];
				$this->_groups[$group['name']]['hidden'] = (bool)$group['hidden'];
				$this->_groups[$group['name']]['auto']   = TRUE;
			}
			else
			{
				$this->_groups[url_title($group['group_id'])] = [
					'id'     => $group['group_id'],
					'name'   => $group['name'],
					'title'  => $group['title'],
					'color'  => $group['color'],
					'icon'   => $group['icon'],
					'hidden' => (bool)$group['hidden'],
					'users'  => $this->db()->select('u.id')->from('nf_users_groups ug')->join('nf_user u', 'ug.user_id = u.id', 'INNER')->where('ug.group_id', $group['group_id'])->where('u.deleted', FALSE)->get(),
					'auto'   => FALSE,
					'order'  => $order++
				];
			}
		}

		foreach ($this->model2('addon')->get('module') as $module)
		{
			if (method_exists($module, 'groups'))
			{
				foreach ($module->groups() as $id => $group)
				{
					$group_id = url_title($module->info()->name.'_'.$id);

					$this->_groups[$group_id]         = !empty($this->_groups[$group_id]) ? array_merge($group, $this->_groups[$group_id]) : $group;
					$this->_groups[$group_id]['auto'] = 'module_'.$module->info()->name;

					if (empty($this->_groups[$group_id]['icon']))
					{
						$this->_groups[$group_id]['icon'] = $module->info()->icon;
					}

					if (empty($this->_groups[$group_id]['color']))
					{
						$this->_groups[$group_id]['color'] = 'secondary';
					}

					if (empty($this->_groups[$group_id]['hidden']))
					{
						$this->_groups[$group_id]['hidden'] = FALSE;
					}

					if (empty($this->_groups[$group_id]['order']))
					{
						$this->_groups[$group_id]['order'] = $order++;
					}
				}
			}
		}

		foreach ($this->_groups as $group_id => &$group)
		{
			if (array_key_exists('users', $group))
			{
				// Le nom passe par `url_title()` : le motif de route `{url_title}` vaut
				// `[a-z0-9-]+`, SANS tiret bas. Un groupe nomme « moderation_junior » produisait
				// donc une adresse qu'aucune route ne reconnaissait, et son ecran d'edition —
				// comme sa page de membres — rendait un 404.
				$group['url'] = url_title($group_id).($group['auto'] != 'neofrag' ? '/'.url_title($group['name']) : '');
			}
			else
			{
				unset($this->_groups[$group_id]);
			}

			unset($group);
		}

		$this->_groups['admins']['order']   = 0;
		$this->_groups['members']['order']  = $order++;
		$this->_groups['visitors']['order'] = $order;

		uasort($this->_groups, function($a, $b){
			// L'ordre d'un groupe est un entier ; `strnatcmp()` attend des chaines.
			return strnatcmp((string) $a['order'], (string) $b['order']);
		});
	}

	public function __invoke($user_id = NULL)
	{
		if (func_num_args() == 1)
		{
			$groups = [];

			foreach ($this->_groups as $group_id => $group)
			{
				if (!empty($group['users']) && in_array($user_id, $group['users']))
				{
					$groups[] = $group_id;
				}
			}

			$groups = array_unique($groups);

			return $groups ?: ['visitors'];
		}
		else
		{
			return $this->_groups;
		}
	}

	/**
	 * Les groupes d'un membre que celui qui regarde peut voir : un groupe caché ne se montre qu'à un administrateur, dans
	 * l'administration — la règle de user_groups(). Le profil public et sa fiche au survol montraient le badge d'un
	 * groupe caché à tous (audit du 2026-10-09).
	 *
	 * @return list<string|int>
	 */
	public function visibles($user_id): array
	{
		$admin = $this->access->effective_admin() && $this->url->admin;

		return array_values(array_filter($this($user_id), fn ($group_id) => $admin || empty($this->_groups[$group_id]['hidden'])));
	}

	public function user_groups($user_id, $label = TRUE)
	{
		$groups = [];

		foreach ($this->_groups as $group_id => $group)
		{
			if (!empty($group['users']) && in_array($user_id, $group['users']) && (!$group['hidden'] || ($this->access->effective_admin() && $this->url->admin)))
			{
				$groups[] = $this->display($group_id, $label);
			}
		}

		return implode(' ', $groups);
	}

	public function display($group_id, $label = TRUE, $link = TRUE)
	{
		if ($label)
		{
			if ($group_id == 'visitors')
			{
				$link = FALSE;
			}

			return $this->label()
						->title($this->_groups[$group_id]['title'])
						->icon($this->_groups[$group_id]['icon'])
						->url_if($link && ($members = $this->module('members')) && $members->is_enabled(), 'members/group/'.$this->_groups[$group_id]['url'])
						->color($this->_groups[$group_id]['color']);
		}
		else
		{
			return $this->_groups[$group_id]['title'];
		}
	}

	public function check_group($args)
	{
		$n = count($args);

		if ($n == 1)
		{
			return $this->_groups[$args[0]] + ['unique_id' => $args[0]];
		}

		if ($n == 3)
		{
			list($module, $group_id, $name) = $args;
			$group_id = $module.'-'.$group_id;
		}
		else if ($n == 2)
		{
			list($group_id, $name) = $args;
		}

		// On compare au PERMALIEN du nom, pas au nom brut : c'est le permalien qui circule dans
		// l'adresse (cf. la construction de `url` plus haut).
		if (isset($this->_groups[$group_id]) && $name == url_title($this->_groups[$group_id]['name']))
		{
			return $this->_groups[$group_id] + ['unique_id' => $group_id];
		}

		return FALSE;
	}

	public function delete($module, $id)
	{
		$group_id = url_title($module.'_'.$id);

		if (isset($this->_groups[$group_id]))
		{
			if (!empty($this->_groups[$group_id]['id']))
			{
				$this->db	->where('group_id', $this->_groups[$group_id]['id'])
							->delete('nf_groups');
			}

			$this->access->revoke($group_id);

			unset($this->_groups[$group_id]);
		}

		return $this;
	}
}
