<?php
namespace NF\Modules\Faq\Controllers;
use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Index extends Controller_Module
{
	public function index($groups)
	{
		$this	->title($this->lang('FAQ'))
				->icon('far fa-question-circle')
				->breadcrumb();

		$body = '';

		if (empty($groups))
		{
			$body = '<div class="alert alert-info text-center">'.$this->lang('Aucune question pour le moment.').'</div>';
		}
		else
		{
			foreach ($groups as $g)
			{
				if (empty($g['questions']))
				{
					continue;
				}

				$body .= '<h2 class="h4 mt-4 mb-2">'.htmlspecialchars($g['cat']['title']).'</h2>';
				$body .= '<div class="accordion" id="faq-cat-'.(int)$g['cat']['id'].'">';

				foreach ($g['questions'] as $q)
				{
					$qid = 'faq-q-'.(int)$q['id'];
					$body .= '<div class="card">';
					$body .= '<div class="card-header" id="head-'.$qid.'">';
					$body .= '<button class="btn btn-link text-start w-100" type="button" data-bs-toggle="collapse" data-target="#'.$qid.'" aria-expanded="false" aria-controls="'.$qid.'">';
					$body .= '<i class="fas fa-chevron-right me-2"></i>'.htmlspecialchars($q['question']);
					$body .= '</button></div>';
					$body .= '<div id="'.$qid.'" class="collapse" aria-labelledby="head-'.$qid.'" data-bs-parent="#faq-cat-'.(int)$g['cat']['id'].'">';
					$body .= '<div class="card-body">'.$q['answer'].'</div></div></div>';
				}

				$body .= '</div>';
			}
		}

		return $this->panel()->title($this->lang('Foire aux questions'), 'far fa-question-circle')->body($body);
	}
}
