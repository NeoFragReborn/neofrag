<?php
declare(strict_types=1);
namespace NF\Modules\Wiki\Controllers;
use NF\NeoFrag\Loadables\Controllers\Module_Checker;

class Admin_Checker extends Module_Checker
{
	const SEARCH_CAP = 500;

	public function index($page = '')
	{
		$filters = [
			'q'      => isset($_GET['q']) ? trim((string)$_GET['q']) : '',
			'status' => isset($_GET['status']) && in_array($_GET['status'], ['published', 'draft'], TRUE) ? $_GET['status'] : ''
		];

		$db = NeoFrag()->db	->select('p.*', 'wl.title', 'u.username', 'COUNT(r.id) AS nb_revisions')
							->from('nf_wiki_pages p')
							->join_lang('nf_wiki_pages_lang wl', 'page_id', 'p.id', NULL, 'LEFT')
							->join('nf_user u', 'p.user_id = u.id', 'LEFT')
							->join('nf_wiki_revisions r', 'p.id = r.page_id', 'LEFT');

		if ($filters['q'] !== '')
		{
			$like = '%'.$filters['q'].'%';
			$db->where('wl.title LIKE', $like, 'OR', 'p.slug LIKE', $like);
		}
		if ($filters['status'] === 'published')
		{
			$db->where('p.published', 1);
		}
		else if ($filters['status'] === 'draft')
		{
			$db->where('p.published', 0);
		}

		$pages = $db	->group_by('p.id')
						->order_by('p.parent_id ASC, p.sort_order ASC, wl.title ASC')
						->limit(self::SEARCH_CAP)
						->get();

		$published = 0;
		foreach ($pages as $p) { if (!empty($p['published'])) $published++; }

		$filters['matched']   = count($pages);
		$filters['published'] = $published;
		$filters['drafts']    = count($pages) - $published;
		$filters['active']    = $filters['q'] !== '' || $filters['status'] !== '';

		$filters['sort_cols'] = [
			'date'      => $this->lang('Date'),
			'title'     => $this->lang('Titre'),
			'views'     => $this->lang('Vues'),
			'revisions' => $this->lang('Révisions')
		];
		list($pages, $filters['sort']) = $this->sort_items($pages, [
			'date'      => 'created_at',
			'title'     => 'title',
			'views'     => 'views',
			'revisions' => 'nb_revisions'
		], 'date', 'desc');

		return [$this->module->pagination->fix_items_per_page(20)->get_data($pages, $page), $filters];
	}

	public function _add() { return [NULL]; }
	public function _edit($slug)
	{
		$p = NeoFrag()->db->select('p.*', 'wl.title')->from('nf_wiki_pages p')->join_lang('nf_wiki_pages_lang wl', 'page_id', 'p.id', NULL, 'LEFT')->where('p.slug', $slug)->row();
		return $p ? [$p] : NULL;
	}
	public function _delete($slug)
	{
		$p = NeoFrag()->db->select('p.id', 'p.slug', 'wl.title')->from('nf_wiki_pages p')->join_lang('nf_wiki_pages_lang wl', 'page_id', 'p.id', NULL, 'LEFT')->where('p.slug', $slug)->row();
		return $p ? [$p] : NULL;
	}
}
