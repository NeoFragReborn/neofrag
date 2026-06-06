<?php
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
			return $forum;
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
			return $category;
		}
	}

	public function _categories_delete($category_id, $name)
	{
		$this->ajax();

		if ($category = $this->model()->check_category($category_id, $name))
		{
			return $category;
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
}
