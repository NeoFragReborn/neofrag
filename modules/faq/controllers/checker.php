<?php
namespace NF\Modules\Faq\Controllers;
use NF\NeoFrag\Loadables\Controllers\Module_Checker;

class Checker extends Module_Checker
{
	public function index()
	{
		$cats = NeoFrag()->db	->select('id', 'title')
								->from('nf_faq_categories')
								->order_by('sort_order ASC, id ASC')
								->get();

		$questions_by_cat = [];
		foreach ($cats as $c)
		{
			$qs = NeoFrag()->db	->select('id', 'question', 'answer')
								->from('nf_faq_questions')
								->where('category_id', $c['id'])
								->where('published', '1')
								->order_by('sort_order ASC, id ASC')
								->get();
			$questions_by_cat[$c['id']] = ['cat' => $c, 'questions' => $qs];
		}

		return [$questions_by_cat];
	}
}
