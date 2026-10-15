<?php
declare(strict_types=1);
namespace NF\Modules\Faq\Controllers;
use NF\NeoFrag\Loadables\Controllers\Module_Checker;

class Checker extends Module_Checker
{
	public function index()
	{
		$cats = NeoFrag()->db	->select('c.id', 'cl.title')
								->from('nf_faq_categories c')
								->join_lang('nf_faq_categories_lang cl', 'category_id', 'c.id', NULL, 'LEFT')
								->order_by('c.sort_order ASC, c.id ASC')
								->get();

		$questions_by_cat = [];
		foreach ($cats as $c)
		{
			$qs = NeoFrag()->db	->select('q.id', 'ql.question', 'ql.answer')
								->from('nf_faq_questions q')
								->join_lang('nf_faq_questions_lang ql', 'question_id', 'q.id', NULL, 'LEFT')
								->where('q.category_id', $c['id'])
								->where('q.published', '1')
								->order_by('q.sort_order ASC, q.id ASC')
								->get();
			$questions_by_cat[$c['id']] = ['cat' => $c, 'questions' => $qs];
		}

		return [$questions_by_cat];
	}
}
