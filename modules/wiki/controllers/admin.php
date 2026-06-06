<?php
namespace NF\Modules\Wiki\Controllers;
use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Admin extends Controller_Module
{
	public function index($pages, $filters)
	{
		$this->title($this->lang('Wiki'))->icon('fas fa-book');

		$published = (int)$filters['published'];
		$drafts    = (int)$filters['drafts'];

		$by_id = [];
		foreach ($pages as $p) { $by_id[$p['id']] = $p; }

		if (empty($pages)) {
			$body = !empty($filters['active'])
				? $this->admin_empty('fas fa-search', $this->lang('Aucune page ne correspond à ces critères.'))
				: $this->admin_empty('fas fa-book', $this->lang('Aucune page wiki pour le moment.'));
		} else {
			$body = '<div class="nf-card-grid">';
			foreach ($pages as $p) {
				$published_p = !empty($p['published']);
				$parent      = $p['parent_id'] && isset($by_id[$p['parent_id']]) ? $by_id[$p['parent_id']]['title'] : null;

				$body .= '<div class="nf-content-card">';
				$body .= '<div class="nf-content-card-head">';
				$body .= '<div class="nf-content-card-title"><a href="'.url('wiki/'.$p['slug']).'">'.htmlspecialchars($p['title']).'</a></div>';
				$body .= '<span class="nf-content-card-status '.($published_p ? 'published' : 'draft').'">';
				$body .= '<i class="fas '.($published_p ? 'fa-check' : 'fa-clock').'"></i> '.($published_p ? $this->lang('Publiée') : $this->lang('Brouillon'));
				$body .= '</span>';
				$body .= '</div>';
				$body .= '<div class="nf-content-card-meta">';
				$body .= '<span><i class="fas fa-link"></i> <code>'.htmlspecialchars($p['slug']).'</code></span>';
				if ($parent) $body .= '<span><i class="fas fa-sitemap"></i> '.htmlspecialchars($parent).'</span>';
				$body .= '<span title="'.$this->lang('Vues').'"><i class="far fa-eye"></i> '.(int)$p['views'].'</span>';
				$body .= '<span title="'.$this->lang('Révisions').'"><i class="fas fa-history"></i> '.(int)$p['nb_revisions'].'</span>';
				$body .= '</div>';
				$body .= '<div class="nf-content-card-foot">';
				$body .= '<span class="nf-content-card-spacer"></span>';
				$body .= '<a class="btn btn-sm btn-outline-primary" href="'.url('admin/wiki/edit/'.$p['slug']).'" title="'.$this->lang('Éditer').'"><i class="fas fa-pen"></i></a>';
				$body .= '<a class="btn btn-sm btn-outline-danger" href="'.url('admin/wiki/delete/'.$p['slug']).'" data-confirm="'.htmlspecialchars($this->lang('Supprimer cette page ? Les révisions seront aussi perdues.'), ENT_QUOTES).'" title="'.$this->lang('Supprimer').'"><i class="far fa-trash-alt"></i></a>';
				$body .= '</div>';
				$body .= '</div>';
			}
			$body .= '</div>';
		}

		// Barre recherche / filtre (GET). $_GET préservé à travers la pagination par get_pagination().
		$form_action = url($this->module->pagination->get_url());
		$toolbar  = '<form method="get" action="'.$form_action.'" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin-bottom:12px;">';
		$toolbar .= '<input type="text" name="q" value="'.htmlspecialchars($filters['q']).'" class="form-control form-control-sm" placeholder="'.htmlspecialchars($this->lang('Rechercher un titre ou slug…'), ENT_QUOTES).'" style="max-width:240px;">';
		$toolbar .= '<select name="status" class="form-control form-control-sm" style="width:auto;">';
		foreach (['' => $this->lang('Tous les statuts'), 'published' => $this->lang('Publiées'), 'draft' => $this->lang('Brouillons')] as $val => $label)
		{
			$toolbar .= '<option value="'.$val.'"'.($filters['status'] === $val ? ' selected' : '').'>'.htmlspecialchars($label).'</option>';
		}
		$toolbar .= '</select>';
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

		$body = $toolbar.$body.$pagination;

		$subtitle = $published.' '.$this->lang('publiée|publiées', $published).($drafts > 0 ? ' · '.$drafts.' '.$this->lang('brouillon|brouillons', $drafts) : '');
		$actions  = '<a class="btn btn-primary btn-sm" href="'.url('admin/wiki/add').'"><i class="fas fa-plus"></i> '.$this->lang('Nouvelle page').'</a>';

		return $this->admin_card('fas fa-book', $this->lang('Pages wiki'), $body, $subtitle, $actions);
	}

	public function _add()    { return $this->_form(NULL); }
	public function _edit($p) { return $this->_form($p); }
	public function _delete($p)
	{
		// Aussi supprimer les pages enfants ? Pour l'instant non, on les libère (parent_id NULL)
		NeoFrag()->db->where('parent_id', $p['id'])->update('nf_wiki_pages', ['parent_id' => NULL]);
		NeoFrag()->db->where('page_id', $p['id'])->delete('nf_wiki_revisions');
		NeoFrag()->db->where('id', $p['id'])->delete('nf_wiki_pages');
		notify($this->lang('Page supprimée. Les pages enfants éventuelles ont été remontées à la racine.'));
		redirect('admin/wiki');
	}

	protected function _form($p)
	{
		$is_new = $p === NULL;
		$this->title($is_new ? 'Nouvelle page wiki' : 'Éditer : '.$p['title'])->icon('fas fa-book')->breadcrumb();

		// Possibles parents (toutes les pages sauf celle-ci pour éviter cycles)
		$query = NeoFrag()->db->select('id', 'title')->from('nf_wiki_pages');
		if (!$is_new) { $query->where('id <>', $p['id']); }
		$parents = $query->order_by('title ASC')->get();
		$parents_array = ['' => '— Aucun (racine) —'];
		foreach ($parents as $par) { $parents_array[$par['id']] = $par['title']; }

		$this->form()
			 ->add_rules([
				'title'        => ['label' => 'Titre', 'type' => 'text', 'value' => $is_new ? '' : $p['title'], 'rules' => 'required'],
				'slug'         => ['label' => 'Slug (URL)', 'type' => 'text', 'value' => $is_new ? '' : $p['slug'], 'rules' => 'required',
				                    'description' => 'Identifiant URL unique. Ex: <code>installation</code>, <code>guide-debutant</code>'],
				'parent_id'    => ['label' => 'Page parente', 'type' => 'select', 'values' => $parents_array, 'value' => $is_new ? '' : ($p['parent_id'] ?? '')],
				'sort_order'   => ['label' => 'Ordre tri', 'type' => 'text', 'value' => $is_new ? '0' : $p['sort_order']],
				'content'      => ['label' => 'Contenu', 'type' => 'editor', 'value' => $is_new ? '' : $p['content'], 'rules' => 'required'],
				'edit_comment' => ['label' => 'Commentaire de modification (optionnel)', 'type' => 'text', 'value' => '',
				                    'description' => 'Décrit brièvement ce qui change. Stocké dans l\'historique.'],
				'published'    => ['label' => 'Publier', 'type' => 'checkbox', 'value' => ['1'], 'values' => ['1' => 'Page publiée'], 'checked' => ['1' => ($is_new || !empty($p['published']))]]
			 ])
			 ->add_submit($is_new ? 'Créer' : 'Enregistrer');

		if ($this->form()->is_valid($post))
		{
			$slug = preg_replace('/[^a-z0-9-]/', '', strtolower(str_replace([' ', '_'], '-', $post['slug'])));

			$data = [
				'slug'       => $slug,
				'title'      => $post['title'],
				'content'    => $post['content'],
				'parent_id'  => $post['parent_id'] !== '' ? (int)$post['parent_id'] : NULL,
				'sort_order' => (int)$post['sort_order'],
				'published'  => in_array('1', $post['published'] ?? []) ? 1 : 0
			];

			if ($is_new)
			{
				$data['user_id'] = $this->user->id;
				NeoFrag()->db->insert('nf_wiki_pages', $data);
				$page_id = (int)NeoFrag()->db->driver()->insert_id();
			}
			else
			{
				$page_id = (int)$p['id'];

				// Stocker l'ancienne version comme révision avant update
				NeoFrag()->db->insert('nf_wiki_revisions', [
					'page_id'  => $page_id,
					'content'  => $p['content'],
					'title'    => $p['title'],
					'user_id'  => $this->user->id,
					'comment'  => $post['edit_comment'] ?? ''
				]);

				$data['user_id'] = $this->user->id;
				NeoFrag()->db->where('id', $page_id)->update('nf_wiki_pages', $data);
			}

			notify($is_new ? 'Page créée.' : 'Page modifiée. Ancienne version archivée dans l\'historique.');
			redirect('admin/wiki');
		}

		return $this->row($this->col($this->panel()->heading()->body($this->form()->display()))->size('col-12'));
	}
}
