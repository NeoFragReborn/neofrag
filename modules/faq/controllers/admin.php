<?php
declare(strict_types=1);
namespace NF\Modules\Faq\Controllers;
use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Admin extends Controller_Module
{
	public function index($cats, $qs, $filters)
	{
		$this->title($this->lang('FAQ'))->icon('far fa-question-circle');

		// Categories aside
		if (empty($cats)) {
			$cats_body = $this->admin_empty('far fa-folder', $this->lang('Aucune catégorie.'));
		} else {
			$cats_body = '<table class="table table-hover" style="margin:0;"><thead><tr><th>'.$this->lang('Titre').'</th><th class="text-end">'.$this->lang('Questions').'</th><th class="text-end"></th></tr></thead><tbody>';
			foreach ($cats as $c) {
				$slug = url_title($c['title']);
				$cats_body .= '<tr>'
					.'<td><strong>'.nf_texte($c['title']).'</strong></td>'
					.'<td class="text-end">'.(int)$c['nb'].'</td>'
					.'<td class="text-end" style="white-space:nowrap;">'
					.'<a class="btn btn-sm btn-outline-secondary" href="'.url('admin/faq/cat/'.$c['id'].'/'.$slug).'" title="'.$this->lang('Éditer').'"><i class="fas fa-pen"></i></a> '
					.'<a class="btn btn-sm btn-outline-danger" href="'.$this->csrf_url('admin/faq/cat/delete/'.$c['id'].'/'.$slug).'" data-confirm="'.nf_texte($this->lang('Supprimer cette catégorie ?')).'" title="'.$this->lang('Supprimer').'"><i class="far fa-trash-alt"></i></a>'
					.'</td></tr>';
			}
			$cats_body .= '</tbody></table>';
		}

		// Questions cards
		$qs_published = (int)$filters['published'];
		$qs_drafts    = (int)$filters['drafts'];

		if (empty($qs)) {
			$qs_body = !empty($filters['active'])
				? $this->admin_empty('fas fa-search', $this->lang('Aucune question ne correspond à ces critères.'))
				: $this->admin_empty('far fa-question-circle', $this->lang('Aucune question.'));
		} else {
			$qs_body = '<div class="nf-card-grid">';
			foreach ($qs as $q) {
				$slug = url_title(substr($q['question'], 0, 50));
				$published = !empty($q['published']);

				$qs_body .= '<div class="nf-content-card">';
				$qs_body .= '<div class="nf-content-card-head">';
				$qs_body .= '<div class="nf-content-card-title">'.nf_texte($q['question']).'</div>';
				$qs_body .= '<span class="nf-content-card-status '.($published ? 'published' : 'draft').'">';
				$qs_body .= '<i class="fas '.($published ? 'fa-check' : 'fa-clock').'"></i> '.($published ? $this->lang('Publiée') : $this->lang('Brouillon'));
				$qs_body .= '</span>';
				$qs_body .= '</div>';
				if (!empty($q['answer'])) {
					$qs_body .= '<div class="nf-content-card-desc">'.nf_texte(strip_tags($q['answer'])).'</div>';
				}
				$qs_body .= '<div class="nf-content-card-meta">';
				$qs_body .= '<span><i class="fas fa-folder"></i> '.nf_texte($q['cat_title']).'</span>';
				$qs_body .= '</div>';
				$qs_body .= '<div class="nf-content-card-foot">';
				$qs_body .= '<span class="nf-content-card-spacer"></span>';
				$qs_body .= '<a class="btn btn-sm btn-outline-secondary" href="'.url('admin/faq/q/'.$q['id'].'/'.$slug).'" title="'.$this->lang('Éditer').'"><i class="fas fa-pen"></i></a>';
				$qs_body .= '<a class="btn btn-sm btn-outline-danger" href="'.$this->csrf_url('admin/faq/q/delete/'.$q['id'].'/'.$slug).'" data-confirm="'.nf_texte($this->lang('Supprimer cette question ?')).'" title="'.$this->lang('Supprimer').'"><i class="far fa-trash-alt"></i></a>';
				$qs_body .= '</div>';
				$qs_body .= '</div>';
			}
			$qs_body .= '</div>';
		}

		// Barre recherche / filtre (GET). $_GET préservé à travers la pagination par get_pagination().
		$form_action = url($this->module->pagination->get_url());
		$toolbar  = '<form method="get" action="'.$form_action.'" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin-bottom:12px;">';
		$toolbar .= '<input type="text" name="q" value="'.nf_texte($filters['q']).'" class="form-control form-control-sm" placeholder="'.nf_texte($this->lang('Rechercher une question ou réponse…')).'" style="max-width:260px;">';
		$toolbar .= '<select name="category" class="form-select form-select-sm" style="width:auto;">';
		$toolbar .= '<option value="0">'.$this->lang('Toutes les catégories').'</option>';
		foreach ($cats as $c)
		{
			$toolbar .= '<option value="'.(int)$c['id'].'"'.((int)$filters['category'] === (int)$c['id'] ? ' selected' : '').'>'.nf_texte($c['title']).'</option>';
		}
		$toolbar .= '</select>';
		$toolbar .= '<select name="status" class="form-select form-select-sm" style="width:auto;">';
		foreach (['' => $this->lang('Tous les statuts'), 'published' => $this->lang('Publiées'), 'draft' => $this->lang('Brouillons')] as $val => $label)
		{
			$toolbar .= '<option value="'.$val.'"'.($filters['status'] === $val ? ' selected' : '').'>'.nf_texte($label).'</option>';
		}
		$toolbar .= '</select>';
		$toolbar .= $this->sort_select($filters['sort_cols'], $filters['sort']);
		$toolbar .= '<button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-filter"></i> '.$this->lang('Filtrer').'</button>';
		if (!empty($filters['active']))
		{
			$toolbar .= '<a href="'.$form_action.'" class="btn btn-sm btn-light"><i class="fas fa-times"></i> '.$this->lang('Réinitialiser').'</a>';
			$toolbar .= '<span class="text-muted" style="font-size:12px;margin-left:auto;">'.$this->lang('%d résultat|%d résultats', (int)$filters['matched'], (int)$filters['matched']).'</span>';
		}
		$toolbar .= '</form>';

		$pagination = (string)$this->module->pagination->get_pagination();
		if ($pagination !== '')
		{
			$pagination = '<div style="margin-top:12px;text-align:center;">'.$pagination.'</div>';
		}

		$qs_body = $toolbar.$qs_body.$pagination;

		$cats_actions  = '<a class="btn btn-sm btn-primary" href="'.url('admin/faq/cat/add').'"><i class="fas fa-plus"></i> '.$this->lang('Nouvelle').'</a>';
		$qs_actions    = '<a class="btn btn-primary btn-sm" href="'.url('admin/faq/q/add').'"><i class="fas fa-plus"></i> '.$this->lang('Nouvelle question').'</a>';
		$qs_subtitle   = $qs_published.' '.$this->lang('publiée|publiées', $qs_published).($qs_drafts > 0 ? ' · '.$qs_drafts.' '.$this->lang('brouillon|brouillons', $qs_drafts) : '');

		return '<div class="nf-list-layout">'
			.'<div class="nf-list-aside">'.$this->admin_card('far fa-folder', $this->lang('Catégories'), $cats_body, count($cats).' '.$this->lang('catégorie|catégories', count($cats)), $cats_actions).'</div>'
			.'<div class="nf-list-main">'.$this->admin_card('far fa-question-circle', $this->lang('Questions'), $qs_body, $qs_subtitle, $qs_actions).'</div>'
			.'</div>';
	}

	// QUESTIONS
	public function _q_add()        { return $this->_q_form(NULL); }
	public function _q_edit($q)     { return $this->_q_form($q); }
	public function _q_delete($q)
	{
		$this->check_csrf('admin/faq');

		NeoFrag()->db->where('id', $q['id'])->delete('nf_faq_questions');
		notify($this->lang('Question supprimée.'));
		redirect('admin/faq');
	}

	protected function _q_form($q)
	{
		$is_new = $q === NULL;
		$this->title($is_new ? $this->lang('Nouvelle question') : $this->lang('Éditer question'))->icon('far fa-question-circle')->breadcrumb();

		$cats = NeoFrag()->db->select('id', 'title')->from('nf_faq_categories')->order_by('sort_order ASC')->get();
		$cats_array = [];
		foreach ($cats as $c) { $cats_array[$c['id']] = $c['title']; }

		$this->form()
			 ->add_rules([
				'category_id' => ['label' => $this->lang('Catégorie'), 'type' => 'select', 'values' => $cats_array, 'value' => $is_new ? key($cats_array) : $q['category_id'], 'rules' => 'required'],
				'question'    => ['label' => $this->lang('Question'), 'type' => 'text', 'value' => $is_new ? '' : $q['question'], 'rules' => 'required'],
				'answer'      => ['label' => $this->lang('Réponse'), 'type' => 'editor', 'value' => $is_new ? '' : $q['answer'], 'rules' => 'required'],
				'sort_order'  => ['label' => $this->lang('Ordre tri'), 'type' => 'text', 'value' => $is_new ? '0' : $q['sort_order']],
				'published'   => ['label' => $this->lang('Publier'), 'type' => 'checkbox', 'value' => ['1'], 'values' => ['1' => $this->lang('Question publiée')], 'checked' => ['1' => ($is_new || !empty($q['published']))]]
			 ])
			 ->add_submit($is_new ? $this->lang('Créer') : $this->lang('Enregistrer'), $is_new ? 'fas fa-plus' : 'fas fa-check');

		if ($this->form()->is_valid($post))
		{
			$data = [
				'category_id' => (int)$post['category_id'],
				'question'    => $post['question'],
				'answer'      => $post['answer'],
				'sort_order'  => (int)$post['sort_order'],
				'published'   => in_array('1', $post['published'] ?? []) ? 1 : 0
			];

			if ($is_new) NeoFrag()->db->insert('nf_faq_questions', $data);
			else         NeoFrag()->db->where('id', $q['id'])->update('nf_faq_questions', $data);

			notify($is_new ? $this->lang('Question créée.') : $this->lang('Question modifiée.'));
			redirect('admin/faq');
		}

		return $this->admin_card($is_new ? 'fas fa-plus' : 'fas fa-edit', $is_new ? $this->lang('Nouvelle question') : $this->lang('Éditer question'), $this->form()->display());
	}

	// CATEGORIES
	public function _cat_add()       { return $this->_cat_form(NULL); }
	public function _cat_edit($c)    { return $this->_cat_form($c); }
	public function _cat_delete($c)
	{
		$this->check_csrf('admin/faq');

		$nb = (int)NeoFrag()->db->select('COUNT(*)')->from('nf_faq_questions')->where('category_id', $c['id'])->row();
		if ($nb > 0)
		{
			notify($this->lang('Impossible : %d question(s) dans cette catégorie.', $nb));
			redirect('admin/faq');
		}
		NeoFrag()->db->where('id', $c['id'])->delete('nf_faq_categories');
		notify($this->lang('Catégorie supprimée.'));
		redirect('admin/faq');
	}

	protected function _cat_form($c)
	{
		$is_new = $c === NULL;
		$this->title($is_new ? $this->lang('Nouvelle catégorie') : $this->lang('Éditer catégorie'))->icon('far fa-folder')->breadcrumb();

		$this->form()
			 ->add_rules([
				'title'      => ['label' => $this->lang('Titre'), 'type' => 'text', 'value' => $is_new ? '' : $c['title'], 'rules' => 'required'],
				'sort_order' => ['label' => $this->lang('Ordre tri'), 'type' => 'text', 'value' => $is_new ? '0' : $c['sort_order']]
			 ])
			 ->add_submit($is_new ? $this->lang('Créer') : $this->lang('Enregistrer'), $is_new ? 'fas fa-plus' : 'fas fa-check');

		if ($this->form()->is_valid($post))
		{
			$data = ['title' => $post['title'], 'sort_order' => (int)$post['sort_order']];
			if ($is_new) NeoFrag()->db->insert('nf_faq_categories', $data);
			else         NeoFrag()->db->where('id', $c['id'])->update('nf_faq_categories', $data);

			notify($is_new ? $this->lang('Catégorie créée.') : $this->lang('Catégorie modifiée.'));
			redirect('admin/faq');
		}

		return $this->admin_card($is_new ? 'fas fa-folder-plus' : 'fas fa-folder-open', $is_new ? $this->lang('Nouvelle catégorie') : $this->lang('Éditer catégorie : %s', $c['title']), $this->form()->display());
	}
}
