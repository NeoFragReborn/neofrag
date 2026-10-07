<?php
declare(strict_types=1);
namespace NF\Modules\Faq\Controllers;
use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Index extends Controller_Module
{
	public function index($groups)
	{
		// La page est faite des questions et réponses, qui n'ont pas de langue à elles : sa canonique est
		// dans la langue première du site.
		nf_seo_sans_langue();

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

				$body .= '<h2 class="h4 mt-4 mb-2">'.nf_texte($g['cat']['title']).'</h2>';
				$body .= '<div class="accordion" id="faq-cat-'.(int)$g['cat']['id'].'">';

				foreach ($g['questions'] as $q)
				{
					// L'accordéon de Bootstrap 5. La FAQ gardait celui de Bootstrap 4 — une carte, un bouton-lien
					// et sa propre flèche — qui s'ouvrait, mais sans l'apparence ni la flèche du composant.
					$qid = 'faq-q-'.(int)$q['id'];
					$body .= '<div class="accordion-item">';
					$body .= '<h3 class="accordion-header" id="head-'.$qid.'">';
					$body .= '<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#'.$qid.'" aria-expanded="false" aria-controls="'.$qid.'">';
					$body .= nf_texte($q['question']);
					$body .= '</button></h3>';
					$body .= '<div id="'.$qid.'" class="accordion-collapse collapse" aria-labelledby="head-'.$qid.'" data-bs-parent="#faq-cat-'.(int)$g['cat']['id'].'">';
					$body .= '<div class="accordion-body">'.$q['answer'].'</div></div></div>';
				}

				$body .= '</div>';
			}
		}

		return $this->panel()->title($this->lang('Foire aux questions'), 'far fa-question-circle')->body($body);
	}
}
