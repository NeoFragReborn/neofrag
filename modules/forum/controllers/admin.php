<?php
/**
 * https://neofr.ag
 * @author: Michaël BILCOT <michael.bilcot@neofr.ag>
 */

namespace NF\Modules\Forum\Controllers;

use NF\NeoFrag\Loadables\Controllers\Module as Controller_Module;

class Admin extends Controller_Module
{
	public function index()
	{
		$this	->subtitle($this->lang('Liste des forums'))
				->css('forum')
				->js('sortable.lib.min')
				->js('forum');

		$categories = $this->model()->get_categories();

		// Top action bar (instead of add_action which crowds the topbar)
		$action_bar = '<div class="forum-admin-actionbar">'
			.'<a class="btn btn-primary" href="'.url('admin/forum/add').'"><i class="fas fa-plus"></i> '.$this->lang('Ajouter un forum').'</a>'
			.'<a class="btn btn-secondary" href="'.url('admin/forum/categories/add').'"><i class="fas fa-folder-plus"></i> '.$this->lang('Ajouter une catégorie').'</a>'
			.'<a class="btn btn-info" href="'.url('admin/forum/attachments').'"><i class="fas fa-paperclip"></i> '.$this->lang('Pièces jointes').'</a>'
			.'<a class="btn btn-info" href="'.url('admin/forum/subscriptions').'"><i class="fas fa-bell"></i> '.$this->lang('Abonnements').'</a>'
			.'<a class="btn btn-info" href="'.url('admin/forum/mentions').'"><i class="fas fa-at"></i> '.$this->lang('Mentions').'</a>'
			.'<a class="btn btn-info" href="'.url('admin/forum/search-config').'"><i class="fas fa-search"></i> '.$this->lang('Recherche').'</a>'
			.'<a class="btn btn-warning" href="'.url('admin/forum/trash').'"><i class="fas fa-trash-alt"></i> '.$this->lang('Corbeille').'</a>'
			.'</div>';

		if (empty($categories))
		{
			return $action_bar
				.'<div class="card"><div class="card-header"><span><i class="fas fa-comments"></i> '.$this->lang('Forum').'</span></div>'
				.'<div class="nf-empty"><i class="far fa-comments"></i>'.$this->lang('Aucune catégorie de forum.<br>Créez une catégorie via le bouton ci-dessus pour commencer.').'</div>'
				.'</div>';
		}

		$html = $action_bar.'<div id="forums-list" class="forum-admin">';
		foreach ($categories as $category)
		{
			$html .= '<div class="card forum-admin-card">'.$this->view('admin', $category).'</div>';
		}
		$html .= '</div>';

		return $html;
	}

	public function add()
	{
		$this	->subtitle($this->lang('Ajouter un forum'))
				->form()
				->add_rules('forum', [
					'categories' => $this->model()->get_categories_list()
				])
				->add_submit($this->lang('Ajouter'))
				->add_back('admin/forum');

		if ($this->form()->is_valid($post))
		{
			$this->model()->add_forum(	$post['title'],
										$post['category'],
										$post['description'],
										$post['url']);

			notify($this->lang('Forum ajouté avec succès'));

			redirect_back('admin/forum');
		}

		return $this->admin_back('admin/forum', $this->lang('Forum'))
			.$this->admin_card('fas fa-comments', $this->lang('Ajouter un forum'), $this->form()->display());
	}

	public function _edit($forum_id, $title, $description, $parent_id, $is_subforum, $url)
	{
		$this	->title($this->lang('Édition du forum'))
				->subtitle($title)
				->form()
				->add_rules('forum', [
					'title'        => $title,
					'description'  => $description,
					'category_id'  => ($is_subforum ? 'f' : '').$parent_id,
					'categories'   => $this->model()->get_categories_list($forum_id),
					'url'          => $url
				])
				->add_submit($this->lang('Éditer'))
				->add_back('admin/forum');

		if ($this->form()->is_valid($post))
		{
			$this->db	->where('forum_id', $forum_id)
						->update('nf_forum', [
							'title'       => $post['title'],
							'parent_id'   => $this->model()->get_parent_id($post['category'], $is_subforum),
							'is_subforum' => $is_subforum,
							'description' => $post['description']
						]);

			if ($post['url'])
			{
				if ($url)
				{
					$this->db	->where('forum_id', $forum_id)
								->update('nf_forum_url', [
									'url' => $post['url']
								]);
				}
				else
				{
					$this->db->insert('nf_forum_url', [
						'forum_id' => $forum_id,
						'url'      => $post['url']
					]);
				}
			}
			else if ($url)
			{
				$this->db	->where('forum_id', $forum_id)
							->delete('nf_forum_url');
			}

			notify($this->lang('Forum édité avec succès'));

			redirect_back('admin/forum');
		}

		return $this->admin_back('admin/forum', $this->lang('Forum'))
			.$this->admin_card('fas fa-comments', $this->lang('Édition du forum').' — '.$title, $this->form()->display());
	}

	public function delete($forum_id, $title)
	{
		$this	->title($this->lang('Suppression forum'))
				->subtitle($title)
				->form()
				->confirm_deletion($this->lang('Confirmation de suppression'), $this->lang('Êtes-vous sûr(e) de vouloir supprimer le forum <b>%s</b> ?<br />Tous les messages seront aussi supprimés.', $title));

		if ($this->form()->is_valid())
		{
			$this->model()->delete_forum($forum_id);

			return 'OK';
		}

		return $this->form()->display();
	}

	public function _categories_add()
	{
		$this	->subtitle($this->lang('Ajouter une catégorie'))
				->form()
				->add_rules('categories')
				->add_back('admin/forum')
				->add_submit($this->lang('Ajouter'));

		if ($this->form()->is_valid($post))
		{
			$this->model()->add_category($post['title'], $post['image'], in_array('on', (array)$post['vip_only']));

			notify($this->lang('Catégorie ajoutée avec succès'));

			redirect_back('admin/forum');
		}

		return $this->admin_back('admin/forum', $this->lang('Forum'))
			.$this->admin_card('fas fa-folder-plus', $this->lang('Ajouter une catégorie'), $this->form()->display());
	}

	public function _categories_edit($category_id, $title)
	{
		$cat = $this->db->select('image_id', 'vip_only')->from('nf_forum_categories')->where('category_id', $category_id)->row(FALSE);

		$this	->title($this->lang('Édition de la catégorie'))
				->subtitle($title)
				->form()
				->add_rules('categories', [
					'title'    => $title,
					'image_id' => $cat['image_id'],
					'vip_only' => $cat['vip_only']
				])
				->add_submit($this->lang('Éditer'))
				->add_back('admin/forum');

		if ($this->form()->is_valid($post))
		{
			$this->model()->edit_category($category_id, $post['title'], $post['image'], in_array('on', (array)$post['vip_only']));

			notify($this->lang('Catégorie éditée avec succès'));

			redirect_back('admin/forum');
		}

		return $this->admin_back('admin/forum', $this->lang('Forum'))
			.$this->admin_card('fas fa-folder-open', $this->lang('Édition de la catégorie').' — '.$title, $this->form()->display());
	}

	public function _categories_delete($category_id, $title)
	{
		$this	->title($this->lang('Suppression catégorie'))
				->subtitle($title)
				->form()
				->confirm_deletion($this->lang('Confirmation de suppression'), $this->lang('Êtes-vous sûr(e) de vouloir supprimer la catégorie <b>%s</b> ?<br />Toutes les forums et messages associés à cette catégorie seront aussi supprimés.', $title));

		if ($this->form()->is_valid())
		{
			$this->model()->delete_category($category_id);

			return 'OK';
		}

		return $this->form()->display();
	}

	// =================================================================
	// Phase 7-bis — Mod avancée
	// =================================================================

	public function _admin_topic_split($topic_id, $title, $forum_id, $category_id)
	{
		$this	->title($this->lang('Scinder le sujet'))
				->subtitle($title);

		$messages = $this->model()->get_messages($topic_id, $forum_id);

		// Récupérer les usernames pour affichage
		$user_ids = array_unique(array_map(function($m){ return (int)$m['user_id']; }, $messages));
		$users    = [];
		if (!empty($user_ids))
		{
			foreach ($this->db->select('id', 'username')->from('nf_user')->where('id', $user_ids)->get() as $u)
			{
				$users[(int)$u['id']] = $u['username'];
			}
		}

		// 1er message = starter, on l'exclut du split
		$starter_id = (int)$messages[0]['message_id'];

		$this	->form()
				->add_rules([
					'new_title' => [
						'rules' => 'required',
						'value' => $this->lang('Re: %s', $title)
					]
				]);

		if ($this->form()->is_valid($post))
		{
			$selected = isset($_POST['split_messages']) && is_array($_POST['split_messages']) ? array_map('intval', $_POST['split_messages']) : [];

			if (empty($selected))
			{
				notify($this->lang('Sélectionne au moins 1 message à déplacer'), 'danger');
			}
			else
			{
				$new_topic_id = $this->model()->split_topic($topic_id, $selected, $post['new_title']);

				if ($new_topic_id)
				{
					notify($this->lang('Sujet scindé : %d messages déplacés vers le nouveau sujet', count($selected)));
					redirect('forum/topic/'.$new_topic_id.'/'.url_title($post['new_title']));
				}
				else
				{
					notify($this->lang('Erreur lors du split'), 'danger');
				}
			}
		}

		return $this->admin_back('forum/topic/'.$topic_id.'/'.url_title($title), $this->lang('Retour au sujet'))
			.$this->admin_card('fas fa-cut', $this->lang('Scinder le sujet en deux').' — '.$title, $this->view('admin/split', [
				'form_id'    => $this->form()->token(),
				'topic_id'   => $topic_id,
				'title'      => $title,
				'starter_id' => $starter_id,
				'messages'   => $messages,
				'users'      => $users
			]));
	}

	public function _admin_topic_merge($topic_id, $title, $forum_id, $category_id)
	{
		$this	->title($this->lang('Fusionner le sujet'))
				->subtitle($title);

		// Liste des autres topics (du même forum) pour le dropdown
		$other_topics = $this->db	->select('topic_id', 'title', 'count_messages')
									->from('nf_forum_topics')
									->where('forum_id', (int)$forum_id)
									->where('topic_id !=', (int)$topic_id)
									->order_by('title')
									->get();

		$this	->form()
				->add_rules([
					'target_topic_id' => [
						'rules' => 'required'
					]
				]);

		if ($this->form()->is_valid($post))
		{
			$target_id = (int)$post['target_topic_id'];

			if ($this->model()->merge_topics($topic_id, $target_id))
			{
				$target = $this->db->select('title')->from('nf_forum_topics')->where('topic_id', $target_id)->row();
				$target_title = is_array($target) ? $target['title'] : $target;
				notify($this->lang('Sujet fusionné avec succès'));
				redirect('forum/topic/'.$target_id.'/'.url_title($target_title));
			}
			else
			{
				notify($this->lang('Erreur lors de la fusion'), 'danger');
			}
		}

		return $this->admin_back('forum/topic/'.$topic_id.'/'.url_title($title), $this->lang('Retour au sujet'))
			.$this->admin_card('fas fa-compress-arrows-alt', $this->lang('Fusionner avec un autre sujet').' — '.$title, $this->view('admin/merge', [
				'form_id'      => $this->form()->token(),
				'topic_id'     => $topic_id,
				'title'        => $title,
				'forum_id'     => $forum_id,
				'other_topics' => $other_topics
			]));
	}

	public function _admin_subscriptions($subs)
	{
		$this->title($this->lang('Abonnements forum'));

		if (!empty($_POST['unsub']) && is_array($_POST['unsub']))
		{
			$count = 0;
			foreach ($_POST['unsub'] as $key)
			{
				if (preg_match('/^(\d+)_(\d+)$/', $key, $m))
				{
					if ($this->model()->admin_unsubscribe((int)$m[1], (int)$m[2]))
					{
						$count++;
					}
				}
			}
			notify($this->lang('%d abonnement(s) supprimé(s)', $count));
			refresh();
		}

		return $this->admin_back('admin/forum', $this->lang('Forum'))
			.$this->admin_card('fas fa-bell', $this->lang('Abonnements forum'), $this->view('admin/subscriptions', ['subs' => $subs]))
			.(string)$this->module->pagination->get_pagination();
	}

	public function _admin_mentions($mentions, $filters)
	{
		$this->title($this->lang('Mentions @user'));

		// Bulk actions
		if (!empty($_POST['mark_read']) && is_array($_POST['mark_read']))
		{
			$n = $this->model()->mark_mentions_read($_POST['mark_read']);
			notify($this->lang('%d mention(s) marquée(s) comme lue(s)', $n));
			refresh();
		}

		if (!empty($_POST['delete']) && is_array($_POST['delete']))
		{
			$n = $this->model()->delete_mentions($_POST['delete']);
			notify($this->lang('%d mention(s) supprimée(s)', $n));
			refresh();
		}

		return $this->admin_back('admin/forum', $this->lang('Forum'))
			.$this->admin_card('fas fa-at', $this->lang('Mentions @user'), $this->view('admin/mentions', [
				'mentions' => $mentions,
				'filters'  => $filters
			]))
			.(string)$this->module->pagination->get_pagination();
	}

	public function _admin_search_config()
	{
		$this->title($this->lang('Configuration recherche'));

		if (!empty($_POST['reindex']))
		{
			$this->model()->reindex_fulltext();
			notify($this->lang('Index full-text reconstruits'));
			refresh();
		}

		$stats = $this->model()->get_search_stats();

		return $this->admin_back('admin/forum', $this->lang('Forum'))
			.$this->admin_card('fas fa-search', $this->lang('Configuration recherche'), $this->view('admin/search_config', ['stats' => $stats]));
	}

	public function _admin_attachments($attachments)
	{
		$this	->title($this->lang('Pièces jointes du forum'))
				->subtitle($this->lang('Toutes les pièces jointes uploadées'));

		// Action delete attachment(s)
		if (!empty($_POST['delete_attachment']) && is_array($_POST['delete_attachment']))
		{
			$count = 0;
			$locked = [];
			foreach (array_map('intval', $_POST['delete_attachment']) as $aid)
			{
				$result = $this->model()->delete_attachment($aid);
				if (is_array($result) && isset($result['locked_by_report']))
				{
					$locked[] = ['aid' => $aid, 'report' => (int)$result['locked_by_report']];
				}
				else if ($result)
				{
					$count++;
				}
			}
			if ($count > 0) notify($this->lang('%d pièce(s) jointe(s) supprimée(s)', $count));
			foreach ($locked as $l)
			{
				notify($this->lang('PJ #%d non supprimée : signalement #%d en cours. Tranche le report d\'abord.', $l['aid'], $l['report']), 'warning');
			}
			refresh();
		}

		$stats   = $this->model()->get_attachments_stats();
		$orphans = $this->model()->find_orphan_files();

		return $this->admin_back('admin/forum', $this->lang('Forum'))
			.$this->admin_card('fas fa-paperclip', $this->lang('Pièces jointes du forum'), $this->view('admin/attachments', [
				'attachments' => $attachments,
				'stats'       => $stats,
				'orphans'     => $orphans
			]))
			.(string)$this->module->pagination->get_pagination();
	}

	public function _admin_trash($trashed)
	{
		$this	->title($this->lang('Corbeille du forum'))
				->subtitle($this->lang('Messages supprimés (soft-delete)'));

		// Action : restore
		if (!empty($_POST['restore']) && is_array($_POST['restore']))
		{
			foreach (array_map('intval', $_POST['restore']) as $mid)
			{
				$this->model()->restore_message($mid);
			}
			notify($this->lang('Message(s) restauré(s)'));
			refresh();
		}

		// Action : purge (hard delete)
		if (!empty($_POST['purge']) && is_array($_POST['purge']))
		{
			foreach (array_map('intval', $_POST['purge']) as $mid)
			{
				$this->model()->hard_delete_message($mid);
			}
			notify($this->lang('Message(s) supprimé(s) définitivement'), 'warning');
			refresh();
		}

		return $this->admin_back('admin/forum', $this->lang('Forum'))
			.$this->admin_card('fas fa-trash-alt', $this->lang('Corbeille du forum'), $this->view('admin/trash', ['trashed' => $trashed]))
			.(string)$this->module->pagination->get_pagination();
	}
}
