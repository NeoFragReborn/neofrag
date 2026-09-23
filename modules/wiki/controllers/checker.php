<?php
declare(strict_types=1);
namespace NF\Modules\Wiki\Controllers;
use NF\NeoFrag\Loadables\Controllers\Module_Checker;

class Checker extends Module_Checker
{
	public function index()
	{
		$pages = NeoFrag()->db	->select('id', 'slug', 'title', 'parent_id')
								->from('nf_wiki_pages')
								->where('published', '1')
								->order_by('parent_id ASC, sort_order ASC, title ASC')
								->get();
		return [$pages];
	}

	public function _page($slug)
	{
		$page = NeoFrag()->db	->select('p.*', 'u.id AS author_id', 'u.username', 'UNIX_TIMESTAMP(p.updated_at) AS updated_ts')
								->from('nf_wiki_pages p')
								->join('nf_user u', 'p.user_id = u.id', 'LEFT')
								->where('p.slug', $slug)
								->where('p.published', '1')
								->row();

		if (empty($page)) return;

		if (count_view('wiki', $page['id']))
		{
			NeoFrag()->db->execute('UPDATE nf_wiki_pages SET views = views + 1 WHERE id = '.(int)$page['id']);
		}

		return [$page];
	}

	public function _history($slug)
	{
		$page = NeoFrag()->db->select('id', 'slug', 'title')->from('nf_wiki_pages')->where('slug', $slug)->row();
		if (empty($page)) return;

		$revisions = NeoFrag()->db	->select('r.id', 'r.title', 'r.comment', 'UNIX_TIMESTAMP(r.created_at) AS ts', 'u.id AS user_id', 'u.username')
									->from('nf_wiki_revisions r')
									->join('nf_user u', 'r.user_id = u.id', 'LEFT')
									->where('r.page_id', $page['id'])
									->order_by('r.created_at DESC')
									->get();

		return [$page, $revisions];
	}

	public function _revision($id)
	{
		$rev = NeoFrag()->db	->select('r.*', 'p.slug AS page_slug', 'u.id AS user_id', 'u.username', 'UNIX_TIMESTAMP(r.created_at) AS ts')
								->from('nf_wiki_revisions r')
								->join('nf_wiki_pages p', 'r.page_id = p.id')
								->join('nf_user u', 'r.user_id = u.id', 'LEFT')
								->where('r.id', $id)
								->row();
		return $rev ? [$rev] : NULL;
	}

	// diff/{id} : révision vs version actuelle ; diff/{id}/{id} : deux révisions (même page).
	public function _diff($id, $id2 = NULL)
	{
		$revision = function($rid){
			return NeoFrag()->db	->select('r.id', 'r.page_id', 'r.title', 'r.content', 'r.comment', 'UNIX_TIMESTAMP(r.created_at) AS ts', 'u.id AS user_id', 'u.username')
									->from('nf_wiki_revisions r')
									->join('nf_user u', 'r.user_id = u.id', 'LEFT')
									->where('r.id', (int)$rid)
									->row();
		};

		$a = $revision($id);

		if (empty($a))
		{
			return;
		}

		$page = NeoFrag()->db	->select('p.id', 'p.slug', 'p.title', 'p.content', 'UNIX_TIMESTAMP(p.updated_at) AS ts', 'u.id AS author_id', 'u.username')
								->from('nf_wiki_pages p')
								->join('nf_user u', 'p.user_id = u.id', 'LEFT')
								->where('p.id', (int)$a['page_id'])
								->row();

		if (empty($page))
		{
			return;
		}

		if ($id2 !== NULL)
		{
			$b = $revision($id2);

			if (empty($b) || (int)$b['page_id'] !== (int)$a['page_id'])
			{
				return;
			}
		}
		else
		{
			// Pseudo-révision « version actuelle » dérivée de la page vive.
			$b = [
				'id'       => 0,
				'page_id'  => $page['id'],
				'title'    => $page['title'],
				'content'  => $page['content'],
				'comment'  => NULL,
				'ts'       => $page['ts'],
				'user_id'  => $page['author_id'],
				'username' => $page['username'],
				'current'  => TRUE
			];
		}

		// Ordonne ancien -> récent pour une lecture naturelle.
		if ($a['ts'] > $b['ts'])
		{
			[$a, $b] = [$b, $a];
		}

		return [$a, $b, $page];
	}
}
