<?php
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\Pages\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Admin extends Controller_Module
{
	public function index($pages)
	{
		$this	->table()
				->add_columns([
					[
						'content' => function($data){
							if (!$data['published'])
							{
								return '<i class="far fa-circle" data-toggle="tooltip" title="'.$this->lang('En attente de publication').'" style="color: #535353;"></i>';
							}

							if (!empty($data['date']) && strtotime($data['date']) > time())
							{
								return '<i class="fas fa-calendar-alt" data-toggle="tooltip" title="'.$this->lang('Programmée le %s', timetostr($this->lang('d/m/Y H:i'), $data['date'])).'" style="color: #e0a32e;"></i>';
							}

							return '<i class="fas fa-circle" data-toggle="tooltip" title="'.$this->lang('Publiée').'" style="color: #7bbb17;"></i>';
						},
						'sort'    => function($data){
							return $data['published'];
						},
						'size'    => TRUE
					],
					[
						'title'   => $this->lang('Titre de la page'),
						'content' => function($data){
							return $data['published'] ? '<a href="'.url($data['name']).'">'.$data['title'].'</a><small class="ml-2">'.$data['subtitle'].'</small>' : $data['title'];
						},
						'sort'    => function($data){
							return $data['title'];
						},
						'search'  => function($data){
							return $data['title'];
						}
					],
					[
						'title'   => $this->lang('Chemin d\'accès'),
						'content' => function($data){
							return '<code>/'.$data['name'].'</code>';
						},
						'sort'    => function($data){
							return $data['name'];
						},
						'search'  => function($data){
							return $data['name'];
						}
					],
					[
						'content' => [
							function($data){
								return $data['published'] ? $this->button()->tooltip($this->lang('Voir la page'))->icon('far fa-eye')->url($data['name'])->compact()->outline() : '';
							},
							function($data){
								return $this->access->effective_admin() ? $this->button_access($data['page_id'], 'page') : NULL;
							},
							function($data){
								return $this->is_authorized('modify_pages') ? $this->button_update('admin/pages/'.$data['page_id'].'/'.url_title($data['title'])) : NULL;
							},
							function($data){
								return $this->is_authorized('delete_pages') ? $this->button_delete('admin/pages/delete/'.$data['page_id'].'/'.url_title($data['title'])) : NULL;
							}
						],
						'size'    => TRUE
					]
				])
				->data($pages)
				->no_data($this->lang('Il n\'y a pas encore de page'));

		$actions = $this->is_authorized('add_pages')
			? '<a class="btn btn-sm btn-primary" href="'.url('admin/pages/add').'">'.icon('fas fa-plus').' '.$this->lang('Créer').'</a>'
			: '';

		return $this->admin_card('fas fa-bars', $this->lang('Liste des pages'), $this->table()->display(), '', $actions);
	}

	public function add()
	{
		$this	->subtitle($this->lang('Ajouter une page'))
				->form()
				->add_rules('pages')
				->add_submit($this->lang('Ajouter'))
				->add_back('admin/pages');

		if ($this->form()->is_valid($post))
		{
			$this->model()->add_page(	$post['name'],
										$post['title'],
										in_array('on', $post['published']),
										$post['subtitle'],
										$post['content'],
										$post['layout'],
										$post['date'] ?? '');

			notify($this->lang('Page ajoutée avec succès'));

			redirect_back('admin/pages');
		}

		return $this->admin_back('admin/pages', $this->lang('Pages')).$this->admin_card('fas fa-plus', $this->lang('Ajouter une page'), $this->form()->display().$this->_blocks_help());
	}

	public function _edit($page_id, $name, $published, $title, $subtitle, $content, $layout, $tab, $date = '')
	{
		$this	->subtitle($title)
				->form()
				->add_rules('pages', [
					'title'          => $title,
					'subtitle'       => $subtitle,
					'name'           => $name,
					'content'        => $content,
					'layout'         => $layout,
					'date'           => $date,
					'published'      => $published
				])
				->add_submit($this->lang('Éditer'))
				->add_back('admin/pages');

		if ($this->form()->is_valid($post))
		{
			$this->model()->edit_page(	$page_id,
										$post['name'],
										$post['title'],
										in_array('on', $post['published']),
										$post['subtitle'],
										$post['content'],
										$this->config->lang->info()->name,
										$post['layout'],
										$post['date'] ?? '');

			notify($this->lang('Page éditée avec succès'));

			redirect_back('admin/pages');
		}

		return $this->admin_back('admin/pages', $this->lang('Pages')).$this->admin_card('fas fa-edit', $this->lang('Édition de la page').' — '.$title, $this->form()->display().$this->_blocks_help());
	}

	/** Note d'aide listant les blocs de module injectables via [block:clé] dans le contenu. */
	private function _blocks_help()
	{
		$blocks = $this->model()->block_registry();

		if (!$blocks)
		{
			return '';
		}

		$items = '';

		foreach ($blocks as $key => $def)
		{
			$items .= '<li><code>[block:'.$key.']</code> — '.htmlspecialchars($def['title']).'</li>';
		}

		return '<div class="alert alert-info mt-3"><i class="fas fa-puzzle-piece"></i> '
			.$this->lang('Blocs de module injectables — collez le code dans le contenu :')
			.'<ul class="mb-0 mt-2">'.$items.'</ul></div>';
	}

	public function _delete($page_id, $title)
	{
		$this	->title($this->lang('Suppression d\'une page'))
				->subtitle($title)
				->form()
				->confirm_deletion($this->lang('Confirmation de suppression'), $this->lang('Êtes-vous sûr(e) de vouloir supprimer la page <b>%s</b> ?', $title));

		if ($this->form()->is_valid())
		{
			$this->model()->delete_page($page_id);

			return 'OK';
		}

		return $this->form()->display();
	}
}
