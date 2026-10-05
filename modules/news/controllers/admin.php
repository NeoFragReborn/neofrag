<?php
declare(strict_types=1);
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\News\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Admin extends Controller_Module
{
	public function index($news, $filters)
	{
		$this->title($this->lang('Actualités'));

		// Actions en masse (POST) : publier / dépublier la sélection, puis refresh.
		if ($this->is_authorized('modify_news') && !empty($_POST['bulk_action']) && !empty($_POST['selected']) && is_array($_POST['selected']))
		{
			$ids    = array_values(array_filter(array_map('intval', $_POST['selected'])));
			$action = (string)$_POST['bulk_action'];

			if ($ids && in_array($action, ['publish', 'unpublish'], TRUE))
			{
				NeoFrag()->db->where('news_id', $ids)->update('nf_news', ['published' => $action === 'publish' ? 1 : 0]);
				notify($this->lang('%d actualité mise à jour.|%d actualités mises à jour.', count($ids), count($ids)));
				refresh();
			}
		}

		// Cards news
		$news_html = '<div class="nf-card-grid">';
		$count = 0;
		foreach ($news as $n)
		{
			$count++;
			$slug         = url_title($n['title']);
			$published    = !empty($n['published']);
			$author       = $n['user_id'] ? NeoFrag()->user->link($n['user_id'], $n['username']) : $this->lang('Visiteur');
			$comments     = $this->module('comments')->admin('news', $n['news_id']);
			$image_html   = '';
			$thumb_id     = !empty($n['image']) ? $n['image'] : null;
			if ($thumb_id)
			{
				$thumb_path = NeoFrag()->model2('file', $thumb_id)->path();
				if ($thumb_path) $image_html = '<div class="nf-content-card-thumb"><img src="'.url($thumb_path).'" alt=""></div>';
			}

			$news_html .= '<div class="nf-content-card">';
			if ($image_html) $news_html .= $image_html;

			$news_html .= '<div class="nf-content-card-head">';
			$news_html .= '<div class="nf-content-card-title">';
			if ($this->is_authorized('modify_news')) $news_html .= '<input type="checkbox" name="selected[]" value="'.(int)$n['news_id'].'" class="nf-bulk-cb" style="margin-right:6px;vertical-align:middle;">';
			$news_html .= '<a href="'.url('news/'.$n['news_id'].'/'.$slug).'">'.nf_texte($n['title']).'</a></div>';
			$is_scheduled = $published && !empty($n['date']) && strtotime($n['date']) > time();
			if (!$published)         { $st_cls = 'draft';     $st_icon = 'fa-clock';            $st_lbl = $this->lang('Brouillon'); }
			elseif ($is_scheduled)   { $st_cls = 'scheduled'; $st_icon = 'fa-calendar-alt';     $st_lbl = $this->lang('Programmée le %s', timetostr($this->lang('d/m/Y H:i'), $n['date'])); }
			else                     { $st_cls = 'published'; $st_icon = 'fa-check';            $st_lbl = $this->lang('Publiée'); }
			$news_html .= '<span class="nf-content-card-status '.$st_cls.'">';
			$news_html .= '<i class="fas '.$st_icon.'"></i> '.$st_lbl;
			$news_html .= '</span>';
			$news_html .= '</div>';

			$news_html .= '<div class="nf-content-card-meta">';
			$news_html .= '<span><i class="fas fa-folder"></i> '.nf_texte($n['category_title'] ?? '—').'</span>';
			$news_html .= '<span><i class="fas fa-user"></i> '.$author.'</span>';
			$news_html .= '<span><i class="far fa-clock"></i> '.time_span($n['date']).'</span>';
			$news_html .= '<span><i class="far fa-comments"></i> '.$comments.'</span>';
			$news_html .= '</div>';

			$news_html .= '<div class="nf-content-card-foot">';
			$news_html .= '<span class="nf-content-card-spacer"></span>';
			if ($this->is_authorized('modify_news'))
			{
				$news_html .= '<a class="btn btn-sm btn-outline-secondary" href="'.url('admin/news/'.$n['news_id'].'/'.$slug).'" title="'.$this->lang('Modifier').'"><i class="fas fa-edit"></i></a>';
			}
			if ($this->is_authorized('delete_news'))
			{
				$news_html .= '<a class="btn btn-sm btn-outline-danger" href="'.url('admin/news/delete/'.$n['news_id'].'/'.$slug).'" data-confirm="'.nf_texte($this->lang('Supprimer "%s" ?', $n['title'])).'" title="'.$this->lang('Supprimer').'"><i class="far fa-trash-alt"></i></a>';
			}
			$news_html .= '</div>';

			$news_html .= '</div>';
		}
		$news_html .= '</div>';

		if ($count === 0)
		{
			$news_html = !empty($filters['active'])
				? $this->admin_empty('fas fa-search', $this->lang('Aucune actualité ne correspond à ces critères.'))
				: $this->admin_empty('far fa-file-alt', $this->lang('Il n\'y a pas encore d\'actualité'));
		}

		// Barre recherche / filtre (GET). $_GET préservé à travers la pagination par get_pagination().
		$form_action = url($this->module->pagination->get_url());
		$toolbar  = '<form method="get" action="'.$form_action.'" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin-bottom:12px;">';
		$toolbar .= '<input type="text" name="q" value="'.nf_texte($filters['q']).'" class="form-control form-control-sm" placeholder="'.nf_texte($this->lang('Rechercher un titre…')).'" style="max-width:240px;">';
		$toolbar .= '<select name="category" class="form-select form-select-sm" style="width:auto;">';
		$toolbar .= '<option value="0">'.$this->lang('Toutes les catégories').'</option>';
		foreach ($filters['categories'] as $cid => $ctitle)
		{
			$toolbar .= '<option value="'.(int)$cid.'"'.((int)$filters['category'] === (int)$cid ? ' selected' : '').'>'.nf_texte($ctitle).'</option>';
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

		// Enveloppe la grille dans un formulaire POST + barre d'action groupée (publier/dépublier).
		if ($count > 0 && $this->is_authorized('modify_news'))
		{
			$bulk_bar = '<div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-bottom:12px;">'
				.'<label style="display:flex;align-items:center;gap:6px;font-size:13px;margin:0;cursor:pointer;"><input type="checkbox" id="nf-bulk-all-news"> '.$this->lang('Tout sélectionner').'</label>'
				.'<select name="bulk_action" class="form-select form-select-sm" style="width:auto;" required>'
					.'<option value="">'.$this->lang('Action groupée…').'</option>'
					.'<option value="publish">'.$this->lang('Publier').'</option>'
					.'<option value="unpublish">'.$this->lang('Dépublier').'</option>'
				.'</select>'
				.'<button type="submit" class="btn btn-sm btn-primary">'.$this->lang('Appliquer').'</button>'
				.'</div>';

			$news_html = '<form method="post" action="'.nf_texte(url($this->url->request)).'">'.$bulk_bar.$news_html.'</form>'
				.'<script>(function(){var a=document.getElementById("nf-bulk-all-news");if(a){a.addEventListener("change",function(){document.querySelectorAll(".nf-bulk-cb").forEach(function(c){c.checked=a.checked;});});}})();</script>';
		}

		$news_html = $toolbar.$news_html.$pagination;

		$categories = $this	->table()
							->add_columns([
								[
									'content' => function($data){
										return '<a href="'.url('admin/news/categories/'.$data['category_id'].'/'.$data['name']).'">'.NeoFrag()->model2('file', $data['icon_id'])->img().' '.$data['title'].'</a>';
									},
									'search'  => function($data){
										return $data['title'];
									},
									'sort'    => function($data){
										return $data['title'];
									}
								],
								[
									'content' => [
										function($data){
											return $this->is_authorized('modify_news_category') ? $this->button_update('admin/news/categories/'.$data['category_id'].'/'.$data['name']) : NULL;
										},
										function($data){
											return $this->is_authorized('delete_news_category') ? $this->button_delete('admin/news/categories/delete/'.$data['category_id'].'/'.$data['name']) : NULL;
										}
									],
									'size'    => TRUE
								]
							])
							->pagination(FALSE)
							->data($this->model('categories')->get_categories())
							->no_data($this->lang('Aucune catégorie'))
							->display();

		$cat_actions  = $this->is_authorized('add_news_category')
			? '<a class="btn btn-sm btn-primary" href="'.url('admin/news/categories/add').'">'.icon('fas fa-plus').' '.$this->lang('Catégorie').'</a>'
			: '';
		$news_actions = $this->is_authorized('add_news')
			? '<a class="btn btn-sm btn-primary" href="'.url('admin/news/add').'">'.icon('fas fa-plus').' '.$this->lang('Actualité').'</a>'
			: '';

		$total_label = (int)$filters['matched'];
		$count_label = $total_label === 1 ? $this->lang('%s actualité', $total_label) : $this->lang('%s actualités', $total_label);

		return '<div class="nf-list-layout">'
			.'<div class="nf-list-aside">'.$this->admin_card('fas fa-align-left', $this->lang('Catégories'), $categories, '', $cat_actions).'</div>'
			.'<div class="nf-list-main">'.$this->admin_card('far fa-file-alt', $this->lang('Actualités'), $news_html, $count_label, $news_actions).'</div>'
			.'</div>';
	}

	public function add()
	{
		$this	->subtitle($this->lang('Ajouter une actualité'))
				->form()
				->add_rules('news', [
					'categories' => $this->model('categories')->get_categories_list()
				])
				->add_submit($this->lang('Ajouter'), 'fas fa-plus')
				->add_back('admin/news');

		if ($this->form()->is_valid($post))
		{
			$published = in_array('on', (array)$post['published']);
			$news_id = $this->model()->add_news(	$post['title'],
										$post['category'],
										$post['image'],
										$post['introduction'],
										$post['content'],
										$post['tags'],
										$published,
										$post['date'] ?? '');

			// Parution : émet event/webhook/gamification/notifications. Ne fait rien si programmée
			// (date future) → l'endpoint de parution (cron) s'en chargera à l'heure réelle.
			$this->model()->announce($news_id);

			$this->_snapshot($news_id, $post, $this->lang('Création'));

			notify($this->lang('Actualité ajoutée avec succès'));

			redirect_back('admin/news');
		}

		return $this->admin_card('far fa-file-alt', $this->lang('Ajouter une actualité'), $this->form()->display());
	}

	public function _edit($news_id, $category_id, $user_id, $image_id, $date, $published, $views, $vote, $title, $introduction, $content, $tags, $category_name, $category_title, $news_image, $category_image, $category_icon)
	{
		$this	->title($this->lang('Éditer l\'actualité'))
				->subtitle($title)
				->form()
				->add_rules('news', [
					'title'        => $title,
					'category_id'  => $category_id,
					'categories'   => $this->model('categories')->get_categories_list(),
					'image_id'     => $image_id,
					'introduction' => $introduction,
					'content'      => $content,
					'tags'         => $tags,
					'published'    => $published,
					'date'         => $date
				])
				->add_submit($this->lang('Enregistrer'))
				->add_back('admin/news');

		if ($this->form()->is_valid($post))
		{
			$this->model()->edit_news(	$news_id,
										$post['category'],
										$post['image'],
										in_array('on', $post['published']),
										$post['title'],
										$post['introduction'],
										$post['content'],
										$post['tags'],
										$this->config->lang->info()->name,
										$post['date'] ?? '');

			// Parution si l'édition rend l'actualité publiable maintenant (brouillon → publié,
			// ou date avancée à maintenant). No-op si déjà annoncée ou encore programmée.
			$this->model()->announce($news_id);

			$this->_snapshot($news_id, $post, $this->lang('Édition'));

			notify($this->lang('Actualité éditée avec succès'));

			redirect_back('admin/news');
		}

		$history_btn = '<a class="btn btn-sm btn-light" href="'.url('admin/news/history/'.(int)$news_id.'/'.url_title($title)).'">'.icon('fas fa-history').' '.$this->lang('Historique').'</a>';

		return $this->admin_card('fas fa-edit', $this->lang('Éditer l\'actualité').' — '.$title, $this->form()->display(), '', $history_btn.' '.nf_seo_bouton('news', (int) $news_id));
	}

	public function _delete($news_id, $title)
	{
		$this	->title($this->lang('Suppression actualité'))
				->subtitle($title)
				->form()
				->confirm_deletion($this->lang('Confirmation de suppression'), $this->lang('Envoyer l\'actualité <b>%s</b> à la corbeille ? Elle restera restaurable depuis l\'admin.', $title));

		if ($this->form()->is_valid())
		{
			$this->model()->delete_news($news_id);

			return 'OK';
		}

		return $this->form()->display();
	}

	public function _categories_add()
	{
		$this	->subtitle($this->lang('Ajouter une catégorie'))
				->form()
				->add_rules('categories')
				->add_back('admin/news')
				->add_submit($this->lang('Ajouter'), 'fas fa-plus');

		if ($this->form()->is_valid($post))
		{
			$this->model('categories')->add_category(	$post['title'],
														$post['image'],
														$post['icon']);

			notify($this->lang('Catégorie ajoutée avec succès'));

			redirect_back('admin/news');
		}

		return $this->admin_card('fas fa-folder-plus', $this->lang('Ajouter une catégorie'), $this->form()->display());
	}

	public function _categories_edit($category_id, $title, $image_id, $icon_id)
	{
		$this	->subtitle($this->lang('Catégorie %s', $title))
				->form()
				->add_rules('categories', [
					'title' => $title,
					'image' => $image_id,
					'icon'  => $icon_id
				])
				->add_submit($this->lang('Enregistrer'))
				->add_back('admin/news');

		if ($this->form()->is_valid($post))
		{
			$this->model('categories')->edit_category(	$category_id,
														$post['title'],
														$post['image'],
														$post['icon']);

			notify($this->lang('Catégorie éditée avec succès'));

			redirect_back('admin/news');
		}

		return $this->admin_card('fas fa-folder-open', $this->lang('Éditer la catégorie').' — '.$title, $this->form()->display());
	}

	public function _categories_delete($category_id, $title)
	{
		$this	->title($this->lang('Suppression catégorie'))
				->subtitle($title)
				->form()
				->confirm_deletion($this->lang('Confirmation de suppression'), $this->lang('Êtes-vous sûr(e) de vouloir supprimer la catégorie <b>%s</b> ?<br />Toutes les actualités associées à cette catégorie seront aussi supprimées.', $title));

		if ($this->form()->is_valid())
		{
			$this->model('categories')->delete_category($category_id);

			return 'OK';
		}

		return $this->form()->display();
	}

	private function _snapshot($news_id, array $post, $summary)
	{
		if ($news_id && ($revisions = $this->module('revisions')))
		{
			$revisions->snapshot('news', (int)$news_id, [
				'title'        => (string)($post['title'] ?? ''),
				'introduction' => (string)($post['introduction'] ?? ''),
				'content'      => (string)($post['content'] ?? ''),
				'tags'         => (string)($post['tags'] ?? '')
			], $this->config->lang->info()->name, $summary);
		}
	}

	public function _history($news_id, $title)
	{
		$this	->title($this->lang('Historique des révisions'))
				->subtitle($title);

		$panel = ($revisions = $this->module('revisions'))
			? $revisions->history_panel('news', (int)$news_id, 'admin/news/revision/restore/'.(int)$news_id.'/'.url_title($title), $this->is_authorized('modify_news'))
			: '';

		return $this->admin_card('fas fa-history', $this->lang('Historique').' — '.$title, $panel);
	}

	public function _revision_restore($news_id, $title, $revision_id)
	{
		$revisions = $this->module('revisions');
		$revision  = $revisions ? $revisions->get($revision_id) : NULL;
		$history   = 'admin/news/history/'.(int)$news_id.'/'.url_title($title);

		$this	->title($this->lang('Restaurer une révision'))
				->subtitle($title);

		if (!$revision || $revision['content_type'] !== 'news' || (int)$revision['content_id'] !== (int)$news_id)
		{
			notify($this->lang('Révision introuvable.'), 'danger');
			redirect_back($history);
		}

		// form2 (champ caché => le POST porte le token et déclenche success). CSRF géré par form2.
		$form = $this->form2()
			->rule($this->form_hidden('confirm', '1'))
			->success(function($data) use ($news_id, $title, $revision, $revision_id, $history){
				$fields  = $revision['fields'];
				$lang    = $revision['lang'] ?: $this->config->lang->info()->name;
				// $title est le vrai titre (renvoyé par le checker) ; check_news compare au slug.
				$current = $this->model()->check_news($news_id, url_title($title), $lang);

				$this->model()->edit_news(	$news_id,
											$current['category_id'],
											$current['image_id'],
											(int)$current['published'],
											$fields['title']        ?? '',
											$fields['introduction'] ?? '',
											$fields['content']      ?? '',
											$fields['tags']         ?? '',
											$lang);

				$this->_snapshot($news_id, $fields, $this->lang('Restauration depuis #%d', (int)$revision_id));

				notify($this->lang('Version restaurée.'));

				redirect_back($history);
			})
			->submit($this->lang('Restaurer'));

		return $this->admin_back($history, $this->lang('Historique'))
			.$this->admin_card('fas fa-undo', $this->lang('Restaurer la révision #%d', (int)$revision_id),
				'<p>'.$this->lang('Le contenu actuel sera remplacé par cette version. La version actuelle reste conservée dans l\'historique.').'</p>'.$form);
	}
}
