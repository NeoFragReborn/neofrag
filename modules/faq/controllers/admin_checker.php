<?php
namespace NF\Modules\Faq\Controllers;
use NF\NeoFrag\Loadables\Controllers\Module_Checker;

class Admin_Checker extends Module_Checker
{
	const SEARCH_CAP = 500;

	public function index($page = '')
	{
		$cats = NeoFrag()->db	->select('c.id', 'c.title', 'COUNT(q.id) AS nb')
								->from('nf_faq_categories c')
								->join('nf_faq_questions q', 'c.id = q.category_id', 'LEFT')
								->group_by('c.id')
								->order_by('c.sort_order ASC, c.id ASC')
								->get();

		$cat_ids = [];
		foreach ($cats as $c) { $cat_ids[(int)$c['id']] = TRUE; }

		$filters = [
			'q'        => isset($_GET['q']) ? trim((string)$_GET['q']) : '',
			'category' => isset($_GET['category']) && isset($cat_ids[(int)$_GET['category']]) ? (int)$_GET['category'] : 0,
			'status'   => isset($_GET['status']) && in_array($_GET['status'], ['published', 'draft'], TRUE) ? $_GET['status'] : ''
		];

		$db = NeoFrag()->db	->select('q.id', 'q.question', 'q.answer', 'q.published', 'q.category_id', 'c.title AS cat_title')
							->from('nf_faq_questions q')
							->join('nf_faq_categories c', 'q.category_id = c.id');

		if ($filters['q'] !== '')
		{
			$like = '%'.$filters['q'].'%';
			$db->where('q.question LIKE', $like, 'OR', 'q.answer LIKE', $like);
		}
		if ($filters['category'])
		{
			$db->where('q.category_id', $filters['category']);
		}
		if ($filters['status'] === 'published')
		{
			$db->where('q.published', 1);
		}
		else if ($filters['status'] === 'draft')
		{
			$db->where('q.published', 0);
		}

		$qs = $db->order_by('c.sort_order ASC, q.sort_order ASC, q.id ASC')->limit(self::SEARCH_CAP)->get();

		$pub = 0;
		foreach ($qs as $q) { if (!empty($q['published'])) $pub++; }

		$filters['matched']   = count($qs);
		$filters['published'] = $pub;
		$filters['drafts']    = count($qs) - $pub;
		$filters['active']    = $filters['q'] !== '' || $filters['category'] || $filters['status'] !== '';

		$filters['sort_cols'] = [
			'title'    => $this->lang('Question'),
			'category' => $this->lang('Catégorie'),
			'status'   => $this->lang('Statut')
		];
		list($qs, $filters['sort']) = $this->sort_items($qs, [
			'title'    => 'question',
			'category' => 'cat_title',
			'status'   => 'published'
		], 'title', 'asc');

		return [$cats, $this->module->pagination->fix_items_per_page(20)->get_data($qs, $page), $filters];
	}

	public function _q_add()  { return [NULL]; }
	public function _q_edit($id, $title)
	{
		$q = NeoFrag()->db->select('*')->from('nf_faq_questions')->where('id', $id)->row();
		return $q ? [$q] : NULL;
	}
	public function _q_delete($id, $title)
	{
		$q = NeoFrag()->db->select('id', 'question')->from('nf_faq_questions')->where('id', $id)->row();
		return $q ? [$q] : NULL;
	}
	public function _cat_add() { return [NULL]; }
	public function _cat_edit($id, $title)
	{
		$c = NeoFrag()->db->select('*')->from('nf_faq_categories')->where('id', $id)->row();
		return $c ? [$c] : NULL;
	}
	public function _cat_delete($id, $title)
	{
		$c = NeoFrag()->db->select('id', 'title')->from('nf_faq_categories')->where('id', $id)->row();
		return $c ? [$c] : NULL;
	}
}
