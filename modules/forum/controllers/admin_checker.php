<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\Forum\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module_Checker;

class Admin_Checker extends Module_Checker
{
	public function _edit($forum_id, $title)
	{
		if ($forum = $this->model()->check_forum($forum_id, $title))
		{
			// Dans l'ordre de la signature d'Admin::_edit(), colonne par colonne : la ligne entière, passée telle quelle,
			// décalait tout depuis l'ajout de `titre_par_defaut` à la requête — le formulaire montrait le titre dans la
			// description, et l'enregistrement rangeait le forum sous une mauvaise catégorie et touchait à sa
			// redirection (trouvé le 2026-10-10 en passant en revue tous les checkers).
			// Le titre et la description de la langue par défaut : les traductions ont leurs propres champs, et
			// check_forum() rend ceux de la langue affichée.
			$defaut = $this->db->select('title', 'description')->from('nf_forum')->where('forum_id', (int) $forum_id)->row();

			return [
				$forum['forum_id'],
				$defaut['title'],
				$defaut['description'],
				$forum['parent_id'],
				$forum['is_subforum'],
				$forum['url'],
			];
		}
	}

	public function delete($forum_id, $title)
	{
		$this->ajax();

		if ($this->model()->check_forum($forum_id, $title))
		{
			return [$forum_id, $title];
		}
	}

	public function _categories_edit($category_id, $name)
	{
		if ($category = $this->model()->check_category($category_id, $name))
		{
			// Le titre de la langue par défaut : les traductions ont leurs propres champs.
			return [$category['category_id'], $category['titre_par_defaut']];
		}
	}

	public function _categories_delete($category_id, $name)
	{
		$this->ajax();

		if ($category = $this->model()->check_category($category_id, $name))
		{
			return [$category['category_id'], $category['title']];
		}
	}

	// Phase 7-bis — Mod avancée

	public function _admin_topic_split($topic_id, $title)
	{
		if ($topic = $this->model()->check_topic($topic_id, $title))
		{
			if ($this->access('forum', 'category_modify', $topic['category_id']))
			{
				return [$topic_id, $topic['topic_title'], $topic['forum_id'], $topic['category_id']];
			}
			else
			{
				$this->error->unauthorized();
			}
		}
	}

	public function _admin_topic_merge($topic_id, $title)
	{
		if ($topic = $this->model()->check_topic($topic_id, $title))
		{
			if ($this->access('forum', 'category_modify', $topic['category_id']))
			{
				return [$topic_id, $topic['topic_title'], $topic['forum_id'], $topic['category_id']];
			}
			else
			{
				$this->error->unauthorized();
			}
		}
	}

	public function _admin_trash($page = '')
	{
		// Accès uniquement aux admins
		if (!$this->user() || !$this->access->effective_admin())
		{
			$this->error->unauthorized();
			return;
		}

		$messages = $this->model()->get_trashed_messages(200);
		return [$this->module->pagination->fix_items_per_page(50)->get_data($messages, $page)];
	}

	public function _admin_attachments($page = '')
	{
		if (!$this->user() || !$this->access->effective_admin())
		{
			$this->error->unauthorized();
			return;
		}

		$attachments = $this->model()->get_all_attachments(500);
		return [$this->module->pagination->fix_items_per_page(50)->get_data($attachments, $page)];
	}

	public function _admin_subscriptions($page = '')
	{
		if (!$this->user() || !$this->access->effective_admin())
		{
			$this->error->unauthorized();
			return;
		}
		$subs = $this->model()->get_all_subscriptions(500);
		return [$this->module->pagination->fix_items_per_page(50)->get_data($subs, $page)];
	}

	public function _admin_mentions($page = '')
	{
		if (!$this->user() || !$this->access->effective_admin())
		{
			$this->error->unauthorized();
			return;
		}

		$filters = [
			'status' => isset($_GET['status']) && in_array($_GET['status'], ['read', 'unread'], TRUE) ? $_GET['status'] : '',
			'user'   => isset($_GET['user']) ? trim((string)$_GET['user']) : ''
		];

		$mentions = $this->model()->get_all_mentions(500, $filters);
		return [$this->module->pagination->fix_items_per_page(50)->get_data($mentions, $page), $filters];
	}

	public function _admin_search_config()
	{
		if (!$this->user() || !$this->access->effective_admin())
		{
			$this->error->unauthorized();
			return;
		}
		return [];
	}

	public function _prefixes()
	{
		return [];
	}

	public function _prefixes_add()
	{
		return [];
	}

	public function _prefixes_edit($prefix_id, $title)
	{
		return ($prefix = $this->_prefixe((int) $prefix_id)) ? [$prefix] : NULL;
	}

	public function _prefixes_delete($prefix_id, $title)
	{
		return ($prefix = $this->_prefixe((int) $prefix_id)) ? [$prefix] : NULL;
	}

	/** Un préfixe, avec son titre PAR DÉFAUT (celui que l'administration édite). */
	private function _prefixe(int $prefix_id): ?array
	{
		$prefix = $this->db->select('prefix_id', 'title', 'color', 'order')->from('nf_forum_prefixes')->where('prefix_id', $prefix_id)->row();

		return is_array($prefix) && $prefix ? $prefix : NULL;
	}
}
