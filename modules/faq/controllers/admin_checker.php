<?php
declare(strict_types=1);
namespace NF\Modules\Faq\Controllers;
use NF\NeoFrag\Loadables\Controllers\Module_Checker;

class Admin_Checker extends Module_Checker
{
	const SEARCH_CAP = 500;

	public function index($page = '')
	{
		/*
		 * Les LISTES montrent la meilleure traduction (`join_lang`), jamais le vide ; l'écran
		 * d'ÉDITION ouvre la langue où l'on travaille, et elle seule (m32, 2026-10-15).
		 */
		$cats = NeoFrag()->db	->select('c.id', 'cl.title', 'COUNT(q.id) AS nb')
								->from('nf_faq_categories c')
								->join_lang('nf_faq_categories_lang cl', 'category_id', 'c.id', NULL, 'LEFT')
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

		$db = NeoFrag()->db	->select('q.id', 'ql.question', 'ql.answer', 'q.published', 'q.category_id', 'q.sort_order', 'cl.title AS cat_title')
							->from('nf_faq_questions q')
							->join_lang('nf_faq_questions_lang ql', 'question_id', 'q.id', NULL, 'LEFT')
							->join('nf_faq_categories c', 'q.category_id = c.id')
							->join_lang('nf_faq_categories_lang cl', 'category_id', 'c.id', NULL, 'LEFT');

		if ($filters['q'] !== '')
		{
			$like = '%'.$filters['q'].'%';
			$db->where('ql.question LIKE', $like, 'OR', 'ql.answer LIKE', $like);
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

		if (!$q)
		{
			return NULL;
		}

		$ecrit = NeoFrag()->db	->select('question', 'answer')
								->from('nf_faq_questions_lang')
								->where('question_id', (int) $q['id'])
								->where('lang', (string) $this->config->lang->info()->name)
								->row();

		$lu = NeoFrag()->db	->select('question')
							->from('nf_faq_questions_lang')
							->where('question_id', (int) $q['id'])
							->order_by("lang = 'fr' DESC, lang ASC")
							->row();

		// Le texte TEL QU'IL EST ÉCRIT dans la langue de l'administration (vide si rien n'y est
		// encore écrit), et la question LUE pour l'en-tête.
		return [$q + [
			'question'    => is_array($ecrit) ? (string) ($ecrit['question'] ?? '') : '',
			'answer'      => is_array($ecrit) ? (string) ($ecrit['answer'] ?? '') : '',
			'question_lue' => is_string($lu) ? $lu : (is_array($ecrit) ? (string) ($ecrit['question'] ?? '') : '')
		]];
	}
	public function _q_delete($id, $title)
	{
		$q = NeoFrag()->db	->select('q.id', 'ql.question')
							->from('nf_faq_questions q')
							->join_lang('nf_faq_questions_lang ql', 'question_id', 'q.id', NULL, 'LEFT')
							->where('q.id', $id)
							->row();
		return $q ? [$q] : NULL;
	}
	public function _cat_add() { return [NULL]; }
	public function _cat_edit($id, $title)
	{
		$c = NeoFrag()->db->select('*')->from('nf_faq_categories')->where('id', $id)->row();

		if (!$c)
		{
			return NULL;
		}

		$ecrit = NeoFrag()->db	->select('title')
								->from('nf_faq_categories_lang')
								->where('category_id', (int) $c['id'])
								->where('lang', (string) $this->config->lang->info()->name)
								->row();

		$lu = NeoFrag()->db	->select('title')
							->from('nf_faq_categories_lang')
							->where('category_id', (int) $c['id'])
							->order_by("lang = 'fr' DESC, lang ASC")
							->row();

		return [$c + [
			'title'    => is_string($ecrit) ? $ecrit : '',
			'title_lu' => is_string($lu) ? $lu : (is_string($ecrit) ? $ecrit : '')
		]];
	}
	public function _cat_delete($id, $title)
	{
		$c = NeoFrag()->db	->select('c.id', 'cl.title')
							->from('nf_faq_categories c')
							->join_lang('nf_faq_categories_lang cl', 'category_id', 'c.id', NULL, 'LEFT')
							->where('c.id', $id)
							->row();
		return $c ? [$c] : NULL;
	}
}
